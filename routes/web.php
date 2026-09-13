<?php

use App\Http\Controllers\Admin\FixedPeriodController;
use App\Http\Controllers\Admin\LenderController;
use App\Http\Controllers\Admin\RateImportController;
use App\Http\Controllers\Admin\RateSetController;
use App\Http\Controllers\Admin\RiskClassController;
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

    Route::resource('admin/lenders', LenderController::class)
        ->except('show')
        ->names('admin.lenders')
        ->parameters(['lenders' => 'lender']);

    Route::resource('admin/lenders.rate-sets', RateSetController::class)
        ->only(['index', 'create', 'store'])
        ->names('admin.lenders.rate-sets')
        ->parameters(['lenders' => 'lender']);

    Route::resource('admin/risk-classes', RiskClassController::class)
        ->except('show')
        ->names('admin.risk-classes')
        ->parameters(['risk-classes' => 'risk_class']);

    Route::resource('admin/fixed-periods', FixedPeriodController::class)
        ->except('show')
        ->names('admin.fixed-periods')
        ->parameters(['fixed-periods' => 'fixed_period']);

    Route::get('admin/rate-imports/create', [RateImportController::class, 'create'])->name('admin.rate-imports.create');
    Route::post('admin/rate-imports', [RateImportController::class, 'store'])->name('admin.rate-imports.store');
    Route::post('admin/rate-imports/confirm', [RateImportController::class, 'confirm'])->name('admin.rate-imports.confirm');
});

require __DIR__.'/auth.php';
