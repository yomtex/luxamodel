<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddCardMoney extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'cardnumber','name', 'expmth', 'expyear'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
