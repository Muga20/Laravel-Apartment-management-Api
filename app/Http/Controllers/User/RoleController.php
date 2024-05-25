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

    public function usersByRole(Request $request, $role)
    {
        try {
            $allRoles = Roles::all();

            $selectedRole = Roles::where('name', $role)->first();

            if (!$selectedRole) {
                return response()->json(['error' => 'Role not found'], 404);
            }

            $roleList = $selectedRole->users()->with('detail')->with('company')->paginate(10);

            $roleList->appends(['keyword' => $request->input('keyword')]);

            return response()->json(['roleList' => $roleList], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch roles: ' . $e->getMessage()], 500);
        }
    }

    public function createRole(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required',
            ]);

            $role = new Roles();
            $role->name = $validatedData['name'];
            $role->slug = Str::slug($validatedData['name'], '-');
            $role->save();

            return response()->json(['success'=> 'Role created successfully'], 201);

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

    public function deactivateUser($dummy, $deactivate = null)
    {
        try {
            $user = User::find($deactivate);

            if (!$user) {
                return redirect()->back()->with('error', 'User not found.');
            }

            $newStatus = $user->status === 'active' ? 'inactive' : 'active';

            $user->update([
                'status' => $newStatus,
            ]);

            $successMessage = $newStatus === 'inactive' ? 'User deactivated successfully.' : 'User activated successfully.';

            return redirect()->back()->with('success', $successMessage);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to deactivate/activate user. Please try again.');
        }
    }

    public function assignRoleToUser(Request $request, $dummy, $addRoleToId = null)
    {
        try {
            $validatedData = $request->validate([
                'role_id' => 'required',
            ]);

            $user = User::where('id', $addRoleToId)->first();

            if (!$user) {
                throw new \Exception('User not found.');
            }

            $role = Roles::find($validatedData['role_id']); // Corrected 'Roles' to 'Role'

            if (!$role) {
                throw new \Exception('Role not found.');
            }

            $existingUserRole = UseRoles::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->first();

            if (!$existingUserRole) {
                $userRole = new UseRoles();
                $userRole->user_id = $user->id;
                $userRole->role_id = $role->id;
                $userRole->save();
            } else {
                throw new \Exception('User already has this role.');
            }

            return back()->with('success', 'Role assigned successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign role: ' . $e->getMessage());
        }
    }

    public function deleteRoleFromUser($dummy, $user, $role)
    {
        try {

            $user = User::findOrFail($user);

            $roleAssociation = UseRoles::where('user_id', $user->id)
                ->where('role_id', $role)
                ->firstOrFail();

            $roleAssociation->delete();

            return redirect()->back()->with('success', 'Role removed successfully from user.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to remove role from user: ' . $e->getMessage());
        }
    }

    public function deleteRole($dummy, $role)
    {
        try {
            $wipeRole = Roles::findOrFail($role);

            $wipeRole->delete();

            return redirect()->back()->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

}
