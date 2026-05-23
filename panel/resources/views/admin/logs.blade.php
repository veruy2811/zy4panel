@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Activity Logs</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Time</th><th>Server</th><th>User</th><th>Action</th><th>IP</th><th>Metadata</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                    <td>{{ $log->server?->name }}</td>
                    <td>{{ $log->user?->email ?? 'system' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td class="font-mono text-xs">{{ json_encode($log->metadata) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $logs->links() }}</div>
@endsection
