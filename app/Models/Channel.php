<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    // Fillable attributes
    protected $fillable = ['channel_name' , 'event' ,'status'];

    // Define the relationship with User model
    public function channelUsers()
    {
        return $this->hasMany(ChannelUsers::class)->with('channel');
    }

}
