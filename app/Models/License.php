<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $table = 'licenses';

    protected $guarded = [];

    protected $appends = ['is_expired', 'days_remaining'];

    public function getIsExpiredAttribute()
    {
        return now()->gt($this->expiration_date);
    }

    public function getDaysRemainingAttribute()
    {
        return now()->diffInDays($this->expiration_date, false);
    }
}
