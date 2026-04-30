<?php

namespace App\Http\Controllers;

use App\Models\WorkShift;
use App\Http\Requests\WorkShiftRequest;
use App\Http\Resources\WorkShiftResource;
use Illuminate\Http\Request;

class WorkShiftController extends Controller
{
    public function index()
    {
        $shifts = WorkShift::all();
        return WorkShiftResource::collection($shifts);
    }

    public function store(WorkShiftRequest $request)
    {
        $shift = WorkShift::create([
            'start'  => $request->start,
            'end'    => $request->end,
            'active' => 0,
        ]);

        return new WorkShiftResource($shift);
    }

    public function open($id)
    {
        $activeShift = WorkShift::where('active', 1)->first();
        if ($activeShift) {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Есть уже открытая смена'
                ]
            ], 403);
        }

        $shift = WorkShift::find($id);
        if (!$shift) {
            return response()->json(['message' => 'Смена не найдена'], 404);
        }

        $shift->update(['active' => 1]);

        return response()->json([
            'data' => [
                'id' => $shift->id,
                'status' => 'open'
            ]
        ]);
    }

    public function close($id)
    {
        $shift = WorkShift::find($id);
        if (!$shift) {
            return response()->json(['message' => 'Смена не найдена'], 404);
        }

        $shift->update(['active' => 0]);

        return response()->json([
            'data' => [
                'id' => $shift->id,
                'status' => 'closed'
            ]
        ]);
    }
}
