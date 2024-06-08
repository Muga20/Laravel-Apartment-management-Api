<?php

use App\Http\Controllers\Notification\EventsController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token' ]], function () {
    Route::prefix('{company}/events')->group(function () {

        Route::get('/', [EventsController ::class, 'showEvents'])->name('showEvents');
        Route::get('/createEvents', [EventsController ::class, 'createEvents'])->name('createEvents');
        Route::post('/storeEvents', [EventsController ::class, 'storeEvents'])->name('storeEvents');
        Route::get('/{event:slug}', [EventsController ::class, 'editEvents'])->name('editEvents');
        Route::put('/updateEvents/{event}', [EventsController ::class, 'updateEvents'])->name('updateEvents');
        Route::delete('/deleteEvents/{event}', [EventsController ::class, 'deleteEvents'])->name('deleteEvents');

    });

});
