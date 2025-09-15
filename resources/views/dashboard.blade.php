<!-- Author: Ho Jie Han -->
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
            <div class="stat-value">{{ number_format($stats['total_products'] ?? 0) }}</div>
        </div>

        <div class="stat-card" style="--accent-color: #f59e0b;">
            <div class="stat-header">
                <div class="stat-title">Low Stock Items</div>
                <div class="stat-icon" style="background: #f59e0b;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['low_stock_items'] ?? 0) }}</div>
        </div>

        <div class="stat-card" style="--accent-color: #10b981;">
            <div class="stat-header">
                <div class="stat-title">Pending Orders</div>
                <div class="stat-icon" style="background: #10b981;">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['pending_orders'] ?? 0) }}</div>
        </div>

        <div class="stat-card" style="--accent-color: #3b82f6;">
            <div class="stat-header">
                <div class="stat-title">Total Branches</div>
                <div class="stat-icon" style="background: #3b82f6;">
                    <i class="fas fa-store"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_branches'] ?? 0) }}</div>
        </div>

        @if(isset($stats['branch_stock_value']))
        <div class="stat-card" style="--accent-color: #ef4444;">
            <div class="stat-header">
                <div class="stat-title">Branch Stock Value</div>
                <div class="stat-icon" style="background: #ef4444;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
            <div class="stat-value">RM {{ number_format((float)$stats['branch_stock_value'], 2) }}</div>
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

    @php
        // Helper for status badge class
        function order_status_class($status) {
            $s = strtolower((string)$status);
            return match ($s) {
                'pending'   => 'status-pending',
                'approved'  => 'status-approved',
                'shipped'   => 'status-shipped',
                'received'  => 'status-received',
                'cancelled','canceled' => 'status-cancelled',
                default     => 'status-default',
            };
        }
    @endphp

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
                        @php
                            // ID
                            $oid = data_get($order, 'id');

                            // Names
                            $reqName = data_get($order, 'requesting_branch.name')
                            ?? data_get($order, 'requesting_branch_name')
                            ?? data_get($order, 'requestingBranch.name')
                            ?? (isset($branchNames[(int) data_get($order, 'requesting_branch_id')])
                                ? $branchNames[(int) data_get($order, 'requesting_branch_id')]
                                : '-');

                            // Supplying name
                            $supName = data_get($order, 'supplying_branch.name')
                                ?? data_get($order, 'supplying_branch_name')
                                ?? data_get($order, 'supplyingBranch.name')
                                ?? (isset($branchNames[(int) data_get($order, 'supplying_branch_id')])
                                    ? $branchNames[(int) data_get($order, 'supplying_branch_id')]
                                    : '-');

                            // Status and date
                            $status  = data_get($order, 'status', '-');
                            $created = data_get($order, 'created_at');

                            try {
                                $dateStr = $created ? \Illuminate\Support\Carbon::parse($created)->format('Y-m-d') : '-';
                            } catch (\Exception $e) {
                                $dateStr = is_string($created) ? $created : '-';
                            }
                        @endphp
                        <tr>
                            <td><strong>#{{ $oid }}</strong></td>
                            <td>{{ $reqName }}</td>
                            <td>{{ $supName }}</td>
                            <td>
                                <span class="status-badge {{ order_status_class($status) }}">
                                    {{ ucfirst((string)$status) }}
                                </span>
                            </td>
                            <td>{{ $dateStr }}</td>
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
                        @php
                            $productName = data_get($stock, 'product.name')
                                ?? data_get($stock, 'product_name')
                                ?? '-';

                            $branchName = data_get($stock, 'branch.name')
                                ?? data_get($stock, 'branch_name')
                                ?? '-';

                            $qty   = (int) (data_get($stock, 'quantity') ?? 0);
                            $min   = (int) (data_get($stock, 'minimum_threshold') ?? data_get($stock, 'min_threshold') ?? 0);
                        @endphp
                        <tr>
                            <td>{{ $productName }}</td>
                            <td>{{ $branchName }}</td>
                            <td>{{ number_format($qty) }}</td>
                            <td>{{ number_format($min) }}</td>
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
