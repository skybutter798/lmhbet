<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WinPayDeposit extends Model
{
    protected $table = 'winpay_deposits';

    protected $fillable = [
        'user_id',
        'bill_number',
        'type',
        'bank_name',
        'depositor_name',
        'amount',
        'status',
        'winpay_status',
        'pay_url',
        'request_payload',
        'create_response',
        'notify_payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'create_response' => 'array',
        'notify_payload' => 'array',
        'paid_at' => 'datetime',
    ];
}
