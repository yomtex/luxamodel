<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Authenticatable implements JWTSubject
{
    use Notifiable, HasFactory; // Added HasFactory for database seeding support

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Automatically hash password before saving.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * Get the identifier that will be stored in the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array containing custom JWT claims.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => 'admin'
        ];
    }
}


