<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\WorkShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddRequest;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function ordersByShifts($id)
    {
        $shift = WorkShift::find($id);
        if (!$shift) {
            return response()->json(['message' => 'Смена не найдена'], 404);
        }

        $workerIds = DB::table('shift_workers')
            ->where('work_shift_id', $id)
            ->pluck('id');

        $orders = Order::whereIn('shift_worker_id', $workerIds)->get();

        $totalCount = $orders->count();

        return response()->json([
            'data' => [
                'id' => $id,
                'orders_count' => $totalCount,
                'orders' => $orders
            ]
        ], 200);
    }


    public function store(AddRequest $request)
    {

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $activeShiftWorker = $user->shiftWorkers()
            ->whereHas('workShift', function ($q) {
                $q->where('active', 1);
            })
            ->first();

        if (!$activeShiftWorker) {
            return response()->json(['error' => 'У вас нет активной рабочей смены'], 403);
        }

        $order = Order::create([
            'table_id' => $request->table_id,
            'number_of_person' => $request->number_of_person,
            'status_order_id' => 1, // Статус "Принят"
            'shift_worker_id' => $activeShiftWorker->id,
        ]);

        return new OrderResource($order);
    }

    public function show($id)
    {
        $order = Order::with([
            'table',
            'status_order',
            'shift_worker.user'
        ])->find($id);

        if (!$order) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Заказ не найден'
                ]
            ], 404);
        }

        return response()->json([
            'data' => $order
        ]);
    }


    public function ordersByShift($id)
    {
        $shift = WorkShift::find($id);
        if (!$shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Смена не найдена'
                ]
            ], 404);
        }

        $orders = Order::whereHas('shift_worker', function ($query) use ($id) {
            $query->where('work_shift_id', $id);
        })
            ->with(['table', 'status_order', 'shift_worker.user'])
            ->get();

        return response()->json([
            'data' => [
                'id' => $shift->id,
                'start' => $shift->start,
                'end' => $shift->end,
                'orders' => $orders,
                'total_amount' => $orders->count()
            ]
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|exists:status_orders,id'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Заказ не найден'
                ]
            ], 404);
        }

        $order->status_order_id = $request->status_id;
        $order->save();

        return response()->json([
            'data' => [
                'id' => $order->id,
                'status' => $order->status_order_id,
                'message' => 'Статус заказа успешно обновлен'
            ]
        ]);
    }

    public function addToOrder(Request $request, $id)
    {
        $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'count' => 'required|integer|min:1'
        ]);

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        if (in_array($order->status_order_id, [4, 5])) {
            return response()->json(['message' => 'Нельзя изменять закрытый заказ'], 403);
        }

        DB::table('order_menus')->insert([
            'order_id' => $id,
            'menu_id' => $request->menu_id,
            'count' => $request->count,
        ]);

        return response()->json([
            'data' => [
                'message' => 'Блюдо добавлено в заказ'
            ]
        ], 201);
    }


    public function removeFromOrder($orderId, $menuId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }


        if (in_array($order->status_order_id, [4, 5])) {
            return response()->json(['message' => 'Нельзя менять состав закрытого заказа'], 403);
        }

        $deleted = DB::table('order_menus')
            ->where('order_id', $orderId)
            ->where('menu_id', $menuId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Эта позиция не найдена в заказе'], 404);
        }

        return response()->json([
            'data' => [
                'message' => 'Позиция успешно удалена из заказа'
            ]
        ]);
    }

    public function updateStatusByCook(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|exists:status_orders,id'
        ]);

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        if ($order->status_order_id >= 4) {
            return response()->json(['message' => 'Заказ уже завершен или оплачен'], 403);
        }

        $order->status_order_id = $request->status_id;
        $order->save();

        return response()->json([
            'data' => [
                'id' => $order->id,
                'status' => $order->status_order_id,
                'message' => 'Статус заказа обновлен поваром'
            ]
        ]);
    }

    public function activeShiftOrders()
    {
        $activeShift = DB::table('work_shifts')->where('active', 1)->first();

        if (!$activeShift) {
            return response()->json(['message' => 'Нет активных смен'], 404);
        }

        $orders = Order::whereHas('shift_worker', function ($query) use ($activeShift) {
            $query->where('work_shift_id', $activeShift->id);
        })
            ->with(['table', 'status_order', 'items'])
            ->get();

        return response()->json(['data' => $orders]);
    }
}
