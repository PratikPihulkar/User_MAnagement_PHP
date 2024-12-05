<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plans';

    protected $primaryKey = 'plan_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'array', // Automatically cast JSON field to array
        'description' => 'array', // Automatically cast JSON field to array
    ];

}
