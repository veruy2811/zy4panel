<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function home()
    {
        $products = Product::with(['plans' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(5)
            ->get();

        return view('store.home', compact('products'));
    }

    public function products()
    {
        $products = Product::with(['plans' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->paginate(12);

        return view('store.products', compact('products'));
    }

    public function product(Product $product)
    {
        abort_unless($product->is_active, 404);
        $product->load(['plans' => fn ($query) => $query->where('is_active', true)->orderBy('price_monthly')]);

        return view('store.product', compact('product'));
    }

    public function addToCart(Request $request, Plan $plan)
    {
        abort_unless($plan->is_active && $plan->product?->is_active, 404);

        $request->session()->put('cart', [
            'plan_id' => $plan->id,
            'server_name' => $request->input('server_name', $plan->product->name.' Server'),
        ]);

        return redirect()->route('checkout')->with('status', 'Paket masuk cart.');
    }

    public function checkout(Request $request)
    {
        $cart = $request->session()->get('cart');
        $plan = $cart ? Plan::with('product')->find($cart['plan_id']) : null;

        return view('store.checkout', compact('cart', 'plan'));
    }

    public function placeOrder(Request $request)
    {
        $cart = $request->session()->get('cart');
        abort_if(! $cart, 422, 'Cart kosong.');

        $plan = Plan::with('product')->findOrFail($cart['plan_id']);
        $data = $request->validate([
            'server_name' => ['required', 'string', 'max:80'],
        ]);

        [$order, $invoice] = DB::transaction(function () use ($request, $plan, $data): array {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'product_id' => $plan->product_id,
                'plan_id' => $plan->id,
                'status' => 'pending_payment',
                'total' => $plan->price_monthly,
                'meta' => [
                    'server_name' => $data['server_name'],
                    'term' => 'monthly',
                ],
            ]);

            $invoice = Invoice::create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'status' => 'pending',
                'amount' => $plan->price_monthly,
                'due_at' => now()->addDay(),
            ]);

            return [$order, $invoice];
        });

        $request->session()->forget('cart');

        return redirect()->route('invoice.show', $invoice)->with('status', 'Invoice dibuat. Silakan upload bukti pembayaran.');
    }

    public function invoice(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        abort_unless($user->isStaff() || (int) $invoice->user_id === (int) $user->id, 403);
        $invoice->load('order.product', 'order.plan', 'payment');

        return view('store.invoice', compact('invoice'));
    }

    public function uploadPayment(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:102400'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $data['proof']->store('payments', 'public');

        Payment::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'user_id' => $request->user()->id,
                'method' => 'manual',
                'proof_path' => $path,
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ],
        );

        $invoice->update(['status' => 'payment_submitted']);

        return back()->with('status', 'Bukti pembayaran berhasil dikirim.');
    }
}
