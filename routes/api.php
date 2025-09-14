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


// wrap these with auth:sanctum or similar middleware.
Route::get('/stock/availability', [StockApiController::class, 'availability']);
Route::post('/stock/reserve', [StockApiController::class, 'reserve']);
Route::post('/stock/release', [StockApiController::class, 'release']);
Route::post('/stock/receive', [StockApiController::class, 'receive']);
Route::post('/stock/upsert', [StockApiController::class, 'upsert']);

Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/products/bulk', [ProductApiController::class, 'bulk']);
Route::get('/products/{id}', [ProductApiController::class, 'show']);

Route::prefix('branches')->group(function () {
    Route::get('/',        [BranchApiController::class, 'index']);
    Route::get('main',     [BranchApiController::class, 'main']);
    Route::get('{id}',     [BranchApiController::class, 'show']);
    Route::post('/',       [BranchApiController::class, 'store']);
    Route::put('{id}',     [BranchApiController::class, 'update']);
    Route::delete('{id}',  [BranchApiController::class, 'destroy']);
});

//Route::get('/branches/main', [BranchApiController::class, 'main']);
//Route::get('/branches', [BranchApiController::class, 'index']);
//Route::get('/branches/{id}', [BranchApiController::class, 'show']);

Route::post('/orders/return', [OrderApiController::class, 'createReturn']);