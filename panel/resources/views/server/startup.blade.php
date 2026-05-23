@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<div class="zy-card p-6">
    <h1 class="text-2xl font-bold">Startup</h1>
    <form method="POST" action="{{ route('server.startup.update', $server) }}" class="mt-6 space-y-4">
        @csrf
        @method('PATCH')
        <div>
            <label class="zy-label">Docker Image</label>
            <input class="zy-input" name="docker_image" value="{{ $server->docker_image }}" required>
        </div>
        <div>
            <label class="zy-label">Startup Command</label>
            <textarea class="zy-input font-mono" name="startup_command" rows="4">{{ $server->startup_command }}</textarea>
        </div>
        <div>
            <label class="zy-label">Environment Variables</label>
            <textarea class="zy-input font-mono" name="environment" rows="8">@foreach(($server->environment ?? []) as $key => $value){{ $key }}={{ $value }}
@endforeach</textarea>
        </div>
        <button class="zy-btn-primary">Save Startup</button>
    </form>
</div>
@endsection
