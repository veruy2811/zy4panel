<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerDatabase;
use App\Services\DaemonClient;
use App\Services\PathGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerPanelController extends Controller
{
    public function __construct(private readonly DaemonClient $daemon)
    {
    }

    public function dashboard(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $stats = $this->safeDaemon(fn () => $this->daemon->stats($server), []);

        return view('server.dashboard', compact('server', 'stats'));
    }

    public function console(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $token = hash_hmac('sha256', $server->uuid.'|'.now('UTC')->format('YmdH'), (string) config('services.daemon.secret'));
        $wsUrl = preg_replace('#^http#', 'ws', rtrim($server->node->daemon_url, '/'))."/servers/{$server->uuid}/console?token={$token}";

        return view('server.console', compact('server', 'token', 'wsUrl'));
    }

    public function power(Request $request, Server $server, string $action)
    {
        $this->authorizeServer($request, $server);
        abort_unless(in_array($action, ['start', 'stop', 'restart', 'kill'], true), 404);
        abort_if($server->suspended_at, 423, 'Server suspended.');

        try {
            $this->daemon->power($server, $action);
            $server->update(['status' => in_array($action, ['stop', 'kill'], true) ? 'offline' : 'running']);
            $server->recordActivity($request->user(), 'server.'.$action);
            return back()->with('status', ucfirst($action).' dikirim ke daemon.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['daemon' => $exception->getMessage()]);
        }
    }

    public function files(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $path = PathGuard::clean($request->query('path', '/'));
        $listing = $this->safeDaemon(fn () => $this->daemon->files($server, $path), ['items' => [], 'error' => 'Daemon belum merespons.']);

        return view('server.files', compact('server', 'path', 'listing'));
    }

    public function uploadFile(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:500'],
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $path = PathGuard::clean($data['path'] ?? '/');
        $this->daemon->upload($server, $path, $data['file']);
        $server->recordActivity($request->user(), 'files.upload', ['path' => $path, 'name' => $data['file']->getClientOriginalName()]);

        return back()->with('status', 'File berhasil diupload.');
    }

    public function createFile(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'path' => ['required', 'string', 'max:500'],
            'type' => ['required', 'in:file,folder'],
            'content' => ['nullable', 'string'],
        ]);
        $path = PathGuard::clean($data['path']);

        if ($data['type'] === 'folder') {
            $this->daemon->makeDirectory($server, $path);
        } else {
            $this->daemon->writeFile($server, $path, $data['content'] ?? '');
        }

        $server->recordActivity($request->user(), 'files.create', ['path' => $path, 'type' => $data['type']]);

        return back()->with('status', 'Item berhasil dibuat.');
    }

    public function renameFile(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'from' => ['required', 'string', 'max:500'],
            'to' => ['required', 'string', 'max:500'],
        ]);

        $this->daemon->rename($server, PathGuard::clean($data['from']), PathGuard::clean($data['to']));
        $server->recordActivity($request->user(), 'files.rename', $data);

        return back()->with('status', 'File berhasil diganti nama.');
    }

    public function deleteFile(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate(['path' => ['required', 'string', 'max:500']]);
        $path = PathGuard::clean($data['path']);
        $this->daemon->deleteFile($server, $path);
        $server->recordActivity($request->user(), 'files.delete', ['path' => $path]);

        return back()->with('status', 'File/folder dihapus.');
    }

    public function databases(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $databases = $server->databases()->latest()->get();

        return view('server.databases', compact('server', 'databases'));
    }

    public function createDatabase(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        abort_if($server->databases()->count() >= ($server->plan?->database_limit ?? 1), 422, 'Limit database paket tercapai.');

        $data = $request->validate([
            'name' => ['required', 'alpha_dash', 'max:32'],
        ]);

        $database = ServerDatabase::create([
            'server_id' => $server->id,
            'node_id' => $server->node_id,
            'name' => 's'.$server->id.'_'.Str::lower($data['name']),
            'username' => 'u'.$server->id.'_'.Str::lower(Str::random(6)),
            'password' => Str::password(18),
            'host' => $server->node->fqdn ?: $server->node->public_ip ?: '127.0.0.1',
            'port' => 3306,
        ]);

        $server->recordActivity($request->user(), 'databases.create', ['database' => $database->name]);

        return back()->with('status', 'Database dibuat.');
    }

    public function deleteDatabase(Request $request, Server $server, ServerDatabase $database)
    {
        $this->authorizeServer($request, $server);
        abort_unless((int) $database->server_id === (int) $server->id, 404);
        $name = $database->name;
        $database->delete();
        $server->recordActivity($request->user(), 'databases.delete', ['database' => $name]);

        return back()->with('status', 'Database dihapus.');
    }

    public function backups(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $backups = $server->backups()->latest()->get();

        return view('server.backups', compact('server', 'backups'));
    }

    public function createBackup(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        abort_if($server->backups()->whereIn('status', ['pending', 'completed'])->count() >= ($server->plan?->backup_limit ?? 1), 422, 'Limit backup paket tercapai.');
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);

        $backup = ServerBackup::create([
            'server_id' => $server->id,
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'],
            'status' => 'pending',
        ]);

        try {
            $result = $this->daemon->backup($server, $backup->name);
            $backup->update([
                'path' => $result['path'] ?? null,
                'size_bytes' => $result['size_bytes'] ?? 0,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $backup->update(['status' => 'failed']);
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }

        $server->recordActivity($request->user(), 'backups.create', ['backup' => $backup->uuid]);

        return back()->with('status', 'Backup selesai dibuat.');
    }

    public function restoreBackup(Request $request, Server $server, ServerBackup $backup)
    {
        $this->authorizeServer($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);
        $this->daemon->restore($server, $backup->path ?: $backup->name);
        $server->recordActivity($request->user(), 'backups.restore', ['backup' => $backup->uuid]);

        return back()->with('status', 'Restore dikirim ke daemon.');
    }

    public function downloadBackup(Request $request, Server $server, ServerBackup $backup)
    {
        $this->authorizeServer($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);
        abort_unless((bool) $backup->path, 404);

        $response = $this->daemon->downloadBackup($server, $backup->path);

        return response((string) $response->getBody(), 200, [
            'Content-Type' => $response->getHeaderLine('Content-Type') ?: 'application/gzip',
            'Content-Disposition' => 'attachment; filename="'.basename($backup->path).'"',
        ]);
    }

    public function deleteBackup(Request $request, Server $server, ServerBackup $backup)
    {
        $this->authorizeServer($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);
        if ($backup->path) {
            $this->safeDaemon(fn () => $this->daemon->deleteBackup($server, $backup->path), []);
        }
        $backup->delete();
        $server->recordActivity($request->user(), 'backups.delete', ['backup' => $backup->uuid]);

        return back()->with('status', 'Backup dihapus dari panel.');
    }

    public function network(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $allocations = $server->node->allocations()->where('server_id', $server->id)->get();

        return view('server.network', compact('server', 'allocations'));
    }

    public function startup(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);

        return view('server.startup', compact('server'));
    }

    public function updateStartup(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'docker_image' => ['required', 'string', 'max:190'],
            'startup_command' => ['nullable', 'string', 'max:5000'],
            'environment' => ['nullable', 'string', 'max:10000'],
        ]);

        $env = [];
        foreach (preg_split('/\R/', (string) ($data['environment'] ?? '')) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }

        $server->update([
            'docker_image' => $data['docker_image'],
            'startup_command' => $data['startup_command'],
            'environment' => $env,
        ]);
        $server->recordActivity($request->user(), 'startup.update');

        return back()->with('status', 'Startup diperbarui. Restart server agar efeknya aktif.');
    }

    public function settings(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);

        return view('server.settings', compact('server'));
    }

    public function updateSettings(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $server->update($data);
        $server->recordActivity($request->user(), 'settings.update');

        return back()->with('status', 'Settings server disimpan.');
    }

    public function reinstall(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $this->daemon->createServer($server);
        $server->update(['status' => 'offline', 'installed_at' => now()]);
        $server->recordActivity($request->user(), 'settings.reinstall');

        return back()->with('status', 'Reinstall dikirim ke daemon.');
    }

    public function deleteServer(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'confirm' => ['required', 'string'],
        ]);
        abort_unless($data['confirm'] === $server->name, 422, 'Konfirmasi nama server tidak cocok.');

        $this->safeDaemon(fn () => $this->daemon->power($server, 'kill'), []);
        $server->allocation?->update(['server_id' => null, 'is_primary' => false]);
        $server->recordActivity($request->user(), 'settings.delete');
        $server->delete();

        return redirect()->route('client.servers')->with('status', 'Server dihapus dari panel.');
    }

    public function activity(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $logs = $server->activityLogs()->with('user')->latest()->paginate(40);

        return view('server.activity', compact('server', 'logs'));
    }

    private function authorizeServer(Request $request, Server $server): void
    {
        $server->loadMissing('user', 'node', 'allocation', 'plan.product');
        abort_unless($server->canBeAccessedBy($request->user()), 403);
    }

    private function safeDaemon(callable $callback, array $fallback): array
    {
        try {
            return $callback();
        } catch (\Throwable $exception) {
            report($exception);
            return $fallback;
        }
    }
}
