@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">Files</h1>
        <p class="font-mono text-sm text-slate-500">{{ $path }}</p>
    </div>
    <form method="POST" action="{{ route('server.files.upload', $server) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2">
        @csrf
        <input type="hidden" name="path" value="{{ $path }}">
        <input class="zy-input max-w-xs" type="file" name="file" required>
        <button class="zy-btn-primary">Upload</button>
    </form>
</div>

<div class="mb-5 grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('server.files.create', $server) }}" class="zy-card grid gap-3 p-4 md:grid-cols-[1fr_130px_auto]">
        @csrf
        <input class="zy-input" name="path" placeholder="/folder/file.txt" required>
        <select class="zy-input" name="type"><option value="file">File</option><option value="folder">Folder</option></select>
        <button class="zy-btn-secondary">Create</button>
    </form>
    <form method="POST" action="{{ route('server.files.rename', $server) }}" class="zy-card grid gap-3 p-4 md:grid-cols-[1fr_1fr_auto]">
        @csrf
        @method('PATCH')
        <input class="zy-input" name="from" placeholder="/old.txt" required>
        <input class="zy-input" name="to" placeholder="/new.txt" required>
        <button class="zy-btn-secondary">Rename</button>
    </form>
</div>

<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Name</th><th>Type</th><th>Size</th><th>Modified</th><th></th></tr></thead>
        <tbody class="divide-y divide-line">
            @if($path !== '/')
                <tr><td colspan="5"><a class="text-neon" href="{{ route('server.files', [$server, 'path' => dirname($path) === '\\' ? '/' : dirname($path)]) }}">..</a></td></tr>
            @endif
            @forelse(($listing['items'] ?? []) as $item)
                <tr>
                    <td>
                        @if($item['is_dir'] ?? false)
                            <a class="text-neon" href="{{ route('server.files', [$server, 'path' => $item['path']]) }}">{{ $item['name'] }}</a>
                        @else
                            <span>{{ $item['name'] }}</span>
                        @endif
                    </td>
                    <td>{{ ($item['is_dir'] ?? false) ? 'Folder' : 'File' }}</td>
                    <td>{{ $item['size'] ?? 0 }}</td>
                    <td>{{ $item['modified'] ?? '-' }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('server.files.delete', $server) }}" onsubmit="return confirm('Hapus item ini?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="path" value="{{ $item['path'] }}">
                            <button class="text-rose-300 hover:text-rose-200">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-slate-400">{{ $listing['error'] ?? 'Folder kosong.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
