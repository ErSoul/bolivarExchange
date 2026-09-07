<?php

use App\Http\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CurrencyController::class, 'index'])->name('currencies.index');
Route::get('/api/currencies', [CurrencyController::class, 'apiIndex'])->name('currencies.api');
