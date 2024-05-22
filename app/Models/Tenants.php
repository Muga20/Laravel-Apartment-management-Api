<?php

namespace App\Models;

use App\Services\CompressionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenants extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_no', 'name', 'email','company_id',
        'phone', 'date_of_birth','gender',
        'id_number', 'blood_group', 'country', 'status'
    ];

    public static function boot()
    {
        parent::boot();

        static::retrieved(function ($user) {
            $compressionService = app(CompressionService::class);
            $compressionService->decompressModelAttributes($user);
        });
    }


    protected function compressAttribute($key, $value, $compress = true)
    {
        if ($compress && in_array($key, [
                'email','phone',
                'id_number', 'blood_group' ,
            ]))
            return gzcompress($value);
        return $value;
    }

    public function tenantRoles()
    {
        return $this->belongsToMany(Roles::class, 'useroles', 'tenant_id', 'role_id');
    }

    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $this->compressAttribute($key, $value));
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }



}
