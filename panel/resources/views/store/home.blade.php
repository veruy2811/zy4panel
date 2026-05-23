@extends('layouts.app')

@section('content')
<section class="relative min-h-[520px] overflow-hidden rounded-lg border border-line bg-cover bg-center" style="background-image: linear-gradient(90deg, rgba(7,10,18,.94), rgba(7,10,18,.68), rgba(7,10,18,.32)), url('https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=1800&q=80')">
    <div class="flex min-h-[520px] max-w-3xl flex-col justify-center px-6 py-16 md:px-10">
        <p class="mb-3 font-mono text-sm uppercase tracking-wide text-neon">Zy4Store + Zy4Panel</p>
        <h1 class="text-4xl font-extrabold leading-tight text-white md:text-6xl">Hosting game server dengan panel custom yang kamu kendalikan.</h1>
        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300 md:text-lg">Beli paket hosting, upload bukti pembayaran, lalu kelola server lewat console realtime, file manager, database, backup, network, startup, dan activity log.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('products.index') }}" class="zy-btn-primary">Lihat Produk</a>
            @guest
                <a href="{{ route('register') }}" class="zy-btn-secondary">Buat Akun</a>
            @else
                <a href="{{ route('client.servers') }}" class="zy-btn-secondary">Buka Panel</a>
            @endguest
        </div>
    </div>
</section>

<section class="mt-8 grid gap-4 md:grid-cols-4">
    @foreach([
        ['Docker Native', 'Container per server dengan limit RAM, CPU, dan disk.'],
        ['Realtime Console', 'Log container dan command input lewat WebSocket.'],
        ['Secure Files', 'Path dibatasi ke folder server, upload dan edit aman.'],
        ['Manual Payment', 'Invoice, upload bukti, approve admin, provisioning otomatis.'],
    ] as [$title, $copy])
        <div class="zy-card p-5">
            <h2 class="font-semibold text-white">{{ $title }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $copy }}</p>
        </div>
    @endforeach
</section>

<section class="mt-10">
    <div class="mb-4 flex items-end justify-between gap-4">
        <div>
            <p class="font-mono text-xs uppercase tracking-wide text-neon">Katalog</p>
            <h2 class="text-2xl font-bold">Produk Hosting</h2>
        </div>
        <a class="text-sm text-neon hover:text-cyan-200" href="{{ route('products.index') }}">Semua produk</a>
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($products as $product)
            <a href="{{ route('products.show', $product) }}" class="zy-card group block overflow-hidden">
                <div class="h-32 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80')"></div>
                <div class="p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-bold text-white group-hover:text-neon">{{ $product->name }}</h3>
                        <span class="rounded-full border border-line px-2 py-1 text-xs text-slate-400">{{ $product->plans->count() }} plan</span>
                    </div>
                    <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-400">{{ $product->description }}</p>
                    <p class="mt-4 text-sm text-neon">Mulai Rp {{ number_format((float) $product->plans->min('price_monthly'), 0, ',', '.') }}/bulan</p>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endsection
