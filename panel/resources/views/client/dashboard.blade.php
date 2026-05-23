@extends('layouts.app')

@section('content')
<div class="mb-6">
    <p class="font-mono text-xs uppercase tracking-wide text-neon">Client Area</p>
    <h1 class="text-3xl font-bold">Dashboard</h1>
</div>

<div class="grid gap-4 md:grid-cols-3">
    <div class="zy-card p-5"><div class="text-sm text-slate-400">Servers</div><div class="mt-2 text-3xl font-bold">{{ $servers->count() }}</div></div>
    <div class="zy-card p-5"><div class="text-sm text-slate-400">Orders</div><div class="mt-2 text-3xl font-bold">{{ $orders->count() }}</div></div>
    <div class="zy-card p-5"><div class="text-sm text-slate-400">Invoices</div><div class="mt-2 text-3xl font-bold">{{ $invoices->count() }}</div></div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <div class="zy-card overflow-hidden">
        <div class="border-b border-line p-4 font-semibold">Server Terbaru</div>
        <div class="divide-y divide-line">
            @forelse($servers as $server)
                <a href="{{ route('server.dashboard', $server) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-panel">
                    <span>
                        <span class="block font-medium">{{ $server->name }}</span>
                        <span class="block text-sm text-slate-500">{{ $server->allocation?->alias ?: $server->allocation?->ip }}:{{ $server->allocation?->port }}</span>
                    </span>
                    <span class="text-sm text-neon">{{ $server->status }}</span>
                </a>
            @empty
                <div class="p-4 text-sm text-slate-400">Belum ada server.</div>
            @endforelse
        </div>
    </div>

    <div class="zy-card overflow-hidden">
        <div class="border-b border-line p-4 font-semibold">Invoice Terbaru</div>
        <div class="divide-y divide-line">
            @forelse($invoices as $invoice)
                <a href="{{ route('invoice.show', $invoice) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-panel">
                    <span class="font-medium">{{ $invoice->number }}</span>
                    <span class="text-sm text-slate-400">{{ $invoice->status }}</span>
                </a>
            @empty
                <div class="p-4 text-sm text-slate-400">Belum ada invoice.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
