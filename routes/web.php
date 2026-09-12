<?php

use App\Http\Controllers\CalculatorController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/', [CalculatorController::class, 'index']);
