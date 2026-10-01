<?php

use App\Http\Controllers\KasirController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('kasir.index');
});

Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
Route::get('/kasir/produk', [KasirController::class, 'produk'])->name('kasir.produk');
Route::post('/kasir/transaksi', [KasirController::class, 'store'])->name('kasir.store');
Route::get('/kasir/struk/{transaction}', [KasirController::class, 'struk'])->name('kasir.struk');
