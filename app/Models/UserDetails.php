<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'first_name', 'middle_name', 'last_name',
        'username', 'phone', 'gender', 'date_of_birth',
        'country', 'id_number', 'address', 'profileImage',
        'location', 'about_the_user', 'is_verified',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }



}
