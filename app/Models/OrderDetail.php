<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Order;
use App\Models\MenuItem;
class OrderDetail extends Model
{
    //
    protected $table = 'order_detail';
    
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'quantity',
        'unit_price',
        'sub_total',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
    
    /**
     * Boot the model and attach event handlers
     */
    protected static function boot()
    {
        parent::boot();
        
        // Update order total after creating an order detail
        static::created(function ($orderDetail) {
            $order = $orderDetail->order;
            if ($order) {
                $order->recalculateTotalAmount();
            }
        });
        
        // Update order total after updating an order detail
        static::updated(function ($orderDetail) {
            $order = $orderDetail->order;
            if ($order) {
                $order->recalculateTotalAmount();
            }
        });
        
        // Update order total after deleting an order detail
        static::deleted(function ($orderDetail) {
            $order = $orderDetail->order;
            if ($order) {
                $order->recalculateTotalAmount();
            }
        });
    }
}
