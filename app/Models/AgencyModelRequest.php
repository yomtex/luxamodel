<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyModelRequest extends Model
{
    use HasFactory;

    protected $fillable = ['model_id', 'agency_id', 'status', 'request_type'];

    public function model()
    {
        return $this->belongsTo(User::class, 'model_id');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }
}

