@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <div class="zy-card p-6">
        <h1 class="text-2xl font-bold">Settings</h1>
        <form method="POST" action="{{ route('server.settings.update', $server) }}" class="mt-6 space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="zy-label">Nama server</label>
                <input class="zy-input" name="name" value="{{ $server->name }}" required>
            </div>
            <div>
                <label class="zy-label">Deskripsi</label>
                <textarea class="zy-input" name="description" rows="4">{{ $server->description }}</textarea>
            </div>
            <button class="zy-btn-primary">Save Settings</button>
        </form>
    </div>
    <aside class="zy-card p-6">
        <h2 class="font-bold">SFTP Placeholder</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div><dt class="text-slate-500">Host</dt><dd>{{ $server->node->fqdn ?: $server->node->public_ip }}</dd></div>
            <div><dt class="text-slate-500">Username</dt><dd class="font-mono">server_{{ $server->uuid }}</dd></div>
            <div><dt class="text-slate-500">Root</dt><dd class="font-mono">/var/lib/zy4daemon/servers/{{ $server->uuid }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('server.settings.reinstall', $server) }}" class="mt-6" onsubmit="return confirm('Reinstall server ini?')">
            @csrf
            <button class="zy-btn-secondary w-full">Reinstall Server</button>
        </form>
        <form method="POST" action="{{ route('server.settings.delete', $server) }}" class="mt-4 space-y-3" onsubmit="return confirm('Hapus server ini permanen dari panel?')">
            @csrf
            @method('DELETE')
            <label class="zy-label">Ketik nama server untuk hapus</label>
            <input class="zy-input" name="confirm" placeholder="{{ $server->name }}">
            <button class="zy-btn-danger w-full">Delete Server</button>
        </form>
    </aside>
</div>
@endsection
