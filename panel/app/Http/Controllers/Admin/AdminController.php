<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Models\DockerTemplate;
use App\Models\Invoice;
use App\Models\Node;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Server;
use App\Models\ServerActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\ServerProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'servers' => Server::count(),
            'invoices_pending' => Invoice::whereIn('status', ['pending', 'payment_submitted'])->count(),
            'payments_pending' => Payment::where('status', 'pending')->count(),
        ];
        $payments = Payment::with('invoice', 'user')->latest()->take(8)->get();
        $servers = Server::with('user', 'node', 'allocation')->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'payments', 'servers'));
    }

    public function users()
    {
        return view('admin.users', [
            'users' => User::with('role')->latest()->paginate(30),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        User::create($data);

        return back()->with('status', 'User dibuat.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);

        return back()->with('status', 'User diperbarui.');
    }

    public function products()
    {
        return view('admin.products', [
            'products' => Product::withCount('plans')->orderBy('sort_order')->paginate(30),
        ]);
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'alpha_dash', 'max:140', 'unique:products,slug'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        Product::create($data);

        return back()->with('status', 'Produk dibuat.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:140', 'unique:products,slug,'.$product->id],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $product->update($data);

        return back()->with('status', 'Produk diperbarui.');
    }

    public function plans()
    {
        return view('admin.plans', [
            'plans' => Plan::with('product')->latest()->paginate(30),
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function storePlan(Request $request)
    {
        $data = $this->planData($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        Plan::create($data);

        return back()->with('status', 'Plan dibuat.');
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $plan->update($this->planData($request, $plan));

        return back()->with('status', 'Plan diperbarui.');
    }

    public function orders()
    {
        return view('admin.orders', [
            'orders' => Order::with('user', 'product', 'plan', 'invoice')->latest()->paginate(40),
        ]);
    }

    public function invoices()
    {
        return view('admin.invoices', [
            'invoices' => Invoice::with('user', 'order.plan', 'payment')->latest()->paginate(40),
        ]);
    }

    public function payments()
    {
        return view('admin.payments', [
            'payments' => Payment::with('invoice.order.plan', 'user', 'approver')->latest()->paginate(40),
        ]);
    }

    public function approvePayment(Request $request, Payment $payment, ServerProvisioner $provisioner)
    {
        $payment->load('invoice.order');
        $payment->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $payment->invoice->update(['status' => 'paid', 'paid_at' => now()]);
        $payment->invoice->order->update(['status' => 'paid']);
        $provisioner->provisionFromInvoice($payment->invoice);

        return back()->with('status', 'Payment approved dan server diproses.');
    }

    public function rejectPayment(Request $request, Payment $payment)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $payment->update([
            'status' => 'rejected',
            'note' => $data['note'] ?? $payment->note,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $payment->invoice()->update(['status' => 'rejected']);

        return back()->with('status', 'Payment ditolak.');
    }

    public function servers()
    {
        return view('admin.servers', [
            'servers' => Server::with('user', 'node', 'allocation', 'plan.product')->latest()->paginate(40),
        ]);
    }

    public function suspendServer(Server $server)
    {
        $server->update(['suspended_at' => now(), 'status' => 'suspended']);

        return back()->with('status', 'Server suspended.');
    }

    public function unsuspendServer(Server $server)
    {
        $server->update(['suspended_at' => null, 'status' => 'offline']);

        return back()->with('status', 'Server unsuspended.');
    }

    public function nodes()
    {
        return view('admin.nodes', [
            'nodes' => Node::withCount('servers', 'allocations')->latest()->paginate(30),
        ]);
    }

    public function storeNode(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'fqdn' => ['nullable', 'string', 'max:190'],
            'daemon_url' => ['required', 'url', 'max:255'],
            'token' => ['required', 'string', 'min:16'],
            'public_ip' => ['nullable', 'ip'],
            'memory_mb' => ['required', 'integer', 'min:0'],
            'disk_mb' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['scheme'] = str_starts_with($data['daemon_url'], 'https') ? 'https' : 'http';
        $data['is_active'] = $request->boolean('is_active', true);
        Node::create($data);

        return back()->with('status', 'Node dibuat.');
    }

    public function updateNode(Request $request, Node $node)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'fqdn' => ['nullable', 'string', 'max:190'],
            'daemon_url' => ['required', 'url', 'max:255'],
            'token' => ['nullable', 'string', 'min:16'],
            'public_ip' => ['nullable', 'ip'],
            'memory_mb' => ['required', 'integer', 'min:0'],
            'disk_mb' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if (blank($data['token'] ?? null)) {
            unset($data['token']);
        }
        $data['scheme'] = str_starts_with($data['daemon_url'], 'https') ? 'https' : 'http';
        $data['is_active'] = $request->boolean('is_active');
        $node->update($data);

        return back()->with('status', 'Node diperbarui.');
    }

    public function allocations()
    {
        return view('admin.allocations', [
            'allocations' => Allocation::with('node', 'server')->latest()->paginate(50),
            'nodes' => Node::orderBy('name')->get(),
        ]);
    }

    public function storeAllocation(Request $request)
    {
        $data = $request->validate([
            'node_id' => ['required', 'exists:nodes,id'],
            'ip' => ['required', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'alias' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:190'],
        ]);
        Allocation::create($data);

        return back()->with('status', 'Allocation dibuat.');
    }

    public function templates()
    {
        return view('admin.templates', [
            'templates' => DockerTemplate::latest()->paginate(30),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'alpha_dash', 'max:120', 'unique:docker_templates,slug'],
            'image' => ['required', 'string', 'max:190'],
            'startup_command' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['environment'] = [];
        $data['is_active'] = $request->boolean('is_active', true);
        DockerTemplate::create($data);

        return back()->with('status', 'Template dibuat.');
    }

    public function settings(Request $request)
    {
        if ($request->isMethod('post')) {
            foreach ($request->except('_token') as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'string']);
            }
            return back()->with('status', 'Settings disimpan.');
        }

        return view('admin.settings', [
            'settings' => Setting::orderBy('key')->get(),
        ]);
    }

    public function logs()
    {
        return view('admin.logs', [
            'logs' => ServerActivityLog::with('server', 'user')->latest()->paginate(50),
        ]);
    }

    private function planData(Request $request, ?Plan $plan = null): array
    {
        $unique = $plan ? 'unique:plans,slug,'.$plan->id : 'unique:plans,slug';
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'alpha_dash', 'max:140', $unique],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'ram_mb' => ['required', 'integer', 'min:128'],
            'cpu_limit' => ['required', 'numeric', 'min:0.1'],
            'disk_mb' => ['required', 'integer', 'min:512'],
            'database_limit' => ['required', 'integer', 'min:0'],
            'backup_limit' => ['required', 'integer', 'min:0'],
            'allocation_limit' => ['required', 'integer', 'min:1'],
            'docker_image' => ['required', 'string', 'max:190'],
            'startup_command' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['environment'] = ['SERVER_MEMORY' => (string) $data['ram_mb']];
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
