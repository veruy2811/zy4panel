<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerDatabase;
use App\Services\DaemonClient;
use App\Services\PathGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerApiController extends Controller
{
    public function __construct(private readonly DaemonClient $daemon)
    {
    }

    public function index(Request $request)
    {
        $query = Server::with('allocation', 'node', 'plan.product');
        if (! $request->user()->isStaff()) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->latest()->paginate(50));
    }

    public function show(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);

        return response()->json($server->load('allocation', 'node', 'plan.product'));
    }

    public function power(Request $request, Server $server, string $action)
    {
        $this->authorizeServer($request, $server);
        abort_unless(in_array($action, ['start', 'stop', 'restart', 'kill'], true), 404);
        $this->daemon->power($server, $action);
        $server->update(['status' => in_array($action, ['stop', 'kill'], true) ? 'offline' : 'running']);
        $server->recordActivity($request->user(), 'api.server.'.$action);

        return response()->json(['ok' => true, 'status' => $server->status]);
    }

    public function stats(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);

        return response()->json($this->daemon->stats($server));
    }

    public function files(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);

        return response()->json($this->daemon->files($server, PathGuard::clean($request->query('path', '/'))));
    }

    public function upload(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:500'],
            'file' => ['required', 'file', 'max:102400'],
        ]);
        $result = $this->daemon->upload($server, PathGuard::clean($data['path'] ?? '/'), $data['file']);
        $server->recordActivity($request->user(), 'api.files.upload');

        return response()->json($result);
    }

    public function createFile(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'path' => ['required', 'string', 'max:500'],
            'type' => ['nullable', 'in:file,folder'],
            'content' => ['nullable', 'string'],
        ]);
        $path = PathGuard::clean($data['path']);
        $result = ($data['type'] ?? 'file') === 'folder'
            ? $this->daemon->makeDirectory($server, $path)
            : $this->daemon->writeFile($server, $path, $data['content'] ?? '');

        $server->recordActivity($request->user(), 'api.files.create', ['path' => $path]);

        return response()->json($result);
    }

    public function renameFile(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate([
            'from' => ['required', 'string', 'max:500'],
            'to' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($this->daemon->rename($server, PathGuard::clean($data['from']), PathGuard::clean($data['to'])));
    }

    public function deleteFile(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        $data = $request->validate(['path' => ['required', 'string', 'max:500']]);
        $server->recordActivity($request->user(), 'api.files.delete', ['path' => $data['path']]);

        return response()->json($this->daemon->deleteFile($server, PathGuard::clean($data['path'])));
    }

    public function databases(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);

        return response()->json($server->databases()->get());
    }

    public function createDatabase(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        abort_if($server->databases()->count() >= ($server->plan?->database_limit ?? 1), 422, 'Database limit reached.');
        $data = $request->validate(['name' => ['required', 'alpha_dash', 'max:32']]);
        $database = ServerDatabase::create([
            'server_id' => $server->id,
            'node_id' => $server->node_id,
            'name' => 's'.$server->id.'_'.Str::lower($data['name']),
            'username' => 'u'.$server->id.'_'.Str::lower(Str::random(6)),
            'password' => Str::password(18),
            'host' => $server->node->fqdn ?: $server->node->public_ip ?: '127.0.0.1',
            'port' => 3306,
        ]);

        return response()->json($database, 201);
    }

    public function deleteDatabase(Request $request, Server $server, ServerDatabase $database)
    {
        $this->authorizeServer($request, $server);
        abort_unless((int) $database->server_id === (int) $server->id, 404);
        $database->delete();

        return response()->json(['ok' => true]);
    }

    public function backups(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);

        return response()->json($server->backups()->latest()->get());
    }

    public function createBackup(Request $request, Server $server)
    {
        $this->authorizeServer($request, $server);
        abort_if($server->backups()->whereIn('status', ['pending', 'completed'])->count() >= ($server->plan?->backup_limit ?? 1), 422, 'Backup limit reached.');
        $data = $request->validate(['name' => ['nullable', 'string', 'max:80']]);
        $backup = ServerBackup::create([
            'server_id' => $server->id,
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'] ?? 'Backup '.now()->format('Y-m-d H:i'),
            'status' => 'pending',
        ]);
        $result = $this->daemon->backup($server, $backup->name);
        $backup->update([
            'path' => $result['path'] ?? null,
            'size_bytes' => $result['size_bytes'] ?? 0,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json($backup, 201);
    }

    public function restoreBackup(Request $request, Server $server, ServerBackup $backup)
    {
        $this->authorizeServer($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);

        return response()->json($this->daemon->restore($server, $backup->path ?: $backup->name));
    }

    public function deleteBackup(Request $request, Server $server, ServerBackup $backup)
    {
        $this->authorizeServer($request, $server);
        abort_unless((int) $backup->server_id === (int) $server->id, 404);
        if ($backup->path) {
            $this->daemon->deleteBackup($server, $backup->path);
        }
        $backup->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeServer(Request $request, Server $server): void
    {
        $server->loadMissing('node', 'allocation', 'plan');
        abort_unless($server->canBeAccessedBy($request->user()), 403);
    }
}
