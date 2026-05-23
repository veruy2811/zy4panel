@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Plans</h1>
<form method="POST" action="{{ route('admin.plans.store') }}" class="zy-card mb-6 grid gap-3 p-4 md:grid-cols-4">
    @csrf
    <select class="zy-input" name="product_id">@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>
    <input class="zy-input" name="name" placeholder="Plan name" required>
    <input class="zy-input" name="slug" placeholder="slug optional">
    <input class="zy-input" type="number" name="price_monthly" placeholder="Price" required>
    <input class="zy-input" type="number" name="ram_mb" value="1024" required>
    <input class="zy-input" type="number" step="0.1" name="cpu_limit" value="1" required>
    <input class="zy-input" type="number" name="disk_mb" value="5120" required>
    <input class="zy-input" type="number" name="database_limit" value="1" required>
    <input class="zy-input" type="number" name="backup_limit" value="1" required>
    <input class="zy-input" type="number" name="allocation_limit" value="1" required>
    <input class="zy-input md:col-span-2" name="docker_image" value="zy4/generic:latest" required>
    <input class="zy-input md:col-span-4" name="startup_command" placeholder="Startup command">
    <textarea class="zy-input md:col-span-4" name="description" rows="2" placeholder="Description"></textarea>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
    <button class="zy-btn-primary md:col-span-3">Create Plan</button>
</form>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Product</th><th>Name</th><th>Price</th><th>RAM</th><th>CPU</th><th>Disk</th><th>Image</th><th></th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($plans as $plan)
                <tr>
                    <td>{{ $plan->product->name }}</td>
                    <td>{{ $plan->name }}</td>
                    <td>Rp {{ number_format((float) $plan->price_monthly, 0, ',', '.') }}</td>
                    <td>{{ $plan->ram_mb }}</td>
                    <td>{{ $plan->cpu_limit }}</td>
                    <td>{{ $plan->disk_mb }}</td>
                    <td class="font-mono text-xs">{{ $plan->docker_image }}</td>
                    <td><span class="text-slate-500">Edit via form seed or DB</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $plans->links() }}</div>
@endsection
