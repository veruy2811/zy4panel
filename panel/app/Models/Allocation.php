<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{
    protected $fillable = [
        'node_id',
        'server_id',
        'ip',
        'port',
        'alias',
        'is_primary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'port' => 'integer',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
