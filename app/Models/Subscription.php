<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    // Define the table name if it's different from the plural of the model name
    protected $table = 'subscriptions';

    // Define the primary key if it's not 'id'
    protected $primaryKey = 'subscription_id';

    // Specify the data type of the primary key if it's not an auto-incrementing integer
    public $incrementing = true;
    protected $keyType = 'int';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        't_id',
        'u_id',
        'plan_id',
        'expiry'
    ];

    // Define any relationships (if applicable)
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 't_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'u_id');
    }
}
