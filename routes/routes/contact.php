<?php

use App\Http\Controllers\Help\ContactController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {
    Route::prefix('{company}/contact')->group(function () {

        Route::get('/conversation/{with}' ,[ContactController::class , 'conversation'])->name('conversation');
        Route::get('/showMessages', [ContactController ::class, 'showMessages'])->name('showMessages');
        Route::get('/help', [ContactController ::class, 'createMessage'])->name('createMessage');
        Route::post('/storeMessage', [ContactController ::class, 'storeMessage'])->name('storeMessage');
        Route::post('/replyMessage', [ContactController ::class, 'replyMessage'])->name('replyMessage');

    });

});
