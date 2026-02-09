<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KycProfile extends Model
{
    use HasFactory;

    public const STATUS_NOT_SUBMITTED = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    protected $fillable = [
        'user_id',
        'status',
        'full_name',
        'id_type',
        'id_number',
        'dob',
        'document_front_path',
        'document_back_path',
        'selfie_path',
        'remark',
    ];

    protected $casts = [
        'status' => 'integer',
        'dob' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
