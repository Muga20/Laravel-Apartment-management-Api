<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->belongsToMany(User::class, 'useroles', 'role_id', 'user_id');
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenants::class, 'useroles', 'role_id', 'tenant_id');
    }

}
