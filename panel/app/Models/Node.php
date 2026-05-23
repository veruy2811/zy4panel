<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $fillable = [
        'name',
        'fqdn',
        'scheme',
        'daemon_url',
        'token',
        'public_ip',
        'memory_mb',
        'disk_mb',
        'is_active',
        'last_seen_at',
        'stats',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'stats' => 'array',
        ];
    }

    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }
}
