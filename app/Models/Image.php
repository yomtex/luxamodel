<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'file_path', 'album_id', 'user_id'
    ];

    // Many-to-many relationship with agencies
    public function agencies()
    {
        return $this->belongsToMany(Agency::class, 'image_agency', 'image_id', 'agency_id')
            ->select('agencies.id', 'agencies.agency_name'); // Explicitly select agency fields
    }

}

