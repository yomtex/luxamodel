<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Tymon\JWTAuth\Contracts\JWTSubject;

class Agency extends Authenticatable implements JWTSubject 
{
    protected $fillable = [
        'agency_name',
        'email',
        'official_mail',
        'category',
        'phone_number',
        'website',
        'about_agency',
        'address',
        'country',
        'agency_logo',
        'contact_details',
        'status',
        'password',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'contact_details' => 'array',
    ];

        /**
     * Scope to filter only approved agencies.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Many-to-many relationship with images
    public function images()
    {
        return $this->belongsToMany(Image::class, 'image_agency', 'agency_id', 'image_id');
    }

     /**
     * Get the identifier that will be stored in the JWT subject claim.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => 'agency'
        ];
    }
}


