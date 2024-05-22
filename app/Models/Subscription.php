<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'stkPush_id', 'plan_id', 'company_id',
    ];

    public function stkrequest()
    {
        return $this->belongsTo(Stkrequest::class, 'stkPush_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plans::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
