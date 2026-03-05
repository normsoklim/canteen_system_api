<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_status',
        'payment_gateway',
        'payment_reference',
        'transaction_id',
        'transaction_ref',
        'qr_string',
        'qr_code',
        'gateway_response',
        'callback_data',
        'expires_at',
        'error_message',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'callback_data' => 'array',
        'expires_at' => 'datetime',
        'payment_date' => 'date',
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function getIsBakongAttribute()
    {
        return $this->payment_method === 'digital' && $this->payment_gateway === 'bakong';
    }
}