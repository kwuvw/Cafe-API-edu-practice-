<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderMenu;
use App\Models\StatusOrder;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function ordersByShifts($id)
    {
        return $this->ordersByShift($id);
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

        if (! $activeShiftWorker) {
            return response()->json(['error' => 'У вас нет активной рабочей смены'], 403);
        }

        $order = Order::create([
            'table_id' => $request->table_id,
            'number_of_person' => $request->number_of_person,
            'status_order_id' => 1,
            'shift_worker_id' => $activeShiftWorker->id,
        ]);

        return new OrderResource($order);
    }

    public function show($id)
    {
        $order = Order::with([
            'table',
            'status_order',
            'shift_worker.user',
            'orderMenus.menu',
        ])->find($id);

        if (! $order) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Заказ не найден',
                ],
            ], 404);
        }

        return response()->json([
            'data' => $this->formatOrderDetails($order),
        ]);
    }

    public function ordersByShift($id)
    {
        $shift = WorkShift::find($id);

        if (! $shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Смена не найдена',
                ],
            ], 404);
        }

        $orders = Order::query()
            ->whereHas('shift_worker', function ($query) use ($id) {
                $query->where('work_shift_id', $id);
            })
            ->with(['table', 'status_order', 'shift_worker.user', 'items'])
            ->get();

        $data = $orders->map(function (Order $order) {
            $totalPrice = $order->items->sum(function ($item) {
                return ((float) $item->price) * ((int) $item->pivot->count);
            });

            return [
                'id' => $order->id,
                'table_number' => $order->table?->number ?? $order->table?->name ?? $order->table_id,
                'waiter_name' => $order->shift_worker?->user?->display_name,
                'status' => $order->status_order?->name ?? $order->status_order_id,
                'total_price' => round((float) $totalPrice, 2),
            ];
        })->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status_id' => 'nullable|integer|exists:status_orders,id',
            'send_to_kitchen' => 'sometimes|boolean',
        ]);

        $order = Order::with('status_order')->find($id);

        if (! $order) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Заказ не найден',
                ],
            ], 404);
        }

        $statusId = $validated['status_id'] ?? null;

        if (! $statusId && $request->boolean('send_to_kitchen')) {
            $statusId = StatusOrder::PREPARING;
        }

        if (! $statusId) {
            return response()->json([
                'message' => 'The status id field is required.',
                'errors' => [
                    'status_id' => ['The status id field is required.'],
                ],
            ], 422);
        }

        $order->status_order_id = $statusId;
        $order->save();
        $order->load('status_order');

        return response()->json([
            'data' => [
                'id' => $order->id,
                'status_id' => $order->status_order_id,
                'status' => $order->status_order?->name,
                'message' => 'Статус заказа успешно обновлен',
            ],
        ]);
    }

    private function formatOrderDetails(Order $order): array
    {
        return [
            'id' => $order->id,
            'table_id' => $order->table_id,
            'table' => $order->table,
            'table_number' => $order->table?->number ?? $order->table?->name ?? $order->table_id,
            'number_of_person' => $order->number_of_person,
            'number_of_persons' => $order->number_of_person,
            'status_order_id' => $order->status_order_id,
            'status' => $order->status_order?->name,
            'created_at' => $order->created_at,
            'positions' => $order->orderMenus->map(function (OrderMenu $position) {
                return [
                    'id' => $position->id,
                    'position_id' => $position->id,
                    'menu_id' => $position->menu_id,
                    'name' => $position->menu?->name,
                    'item' => $position->menu?->name,
                    'count' => $position->count,
                ];
            })->values(),
        ];
    }

    public function addToOrder(Request $request, $id)
    {
        $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'count' => 'required|integer|min:1',
        ]);

        $order = Order::find($id);
        if (! $order) {
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
                'message' => 'Блюдо добавлено в заказ',
            ],
        ], 201);
    }

    public function updateStatusByCook(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|exists:status_orders,id',
        ]);

        $order = Order::find($id);
        if (! $order) {
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
                'message' => 'Статус заказа обновлен поваром',
            ],
        ]);
    }

    public function index()
    {
        return $this->kitchenOrders();
    }

    public function activeShiftOrders()
    {
        return $this->kitchenOrders();
    }

    public function kitchenOrders()
    {
        $activeShift = WorkShift::query()
            ->where('active', true)
            ->first();

        if (! $activeShift) {
            return response()->json([
                'message' => 'Нет активных смен',
            ], 404);
        }

        $orders = Order::query()
            ->where('status_order_id', StatusOrder::PREPARING)
            ->whereHas('shift_worker', function ($query) use ($activeShift) {
                $query->where('work_shift_id', $activeShift->id);
            })
            ->with(['table', 'status_order', 'orderMenus.menu'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => [
                'shift_id' => $activeShift->id,
                'orders' => $orders
                    ->map(fn (Order $order) => $this->formatKitchenOrder($order))
                    ->values(),
            ],
        ]);
    }

    private function formatKitchenOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status_order?->name,
            'status_id' => $order->status_order_id,
            'status_code' => $order->status_order?->code,
            'table_id' => $order->table_id,
            'table_number' => $order->table?->number ?? $order->table?->name ?? $order->table_id,
            'table' => $order->table,
            'created_at' => $order->created_at,
            'positions' => $order->orderMenus->map(function (OrderMenu $position) {
                return [
                    'id' => $position->id,
                    'menu_id' => $position->menu_id,
                    'name' => $position->menu?->name,
                    'item' => $position->menu?->name,
                    'count' => $position->count,
                ];
            })->values(),
        ];
    }
}
