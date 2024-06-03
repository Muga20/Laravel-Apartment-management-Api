<?php

use App\Http\Controllers\Cart\CheckoutController;
use App\Http\Controllers\Finance\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {
    Route::prefix('{company}/checkout')->group(function () {
        // Use CheckoutController for checkout route
        Route::get('/checkout/{plan}', [CheckoutController::class, 'checkout'])->name('checkout');
        Route::post('companySubscription', [PaymentController::class , 'companySubscription'])->name('companySubscription');

        // Use PaymentController for other routes
        Route::get('/initiatepush', [PaymentController::class, 'initiateStkPush'])->name('initiatepush');
        Route::get('/stkquery', [PaymentController::class, 'stkQuery'])->name('stkquery');
        Route::get('/registerurl', [PaymentController::class, 'registerUrl'])->name('registerurl');
        Route::post('/validation', [PaymentController::class, 'Validation'])->name('validation');
        Route::post('/confirmation', [PaymentController::class, 'Confirmation'])->name('confirmation');
        Route::get('/simulate', [PaymentController::class, 'Simulate'])->name('simulate');
        Route::get('/qrcode', [PaymentController::class, 'qrcode'])->name('qrcode');


    });
});


Route::post('/stkcallback', [PaymentController::class, 'stkCallback'])->name('stkCallback');

