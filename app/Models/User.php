<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Order;
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory;
    
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'provider',
        'provider_id',
        'avatar'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
