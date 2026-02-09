<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;

    public const TYPE_MAIN    = 'main';
    public const TYPE_CHIPS   = 'chips';
    public const TYPE_BONUS   = 'bonus';
    public const TYPE_PROMOTE = 'promote';
    public const TYPE_EXTRA   = 'extra';

    public const STATUS_INACTIVE = 0;
    public const STATUS_PENDING  = 1;
    public const STATUS_ACTIVE   = 2;

    protected $fillable = [
        'user_id',
        'type',
        'balance',
        'status',
        'locked_until',
    ];

    protected $casts = [
        'balance' => 'decimal:18',
        'status' => 'integer',
        'locked_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
