<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Home extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'location', 'images',
        'houseCategory', 'stories',
        'status', 'description','rentPaymentDay',
        'company_id', 'phone','agent_id',
        'email', 'slug','user_id','landlord_id'
    ];

    protected $table = 'homes';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function units()
    {
        return $this->hasMany(Units::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function homePaymentTypes()
    {
        return $this->hasMany(HomePaymentTypes::class);
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }




}
