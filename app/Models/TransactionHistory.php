<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'fees',
        'status',
        'reference',
        'gateway_response',
        'paid_at',
        'channel',
        'currency',
        'ip_address',
        'transaction_id',
        'sender',
        'receiver',
        'transaction_type',
        'details',
        // Add other fields from the payment processor's response
        'domain',
        'receipt_number',
        'message',
        'metadata',
        'log',
        'authorization',
        'customer',
        'plan',
        'split',
        'order_id',
        'transaction_date',
        'plan_object',
        'subaccount',
    ];

    protected $casts = [
        // Add casting for specific JSON fields if needed
        'authorization' => 'json',
        'customer' => 'json',
        'plan' => 'json',
        'split' => 'json',
        'transaction_date' => 'datetime',
        'plan_object' => 'json',
        'subaccount' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // You can define additional relationships or methods here
}
