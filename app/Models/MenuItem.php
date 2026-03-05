<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Category;
use App\Models\OrderDetail;
class MenuItem extends Model
{
    use  HasFactory;   

     protected $fillable = [
        'item_name',
        'description',
        'price',
        'cost_price',
        'availability_status',
        'image_url',
        'category_id',
    ];
     // Each MenuItem belongs to one Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

}