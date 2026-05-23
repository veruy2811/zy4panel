@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Orders</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>ID</th><th>Produk</th><th>Plan</th><th>Status</th><th>Total</th><th>Invoice</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->product->name }}</td>
                    <td>{{ $order->plan->name }}</td>
                    <td>{{ $order->status }}</td>
                    <td>Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                    <td>@if($order->invoice)<a class="text-neon" href="{{ route('invoice.show', $order->invoice) }}">{{ $order->invoice->number }}</a>@endif</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-slate-400">Belum ada order.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $orders->links() }}</div>
@endsection
