<?php
// /home/lmh/app/app/Models/DBOXProvider.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DBOXProvider extends Model
{
    protected $table = 'dbox_providers';

    protected $fillable = [
        'code','name','is_active','last_synced_at','sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function games()
    {
        return $this->hasMany(DBOXGame::class, 'provider_id');
    }

    public function promotions()
    {
        return $this->belongsToMany(
            Promotion::class,
            'dbox_provider_promotion',
            'provider_id',
            'promotion_id'
        )->withTimestamps();
    }
    
    public function images()
    {
        return $this->hasMany(DBOXProviderImg::class, 'provider_id');
    }
    
    public function primaryImage()
    {
        return $this->hasOne(DBOXProviderImg::class, 'provider_id')->where('is_primary', true);
    }
    
    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('name');
        // or: orderByDesc('sort_order')->orderBy('name');
    }

}
