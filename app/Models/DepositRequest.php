<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositRequest extends Model
{
    protected $fillable = [
      'user_id','currency','method','bank_name','amount','status','reference','remark','processed_at',
      'provider','out_trade_no','trade_no','pay_url','trade_code','paid_at','provider_payload',
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'provider_payload' => 'array',
        'bonus_amount' => 'decimal:2',
        'turnover_required' => 'decimal:2',
        'turnover_progress' => 'decimal:2',
        'paid_at' => 'datetime',
        'bonus_done_at' => 'datetime',
    ];

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_E_WALLET      = 'e_wallet';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function promotion()
    {
        return $this->belongsTo(\App\Models\Promotion::class, 'promotion_id');
    }
}
