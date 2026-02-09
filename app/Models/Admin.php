<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'username',
        'password',
        'pin',
        'two_fa_secret',
        'upline_id',
        'group',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'pin',
        'two_fa_secret',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed', // Laravel will hash when set
        ];
    }

    public function upline()
    {
        return $this->belongsTo(self::class, 'upline_id');
    }

    public function downlines()
    {
        return $this->hasMany(self::class, 'upline_id');
    }
}
