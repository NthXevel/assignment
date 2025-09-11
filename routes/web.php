<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\RecordsController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Protected routes (all require auth)
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // -----------------------------
    // Product Management
    // -----------------------------
    Route::resource('products', ProductController::class);
    Route::get('/products/category/{category}', [ProductController::class, 'byCategory'])
        ->name('products.by-category');

    // Category management
    Route::post('/products/store-category', [ProductController::class, 'storeCategory'])
        ->name('products.store-category');

    // -----------------------------
    // Stock Management
    // -----------------------------
    // Accessible to all logged-in users
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stocks/{stock}', [StockController::class, 'show'])->name('stocks.show');
    Route::get('/stocks/reports/low-stock', [StockController::class, 'lowStock'])->name('stocks.low-stock');

    // Admin-only stock actions
    Route::middleware('permission:manage_stock')->group(function () {
        Route::get('/stocks/create', [StockController::class, 'create'])->name('stocks.create');
        Route::post('/stocks', [StockController::class, 'store'])->name('stocks.store');
        Route::post('/stocks/{stock}/adjust', [StockController::class, 'adjust'])->name('stocks.adjust');
        
        // Simple quantity management (no edit route)
        Route::post('/stocks/{stock}/update-quantity', [StockController::class, 'updateQuantity'])->name('stocks.update-quantity');
        Route::post('/stocks/{stock}/adjust-quantity', [StockController::class, 'adjustQuantity'])->name('stocks.adjust-quantity');
    });

    // -----------------------------
    // Order Management
    // -----------------------------
    Route::resource('orders', OrderController::class);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
    Route::post('/orders/{order}/receive', [OrderController::class, 'receive'])->name('orders.receive');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/branches-with-stock', [OrderController::class, 'getBranchesWithStock'])
        ->name('orders.branches-with-stock');

    // -----------------------------
    // User Management
    // -----------------------------
    Route::resource('users', UserController::class);

    // -----------------------------
    // Branch Management
    // -----------------------------
    Route::middleware(['auth'])->group(function () {
        // Admin-only: manage branches (place these first)
        Route::middleware(['permission:manage_branches'])->group(function () {
            Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
            Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
            Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
            Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
            Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
        });

        // Public: view branches (place these after)
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
    });

    // -----------------------------
    // Records
    // -----------------------------
    Route::prefix('records')->name('records.')->group(function () {
        Route::get('/', [App\Http\Controllers\RecordsController::class, 'index'])->name('index');
        Route::get('/stock', [App\Http\Controllers\RecordsController::class, 'stock'])->name('stock');
        Route::get('/orders', [App\Http\Controllers\RecordsController::class, 'orders'])->name('orders');
    });

    // -----------------------------
    // Settings
    // -----------------------------
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        // Actions handled from the same page
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
    });
});

// -----------------------------
// Authentication
// -----------------------------
Auth::routes();
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');