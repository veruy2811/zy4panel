<?php

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\ApiToken;
use App\Models\DaemonToken;
use App\Models\DockerTemplate;
use App\Models\Node;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $clientRole = Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client']);

        foreach ($this->permissions() as $permission) {
            Permission::firstOrCreate(['slug' => $permission], ['name' => Str::headline($permission)]);
        }

        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'role_id' => $adminRole->id,
                'name' => env('ADMIN_NAME', 'Zy4 Admin'),
                'password' => env('ADMIN_PASSWORD', 'ChangeMe123!'),
                'is_active' => true,
            ],
        );

        ApiToken::firstOrCreate(
            ['name' => 'Default Admin Token'],
            [
                'user_id' => $admin->id,
                'token' => hash('sha256', env('DAEMON_SECRET', Str::random(48)).'admin-api'),
                'abilities' => ['*'],
            ],
        );

        $products = [
            [
                'name' => 'Minecraft Hosting',
                'slug' => 'minecraft-hosting',
                'category' => 'game',
                'description' => 'Server Minecraft performa tinggi dengan Docker container, console realtime, backup, database, dan file manager.',
                'plans' => [
                    ['Stone', 25000, 1024, 1, 5120, 'zy4/minecraft:latest', 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar nogui'],
                    ['Diamond', 65000, 4096, 2, 20480, 'zy4/minecraft:latest', 'java -Xms256M -Xmx{{SERVER_MEMORY}}M -jar server.jar nogui'],
                ],
            ],
            [
                'name' => 'SAMP Hosting',
                'slug' => 'samp-hosting',
                'category' => 'game',
                'description' => 'Hosting SA-MP ringan untuk roleplay dan freeroam dengan port allocation fleksibel.',
                'plans' => [
                    ['Starter', 30000, 1024, 1, 8192, 'zy4/samp:latest', './samp03svr'],
                    ['Community', 75000, 3072, 2, 20480, 'zy4/samp:latest', './samp03svr'],
                ],
            ],
            [
                'name' => 'FiveM Hosting',
                'slug' => 'fivem-hosting',
                'category' => 'game',
                'description' => 'Template awal untuk server FiveM berbasis container. Resource dan license key diatur lewat Startup variables.',
                'plans' => [
                    ['FX Lite', 90000, 4096, 2, 30720, 'zy4/generic:latest', './run.sh +exec server.cfg'],
                ],
            ],
            [
                'name' => 'Discord Bot Hosting',
                'slug' => 'discord-bot-hosting',
                'category' => 'bot',
                'description' => 'Hosting bot Node.js dengan upload source, log realtime, restart cepat, dan environment variables.',
                'plans' => [
                    ['Bot Nano', 15000, 512, 0.5, 2048, 'zy4/nodejs:latest', 'npm start'],
                    ['Bot Pro', 35000, 1024, 1, 5120, 'zy4/nodejs:latest', 'npm start'],
                ],
            ],
            [
                'name' => 'VPS Hosting',
                'slug' => 'vps-hosting',
                'category' => 'vps',
                'description' => 'Produk VPS placeholder untuk katalog Zy4Store. Provisioning VPS native dapat dikembangkan setelah MVP game panel stabil.',
                'plans' => [
                    ['VPS Dev', 55000, 2048, 1, 20480, 'zy4/generic:latest', '/bin/bash start.sh'],
                ],
            ],
        ];

        foreach ($products as $sort => $item) {
            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'is_active' => true,
                    'sort_order' => $sort,
                ],
            );

            foreach ($item['plans'] as [$name, $price, $ram, $cpu, $disk, $image, $startup]) {
                Plan::updateOrCreate(
                    ['product_id' => $product->id, 'slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'description' => "{$ram}MB RAM, {$cpu} vCPU, ".round($disk / 1024).'GB disk',
                        'price_monthly' => $price,
                        'ram_mb' => $ram,
                        'cpu_limit' => $cpu,
                        'disk_mb' => $disk,
                        'database_limit' => 2,
                        'backup_limit' => 3,
                        'allocation_limit' => 1,
                        'docker_image' => $image,
                        'startup_command' => $startup,
                        'environment' => ['SERVER_MEMORY' => (string) $ram],
                        'is_active' => true,
                    ],
                );
            }
        }

        $nodeToken = env('DAEMON_SECRET') ?: Str::random(48);
        $node = Node::updateOrCreate(
            ['name' => 'Local Node 01'],
            [
                'fqdn' => '127.0.0.1',
                'scheme' => 'http',
                'daemon_url' => env('DAEMON_DEFAULT_URL', 'http://127.0.0.1:7443'),
                'token' => $nodeToken,
                'public_ip' => '127.0.0.1',
                'memory_mb' => 32768,
                'disk_mb' => 524288,
                'is_active' => true,
            ],
        );

        DaemonToken::firstOrCreate(
            ['name' => 'Local Node Token'],
            ['node_id' => $node->id, 'token' => $nodeToken],
        );

        foreach (range(25565, 25570) as $port) {
            Allocation::firstOrCreate(
                ['node_id' => $node->id, 'ip' => '0.0.0.0', 'port' => $port],
                ['alias' => '127.0.0.1', 'is_primary' => false],
            );
        }

        foreach ([
            ['Minecraft Java', 'minecraft', 'zy4/minecraft:latest', 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar nogui'],
            ['SA-MP', 'samp', 'zy4/samp:latest', './samp03svr'],
            ['Node.js', 'nodejs', 'zy4/nodejs:latest', 'npm start'],
            ['Generic Ubuntu', 'generic', 'zy4/generic:latest', '/bin/bash start.sh'],
        ] as [$name, $slug, $image, $startup]) {
            DockerTemplate::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'image' => $image, 'startup_command' => $startup, 'environment' => [], 'is_active' => true],
            );
        }

        foreach ([
            'site_name' => 'Zy4Store',
            'panel_name' => 'Zy4Panel',
            'payment_instructions' => 'Transfer sesuai nominal invoice lalu upload bukti pembayaran. Admin akan memverifikasi secara manual.',
            'client_can_request_allocations' => 'false',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'string']);
        }

        User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'role_id' => $clientRole->id,
                'name' => 'Demo Client',
                'password' => 'client12345',
                'is_active' => true,
            ],
        );
    }

    private function permissions(): array
    {
        return [
            'console.read',
            'console.send',
            'server.start',
            'server.stop',
            'server.restart',
            'server.kill',
            'files.read',
            'files.write',
            'files.delete',
            'databases.read',
            'databases.create',
            'databases.delete',
            'backups.read',
            'backups.create',
            'backups.restore',
            'backups.delete',
            'network.read',
            'startup.read',
            'startup.update',
            'settings.read',
            'settings.update',
        ];
    }
}
