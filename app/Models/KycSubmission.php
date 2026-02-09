<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'status',
        'remarks',
        'submitted_at',
        'verified_at',
    ];
    
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    
    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maskedAccountNumber(): string
    {
        $n = (string) $this->account_number;
        $len = mb_strlen($n);
        if ($len <= 4) return $n;
        return str_repeat('*', max(0, $len - 4)) . mb_substr($n, -4);
    }
}
