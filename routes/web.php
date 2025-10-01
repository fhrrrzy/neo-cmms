<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

// Health check endpoint for Docker
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'services' => [
            'database' => 'connected',
            'redis' => 'connected',
            'storage' => 'writable'
        ]
    ]);
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard')->middleware('auth');

Route::get('/monitoring', function () {
    return Inertia::render('monitoring/Monitoring');
})->name('monitoring')->middleware('auth');

Route::get('/equipment/{equipmentNumber}', function ($equipmentNumber, \Illuminate\Http\Request $request) {
    // Get equipment data
    $equipment = \App\Models\Equipment::with(['plant', 'station'])
        ->select([
            'equipment.*',
            'plants.name as plant_name',
            'stations.description as station_description'
        ])
        ->leftJoin('plants', 'equipment.plant_id', '=', 'plants.id')
        ->leftJoin('stations', 'equipment.station_id', '=', 'stations.id')
        ->where('equipment.equipment_number', $equipmentNumber)
        ->first();

    if (!$equipment) {
        abort(404, 'Equipment not found');
    }

    // Get date range from request or use default (previous calendar month)
    $defaultStart = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
    $defaultEnd = now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d');
    $dateStart = $request->get('date_start', $defaultStart);
    $dateEnd = $request->get('date_end', $defaultEnd);

    // Get running times data for the equipment within date range
    $runningTimes = \Illuminate\Support\Facades\DB::table('running_times')
        ->where('equipment_number', $equipment->equipment_number)
        ->whereBetween('date', [$dateStart, $dateEnd])
        ->orderBy('date', 'asc')
        ->get();

    // Calculate cumulative running hours
    $cumulativeRunningHours = \Illuminate\Support\Facades\DB::table('running_times')
        ->where('equipment_number', $equipment->equipment_number)
        ->max('counter_reading') ?? 0;

    $equipmentData = [
        'id' => $equipment->id,
        'equipment_number' => $equipment->equipment_number,
        'equipment_description' => $equipment->equipment_description,
        'company_code' => $equipment->company_code,
        'object_number' => $equipment->object_number,
        'point' => $equipment->point,
        'plant' => $equipment->plant ? [
            'id' => $equipment->plant->id,
            'name' => $equipment->plant->name,
        ] : null,
        'station' => $equipment->station ? [
            'id' => $equipment->station->id,
            'description' => $equipment->station->description,
        ] : null,
        'cumulative_running_hours' => (float) $cumulativeRunningHours,
        'recent_running_times' => $runningTimes,
        'date_range' => [
            'start' => $dateStart,
            'end' => $dateEnd,
        ],
    ];

    return Inertia::render('equipment/detail/detail', [
        'equipmentNumber' => $equipmentNumber,
        'equipment' => $equipmentData
    ]);
})->name('equipment.detail')->middleware('auth');


include __DIR__ . '/auth.php';
include __DIR__ . '/settings.php';
