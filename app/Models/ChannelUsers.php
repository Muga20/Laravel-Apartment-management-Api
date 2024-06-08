<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelUsers extends Model
{
    use HasFactory;

    // Define the table name if it's different from the default 'channel_users'
    protected $table = 'channel_users';

    // Define fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'company_id',
        'channel_id',
    ];

    /**
     * Define the relationship with the User model.
     * Assuming each channel user belongs to a specific user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship with the Company model.
     * Assuming each channel user belongs to a specific company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Define the relationship with the Channel model.
     * Assuming each channel user belongs to a specific channel.
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
