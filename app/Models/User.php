<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'phone_country',
        'phone',
        'country',
        'currency',
        'referral_code',
        'referred_by_user_id',
        'is_active',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'banned_at' => 'datetime',
            'locked_until' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
    
    public function referrer()
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }
    
    public function referrals()
    {
        return $this->hasMany(self::class, 'referred_by_user_id');
    }
    
    public function wallets()
    {
        return $this->hasMany(\App\Models\Wallet::class);
    }
    
    public function walletTransactions()
    {
        return $this->hasMany(\App\Models\WalletTransaction::class);
    }
    
    // optional helpers (nice for quick access)
    public function mainWallet()
    {
        return $this->hasOne(\App\Models\Wallet::class)->where('type', \App\Models\Wallet::TYPE_MAIN);
    }
    
    public function bonusWallet()
    {
        return $this->hasOne(\App\Models\Wallet::class)->where('type', \App\Models\Wallet::TYPE_BONUS);
    }
    
    protected static function booted()
    {
        static::created(function (User $user) {
            $now = now();
            $types = [
                \App\Models\Wallet::TYPE_MAIN,
                \App\Models\Wallet::TYPE_CHIPS,
                \App\Models\Wallet::TYPE_BONUS,
                // \App\Models\Wallet::TYPE_PROMOTE,
                // \App\Models\Wallet::TYPE_EXTRA,
            ];
    
            $rows = [];
            foreach ($types as $type) {
                $rows[] = [
                    'user_id' => $user->id,
                    'type' => $type,
                    'balance' => 0,
                    'status' => \App\Models\Wallet::STATUS_ACTIVE,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
    
            \App\Models\Wallet::insert($rows);
        });
    }
    
    public function vipTier()
    {
        return $this->belongsTo(\App\Models\VipTier::class);
    }
    
    public function kycProfile()
    {
        return $this->hasOne(\App\Models\KycProfile::class);
    }
    
    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(KycSubmission::class);
    }

    public function depositRequests(): HasMany
    {
        return $this->hasMany(DepositRequest::class);
    }

}
