<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'target_user_id',
        'target_type',
        'target_id',
        'ip',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(\App\Models\Admin::class);
    }
}
