<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalBankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'account_last4',
    ];

    protected $casts = [
        'account_number' => 'encrypted', // ✅ automatic encrypt/decrypt
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maskedAccountNumber(): string
    {
        $last4 = $this->account_last4;
        if (!$last4) {
            $n = (string) ($this->account_number ?? '');
            $len = mb_strlen($n);
            $last4 = $len >= 4 ? mb_substr($n, -4) : $n;
        }
        return '**** ' . $last4;
    }
}