<?php


use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => ['token']], function () {

    Route::prefix('{company}/dashboard')->group(function () {
           Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
           Route::get('/stuff', [DashboardController::class, 'stuff'])->name('stuff');
           Route::get('/calendar', [DashboardController::class, 'calendar'])->name('calendar');


    });

});
