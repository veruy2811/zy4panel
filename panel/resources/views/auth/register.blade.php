@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md">
    <div class="zy-card p-6">
        <p class="font-mono text-xs uppercase tracking-wide text-neon">Zy4Store</p>
        <h1 class="mt-2 text-2xl font-bold">Register</h1>
        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="zy-label">Nama</label>
                <input class="zy-input" name="name" value="{{ old('name') }}" required autofocus>
            </div>
            <div>
                <label class="zy-label">Email</label>
                <input class="zy-input" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="zy-label">Password</label>
                <input class="zy-input" type="password" name="password" required>
            </div>
            <div>
                <label class="zy-label">Konfirmasi Password</label>
                <input class="zy-input" type="password" name="password_confirmation" required>
            </div>
            <button class="zy-btn-primary w-full">Buat Akun</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-400">Sudah punya akun? <a class="text-neon" href="{{ route('login') }}">Login</a></p>
    </div>
</div>
@endsection
