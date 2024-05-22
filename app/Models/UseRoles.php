<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UseRoles extends Model
{
    use HasFactory;

    protected $table = 'useroles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'role_id',
    ];

    /**
     * Get the user associated with the role.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Get the role associated with the user.
     */
    public function role()
    {
        return $this->belongsTo(Roles::class);
    }


    public function tenant()
    {
        return $this->belongsTo(Tenants::class);
    }
}
