<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'street',
        'city',
        'state',
        'zip'
    ];

    public function policyHolder()
    {
        return $this->hasOne(PolicyHolder::class);
    }

    public function garagingAddress()
    {
        return $this->hasOne(GaragingAddress::class);
    }
}
