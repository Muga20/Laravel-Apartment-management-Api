<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    protected $fillable = [
        "name", "status", "slug",
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenants::class, 'user_roles', 'role_id', 'tenant_id');
    }

}
