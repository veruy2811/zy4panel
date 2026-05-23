@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Invoices</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Nomor</th><th>Status</th><th>Total</th><th>Payment</th><th>Action</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->status }}</td>
                    <td>Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                    <td>{{ $invoice->payment?->status ?? '-' }}</td>
                    <td><a class="text-neon" href="{{ route('invoice.show', $invoice) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-slate-400">Belum ada invoice.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $invoices->links() }}</div>
@endsection
