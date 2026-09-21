<?php

use App\Http\Controllers\Api\BatchProfitController;
use App\Http\Controllers\Api\ClientRefundController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProviderRefundController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\StorageStockController;
use Illuminate\Support\Facades\Route;

Route::post('/purchases', [PurchaseController::class, 'store']);
Route::post('/provider-refunds', [ProviderRefundController::class, 'store']);
Route::get('/products/available', [ProductController::class, 'available']);
Route::post('/orders', [OrderController::class, 'store']);
Route::post('/client-refunds', [ClientRefundController::class, 'store']);
Route::get('/storage/stock', [StorageStockController::class, 'index']);
Route::get('/batches/profit', [BatchProfitController::class, 'index']);
Route::get('/batches/{batch}/profit', [BatchProfitController::class, 'show']);
