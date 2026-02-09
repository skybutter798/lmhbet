<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DBOXGameCurrency extends Model
{
    protected $table = 'dbox_game_currencies';

    protected $fillable = [
        'game_id','currency','is_active','last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(DBOXGame::class, 'game_id');
    }
}
