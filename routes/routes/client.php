<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;


Route::get('/', [ClientController::class, 'landingPage'])->name('landingPage');
