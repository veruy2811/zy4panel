@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">Servers</h1>
        <p class="mt-1 text-slate-400">Kelola game server dan bot hosting milikmu.</p>
    </div>
    <a class="zy-btn-primary" href="{{ route('products.index') }}">Order Baru</a>
</div>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($servers as $server)
        <a class="zy-card block p-5 hover:border-neon/70" href="{{ route('server.dashboard', $server) }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold">{{ $server->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $server->plan?->product?->name }} - {{ $server->plan?->name }}</p>
                </div>
                <span class="rounded-full border border-line px-2 py-1 text-xs text-neon">{{ $server->status }}</span>
            </div>
            <div class="mt-5 grid grid-cols-3 gap-2 text-sm">
                <div class="rounded-lg bg-panel p-3"><div class="text-slate-500">RAM</div><div>{{ $server->memory_mb }}MB</div></div>
                <div class="rounded-lg bg-panel p-3"><div class="text-slate-500">CPU</div><div>{{ $server->cpu_limit }}</div></div>
                <div class="rounded-lg bg-panel p-3"><div class="text-slate-500">Disk</div><div>{{ round($server->disk_mb / 1024, 1) }}GB</div></div>
            </div>
            <p class="mt-4 font-mono text-sm text-slate-400">{{ $server->allocation?->alias ?: $server->allocation?->ip }}:{{ $server->allocation?->port }}</p>
        </a>
    @empty
        <div class="zy-card p-5 text-slate-400">Belum ada server.</div>
    @endforelse
</div>
<div class="mt-5">{{ $servers->links() }}</div>
@endsection
