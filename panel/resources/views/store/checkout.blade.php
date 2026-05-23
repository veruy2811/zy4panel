@extends('layouts.app')

@section('content')
<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <div class="zy-card p-6">
        <h1 class="text-2xl font-bold">Checkout Manual Payment</h1>
        @if($plan)
            <form method="POST" action="{{ route('checkout.place') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="zy-label">Nama server</label>
                    <input class="zy-input" name="server_name" value="{{ $cart['server_name'] ?? $plan->product->name.' Server' }}" required>
                </div>
                <button class="zy-btn-primary">Buat Invoice</button>
            </form>
        @else
            <p class="mt-4 text-slate-400">Cart kosong. Pilih produk terlebih dahulu.</p>
            <a class="zy-btn-primary mt-5" href="{{ route('products.index') }}">Lihat Produk</a>
        @endif
    </div>

    <aside class="zy-card p-6">
        <h2 class="font-bold">Ringkasan</h2>
        @if($plan)
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><span>Produk</span><span>{{ $plan->product->name }}</span></div>
                <div class="flex justify-between"><span>Plan</span><span>{{ $plan->name }}</span></div>
                <div class="flex justify-between"><span>RAM</span><span>{{ $plan->ram_mb }} MB</span></div>
                <div class="flex justify-between border-t border-line pt-3 text-base font-bold text-neon"><span>Total</span><span>Rp {{ number_format((float) $plan->price_monthly, 0, ',', '.') }}</span></div>
            </div>
        @endif
    </aside>
</div>
@endsection
