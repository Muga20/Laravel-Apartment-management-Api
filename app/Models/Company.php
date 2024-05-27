<?php

namespace App\Models;

use App\Services\CompressionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'status', 'address',
        'phone', 'description', 'theme',
        'logoImage', 'slug', 'location',
        'companyUrl','companyId'
    ];

    public static function boot()
    {
        parent::boot();

        static::retrieved(function ($company) {
            $compressionService = app(CompressionService::class);
            $compressionService->decompressModelAttributes($company);
        });
    }

    protected function compressAttribute($key, $value, $compress = true)
    {
        if ($compress && in_array($key,  [
        'email', 'address', 'phone',
        'description',
    ]))
            return gzcompress($value);
        return $value;
    }

    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $this->compressAttribute($key, $value));
    }

    public function plan()
    {
        return $this->belongsTo(Plans::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function Home()
    {
        return $this->hasMany(Home::class);
    }


    public function tenants()
    {
        return $this->hasMany(Tenants::class);
    }
}
