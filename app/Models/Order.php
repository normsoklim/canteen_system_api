<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\OrderDetail;

class Order extends Model
{
    //
    protected $fillable = [
        'order_date',
        'total_amount',
        'order_status',
        'user_id',
        'payment_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
    
    /**
     * Calculate total amount from order details
     */
    public function calculateTotalAmount()
    {
        return $this->orderDetails->sum('sub_total');
    }
    
    /**
     * Recalculate and update the total amount from order details
     */
    public function recalculateTotalAmount()
    {
        $total = $this->orderDetails->sum('sub_total');
        $this->update(['total_amount' => $total]);
        return $total;
    }
    
    /**
     * Check if payment is completed for this order
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }
    
}



