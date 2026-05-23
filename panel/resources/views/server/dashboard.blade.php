@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-wide text-neon">Zy4Panel</p>
        <h1 class="text-3xl font-bold">{{ $server->name }}</h1>
        <p class="mt-1 text-sm text-slate-400">{{ $server->allocation?->alias ?: $server->allocation?->ip }}:{{ $server->allocation?->port }}</p>
    </div>
    @include('partials.power-buttons')
</div>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
    @foreach([
        'Status' => $server->status,
        'CPU' => ($stats['cpu_percent'] ?? 0).'%',
        'RAM' => ($stats['memory_mb'] ?? 0).' / '.$server->memory_mb.' MB',
        'Disk' => ($stats['disk_mb'] ?? 0).' / '.$server->disk_mb.' MB',
        'Uptime' => $stats['uptime'] ?? '-',
    ] as $label => $value)
        <div class="zy-card p-5">
            <div class="text-sm text-slate-500">{{ $label }}</div>
            <div class="mt-2 break-words text-xl font-bold">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="zy-card p-5">
        <h2 class="font-bold">Resource</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-400">Docker Image</dt><dd>{{ $server->docker_image }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Node</dt><dd>{{ $server->node->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">UUID</dt><dd class="font-mono text-xs">{{ $server->uuid }}</dd></div>
        </dl>
    </div>
    <div class="zy-card p-5">
        <h2 class="font-bold">Startup</h2>
        <pre class="mt-4 overflow-auto rounded-lg bg-black/50 p-4 font-mono text-sm text-slate-300">{{ $server->startup_command }}</pre>
    </div>
</div>
@endsection
