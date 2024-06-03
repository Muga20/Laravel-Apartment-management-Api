<?php

use App\Http\Controllers\Manage\TenantsController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {

    Route::prefix('/roles')->group(function () {

        Route::get('/', [RoleController::class, 'index']);
        Route::get('/create', [RoleController::class, 'create'])->name('role.create');
        Route::get('/tenants', [TenantsController::class, 'allTenants'])->name('allTenants');

        Route::get('/all-users', [UserController::class, 'allUsers'])->name('allUsers');

        Route::post('/create-role', [RoleController::class, 'createRole'])->name('createRole');
        Route::get('/{role}', [RoleController::class, 'getUsersByRoleAndSearch']);

        Route::get('/edit-role/{role}' ,[RoleController::class, 'editRole'])->name('editRole');
        Route::put('update-role/{role}' ,[RoleController::class, 'updateRole'])->name('updateRole');


        Route::put('/deactivate_user/{deactivate}', [RoleController::class, 'deactivateUser']);
        Route::post('/assign_role_to_user/{id}', [RoleController::class, 'assignRoleToUser']);

        // Route for deleting a role
        Route::delete('/delete_role_action/{user}/{role}', [RoleController::class, 'deleteRoleFromUser']);
        Route::delete('/delete_role/{role}', [RoleController::class, 'deleteRole']);



    });

});


