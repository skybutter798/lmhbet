<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BetRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'round_ref',
        'bet_id',
        'game_code',
        'currency',
        'wallet_type',
        'stake_amount',
        'payout_amount',
        'profit_amount',
        'bet_reference',
        'settle_reference',
        'bet_at',
        'settled_at',
        'status',
        'meta',
    ];

    protected $casts = [
        'stake_amount'  => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'bet_at'        => 'datetime',
        'settled_at'    => 'datetime',
        'meta'          => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
