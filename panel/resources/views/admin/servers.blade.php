@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Servers</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Name</th><th>User</th><th>Node</th><th>Address</th><th>Status</th><th>Resource</th><th>Actions</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($servers as $server)
                <tr>
                    <td><a class="text-neon" href="{{ route('server.dashboard', $server) }}">{{ $server->name }}</a></td>
                    <td>{{ $server->user->email }}</td>
                    <td>{{ $server->node->name }}</td>
                    <td>{{ $server->allocation?->alias ?: $server->allocation?->ip }}:{{ $server->allocation?->port }}</td>
                    <td>{{ $server->status }}</td>
                    <td>{{ $server->memory_mb }}MB / {{ $server->cpu_limit }} CPU</td>
                    <td>
                        @if($server->suspended_at)
                            <form method="POST" action="{{ route('admin.servers.unsuspend', $server) }}">@csrf<button class="text-neon">Unsuspend</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.servers.suspend', $server) }}">@csrf<button class="text-rose-300">Suspend</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $servers->links() }}</div>
@endsection
