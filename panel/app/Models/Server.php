<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Server extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'order_id',
        'node_id',
        'allocation_id',
        'plan_id',
        'name',
        'description',
        'status',
        'docker_image',
        'startup_command',
        'environment',
        'memory_mb',
        'cpu_limit',
        'disk_mb',
        'suspended_at',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'environment' => 'array',
            'cpu_limit' => 'float',
            'suspended_at' => 'datetime',
            'installed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Server $server): void {
            $server->uuid ??= (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function variables()
    {
        return $this->hasMany(ServerVariable::class);
    }

    public function databases()
    {
        return $this->hasMany(ServerDatabase::class);
    }

    public function backups()
    {
        return $this->hasMany(ServerBackup::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ServerActivityLog::class);
    }

    public function canBeAccessedBy(User $user): bool
    {
        return $user->isStaff() || (int) $this->user_id === (int) $user->id;
    }

    public function recordActivity(User $user, string $action, array $metadata = []): ServerActivityLog
    {
        return $this->activityLogs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => request()?->ip(),
            'metadata' => $metadata,
        ]);
    }
}
