<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Product Management
    Route::resource('products', ProductController::class);
    Route::get('/products/category/{category}', [ProductController::class, 'byCategory'])->name('products.by-category');

    // Stock Management
    Route::resource('stocks', StockController::class);
    Route::post('/stocks/{stock}/adjust', [StockController::class, 'adjust'])->name('stocks.adjust');
    Route::get('/stocks/reports/low-stock', [StockController::class, 'lowStock'])->name('stocks.low-stock');

    // Order Management
    Route::resource('orders', OrderController::class);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
    Route::post('/orders/{order}/receive', [OrderController::class, 'receive'])->name('orders.receive');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // User Management
    Route::resource('users', UserController::class);
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // Branch Management (Admin only)
    Route::middleware(['permission:manage_branches'])->group(function () {
        Route::resource('branches', BranchController::class);
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/orders', [ReportController::class, 'orders'])->name('orders');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
    });
});

// Authentication
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
