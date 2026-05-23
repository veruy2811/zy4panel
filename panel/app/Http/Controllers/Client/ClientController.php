<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $servers = $user->servers()->with('allocation', 'node')->latest()->get();
        $orders = $user->orders()->latest()->take(5)->get();
        $invoices = $user->invoices()->latest()->take(5)->get();

        return view('client.dashboard', compact('servers', 'orders', 'invoices'));
    }

    public function orders(Request $request)
    {
        $orders = $request->user()->orders()->with('product', 'plan', 'invoice')->latest()->paginate(20);

        return view('client.orders', compact('orders'));
    }

    public function invoices(Request $request)
    {
        $invoices = $request->user()->invoices()->with('order.plan', 'payment')->latest()->paginate(20);

        return view('client.invoices', compact('invoices'));
    }

    public function servers(Request $request)
    {
        $servers = $request->user()->servers()->with('allocation', 'node', 'plan.product')->latest()->paginate(20);

        return view('client.servers', compact('servers'));
    }
}
