<?php

// app/Traits/TenantSearchTrait.php

namespace App\Traits;

use App\Models\UserDetails;
use App\Models\Roles;
use App\Services\CompressionService;

trait TenantSearchTrait
{
    public function searchTenants($request)
    {
        $keyword = $request->input('keyword');

        $query = UserDetails::with(['user' => function ($query) {
            $query->with('company');
        }])->whereHas('user.roles', function ($query) {
            $query->where('name', 'tenant');
        });

        if ($keyword) {
            $query->where(function ($query) use ($keyword) {
                $query->whereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
            });

            $compressionService = new CompressionService();
            $compressedKeyword = $compressionService->compressAttribute($keyword);

            $query->orWhereHas('user', function ($query) use ($compressedKeyword, $keyword) {
                $query->where('email', 'like', "%{$compressedKeyword}%");
            });
        }

        return $query;
    }
}

