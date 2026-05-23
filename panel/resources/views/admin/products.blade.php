@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Products</h1>
<form method="POST" action="{{ route('admin.products.store') }}" class="zy-card mb-6 grid gap-3 p-4 md:grid-cols-3">
    @csrf
    <input class="zy-input" name="name" placeholder="Product name" required>
    <input class="zy-input" name="slug" placeholder="slug optional">
    <input class="zy-input" name="category" value="game" required>
    <input class="zy-input md:col-span-2" name="image_url" placeholder="Image URL">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
    <textarea class="zy-input md:col-span-3" name="description" rows="3" placeholder="Description"></textarea>
    <button class="zy-btn-primary md:col-span-3">Create Product</button>
</form>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Category</th><th>Plans</th><th>Active</th><th></th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($products as $product)
                <tr>
                    <form method="POST" action="{{ route('admin.products.update', $product) }}">
                        @csrf @method('PATCH')
                        <td><input class="zy-input" name="name" value="{{ $product->name }}"></td>
                        <td><input class="zy-input" name="slug" value="{{ $product->slug }}"></td>
                        <td><input class="zy-input" name="category" value="{{ $product->category }}"></td>
                        <td>{{ $product->plans_count }}</td>
                        <td><input type="checkbox" name="is_active" value="1" @checked($product->is_active)></td>
                        <td><input type="hidden" name="description" value="{{ $product->description }}"><input type="hidden" name="image_url" value="{{ $product->image_url }}"><button class="zy-btn-secondary">Save</button></td>
                    </form>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $products->links() }}</div>
@endsection
