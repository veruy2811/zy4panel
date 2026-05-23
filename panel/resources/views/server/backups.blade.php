@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<div class="mb-5 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Backups</h1>
        <p class="text-sm text-slate-400">Limit paket: {{ $server->plan?->backup_limit ?? 1 }}</p>
    </div>
    <form method="POST" action="{{ route('server.backups.create', $server) }}" class="flex gap-2">
        @csrf
        <input class="zy-input" name="name" placeholder="Backup name" value="Backup {{ now()->format('Y-m-d H:i') }}" required>
        <button class="zy-btn-primary">Create</button>
    </form>
</div>

<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Name</th><th>Status</th><th>Size</th><th>Completed</th><th>Actions</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($backups as $backup)
                <tr>
                    <td>{{ $backup->name }}</td>
                    <td>{{ $backup->status }}</td>
                    <td>{{ number_format($backup->size_bytes / 1024 / 1024, 2) }} MB</td>
                    <td>{{ optional($backup->completed_at)->format('d M Y H:i') ?? '-' }}</td>
                    <td class="flex gap-2">
                        @if($backup->path)
                            <a class="text-neon" href="{{ route('server.backups.download', [$server, $backup]) }}">Download</a>
                        @endif
                        <form method="POST" action="{{ route('server.backups.restore', [$server, $backup]) }}">
                            @csrf
                            <button class="text-neon">Restore</button>
                        </form>
                        <form method="POST" action="{{ route('server.backups.delete', [$server, $backup]) }}" onsubmit="return confirm('Hapus backup?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-rose-300">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-slate-400">Belum ada backup.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
