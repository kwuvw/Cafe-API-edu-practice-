<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderMenu;
use App\Http\Requests\AddPositionRequest;
use App\Http\Requests\RemovePositionRequest;
use App\Http\Resources\PositionResource;

class PositionController extends Controller
{
    public function store(Order $order, AddPositionRequest $request)
    {
        $position = OrderMenu::create([
            'order_id' => $order->id,
            'menu_id'  => $request->menu_id,
            'count'    => $request->count,
        ]);

        return new PositionResource($position);
    }

    public function destroy($id)
    {
        $position = OrderMenu::find($id);

        if (!$position) {
            return response()->json(['message' => 'Позиция не найдена'], 404);
        }

        $position->delete();

        return response()->json(['message' => 'Позиция удалена'], 200);
    }
}
