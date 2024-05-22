<?php

use App\Http\Controllers\Finance\SubscriptionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;


Route::group(['middleware' => ['token']], function () {

    Route::prefix('{company}/company')->group(function () {

        Route::get('/', [CompanyController::class, 'index'])->name('company.index');
        Route::post('/store', [CompanyController::class, 'store'])->name('company.store');

        Route::get('/showAvailableCompanies' , [CompanyController::class , 'showAvailableCompanies'])->name('showAvailableCompanies');
        Route::get('/showAvailableCompanies/CompaniesPaid' ,[SubscriptionController::class , 'CompaniesPaid'])->name('CompaniesPaid');
        Route::get('/showAvailableCompanies/company-owner', [CompanyController::class, 'companyOwner'])->name('companyOwner');
        Route::post('/showAvailableCompanies/companyOwnerRegistration', [CompanyController::class, 'companyOwnerRegistration'])->name('companyOwnerRegistration');
        Route::get('/showAvailableCompanies/create', [CompanyController::class, 'create'])->name('company.create');
    });

});


