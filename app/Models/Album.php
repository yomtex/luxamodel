<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'hidden',
        'privacy',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the user that owns the album.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
