<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stkrequest extends Model
{
    use HasFactory;
//
//    protected $fillable = [
//    ];

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }
}
