<?php

use App\Http\Controllers\Auth\AuthNewUserController;
use App\Http\Controllers\Auth\DeactivateUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\AuthController;

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {
    Route::prefix('/auth')->group(function () {
        Route::get('/register-new-user', [RegisterController::class, 'register'])->name('register');
        Route::post('/register', [RegisterController::class, 'storeUser'])->name('storeUser');
        Route::post('/reset-password', [ResetPasswordController::class, 'updateSecurity'])->name('updateSecurity');
        Route::post('/reset-email', [ResetPasswordController::class, 'updateEmailSecurity'])->name('updateEmailSecurity');
    });
});


Route::get('/login', [LoginController::class, 'login'])->middleware('redirect.auth')->name('login');
Route::post('/authenticate', [LoginController::class, 'authenticate'])->name('authenticate');
Route::post('/login_user', [LoginController::class, 'logInUser'])->name('logInUser');
Route::post('/receive-OTP', [LoginController::class, 'receiveOTP'])->name('receiveOTP');
Route::post('/authenticate-OTP', [LoginController::class, 'authenticateOTP'])->name('authenticateOTP');
Route::post('/activate', [DeactivateUserController::class, 'sendToken'])->name('sendToken');
Route::get('/verify-code', [LoginController::class, 'verifyTwoFa'])->name('verifyTwoFa');
Route::post('/verify-code', [LoginController::class, 'verifyTwoFaCode'])->name('verifyTwoFaCode');
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);


Route::get('/auth/{provider}/login', [SocialiteController::class, 'autoAuth'])->name('autoAuth');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'autoAuthCallBack'])->name('autoAuthCallBack');

Route::post('/check-login-type', [LoginController::class, 'checkLoginType'])->name('checkLoginType');
Route::post('/authenticate-user', [LoginController::class, 'logInUser'])->name('logInUser');

// Route for logging out
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Route for viewing the token from cookies
Route::get('/view-token', [LoginController::class, 'viewTokenFromCookie'])->name('viewTokenFromCookie');


Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetPasswordEmail'])->name('sendResetPasswordEmail');

Route::get('/passwordReset/{token}', [ResetPasswordController::class, 'passwordReset'])->middleware('redirect.auth')->name('passwordReset');
Route::post('/newPassword/{token}', [ResetPasswordController::class, 'newPassword'])->middleware('redirect.auth')->name('newPassword');

Route::get('/auth-new-user/{authLink}', [AuthNewUserController::class, 'AuthNewUser'])->middleware('redirect.auth')->name('AuthNewUser');
Route::post('/auth-new-user/{authLink}', [AuthNewUserController::class, 'ConfirmAuthNewUser'])->middleware('redirect.auth')->name('ConfirmAuthNewUser');
