<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\BranchApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// wrap these with auth:sanctum or similar middleware.
Route::get('/stock/availability', [StockApiController::class, 'availability']);
Route::post('/stock/reserve', [StockApiController::class, 'reserve']);
Route::post('/stock/release', [StockApiController::class, 'release']);
Route::post('/stock/receive', [StockApiController::class, 'receive']);
Route::post('/stock/upsert', [StockApiController::class, 'upsert']);

Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/products/bulk', [ProductApiController::class, 'bulk']);
Route::get('/products/{id}', [ProductApiController::class, 'show']);

Route::get('/branches', [BranchApiController::class, 'index']);
Route::get('/branches/{id}', [BranchApiController::class, 'show']);
