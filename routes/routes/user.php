<?php


use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\DeactivateUserController;
use App\Http\Controllers\DashboardController;


Route::group(['middleware' => ['token']], function () {

    Route::prefix('/member')->group(function () {

        Route::post('/create-member', [UserController::class, 'storeNewUser'])->name('storeNewUser');
        Route::post('/update-profile-image', [UserController::class, 'updateProfileImage']);

        Route::get('/userSettings', [UserController::class, 'userSettings'])->name('userSettings');
        Route::get('/userSecurity', [UserController::class, 'userSecurity'])->name('userSecurity');
        Route::get('/userDeactivate', [UserController::class, 'userDeactivate'])->name('userDeactivate');
        Route::post('/two-fa-setup', [UserController::class, 'twoFaSetup'])->name('twoFaSetup');

        Route::get('/get-authenticated-cred' ,[UserController::class , 'getAuthenticatedUser']);

        Route::get('/staff', [DashboardController::class, 'stuff'])->name('staff');


        Route::post('/update-this-user-profile', [UserController::class, 'updateUser']);

        Route::get('/profile', [ProfileController::class, 'profile'])->name('profile');

        Route::get('/profile/{userData}', [ProfileController::class, 'profileInfo'])->name('profileInfo');

        Route::post('/set-auth-type',[UserController::class, 'setAuthType'])->name('setAuthType');

        Route::post('/deactivate-account', [DeactivateUserController::class, 'deactivateMyAccount']);

    });

    Route::get('/auth/{provider}/redirect/{userId}',[SocialiteController::class, 'userAuth'] )->name('userAuth');

});




