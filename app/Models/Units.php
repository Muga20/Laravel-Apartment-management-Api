<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Units extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_name', 'number_of_rooms', 'payableRent','previousMeterReading',
        'paymentPeriod', 'lastWaterBill','currentMeterReading',
        'damages', 'user_id', 'tenant_id', 'home_id','dateOfOccupation',
        'slug', 'status','isPaid','lastGarbageBill'
    ];


    protected $table = 'units';

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function home()
    {
        return $this->belongsTo(Home::class);
    }


    public function paymentRecords()
    {
        return $this->hasMany(unitRecords::class, 'unit_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }


    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }




}
