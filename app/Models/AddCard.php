<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddCard extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'cardnumber', 'expmth', 'expyear'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
