<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminOnlyPhone extends Model
{
    protected $fillable = [
        'phone',
        'label',
    ];
}
