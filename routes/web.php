<?php

use App\Http\Controllers\Admin\TaxYearController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/', [CalculatorController::class, 'index']);

Route::get('/admin', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/admin/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/admin/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('admin/tax-years', TaxYearController::class)
        ->except('show')
        ->names('admin.tax-years')
        ->parameters(['tax-years' => 'tax_year']);
});

require __DIR__.'/auth.php';
