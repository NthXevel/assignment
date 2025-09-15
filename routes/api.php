<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\BranchApiController;
use App\Http\Controllers\Api\UserApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('users')->group(function () {
    Route::get('/',        [UserApiController::class, 'index']);
    Route::get('{id}',     [UserApiController::class, 'show']);
    Route::post('/',       [UserApiController::class, 'store']);
    Route::put('{id}',     [UserApiController::class, 'update']);
    Route::delete('{id}',  [UserApiController::class, 'destroy']);
});
Route::get('me', [UserApiController::class, 'me']);

// Stocks
Route::get('/stock/availability', [StockApiController::class, 'availability']);
Route::post('/stock/reserve', [StockApiController::class, 'reserve']);
Route::post('/stock/release', [StockApiController::class, 'release']);
Route::post('/stock/receive', [StockApiController::class, 'receive']);
Route::post('/stock/upsert', [StockApiController::class, 'upsert']);

// list, movements, and aggregated value
Route::get('/stock/list',       [StockApiController::class, 'list']);       
Route::get('/stock/movements',  [StockApiController::class, 'movements']);  
Route::get('/stock/value',      [StockApiController::class, 'value']);

// Products
Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/products/bulk', [ProductApiController::class, 'bulk']);
Route::get('/products/{id}', [ProductApiController::class, 'show']);

// Branches
Route::prefix('branches')->group(function () {
    Route::get('/',        [BranchApiController::class, 'index']);
    Route::get('main',     [BranchApiController::class, 'main']);
    Route::get('{id}',     [BranchApiController::class, 'show']);
    Route::post('/',       [BranchApiController::class, 'store']);
    Route::put('{id}',     [BranchApiController::class, 'update']);
    Route::delete('{id}',  [BranchApiController::class, 'destroy']);
});

// Orders
Route::prefix('orders')->group(function () {
    // READ endpoints (more lenient)
    Route::middleware('throttle:orders-read')->group(function () {
        Route::get('/',    [OrderApiController::class, 'index']); // list
        Route::get('{id}', [OrderApiController::class, 'show']);  // read
    });

    // WRITE endpoints (stricter)
    Route::middleware('throttle:orders-write')->group(function () {
        Route::post('/',           [OrderApiController::class, 'store']);       // create
        Route::post('return',      [OrderApiController::class, 'createReturn']); // special flow

        // transitions
        Route::post('{id}/approve', [OrderApiController::class, 'approve']);
        Route::post('{id}/ship',    [OrderApiController::class, 'ship']);
        Route::post('{id}/receive', [OrderApiController::class, 'receive']);
        Route::post('{id}/cancel',  [OrderApiController::class, 'cancel']);
    });
});