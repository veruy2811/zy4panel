<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerActivityLog extends Model
{
    protected $fillable = ['server_id', 'user_id', 'action', 'ip_address', 'metadata'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
