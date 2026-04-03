<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceFcmToken extends Model
{
    protected $fillable = [
        'fcm_token',
    ];
}
