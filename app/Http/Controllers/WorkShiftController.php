<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkShiftRequest;
use App\Http\Resources\WorkShiftResource;
use App\Models\ShiftWorker;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkShiftController extends Controller
{
    public function index()
    {
        $shifts = WorkShift::query()
            ->orderByDesc('start')
            ->get();

        return WorkShiftResource::collection($shifts);
    }

    public function store(WorkShiftRequest $request)
    {
        $shift = DB::transaction(function () {
            if ($this->hasActiveShift()) {
                return null;
            }

            return WorkShift::query()->create([
                'start' => now(),
                'end' => null,
                'active' => true,
            ]);
        });

        if (! $shift instanceof WorkShift) {
            return $this->activeShiftConflictResponse();
        }

        return new WorkShiftResource($shift);
    }

    public function open($id)
    {
        return DB::transaction(function () use ($id) {
            $shift = WorkShift::query()
                ->lockForUpdate()
                ->find($id);

            if (! $shift) {
                return $this->shiftNotFoundResponse();
            }

            $activeShift = WorkShift::query()
                ->lockForUpdate()
                ->where('active', true)
                ->first();

            if ($activeShift && $activeShift->id !== $shift->id) {
                return $this->activeShiftConflictResponse();
            }

            if (! $shift->active) {
                $shift->forceFill([
                    'end' => null,
                    'active' => true,
                ])->save();
            }

            return $this->shiftStateResponse($shift->fresh(), 'open');
        });
    }

    public function close($id)
    {
        return DB::transaction(function () use ($id) {
            $shift = WorkShift::query()
                ->lockForUpdate()
                ->find($id);

            if (! $shift) {
                return $this->shiftNotFoundResponse();
            }

            if ($shift->active || $shift->end === null) {
                $shift->forceFill([
                    'end' => now(),
                    'active' => false,
                ])->save();
            }

            return $this->shiftStateResponse($shift->fresh(), 'closed');
        });
    }

    public function addUser(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $shift = WorkShift::query()
                ->lockForUpdate()
                ->find($id);

            if (! $shift) {
                return $this->shiftNotFoundResponse();
            }

            $alreadyAssigned = ShiftWorker::query()
                ->lockForUpdate()
                ->where('work_shift_id', $shift->id)
                ->where('user_id', $validated['user_id'])
                ->exists();

            if ($alreadyAssigned) {
                return response()->json([
                    'message' => 'Сотрудник уже добавлен на эту смену',
                ], 422);
            }

            $shift->shiftWorkers()->create([
                'user_id' => $validated['user_id'],
            ]);

            User::query()
                ->whereKey($validated['user_id'])
                ->where('status', User::STATUS_FIRED)
                ->update(['status' => User::STATUS_WORKING]);

            return response()->json([
                'message' => 'Сотрудник успешно добавлен на смену',
            ]);
        });
    }

    private function hasActiveShift(): bool
    {
        return WorkShift::query()
            ->lockForUpdate()
            ->where('active', true)
            ->exists();
    }

    private function activeShiftConflictResponse(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 403,
                'message' => 'An active work shift already exists.',
            ],
        ], 403);
    }

    private function shiftNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Work shift not found.',
        ], 404);
    }

    private function shiftStateResponse(WorkShift $shift, string $status): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $shift->id,
                'start' => $shift->start?->format('Y-m-d H:i:s'),
                'end' => $shift->end?->format('Y-m-d H:i:s'),
                'active' => (bool) $shift->active,
                'status' => $status,
            ],
        ]);
    }
}
