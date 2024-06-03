<?php

use App\Http\Controllers\Manage\HomeController;
use App\Http\Controllers\Manage\PaymentsController;
use App\Http\Controllers\Manage\TenantsController;
use App\Http\Controllers\Manage\UnitPaymentController;
use App\Http\Controllers\Manage\UnitsController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {
    Route::prefix('management')->group(function () {


        Route::get('/', [HomeController::class, 'showHomes'])->name('showHomes');
        Route::get('/home/{unit}', [HomeController::class, 'HomeProfile'])->name('HomeProfile');

        //Home
        Route::post('/create-home', [HomeController::class, 'storeHome'])->name('storeHome');
        Route::get('/edit-home/{slug}', [HomeController::class, 'editHome'])->name('editHome');
        Route::post('/update-home/{slug}', [HomeController::class, 'storeEditedHome'])->name('storeEditedHome');

        //PaymentInformation
        Route::get('/edit-home/{slug}/Payment-info', [HomeController::class, 'createHomePaymentInfo'])->name('createHomePaymentInfo');
        Route::post('/edit-home/{slug}/Payment-info', [HomeController::class, 'storeHomePaymentInfo'])->name('storeHomePaymentInfo');
        Route::get('/edit-home/{slug}/edit-payment/{payment}', [HomeController::class, 'updatePaymentInfo'])->name('updatePaymentInfo');
        Route::put('/edit-home/{slug}/edit-payment/{payment}', [HomeController::class, 'updateHomePaymentInfo'])->name('updateHomePaymentInfo');
        Route::delete('/edit-home/delete-payment-ifo/{payment}', [HomeController::class, 'deletePaymentInfo'])->name('deletePaymentInfo');


        //Units
        Route::get('/home/manage-unit/{unit}', [UnitsController::class, 'manageUnits'])->name('manageUnits');
        Route::get('/single-unit/{unit}/{home}' , [UnitsController::class, 'singleUnit'])->name('singleUnit');
        Route::post('/rent-a-home/{tenant}/{unit}', [UnitsController::class, 'rentTenant'])->name('rentTenant');
        Route::post('/remove-tenant/{unit}', [UnitsController::class, 'removeTenant'])->name('removeTenant');
        Route::get('/single-unit/{unit}/{home}/Payment', [UnitsController::class, 'paymentUnits'])->name('paymentUnits');
        Route::get('/single-unit/{unit}/{home}/edit', [UnitsController::class, 'editUnits'])->name('editUnits');
        Route::post('/single-unit/{unit}/{home}/edit', [UnitsController::class, 'storeEditedUnit'])->name('storeEditedUnit');
        Route::get('/single-unit/{unit}/{home}/transactions', [UnitsController::class, 'paymentTransactions'])->name('paymentTransactions');
        Route::post('/single-unit/{unit}/water-reading', [UnitsController::class, 'updateWaterReading'])->name('updateWaterReading');
        Route::get('/single-unit/{unit}/{home}/statement', [UnitsController::class, 'transactionStatement'])->name('transactionStatement');
        Route::get('/my-house', [UnitsController::class, 'myHouse'])->name('myHouse');




        //Payments
        Route::get('/single-unit/{unit}/{home}/Payment/rent', [UnitPaymentController::class, 'RentPaymentUnits'])->name('RentPaymentUnits');
        Route::get('/single-unit/{unit}/{home}/Payment/garbage', [UnitPaymentController::class, 'GarbagePaymentUnits'])->name('GarbagePaymentUnits');
        Route::get('/single-unit/{unit}/{home}/Payment/water', [UnitPaymentController::class, 'WaterPaymentUnits'])->name('WaterPaymentUnits');
        Route::post('/single-unit/{unit}/{home}/Payment', [UnitPaymentController::class, 'storeUnitPayment'])->name('storeUnitPayment');

        //Tenants
        Route::get('/create-tenant/{unit}', [TenantsController::class, 'createTenant'])->name('createTenant');
        Route::post('/create-tenant/{unit}', [TenantsController::class, 'storeTenant'])->name('storeTenant');
        Route::get('/get-tenants/{unit}', [TenantsController::class, 'getTenants'])->name('getTenants');
        Route::post('/deactivate_tenants/{deactivate}', [TenantsController::class, 'deactivateTenant'])->name('deactivateTenant');


        //PaymentsVerification
        Route::get('/payments/{unit}', [PaymentsController::class, 'paymentIndex'])->name('paymentIndex');
        Route::get('/payments/{unit}/payment-for/{trans_id}', [PaymentsController::class, 'paymentVerification'])->name('paymentVerification');
        Route::post('/payment/{unit}/update-payment', [PaymentsController::class, 'updateUnitRecordPayment'])->name('updateUnitRecordPayment');
        Route::post('/payment/{unit}/reject-payment', [PaymentsController::class, 'rejectUnitRecordPayment'])->name('rejectUnitRecordPayment');


    });
});
