<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewUserSession extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'token', 'otp_code'];
}
