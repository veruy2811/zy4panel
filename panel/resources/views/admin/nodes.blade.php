@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Nodes</h1>
<form method="POST" action="{{ route('admin.nodes.store') }}" class="zy-card mb-6 grid gap-3 p-4 md:grid-cols-4">
    @csrf
    <input class="zy-input" name="name" placeholder="Node name" required>
    <input class="zy-input" name="fqdn" placeholder="FQDN">
    <input class="zy-input" type="url" name="daemon_url" value="{{ config('services.daemon.default_url') }}" required>
    <input class="zy-input" name="token" placeholder="Daemon token" required>
    <input class="zy-input" name="public_ip" placeholder="Public IP">
    <input class="zy-input" type="number" name="memory_mb" value="32768" required>
    <input class="zy-input" type="number" name="disk_mb" value="524288" required>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
    <button class="zy-btn-primary md:col-span-4">Create Node</button>
</form>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Name</th><th>URL</th><th>IP</th><th>Servers</th><th>Allocations</th><th>Last Seen</th><th>Active</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($nodes as $node)
                <tr>
                    <td>{{ $node->name }}</td>
                    <td class="font-mono text-xs">{{ $node->daemon_url }}</td>
                    <td>{{ $node->public_ip }}</td>
                    <td>{{ $node->servers_count }}</td>
                    <td>{{ $node->allocations_count }}</td>
                    <td>{{ optional($node->last_seen_at)->diffForHumans() ?? '-' }}</td>
                    <td>{{ $node->is_active ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $nodes->links() }}</div>
@endsection
