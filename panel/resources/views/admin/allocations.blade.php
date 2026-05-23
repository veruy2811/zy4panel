@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Allocations</h1>
<form method="POST" action="{{ route('admin.allocations.store') }}" class="zy-card mb-6 grid gap-3 p-4 md:grid-cols-5">
    @csrf
    <select class="zy-input" name="node_id">@foreach($nodes as $node)<option value="{{ $node->id }}">{{ $node->name }}</option>@endforeach</select>
    <input class="zy-input" name="ip" value="0.0.0.0" required>
    <input class="zy-input" type="number" name="port" placeholder="25565" required>
    <input class="zy-input" name="alias" placeholder="Public alias">
    <button class="zy-btn-primary">Create</button>
</form>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Node</th><th>IP</th><th>Alias</th><th>Port</th><th>Server</th><th>Primary</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($allocations as $allocation)
                <tr>
                    <td>{{ $allocation->node->name }}</td>
                    <td>{{ $allocation->ip }}</td>
                    <td>{{ $allocation->alias }}</td>
                    <td>{{ $allocation->port }}</td>
                    <td>{{ $allocation->server?->name ?? '-' }}</td>
                    <td>{{ $allocation->is_primary ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $allocations->links() }}</div>
@endsection
