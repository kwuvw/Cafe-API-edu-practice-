<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkShiftController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PositionController;



Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/logout', [UserController::class, 'logout']);

    Route::prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/shifts', [WorkShiftController::class, 'index']);
        Route::post('/shifts', [WorkShiftController::class, 'store']);
        Route::patch('/shifts/{id}/open', [WorkShiftController::class, 'open']);
        Route::patch('/shifts/{id}/close', [WorkShiftController::class, 'close']);
        Route::get('/shifts/{id}/orders', [OrderController::class, 'ordersByShifts']);
    });

    Route::prefix('waiter')->group(function () {
        Route::post('/order', [OrderController::class, 'store']);
        Route::get('/order/{id}', [OrderController::class, 'show']);
        Route::post('/order/{order}/position', [PositionController::class, 'store']);
        Route::delete('/position/{id}', [PositionController::class, 'destroy']);
        Route::get('/shifts/{id}/orders', [OrderController::class, 'ordersByShift']);
        Route::patch('/order/{id}/status', [OrderController::class, 'updateStatus']);
    });

    Route::prefix('cook')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::patch('/order/{id}/status', [OrderController::class, 'updateStatusByCook']);
        Route::get('/orders/active', [OrderController::class, 'activeShiftOrders']);
    });
});
