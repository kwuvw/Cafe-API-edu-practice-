<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkShiftController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PositionController;

// Публичный роут для входа (Убедись, что в UserController метод называется store или login, как у тебя)
Route::post('/login', [UserController::class, 'login']);

// Все защищенные маршруты (требуют Bearer Token)
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/logout', [UserController::class, 'logout']);

    // --- ГРУППА ТОЛЬКО ДЛЯ АДМИНИСТРАТОРА ---
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);      // Просмотр сотрудников
        Route::post('/users', [UserController::class, 'store']);     // Добавление сотрудника
        Route::get('/shifts', [WorkShiftController::class, 'index']); // Просмотр всех смен
        Route::post('/shifts', [WorkShiftController::class, 'store']); // Создание смены
        Route::patch('/shifts/{id}/open', [WorkShiftController::class, 'open']);   // Открытие смены
        Route::patch('/shifts/{id}/close', [WorkShiftController::class, 'close']); // Закрытие смены
    });

    // --- СОВМЕСТНЫЙ ДОСТУП: АДМИН И ОФИЦИАНТ ---
    // Просмотр заказов конкретной смены (Пункт 2.1 задания)
    Route::get('/shifts/{id}/orders', [OrderController::class, 'ordersByShift'])
        ->middleware('role:admin,waiter');

    // --- ГРУППА ТОЛЬКО ДЛЯ ОФИЦИАНТА ---
    Route::middleware('role:waiter')->group(function () {
        Route::post('/order', [OrderController::class, 'store']);                       // Создание заказа
        Route::get('/order/{id}', [OrderController::class, 'show']);                    // Просмотр деталей заказа
        Route::post('/order/{order}/position', [PositionController::class, 'store']);   // Добавление блюда в заказ
        Route::delete('/position/{id}', [PositionController::class, 'destroy']);        // Удаление блюда из заказа
    });

    // --- СОВМЕСТНЫЙ ДОСТУП: ОФИЦИАНТ И ПОВАР ---
    // Изменение статуса заказа (Пункт 2.2 задания)
    Route::patch('/order/{id}/status', [OrderController::class, 'updateStatus'])
        ->middleware('role:waiter,cook');

    // --- ГРУППА ТОЛЬКО ДЛЯ ПОВАРА ---
    Route::middleware('role:cook')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);               // Просмотр всех заказов для кухни
        Route::get('/orders/active', [OrderController::class, 'activeShiftOrders']); // Заказы активной смены
    });
});
