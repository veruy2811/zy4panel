@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Global Settings</h1>
<form method="POST" action="{{ route('admin.settings') }}" class="zy-card space-y-4 p-6">
    @csrf
    @foreach($settings as $setting)
        <div>
            <label class="zy-label">{{ $setting->key }}</label>
            <textarea class="zy-input" name="{{ $setting->key }}" rows="2">{{ $setting->value }}</textarea>
        </div>
    @endforeach
    <button class="zy-btn-primary">Save Settings</button>
</form>
@endsection
