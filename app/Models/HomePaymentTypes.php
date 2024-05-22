<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePaymentTypes extends Model
{
    use HasFactory;

    use HasFactory;

    protected $fillable = [
        'account_name',
        'account_payBill',
        'account_number',
        'home_id',
        'unit_id',
    ];

    public function home()
    {
        return $this->belongsTo(Home::class);
    }

    public function unit()
    {
        return $this->belongsTo(Units::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function unitRecords()
    {
        return $this->belongsTo(unitRecords::class);
    }



}
