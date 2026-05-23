<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerVariable extends Model
{
    protected $fillable = ['server_id', 'key', 'value', 'is_secret'];

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
            'is_secret' => 'boolean',
        ];
    }
}
