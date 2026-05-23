<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerSubuser extends Model
{
    protected $fillable = ['server_id', 'user_id', 'permissions'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
