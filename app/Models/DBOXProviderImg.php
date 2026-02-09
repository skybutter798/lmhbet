<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DBOXProviderImg extends Model
{
    protected $table = 'dbox_provider_imgs';

    protected $fillable = [
        'provider_id',
        'path',
        'label',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function provider()
    {
        return $this->belongsTo(DBOXProvider::class, 'provider_id');
    }

    public function getUrlAttribute(): string
    {
        return asset($this->path);
    }
}
