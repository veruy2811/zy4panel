<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Zy4Store') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-void text-slate-100">
    <div class="min-h-screen">
        <header class="sticky top-0 z-40 border-b border-line bg-void/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg border border-neon/60 bg-panel2 font-mono font-bold text-neon">Z4</span>
                    <span>
                        <span class="block text-sm font-bold tracking-wide">Zy4Store</span>
                        <span class="block text-xs text-slate-400">Zy4Panel Hosting</span>
                    </span>
                </a>
                <nav class="hidden items-center gap-5 text-sm text-slate-300 md:flex">
                    <a class="hover:text-neon" href="{{ route('products.index') }}">Produk</a>
                    @auth
                        <a class="hover:text-neon" href="{{ route('client.dashboard') }}">Client Area</a>
                        @if(auth()->user()->isAdmin())
                            <a class="hover:text-neon" href="{{ route('admin.dashboard') }}">Admin</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="hover:text-neon">Logout</button>
                        </form>
                    @else
                        <a class="hover:text-neon" href="{{ route('login') }}">Login</a>
                        <a class="zy-btn-primary" href="{{ route('register') }}">Register</a>
                    @endauth
                </nav>
            </div>
        </header>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 lg:grid-cols-[240px_1fr]">
            @auth
                <aside class="hidden lg:block">
                    <div class="sticky top-20 space-y-6">
                        <div class="zy-card p-3">
                            <div class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Client</div>
                            <a class="block rounded-lg px-3 py-2 text-sm hover:bg-panel {{ request()->routeIs('client.dashboard') ? 'text-neon' : 'text-slate-300' }}" href="{{ route('client.dashboard') }}">Dashboard</a>
                            <a class="block rounded-lg px-3 py-2 text-sm hover:bg-panel {{ request()->routeIs('client.orders') ? 'text-neon' : 'text-slate-300' }}" href="{{ route('client.orders') }}">Orders</a>
                            <a class="block rounded-lg px-3 py-2 text-sm hover:bg-panel {{ request()->routeIs('client.invoices') ? 'text-neon' : 'text-slate-300' }}" href="{{ route('client.invoices') }}">Invoices</a>
                            <a class="block rounded-lg px-3 py-2 text-sm hover:bg-panel {{ request()->routeIs('client.servers') ? 'text-neon' : 'text-slate-300' }}" href="{{ route('client.servers') }}">Servers</a>
                        </div>
                        @if(auth()->user()->isAdmin())
                            <div class="zy-card p-3">
                                <div class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Admin</div>
                                @foreach([
                                    'dashboard' => ['Admin', 'admin.dashboard'],
                                    'users' => ['Users', 'admin.users'],
                                    'products' => ['Products', 'admin.products'],
                                    'plans' => ['Plans', 'admin.plans'],
                                    'orders' => ['Orders', 'admin.orders'],
                                    'invoices' => ['Invoices', 'admin.invoices'],
                                    'payments' => ['Payments', 'admin.payments'],
                                    'servers' => ['Servers', 'admin.servers'],
                                    'nodes' => ['Nodes', 'admin.nodes'],
                                    'allocations' => ['Allocations', 'admin.allocations'],
                                    'templates' => ['Templates', 'admin.templates'],
                                    'settings' => ['Settings', 'admin.settings'],
                                    'logs' => ['Logs', 'admin.logs'],
                                ] as $item)
                                    <a class="block rounded-lg px-3 py-2 text-sm hover:bg-panel {{ request()->routeIs($item[1]) ? 'text-neon' : 'text-slate-300' }}" href="{{ route($item[1]) }}">{{ $item[0] }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </aside>
            @endauth

            <main class="{{ auth()->check() ? '' : 'lg:col-span-2' }}">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
