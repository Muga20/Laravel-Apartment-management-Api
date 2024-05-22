<?php

// app/Traits/SearchTrait.php

namespace App\Traits;

use App\Models\User;
use App\Models\UserDetails;
use App\Models\Roles;
use App\Services\CompressionService;
use Illuminate\Http\Request;

trait SearchTrait
{
    public function search(Request $request)
    {
        $keyword = $request->input('keyword');

        $query = User::query()->join('user_details', 'users.id', '=', 'user_details.user_id')->select('users.*');

        $allUsers = $query->latest();

        if ($keyword) {
            $compressionService = new CompressionService();
            $compressedKeyword = $compressionService->compressAttribute($keyword);

            $allUsers->where(function ($query) use ($keyword, $compressedKeyword) {
                $query->whereRaw("CONCAT(user_details.first_name, ' ', user_details.middle_name, ' ', user_details.last_name) LIKE ?", ["%{$keyword}%"])
                    ->orWhere('users.email', 'like', "%{$compressedKeyword}%");
            });
        }

        return $allUsers;
    }
}
