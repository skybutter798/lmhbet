<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DBOXProvider; // ✅ IMPORTANT

class Promotion extends Model
{
    protected $table = 'promotions';

    protected $fillable = [
        'user_id',
        'currency',
        'method',
        'bank_name',
        'promotion_id',     // ✅ ADD THIS
        'amount',
        'status',
        'reference',
        'remark',
        'processed_at',

        // if you want these mass-assignable too:
        'provider',
        'out_trade_no',
        'trade_no',
        'pay_url',
        'trade_code',
        'paid_at',
        'provider_payload',
    ];

    protected $casts = [
        'bonus_value' => 'decimal:4',
        'bonus_cap' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'turnover_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function dboxProviders()
    {
        return $this->belongsToMany(
            DBOXProvider::class,          // ✅ MATCHES FILE NAME
            'dbox_provider_promotion',
            'promotion_id',
            'provider_id'
        )->withTimestamps();
    }
}
