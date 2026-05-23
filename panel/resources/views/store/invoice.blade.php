@extends('layouts.app')

@section('content')
<div class="grid gap-6 lg:grid-cols-[1fr_380px]">
    <div class="zy-card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs uppercase tracking-wide text-neon">Invoice</p>
                <h1 class="text-2xl font-bold">{{ $invoice->number }}</h1>
            </div>
            <span class="rounded-full border border-line px-3 py-1 text-sm text-slate-300">{{ $invoice->status }}</span>
        </div>
        <dl class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-panel p-4"><dt class="text-xs text-slate-500">Produk</dt><dd class="mt-1 font-semibold">{{ $invoice->order->product->name }}</dd></div>
            <div class="rounded-lg bg-panel p-4"><dt class="text-xs text-slate-500">Plan</dt><dd class="mt-1 font-semibold">{{ $invoice->order->plan->name }}</dd></div>
            <div class="rounded-lg bg-panel p-4"><dt class="text-xs text-slate-500">Total</dt><dd class="mt-1 font-semibold text-neon">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</dd></div>
            <div class="rounded-lg bg-panel p-4"><dt class="text-xs text-slate-500">Jatuh tempo</dt><dd class="mt-1 font-semibold">{{ optional($invoice->due_at)->format('d M Y H:i') }}</dd></div>
        </dl>
    </div>

    <aside class="zy-card p-6">
        <h2 class="font-bold">Pembayaran Manual</h2>
        <div class="mt-4 rounded-lg border border-line bg-panel p-4 text-sm">
            <div class="text-slate-400">{{ config('services.payments.bank_name') }}</div>
            <div class="mt-1 font-semibold">{{ config('services.payments.account_name') }}</div>
            <div class="font-mono text-neon">{{ config('services.payments.account_number') }}</div>
        </div>
        @if($invoice->payment)
            <p class="mt-4 text-sm text-slate-400">Status bukti: <span class="text-slate-100">{{ $invoice->payment->status }}</span></p>
            @if($invoice->payment->proof_path)
                <a class="mt-3 inline-block text-sm text-neon" href="{{ asset('storage/'.$invoice->payment->proof_path) }}" target="_blank">Lihat bukti</a>
            @endif
        @endif
        @if(! in_array($invoice->status, ['paid'], true))
            <form method="POST" action="{{ route('invoice.payment', $invoice) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="zy-label">Upload bukti</label>
                    <input class="zy-input" type="file" name="proof" required>
                </div>
                <textarea class="zy-input" name="note" rows="3" placeholder="Catatan transfer"></textarea>
                <button class="zy-btn-primary w-full">Kirim Bukti</button>
            </form>
        @endif
    </aside>
</div>
@endsection
