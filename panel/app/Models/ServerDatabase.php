<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerDatabase extends Model
{
    protected $fillable = [
        'server_id',
        'node_id',
        'name',
        'username',
        'password',
        'host',
        'port',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'port' => 'integer',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
