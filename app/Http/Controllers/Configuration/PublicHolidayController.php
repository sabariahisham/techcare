<?php

declare(strict_types=1);

namespace App\Http\Controllers\Configuration;

use App\Http\Requests\StorePublicHolidayRequest;
use App\Http\Requests\UpdatePublicHolidayRequest;
use App\Models\PublicHoliday;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicHolidayController
{
    public function index(): View
    {
        $currentYear = now()->year;
        $publicHolidays = PublicHoliday::orderBy('holiday_date', 'asc')->get();
        $totalPublicHolidays = PublicHoliday::whereYear('holiday_date', $currentYear)->count();

        // Count national holidays. National is stored as ["Nasional"]; legacy
        // rows may still use NULL, so match both.
        $nationalHolidays = PublicHoliday::whereYear('holiday_date', $currentYear)
            ->where(function ($query) {
                $query->whereNull('states')
                    ->orWhereJsonContains('states', 'Nasional');
            })
            ->count();

        return view('public-holidays.index', [
            'publicHolidays' => $publicHolidays,
            'totalPublicHolidays' => $totalPublicHolidays,
            'nationalHolidays' => $nationalHolidays,
        ]);
    }

    public function store(StorePublicHolidayRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Handle states: if national is selected, store as ["Nasional"]
            $states = isset($validated['states']) && ! empty($validated['states']) ? $validated['states'] : null;

            // If national is in states array, store the Nasional marker only
            if (is_array($states) && in_array('national', $states)) {
                $states = ['Nasional'];
            }

            $publicHoliday = PublicHoliday::create([
                'holiday_date' => $validated['holiday_date'],
                'holiday_name' => $validated['holiday_name'],
                'states' => $states,
                'states_except' => $validated['states_except'] ?? null,
                'day' => date('l', strtotime($validated['holiday_date'])),
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cuti umum berhasil disimpan',
                'data' => $publicHoliday,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit(PublicHoliday $publicHoliday): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'holiday' => $publicHoliday,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cuti umum tidak ditemui',
            ], 404);
        }
    }

    public function update(UpdatePublicHolidayRequest $request, PublicHoliday $publicHoliday): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Handle states: if national is selected, store as ["Nasional"]
            $states = isset($validated['states']) && ! empty($validated['states']) ? $validated['states'] : null;

            // If national is in states array, store the Nasional marker only
            if (is_array($states) && in_array('national', $states)) {
                $states = ['Nasional'];
            }

            $publicHoliday->update([
                'holiday_date' => $validated['holiday_date'],
                'holiday_name' => $validated['holiday_name'],
                'states' => $states,
                'states_except' => $validated['states_except'] ?? null,
                'day' => date('l', strtotime($validated['holiday_date'])),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cuti umum berhasil dikemaskini',
                'data' => $publicHoliday,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(PublicHoliday $publicHoliday): JsonResponse
    {
        try {
            $publicHoliday->update([
                'deleted_by' => auth()->id(),
            ]);

            $publicHoliday->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cuti umum berhasil dipadam',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
