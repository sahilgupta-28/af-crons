<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Agentfire\TimedoctorController;

Route::get('/', [Timedoctor::class, 'index']);
Route::match(['get', 'post'], '/', [TimedoctorController::class, 'handleTimedoctor'])->name('timedoctor.handle');
