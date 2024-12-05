<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // Define the table name if it's different from the plural of the model name
    protected $table = 'transactions';

    // Define the primary key if it's not 'id'
    protected $primaryKey = 'transaction_id';

    // Specify the data type of the primary key if it's not an auto-incrementing integer
    public $incrementing = true;
    protected $keyType = 'int';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'amount',
        'payment_type',
        'plan_id',
        'payment_option_details'
    ];

    // Define the relationships (if applicable)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
