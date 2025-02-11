<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaragingAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'address_id'
    ];

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
