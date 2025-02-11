<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyHolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id',
        'address_id',
        'first_name',
        'last_name'
    ];

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
