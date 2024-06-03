<?php

use App\Http\Controllers\Setting\PaymentSettingController;
use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {
    Route::prefix('/settings')->group(function () {

        Route::get('/company', [SettingController::class, 'settingIndex'])->name('settingIndex');
        Route::get('/editCompany', [SettingController::class , 'editCompanyProfile'])->name('editCompanyProfile');
        Route::post('/storeEditedProfile' ,[SettingController::class , 'storeEditedProfile'])->name('storeEditedProfile');


        Route::get('/get-payment-mode-types', [PaymentSettingController::class, 'paymentModeTypes'])->name('paymentSetting');
        Route::put('/deactivate_payment/{deactivate}', [PaymentSettingController::class, 'deactivatePayment']);

        Route::post('/create-Payment', [PaymentSettingController::class, 'storePayment'])->name('storePayment');
        Route::get('/Payment-setting/edit-type/{Payment}', [PaymentSettingController::class, 'editPayment'])->name('editPayment');
        Route::put('/Payment-setting/update-type/{Payment}', [PaymentSettingController::class, 'updatePayment'])->name('updatePayment');


        Route::put('/Payment-information', [PaymentSettingController::class, 'paymentInformation'])->name('paymentInformation');
        Route::post('/create-company-Payment/{paymentId}', [PaymentSettingController::class, 'createCompanyPayment'])->name('createCompanyPayment');
        Route::post('/delete_payment_action/{paymentId}', [PaymentSettingController::class, 'deletePaymentAction'])->name('deletePaymentAction');
        Route::delete('/deleteThisPayment/{Payment}', [PaymentSettingController::class, 'deleteThisPayment'])->name('deleteThisPayment');



    });
});
