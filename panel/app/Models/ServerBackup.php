<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerBackup extends Model
{
    protected $fillable = [
        'server_id',
        'uuid',
        'name',
        'path',
        'size_bytes',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
