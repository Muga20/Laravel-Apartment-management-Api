<?php

use App\Http\Controllers\Notification\NotificationController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {
    Route::prefix('/notification')->group(function () {
        Route::get('/', [NotificationController::class, 'getAllNotifications']);
        Route::delete('/delete_notification/{id}', [NotificationController::class, 'deleteNotification']);

        Route::get('/channels', [NotificationController::class, 'getAllChannels']);
        Route::post('/create-channel', [NotificationController::class, 'createChannel']);

    });
});
