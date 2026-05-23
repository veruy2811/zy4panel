@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Invoices</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Number</th><th>User</th><th>Status</th><th>Total</th><th>Payment</th><th>Due</th><th></th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->user->email }}</td>
                    <td>{{ $invoice->status }}</td>
                    <td>Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                    <td>{{ $invoice->payment?->status ?? '-' }}</td>
                    <td>{{ optional($invoice->due_at)->format('d M Y') }}</td>
                    <td><a class="text-neon" href="{{ route('invoice.show', $invoice) }}">Open</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $invoices->links() }}</div>
@endsection
