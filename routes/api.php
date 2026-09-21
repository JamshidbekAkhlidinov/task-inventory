<?php

use App\Http\Controllers\Api\BatchProfitController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientRefundController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ProviderRefundController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\StorageController;
use App\Http\Controllers\Api\StorageStockController;
use Illuminate\Support\Facades\Route;

Route::get('/clients', [ClientController::class, 'index']);
Route::post('/clients', [ClientController::class, 'store']);
Route::patch('/clients/{client}', [ClientController::class, 'update']);
Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
Route::get('/clients/{client}/orders', [ClientController::class, 'orders']);

Route::get('/providers', [ProviderController::class, 'index']);
Route::post('/providers', [ProviderController::class, 'store']);
Route::patch('/providers/{provider}', [ProviderController::class, 'update']);
Route::delete('/providers/{provider}', [ProviderController::class, 'destroy']);

Route::get('/storages', [StorageController::class, 'index']);
Route::post('/storages', [StorageController::class, 'store']);
Route::patch('/storages/{storage}', [StorageController::class, 'update']);
Route::delete('/storages/{storage}', [StorageController::class, 'destroy']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store']);
Route::patch('/categories/{category}', [CategoryController::class, 'update']);
Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

Route::get('/products/available', [ProductController::class, 'available']);
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::patch('/products/{product}', [ProductController::class, 'update']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);

Route::post('/purchases', [PurchaseController::class, 'store']);
Route::post('/provider-refunds', [ProviderRefundController::class, 'store']);
Route::post('/orders', [OrderController::class, 'store']);
Route::post('/client-refunds', [ClientRefundController::class, 'store']);
Route::get('/storage/stock', [StorageStockController::class, 'index']);
Route::get('/batches/profit', [BatchProfitController::class, 'index']);
Route::get('/batches/{batch}/profit', [BatchProfitController::class, 'show']);
