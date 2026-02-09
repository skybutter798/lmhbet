<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    public const DIR_CREDIT = 'credit';
    public const DIR_DEBIT  = 'debit';

    public const STATUS_PENDING   = 0;
    public const STATUS_COMPLETED = 1;
    public const STATUS_REVERSED  = 2;
    public const STATUS_FAILED    = 3;
    public const STATUS_CANCELLED = 4;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'wallet_type',
        'direction',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'reference',
        'external_id',
        'tx_hash',

        // ✅ NEW (DBOX/support)
        'provider',
        'round_ref',
        'bet_id',
        'game_code',

        'title',
        'description',
        'created_by',
        'approved_by',
        'ip',
        'user_agent',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        // ✅ If you use MYR/chips, 2 decimals is usually correct
        // If you need more precision (some providers), use decimal:4 instead.
        'amount' => 'decimal:18',
        'balance_before' => 'decimal:18',
        'balance_after' => 'decimal:18',

        'status' => 'integer',
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
