@extends('layouts.app')

@section('content')
<div class="mb-6">
    <p class="font-mono text-xs uppercase tracking-wide text-neon">Admin</p>
    <h1 class="text-3xl font-bold">Dashboard</h1>
</div>
<div class="grid gap-4 md:grid-cols-4">
    @foreach($stats as $label => $value)
        <div class="zy-card p-5">
            <div class="text-sm capitalize text-slate-500">{{ str_replace('_', ' ', $label) }}</div>
            <div class="mt-2 text-3xl font-bold">{{ $value }}</div>
        </div>
    @endforeach
</div>
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <div class="zy-card overflow-hidden">
        <div class="border-b border-line p-4 font-semibold">Pending Payments</div>
        <div class="divide-y divide-line">
            @forelse($payments as $payment)
                <div class="flex items-center justify-between gap-4 p-4">
                    <div><div class="font-medium">{{ $payment->user->email }}</div><div class="text-sm text-slate-500">{{ $payment->invoice->number }}</div></div>
                    <span class="text-sm text-neon">{{ $payment->status }}</span>
                </div>
            @empty
                <div class="p-4 text-sm text-slate-400">Tidak ada payment.</div>
            @endforelse
        </div>
    </div>
    <div class="zy-card overflow-hidden">
        <div class="border-b border-line p-4 font-semibold">Latest Servers</div>
        <div class="divide-y divide-line">
            @forelse($servers as $server)
                <a class="flex items-center justify-between gap-4 p-4 hover:bg-panel" href="{{ route('server.dashboard', $server) }}">
                    <div><div class="font-medium">{{ $server->name }}</div><div class="text-sm text-slate-500">{{ $server->user->email }}</div></div>
                    <span class="text-sm text-neon">{{ $server->status }}</span>
                </a>
            @empty
                <div class="p-4 text-sm text-slate-400">Belum ada server.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
