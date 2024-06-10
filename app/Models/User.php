<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * @var mixed|string
     */

    /**
     * @var mixed|string
     */

    protected $fillable = [
        'email', 'password', 'status', 'company_id',
        'role_id', 'otp', 'authType', 'uuid', 'provider',
        'provider_id', 'provider_token', 'two_factor_code'
        , 'two_factor_expires_at', 'sms_number', 'two_fa_status',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Roles::class, 'user_roles', 'user_id', 'role_id');
    }

    public function hasAnyRole($roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function detail()
    {
        return $this->hasOne(UserDetails::class);
    }

    public function agentUnits()
    {
        return $this->hasMany(Units::class, 'agent_id');
    }

    public function tenantUnits()
    {
        return $this->hasMany(Units::class, 'tenant_id');
    }

    public function units()
    {
        return $this->hasMany(Units::class, 'tenant_id');
    }

    public function unit_records()
    {
        return $this->hasMany(unitRecords::class, 'tenant_id');
    }

    public function channelUsers()
    {
        return $this->hasMany(ChannelUsers::class)->with('channel');
    }

}
