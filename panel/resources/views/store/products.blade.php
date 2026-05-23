@extends('layouts.app')

@section('content')
<div class="mb-6">
    <p class="font-mono text-xs uppercase tracking-wide text-neon">Zy4Store</p>
    <h1 class="text-3xl font-bold">Produk Hosting</h1>
    <p class="mt-2 text-slate-400">Pilih produk, tentukan plan, checkout manual payment, lalu server dibuat otomatis setelah admin approve.</p>
</div>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach($products as $product)
        <a href="{{ route('products.show', $product) }}" class="zy-card group block overflow-hidden">
            <div class="h-36 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=900&q=80')"></div>
            <div class="p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-white group-hover:text-neon">{{ $product->name }}</h2>
                    <span class="rounded-full border border-line px-2 py-1 text-xs text-slate-400">{{ $product->category }}</span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-400">{{ $product->description }}</p>
                <div class="mt-4 text-sm text-neon">Mulai Rp {{ number_format((float) $product->plans->min('price_monthly'), 0, ',', '.') }}/bulan</div>
            </div>
        </a>
    @endforeach
</div>

<div class="mt-6">{{ $products->links() }}</div>
@endsection
