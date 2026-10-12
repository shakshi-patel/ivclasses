<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvailableSlotsService;
use Illuminate\Http\Request;

class AvailableSlotsController extends Controller
{
    public function index(
        Request $request,
        AvailableSlotsService $availableSlotsService
    ) {
        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        try {

            $slots = $availableSlotsService->getAvailableSlots(
                $validated['date']
            );

            return response()->json([
                'success' => true,
                'date' => $validated['date'],
                'slots' => $slots,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}