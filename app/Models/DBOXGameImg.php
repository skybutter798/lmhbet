<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DBOXGameImg extends Model
{
    protected $table = 'dbox_game_imgs';

    protected $fillable = [
        'game_id',
        'path',
        'label',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function game()
    {
        return $this->belongsTo(DBOXGame::class, 'game_id');
    }

    public function getUrlAttribute(): string
    {
        // path is like "images/games/xxx.png"
        return asset($this->path);
    }
}
