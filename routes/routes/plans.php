<?php

use App\Http\Controllers\Finance\PlanController;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => ['token']], function () {

    Route::prefix('plans')->group(function () {

    Route::get('/', [PlanController::class, 'index'])->name('plan.index');
    Route::get('/create', [PlanController::class, 'create'])->name('plan.create');
    Route::post('/store', [PlanController::class, 'store'])->name('plan.store');

    Route::get('/edit-plan/{plan}', [PlanController::class, 'editPlan'])->name('editPlan');
    Route::put('/update-plan/{plan}', [PlanController::class, 'updatePlan'])->name('updatePlan');
    Route::delete('/delete-plan/{plan}', [PlanController::class, 'deletePlan'])->name('deletePlan');


    });

});

