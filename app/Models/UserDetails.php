<?php

namespace App\Models;

use App\Services\CompressionService;
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


    public static function boot()
    {
        parent::boot();

        static::retrieved(function ($userDetails) {
            $compressionService = app(CompressionService::class);
            $compressionService->decompressModelAttributes($userDetails);
        });
    }

    protected function compressAttribute($key, $value, $compress = true)
    {
        if ($compress && in_array($key,  [
                'username', 'phone', 'gender', 'country', 'id_number',
                'address', 'location', 'about_the_user',
            ]))
            return gzcompress($value);
        return $value;
    }

    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $this->compressAttribute($key, $value));
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }



}
