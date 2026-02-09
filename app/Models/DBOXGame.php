<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DBOXGame extends Model
{
    protected $table = 'dbox_games';

    protected $fillable = [
        'provider_id','code','name',
        'product_group','sub_product_group',
        'product_group_name','sub_product_group_name',
        'supports_launch','is_active','last_seen_at',
        'sort_order',
    ];

    protected $casts = [
        'supports_launch' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * Always sort by sort_order ASC, then name ASC.
     */
    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order', 'asc')
                 ->orderBy('name', 'asc');
    }

    public function provider()
    {
        return $this->belongsTo(DBOXProvider::class, 'provider_id');
    }

    public function currencies()
    {
        return $this->hasMany(DBOXGameCurrency::class, 'game_id');
    }

    public function images()
    {
        return $this->hasMany(DBOXGameImg::class, 'game_id');
    }

    public function primaryImage()
    {
        return $this->hasOne(DBOXGameImg::class, 'game_id')->where('is_primary', true);
    }
}
