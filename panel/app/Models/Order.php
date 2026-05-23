<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'plan_id',
        'status',
        'total',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function server()
    {
        return $this->hasOne(Server::class);
    }
}
