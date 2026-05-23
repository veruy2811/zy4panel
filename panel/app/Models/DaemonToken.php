<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaemonToken extends Model
{
    protected $fillable = ['node_id', 'name', 'token', 'last_used_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'last_used_at' => 'datetime',
        ];
    }
}
