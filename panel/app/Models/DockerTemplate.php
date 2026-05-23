<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DockerTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'image', 'startup_command', 'environment', 'is_active'];

    protected function casts(): array
    {
        return [
            'environment' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
