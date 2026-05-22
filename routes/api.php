<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkShiftController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserController::class, 'login']);

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [UserController::class, 'logout']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);

        Route::get('/shifts', [WorkShiftController::class, 'index']);
        Route::post('/shifts', [WorkShiftController::class, 'store']);
        Route::patch('/shifts/{id}/open', [WorkShiftController::class, 'open']);
        Route::patch('/shifts/{id}/close', [WorkShiftController::class, 'close']);
    });

    Route::get('/shifts/{id}/orders', [OrderController::class, 'ordersByShift'])
        ->middleware('role:admin,waiter');

    Route::middleware('role:waiter')->group(function () {
        Route::post('/order', [OrderController::class, 'store']);
        Route::get('/order/{id}', [OrderController::class, 'show']);
        Route::post('/order/{order}/position', [PositionController::class, 'store']);
        Route::delete('/position/{id}', [PositionController::class, 'destroy']);
    });

    Route::patch('/order/{id}/status', [OrderController::class, 'updateStatus'])
        ->middleware('role:waiter,cook');

    Route::middleware('role:cook')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/active', [OrderController::class, 'activeShiftOrders']);
    });

});

