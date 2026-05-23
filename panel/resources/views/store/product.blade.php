@extends('layouts.app')

@section('content')
<div class="overflow-hidden rounded-lg border border-line bg-cover bg-center" style="background-image: linear-gradient(90deg, rgba(7,10,18,.92), rgba(7,10,18,.62)), url('https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1800&q=80')">
    <div class="px-6 py-12 md:px-10">
        <p class="font-mono text-xs uppercase tracking-wide text-neon">{{ $product->category }}</p>
        <h1 class="mt-2 text-4xl font-extrabold">{{ $product->name }}</h1>
        <p class="mt-4 max-w-2xl leading-7 text-slate-300">{{ $product->description }}</p>
    </div>
</div>

<div class="mt-6 grid gap-4 lg:grid-cols-3">
    @foreach($product->plans as $plan)
        <div class="zy-card p-5">
            <h2 class="text-xl font-bold">{{ $plan->name }}</h2>
            <p class="mt-2 text-sm text-slate-400">{{ $plan->description }}</p>
            <div class="mt-5 text-3xl font-extrabold text-neon">Rp {{ number_format((float) $plan->price_monthly, 0, ',', '.') }}</div>
            <div class="mt-1 text-sm text-slate-500">per bulan</div>
            <dl class="mt-5 space-y-2 text-sm text-slate-300">
                <div class="flex justify-between"><dt>RAM</dt><dd>{{ $plan->ram_mb }} MB</dd></div>
                <div class="flex justify-between"><dt>CPU</dt><dd>{{ $plan->cpu_limit }} vCPU</dd></div>
                <div class="flex justify-between"><dt>Disk</dt><dd>{{ round($plan->disk_mb / 1024, 1) }} GB</dd></div>
                <div class="flex justify-between"><dt>Database</dt><dd>{{ $plan->database_limit }}</dd></div>
                <div class="flex justify-between"><dt>Backup</dt><dd>{{ $plan->backup_limit }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('cart.add', $plan) }}" class="mt-5 space-y-3">
                @csrf
                <input class="zy-input" name="server_name" placeholder="Nama server" value="{{ $product->name }} Server">
                <button class="zy-btn-primary w-full">Pilih Plan</button>
            </form>
        </div>
    @endforeach
</div>
@endsection
