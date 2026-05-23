<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'description',
        'price_monthly',
        'ram_mb',
        'cpu_limit',
        'disk_mb',
        'database_limit',
        'backup_limit',
        'allocation_limit',
        'docker_image',
        'startup_command',
        'environment',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'cpu_limit' => 'float',
            'environment' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
