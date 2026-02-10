<?php
// /home/lmh/app/app/Models/SupportMessage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'sender_role',
        'body',
        'read_by_user_at',
        'read_by_admin_at',
    ];

    protected $casts = [
        'read_by_user_at' => 'datetime',
        'read_by_admin_at' => 'datetime',
    ];

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}