<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use Illuminate\Http\Request;

class DaemonController extends Controller
{
    public function auth(Request $request)
    {
        $data = $request->validate([
            'secret' => ['required', 'string'],
            'node' => ['nullable', 'string'],
        ]);

        abort_unless(hash_equals((string) config('services.daemon.secret'), $data['secret']), 401, 'Invalid daemon secret.');

        return response()->json([
            'ok' => true,
            'message' => 'Zy4Daemon authenticated.',
        ]);
    }

    public function heartbeat(Request $request)
    {
        $token = $request->bearerToken() ?: $request->header('X-Daemon-Token') ?: $request->input('secret');
        abort_unless($token, 401, 'Daemon token required.');

        $node = Node::all()->first(fn (Node $node) => hash_equals((string) $node->token, (string) $token))
            ?: (hash_equals((string) config('services.daemon.secret'), (string) $token) ? Node::first() : null);

        abort_unless($node, 401, 'Invalid daemon token.');

        $node->update([
            'last_seen_at' => now(),
            'stats' => $request->input('stats', []),
        ]);

        return response()->json(['ok' => true]);
    }
}
