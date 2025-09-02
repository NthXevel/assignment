{{-- resources/views/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroStore Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* keep all your existing CSS here */
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-bolt"></i> ElectroStore</h1>
                <p>Management System</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li class="nav-item"><a href="{{ route('stocks.index') }}" class="nav-link"><i class="fas fa-boxes"></i> Stock Management</a></li>
                <li class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item"><a href="{{ route('branches.index') }}" class="nav-link"><i class="fas fa-store"></i> Branches</a></li>
                <li class="nav-item"><a href="{{ route('users.index') }}" class="nav-link"><i class="fas fa-users"></i> User Management</a></li>
                <li class="nav-item"><a href="{{ route('reports.index') }}" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item"><a href="{{ route('settings.index') }}" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <!-- Header -->
        <header class="header">
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search products, orders, or branches...">
            </div>
            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-details">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ auth()->user()->role ?? 'User' }}</div>
                </div>
                <i class="fas fa-chevron-down" style="color: #666; cursor: pointer;"></i>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-title">
                <h1>Dashboard Overview</h1>
                <p class="page-subtitle">Welcome back, {{ auth()->user()->name }}! Here's what's happening in your store today.</p>
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

                <div class="stat-card" style="--accent-color: #8b5cf6;">
                    <div class="stat-header">
                        <div class="stat-title">Total Branches</div>
                        <div class="stat-icon" style="background: #8b5cf6;">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_branches']) }}</div>
                </div>

                @if(isset($stats['branch_stock_value']))
                <div class="stat-card" style="--accent-color: #06b6d4;">
                    <div class="stat-header">
                        <div class="stat-title">Stock Value</div>
                        <div class="stat-icon" style="background: #06b6d4;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">RM {{ number_format($stats['branch_stock_value'], 2) }}</div>
                </div>

                <div class="stat-card" style="--accent-color: #ef4444;">
                    <div class="stat-header">
                        <div class="stat-title">Orders This Month</div>
                        <div class="stat-icon" style="background: #ef4444;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['branch_orders_this_month']) }}</div>
                </div>
                @endif
            </div>

            <!-- Recent Orders -->
            <div class="table-container">
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
                                    <td><span class="status-badge {{ $order->status == 'pending' ? 'status-pending' : 'status-approved' }}">{{ ucfirst($order->status) }}</span></td>
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
            <div class="table-container">
                <h3 class="chart-title"><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Low Stock Items</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Branch</th>
                                <th>Current Stock</th>
                                <th>Min Threshold</th>
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
        </main>
    </div>
</body>
</html>
