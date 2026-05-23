<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerScheduleTask extends Model
{
    protected $fillable = ['server_schedule_id', 'action', 'payload', 'sequence'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sequence' => 'integer',
        ];
    }
}
