<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = [
        'flag_key',
        'name',
        'description',
        'is_enabled',
        'owner',
        'sunset_date',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sunset_date' => 'date',
    ];
}
