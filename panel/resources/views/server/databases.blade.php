@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<div class="mb-5 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Databases</h1>
        <p class="text-sm text-slate-400">Limit paket: {{ $server->plan?->database_limit ?? 1 }}</p>
    </div>
    <form method="POST" action="{{ route('server.databases.create', $server) }}" class="flex gap-2">
        @csrf
        <input class="zy-input" name="name" placeholder="nama_database" required>
        <button class="zy-btn-primary">Create</button>
    </form>
</div>

<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Database</th><th>Username</th><th>Password</th><th>Host</th><th>Port</th><th></th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($databases as $database)
                <tr>
                    <td>{{ $database->name }}</td>
                    <td>{{ $database->username }}</td>
                    <td class="font-mono text-xs">{{ $database->password }}</td>
                    <td>{{ $database->host }}</td>
                    <td>{{ $database->port }}</td>
                    <td>
                        <form method="POST" action="{{ route('server.databases.delete', [$server, $database]) }}" onsubmit="return confirm('Hapus database?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-rose-300">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-slate-400">Belum ada database.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
