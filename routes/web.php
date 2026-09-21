<?php

use App\Http\Controllers\Web\BatchProfitController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\ClientRefundController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ProviderController;
use App\Http\Controllers\Web\ProviderRefundController;
use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\StorageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
Route::get('/providers', [ProviderController::class, 'index'])->name('providers.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/storages', [StorageController::class, 'index'])->name('storages.index');

Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
Route::get('/provider-refunds/create', [ProviderRefundController::class, 'create'])->name('provider-refunds.create');
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::get('/client-refunds/create', [ClientRefundController::class, 'create'])->name('client-refunds.create');
Route::get('/batches/profit', [BatchProfitController::class, 'index'])->name('batches.profit');
