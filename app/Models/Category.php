<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MenuItem;

class Category extends Model
{
    use  HasFactory;
    
    protected $fillable = [
        'category_name',
        'description',
        'status',
    ];
    
     // One Category has many MenuItems
    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
}
