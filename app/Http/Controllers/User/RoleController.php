<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Roles;
use App\Models\User;
use App\Models\UserRoles;
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles: ' . $e->getMessage()], 500);
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

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully'], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Roles. Please try again.'], 500);
        }
    }


    public function updateRole(Request $request, $role)
    {
        try {

            $roleName = Roles::findOrFail($decodedRoleId);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $roleName->name = $validatedData['name'];

            $roleName->save();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully'], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update roles. please try again.'], 500);
        }
    }

    public function deactivateUser(Request $request, $deactivate = null)
    {
        try {
            $user = User::findOrFail($deactivate);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'], 404);
            }

            $newStatus = $user->status === 'active' ? 'inactive' : 'active';

            $user->update([
                'status' => $newStatus,
            ]);

            $successMessage = $newStatus === 'inactive' ? 'User deactivated successfully.' : 'User activated successfully.';

            return response()->json([
                'success' => true,
                'message' => $successMessage], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate/activate user. Please try again.'], 500);
        }
    }

    public function deactivateOrActivateRole(Request $request, $role = null)
    {
        try {
            $role = Roles::findOrFail($role);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found.'], 404);
            }

            $newStatus = $role->status === 'active' ? 'inactive' : 'active';

            $role->update([
                'status' => $newStatus,
            ]);

            $successMessage = $newStatus === 'inactive' ? 'Role deactivated successfully.' : 'Role activated successfully.';

            return response()->json([
                'success' => true,
                'message' => $successMessage], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate/activate role. Please try again.'], 500);
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

            $existingUserRole = UserRoles::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->first();

            if (!$existingUserRole) {
                $userRole = new UserRoles();
                $userRole->user_id = $user->id;
                $userRole->role_id = $role->id;
                $userRole->save();
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'User already has this role.'], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role: ' . $e->getMessage()], 500);
        }
    }

    public function deleteRoleFromUser($user, $role)
    {
        try {

            $user = User::findOrFail($user);

            $roleAssociation = UserRoles::where('user_id', $user->id)
                ->where('role_id', $role)
                ->firstOrFail();

            $roleAssociation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully from user'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove role from user: ' . $e->getMessage()], 500);
        }
    }

    public function deleteRole($role)
    {
        try {
            $roleModel = Roles::findOrFail($role);

            if ($roleModel->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Role is active and cannot be deleted.',
                ], 400);
            }

            $roleModel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage(),
            ], 500);
        }
    }

}
