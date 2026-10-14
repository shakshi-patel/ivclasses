<?php

namespace App\Http\Controllers\Admin;

use App\Models\DemoAvailability;
use Illuminate\Http\Request;

class DemoAvailabilityController extends AdminController
{
    /**
     * Display demo availability list.
     */
    public function index()
    {
        $availabilities = DemoAvailability::orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('admin.demo-availability.index', compact('availabilities'));
    }

    /**
     * Store a new demo availability.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => [
                'required',
                'integer',
                'between:0,6',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],

            'slot_duration_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:240',
            ],

            'buffer_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:120',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check overlapping availability
        |--------------------------------------------------------------------------
        */

        $overlapExists = DemoAvailability::where(
            'day_of_week',
            $validated['day_of_week']
        )
            ->where('is_active', true)
            ->where(function ($query) use ($validated) {
                $query
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($overlapExists) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_time' => 'This time overlaps with an existing availability for the selected day.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create availability
        |--------------------------------------------------------------------------
        */

        DemoAvailability::create([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'slot_duration_minutes' => $validated['slot_duration_minutes'],
            'buffer_minutes' => $validated['buffer_minutes'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.demo-availability.index')
            ->with('success', 'Demo availability added successfully.');
    }

    /**
     * Update an existing demo availability.
     */
    public function update(Request $request, DemoAvailability $demoAvailability)
    {
        $validated = $request->validate([
            'day_of_week' => [
                'required',
                'integer',
                'between:0,6',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],

            'slot_duration_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:240',
            ],

            'buffer_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:120',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check overlapping availability
        |--------------------------------------------------------------------------
        |
        | Exclude the current record from the overlap check.
        |
        */

        $overlapExists = DemoAvailability::where(
            'day_of_week',
            $validated['day_of_week']
        )
            ->where('id', '!=', $demoAvailability->id)
            ->where('is_active', true)
            ->where(function ($query) use ($validated) {
                $query
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($overlapExists) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_time' => 'This time overlaps with an existing availability for the selected day.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Update availability
        |--------------------------------------------------------------------------
        */

        $demoAvailability->update([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'slot_duration_minutes' => $validated['slot_duration_minutes'],
            'buffer_minutes' => $validated['buffer_minutes'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.demo-availability.index')
            ->with('success', 'Demo availability updated successfully.');
    }

    /**
     * Delete an existing demo availability.
     */
    public function destroy(DemoAvailability $demoAvailability)
    {
        $demoAvailability->delete();

        return redirect()
            ->route('admin.demo-availability.index')
            ->with('success', 'Demo availability deleted successfully.');
    }
}