@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<h1 class="mb-5 text-2xl font-bold">Activity Logs</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>IP</th><th>Metadata</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                    <td>{{ $log->user?->email ?? 'system' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td class="font-mono text-xs">{{ json_encode($log->metadata) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-slate-400">Belum ada log.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $logs->links() }}</div>
@endsection
