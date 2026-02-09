<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusTurnoverItem extends Model
{
    protected $fillable = [
        'deposit_request_id',
        'bet_record_id',
        'counted_amount',
    ];
}