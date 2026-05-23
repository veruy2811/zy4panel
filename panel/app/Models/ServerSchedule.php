<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerSchedule extends Model
{
    protected $fillable = ['server_id', 'name', 'cron', 'is_active', 'last_run_at', 'next_run_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }
}
