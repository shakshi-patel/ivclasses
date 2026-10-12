<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AvailableSlotsController;

Route::get(
    '/demo/available-slots',
    [AvailableSlotsController::class, 'index']
)->name('api.demo.available-slots');