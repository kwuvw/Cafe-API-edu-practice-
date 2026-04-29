<?php

namespace App\Http\Controllers;

use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class WorkShiftController extends Controller
{
    public function index()
    {
        $shifts = WorkShift::all();

        return response()->json([
            'data' => $shifts
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start' => 'required|date_format:Y-m-d H:i:s',
            'end'   => 'required|date_format:Y-m-d H:i:s|after:start',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 422,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $shift = WorkShift::create([
            'start' => $request->start,
            'end'   => $request->end,
            'active' => 0,
        ]);

        return response()->json([
            'id' => $shift->id,
            'start' => $shift->start,
            'end' => $shift->end,
            'active' => $shift->active
        ], 201);
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

        $shift->active = 1;
        $shift->save();

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

        $shift->active = 0;
        $shift->save();

        return response()->json([
            'data' => [
                'id' => $shift->id,
                'status' => 'closed'
            ]
        ]);
    }
}
