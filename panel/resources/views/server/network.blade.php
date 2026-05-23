@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<h1 class="mb-5 text-2xl font-bold">Network</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>IP</th><th>Alias</th><th>Port</th><th>Primary</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($allocations as $allocation)
                <tr>
                    <td>{{ $allocation->ip }}</td>
                    <td>{{ $allocation->alias ?: '-' }}</td>
                    <td>{{ $allocation->port }}</td>
                    <td>{{ $allocation->is_primary ? 'Yes' : 'No' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-slate-400">Belum ada allocation.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
