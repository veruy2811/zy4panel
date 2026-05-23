<?php

namespace App\Services;

use App\Models\Allocation;
use App\Models\Invoice;
use App\Models\Node;
use App\Models\Order;
use App\Models\Server;
use Illuminate\Support\Facades\DB;

class ServerProvisioner
{
    public function __construct(private readonly DaemonClient $daemon)
    {
    }

    public function provisionFromInvoice(Invoice $invoice): Server
    {
        $invoice->loadMissing('order.plan.product', 'user');
        $order = $invoice->order;

        if ($existing = Server::where('order_id', $order->id)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($order): Server {
            $plan = $order->plan;
            $node = Node::where('is_active', true)->orderBy('id')->lockForUpdate()->firstOrFail();
            $allocation = Allocation::where('node_id', $node->id)->whereNull('server_id')->lockForUpdate()->first();

            $server = Server::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'node_id' => $node->id,
                'allocation_id' => $allocation?->id,
                'plan_id' => $plan->id,
                'name' => $order->meta['server_name'] ?? $plan->product->name.' Server',
                'description' => 'Provisioned from invoice '.$order->invoice?->number,
                'status' => 'installing',
                'docker_image' => $plan->docker_image,
                'startup_command' => $plan->startup_command,
                'environment' => array_merge($plan->environment ?? [], [
                    'SERVER_MEMORY' => (string) $plan->ram_mb,
                    'SERVER_PORT' => (string) ($allocation?->port ?? ''),
                ]),
                'memory_mb' => $plan->ram_mb,
                'cpu_limit' => $plan->cpu_limit,
                'disk_mb' => $plan->disk_mb,
            ]);

            if ($allocation) {
                $allocation->update(['server_id' => $server->id, 'is_primary' => true]);
            }

            try {
                $this->daemon->createServer($server->fresh(['node', 'allocation']));
                $server->update(['status' => 'offline', 'installed_at' => now()]);
                $server->recordActivity($order->user, 'server.provisioned', ['order_id' => $order->id]);
                $order->update(['status' => 'provisioned']);
            } catch (\Throwable $exception) {
                $server->update(['status' => 'install_failed', 'allocation_id' => null]);
                $allocation?->update(['server_id' => null, 'is_primary' => false]);
                $server->recordActivity($order->user, 'server.provision_failed', ['error' => $exception->getMessage()]);
                report($exception);
            }

            return $server;
        });
    }
}
