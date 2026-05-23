@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md">
    <div class="zy-card p-6">
        <p class="font-mono text-xs uppercase tracking-wide text-neon">Zy4Panel</p>
        <h1 class="mt-2 text-2xl font-bold">Login</h1>
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="zy-label">Email</label>
                <input class="zy-input" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label class="zy-label">Password</label>
                <input class="zy-input" type="password" name="password" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input class="rounded border-line bg-panel text-neon focus:ring-neon/40" type="checkbox" name="remember" value="1">
                Remember me
            </label>
            <button class="zy-btn-primary w-full">Login</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-400">Belum punya akun? <a class="text-neon" href="{{ route('register') }}">Register</a></p>
    </div>
</div>
@endsection
