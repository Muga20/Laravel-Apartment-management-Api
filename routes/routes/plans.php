<?php

use App\Http\Controllers\Finance\PlanController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['token']], function () {
    Route::prefix('plans')->group(function () {
        Route::get('/', [PlanController::class, 'index']);
        Route::post('/create_plan', [PlanController::class, 'CreatePlan']);

        Route::get('/edit-plan/{plan}', [PlanController::class, 'editPlan'])->name('editPlan');
        Route::put('/update-plan/{plan}', [PlanController::class, 'updatePlan'])->name('updatePlan');
        Route::delete('/delete-plan/{plan}', [PlanController::class, 'deletePlan'])->name('deletePlan');

        // Add the toggle route
        Route::put('/toggle-plan-status/{plan}', [PlanController::class, 'togglePlanStatus']);
    });
});
