<?php

use App\Http\Controllers\Manage\TenantsController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {

    Route::prefix('/roles')->group(function () {

        Route::get('/', [RoleController::class, 'index'])->name('role.index');
        Route::get('/create', [RoleController::class, 'create'])->name('role.create');
        Route::get('/tenants', [TenantsController::class, 'allTenants'])->name('allTenants');

        Route::get('/all-users', [UserController::class, 'allUsers'])->name('allUsers');

        Route::post('/create-role', [RoleController::class, 'createRole'])->name('createRole');
        Route::get('/{role}', [RoleController::class, 'usersByRole'])->name('users_by_role');

        Route::get('/edit-role/{role}' ,[RoleController::class, 'editRole'])->name('editRole');
        Route::put('update-role/{role}' ,[RoleController::class, 'updateRole'])->name('updateRole');


        Route::post('deactivate_user/{deactivate}', [RoleController::class, 'deactivateUser'])->name('deactivate_user');
        Route::post('assign_role_to_user/{addRoleToId}', [RoleController::class, 'assignRoleToUser'])->name('assign_role_to_user');

        // Route for deleting a role
        Route::post('delete_role_action/{user}/{role}', [RoleController::class, 'deleteRoleFromUser'])->name('delete_role_action');
        Route::delete('/delete_role/{role}', [RoleController::class, 'deleteRole'])->name('deleteRole');



    });

});


