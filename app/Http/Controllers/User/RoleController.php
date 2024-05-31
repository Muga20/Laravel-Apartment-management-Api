<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Roles;
use App\Models\User;
use App\Models\UseRoles;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);

            $roleColors = [
                'admin' => 'blue',
                'user' => 'green',
                'agent' => 'orange',
                'sudo' => 'red',
            ];
            $roles = Roles::withCount('users')->get();

            return response()->json(['roles' => $roles, 'color' => $roleColors], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch roles: ' . $e->getMessage()], 500);
        }
    }

    public function getUsersByRoleAndSearch(Request $request, $role)
    {
        return $this->usersByRoleAndSearch($request, $role);
    }

    public function createRole(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required',
            ]);

            $role = new Roles();
            $role->name = $validatedData['name'];
            $role->status = 'inactive';
            $role->slug = Str::slug($validatedData['name'], '-');
            $role->save();

            return response()->json(['success' => 'Role created successfully'], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create Roles. Please try again.'], 500);
        }
    }

    public function editRole(Request $request, $dummy, $role)
    {
        $data = $this->loadCommonData($request);

        $decodedRoleId = base64_decode($role);
        $roleId = unserialize($decodedRoleId);

        $roleName = Roles::findOrFail($roleId);

        return view('pages.Roles.edit', compact('roleName') + $data);
    }

    public function updateRole(Request $request, $dummy, $role)
    {
        try {
            $decodedRoleId = unserialize(base64_decode($role));
            $roleName = Roles::findOrFail($decodedRoleId);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $roleName->name = $validatedData['name'];
            $roleName->save();

            return redirect()->back()->with('success', 'Role Updated Successfully');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update Role. Please try again.');
        }
    }

    public function deactivateUser(Request $request, $deactivate = null)
    {
        try {
            $user = User::findOrFail($deactivate);

            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            $newStatus = $user->status === 'active' ? 'inactive' : 'active';

            $user->update([
                'status' => $newStatus,
            ]);

            $successMessage = $newStatus === 'inactive' ? 'User deactivated successfully.' : 'User activated successfully.';

            return response()->json(['message' => $successMessage], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to deactivate/activate user. Please try again.'], 500);
        }
    }

    public function assignRoleToUser(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'role_id' => 'required',
            ]);

            $user = User::findOrFail($id);

            $role = Roles::findOrFail($validatedData['role_id']);

            $existingUserRole = UseRoles::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->first();

            if (!$existingUserRole) {
                $userRole = new UseRoles();
                $userRole->user_id = $user->id;
                $userRole->role_id = $role->id;
                $userRole->save();
            } else {
                return response()->json(['message' => 'User already has this role.'], 200);
            }

            return response()->json(['message' => 'Role assigned successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to assign role: ' . $e->getMessage()], 500);
        }
    }

    public function deleteRoleFromUser($user, $role)
    {
        try {
            $user = User::findOrFail($user);

            $roleAssociation = UseRoles::where('user_id', $user->id)
                ->where('role_id', $role)
                ->firstOrFail();

            $roleAssociation->delete();

            return response()->json(['message' => 'Role removed successfully from user'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to remove role from user: ' . $e->getMessage()], 500);
        }
    }

    public function deleteRole($role)
    {
        try {
            $roleModel = Roles::findOrFail($role);

            if ($roleModel->status === 'active') {
                return response()->json([
                    'error' => 'Role is active and cannot be deleted.',
                ], 400);
            }

            $roleModel->delete();

            return response()->json([
                'message' => 'Role deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete role: ' . $e->getMessage(),
            ], 500);
        }
    }

}
