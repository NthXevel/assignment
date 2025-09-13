{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Page Title -->
    <div class="page-title mb-4">
        <h1>Dashboard Overview</h1>
        <p class="page-subtitle">Welcome back, {{ auth()->user()->username }}! Here's what's happening in your store today.</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" style="--accent-color: #667eea;">
            <div class="stat-header">
                <div class="stat-title">Total Products</div>
                <div class="stat-icon" style="background: #667eea;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_products']) }}</div>
        </div>

        <div class="stat-card" style="--accent-color: #f59e0b;">
            <div class="stat-header">
                <div class="stat-title">Low Stock Items</div>
                <div class="stat-icon" style="background: #f59e0b;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['low_stock_items']) }}</div>
        </div>

        <div class="stat-card" style="--accent-color: #10b981;">
            <div class="stat-header">
                <div class="stat-title">Pending Orders</div>
                <div class="stat-icon" style="background: #10b981;">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['pending_orders']) }}</div>
        </div>

        <div class="stat-card" style="--accent-color: #3b82f6;">
            <div class="stat-header">
                <div class="stat-title">Total Branches</div>
                <div class="stat-icon" style="background: #3b82f6;">
                    <i class="fas fa-store"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_branches']) }}</div>
        </div>

        @if(isset($stats['branch_stock_value']))
        <div class="stat-card" style="--accent-color: #ef4444;">
            <div class="stat-header">
                <div class="stat-title">Branch Stock Value</div>
                <div class="stat-icon" style="background: #ef4444;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
            <div class="stat-value">RM {{ number_format($stats['branch_stock_value'], 2) }}</div>
        </div>
        @endif

        @if(isset($stats['branch_orders_this_month']))
        <div class="stat-card" style="--accent-color: #8b5cf6;">
            <div class="stat-header">
                <div class="stat-title">Orders This Month</div>
                <div class="stat-icon" style="background: #8b5cf6;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['branch_orders_this_month']) }}</div>
        </div>
        @endif
    </div>

    <!-- Recent Orders -->
    <div class="table-container mt-6">
        <h3 class="chart-title"><i class="fas fa-shopping-cart"></i> Recent Orders</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Requesting Branch</th>
                        <th>Supplying Branch</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td><strong>#{{ $order->id }}</strong></td>
                            <td>{{ $order->requestingBranch->name ?? '-' }}</td>
                            <td>{{ $order->supplyingBranch->name ?? '-' }}</td>
                            <td>
                                <span class="status-badge {{ $order->status == 'pending' ? 'status-pending' : 'status-approved' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No recent orders</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Items -->
    <div class="table-container mt-6">
        <h3 class="chart-title"><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Low Stock Items</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>Current Stock</th>
                        <th>Minimum Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lowStockItems as $stock)
                        <tr>
                            <td>{{ $stock->product->name ?? '-' }}</td>
                            <td>{{ $stock->branch->name ?? '-' }}</td>
                            <td>{{ $stock->quantity }}</td>
                            <td>{{ $stock->minimum_threshold }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No low stock items</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
