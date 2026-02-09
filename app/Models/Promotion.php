<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DBOXProvider; // ✅ IMPORTANT

class Promotion extends Model
{
    protected $table = 'promotions';

    protected $fillable = [
        'code',
        'title',
        'type',
        'bonus_type',
        'bonus_value',
        'bonus_cap',
        'min_amount',
        'max_amount',
        'turnover_multiplier',
        'currency',
        'terms',
        'is_active',
        'starts_at',
        'ends_at',
        'sort_order',
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
