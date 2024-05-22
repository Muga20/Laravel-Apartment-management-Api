<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'status'
    ];

    public function payments()
    {
        return $this->hasMany(unitRecords::class, 'payment_type_id');
    }

    public function homes()
    {
        return $this->belongsToMany(Home::class, 'home_payment_types', 'payment_type_id', 'home_id');
    }


}
