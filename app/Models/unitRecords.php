<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unitRecords extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'rentFee', 'waterFee','recommendation',
        'garbageFee', 'phone', 'acc_number','isApproved',
        'receipt', 'transaction_date', 'status', 'unit_id',
        'tenant_id', 'payment_type_id', 'stkPush_id',
        'mpesaReceiptImage' ,'bankReceiptImage','payingFor'
    ];

    public function unit()
    {
        return $this->belongsTo(Units::class, 'unit_id');
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }

    public function HomePaymentTypes()
    {
        return $this->belongsTo(HomePaymentTypes::class, 'payment_type_id');

    }


    public function stkPush()
    {
        return $this->belongsTo(STKRequest::class, 'stkPush_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }





}
