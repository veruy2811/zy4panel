@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Payments</h1>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>ID</th><th>User</th><th>Invoice</th><th>Status</th><th>Proof</th><th>Note</th><th>Actions</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($payments as $payment)
                <tr>
                    <td>#{{ $payment->id }}</td>
                    <td>{{ $payment->user->email }}</td>
                    <td>{{ $payment->invoice->number }}</td>
                    <td>{{ $payment->status }}</td>
                    <td>@if($payment->proof_path)<a class="text-neon" target="_blank" href="{{ asset('storage/'.$payment->proof_path) }}">Open</a>@endif</td>
                    <td>{{ $payment->note }}</td>
                    <td class="space-y-2">
                        @if($payment->status === 'pending')
                            <form method="POST" action="{{ route('admin.payments.approve', $payment) }}">@csrf<button class="zy-btn-primary py-1">Approve</button></form>
                            <form method="POST" action="{{ route('admin.payments.reject', $payment) }}">@csrf<input type="hidden" name="note" value="Rejected by admin"><button class="zy-btn-danger py-1">Reject</button></form>
                        @else
                            <span class="text-slate-500">{{ $payment->approved_at?->format('d M Y H:i') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $payments->links() }}</div>
@endsection
