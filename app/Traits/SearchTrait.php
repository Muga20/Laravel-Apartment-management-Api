<?php

// app/Traits/SearchTrait.php

namespace App\Traits;

use App\Models\Roles;
use Illuminate\Http\Request;

trait SearchTrait
{
    public function usersByRoleAndSearch(Request $request, $role)
    {
        try {
            $keyword = $request->input('keyword');
            $status = $request->input('status');
            $page = $request->input('page', 1);

            $allRoles = Roles::all();

            $selectedRole = Roles::where('name', $role)->first();

            if (!$selectedRole) {
                return response()->json(['error' => 'Role not found'], 404);
            }

            $query = $selectedRole->users()->with('detail')->with('company')->with('roles');

            if ($keyword) {
             
                $query->whereHas('detail', function ($q) use ($keyword) {
                    $q->whereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                })->orWhere('email', 'like', "%{$keyword}%");
            }

            if ($status) {
                $query->where('status', $status);
            }

            $roleList = $query->paginate(10, ['*'], 'page', $page);
            $roleList->appends($request->only(['keyword', 'status', 'page']));

            return response()->json(['roleList' => $roleList], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch roles: ' . $e->getMessage()], 500);
        }
    }
}
