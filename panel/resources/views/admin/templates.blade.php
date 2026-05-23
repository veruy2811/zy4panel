@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Docker Templates</h1>
<form method="POST" action="{{ route('admin.templates.store') }}" class="zy-card mb-6 grid gap-3 p-4 md:grid-cols-4">
    @csrf
    <input class="zy-input" name="name" placeholder="Template name" required>
    <input class="zy-input" name="slug" placeholder="slug optional">
    <input class="zy-input" name="image" placeholder="zy4/generic:latest" required>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
    <input class="zy-input md:col-span-4" name="startup_command" placeholder="Startup command">
    <button class="zy-btn-primary md:col-span-4">Create Template</button>
</form>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Image</th><th>Startup</th><th>Active</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($templates as $template)
                <tr>
                    <td>{{ $template->name }}</td>
                    <td>{{ $template->slug }}</td>
                    <td class="font-mono text-xs">{{ $template->image }}</td>
                    <td class="font-mono text-xs">{{ $template->startup_command }}</td>
                    <td>{{ $template->is_active ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $templates->links() }}</div>
@endsection
