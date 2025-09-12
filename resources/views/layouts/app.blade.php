<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <title>ElectroStore Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: linear-gradient(135deg, rgba(102, 126, 234, 0.6), rgba(118, 75, 162, 0.6)), url('{{ asset('images/warehouse-bg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            color: #333;
        }

        .dashboard {
            display: grid;
            grid-template-areas:
                "sidebar header header"
                "sidebar main main";
            grid-template-columns: 280px 1fr;
            grid-template-rows: 70px 1fr;
            min-height: 100vh;
            gap: 0;
        }

        .sidebar {
            grid-area: sidebar;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 4px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            padding: 0 20px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .logo h1 {
            font-size: 1.4rem;
            color: #667eea;
            font-weight: 700;
        }

        .logo p {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }

        .nav-menu {
            list-style: none;
            padding: 0 10px;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #666;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }

        .nav-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .header {
            grid-area: header;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            justify-content: flex-end;
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            margin: 0 20px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 45px;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 25px;
            outline: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #333;
        }

        .user-role {
            font-size: 0.8rem;
            color: #666;
        }

        .main-content {
            grid-area: main;
            padding: 30px;
            overflow-y: auto;
        }

        .page-title {
            margin-bottom: 30px;
        }

        .page-title h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-color, #667eea);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: var(--accent-color, #667eea);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .stat-change {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-change.positive {
            color: #10b981;
        }

        .stat-change.negative {
            color: #ef4444;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-feed {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .activity-desc {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #999;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table th {
            background: rgba(102, 126, 234, 0.1);
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #047857;
        }

        .status-shipped {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-low {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-normal {
            background: #d1fae5;
            color: #047857;
        }

        .chart-placeholder {
            height: 300px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .quick-action {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            text-align: center;
        }

        .quick-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: #333;
        }

        .quick-action-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.3rem;
        }

        .quick-action-title {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .quick-action-desc {
            font-size: 0.9rem;
            color: #666;
        }



        @media (max-width: 768px) {
            .dashboard {
                grid-template-areas:
                    "header"
                    "main";
                grid-template-columns: 1fr;
                grid-template-rows: 70px 1fr;
            }

            .sidebar {
                display: none;
            }

            .main-content {
                padding: 20px;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
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
                <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link"><i
                            class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link"><i
                            class="fas fa-mobile-alt"></i> Products</a></li>
                <li class="nav-item"><a href="{{ route('stocks.index') }}" class="nav-link"><i class="fas fa-boxes"></i>
                        Stock Management</a></li>
                <li class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link"><i
                            class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item"><a href="{{ route('branches.index') }}" class="nav-link"><i
                            class="fas fa-store"></i> Branches</a></li>
                {{-- Show User Management only for admin or stock_manager --}}
                @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'branch_manager']))
                    <li class="nav-item"><a href="{{ route('users.index') }}" class="nav-link"><i class="fas fa-users"></i>
                            User Management</a></li>
                @endif

                <li class="nav-item"><a href="{{ route('settings.index') }}" class="nav-link"><i class="fas fa-cog"></i>
                        Settings</a></li>
            </ul>
        </aside>

        <!-- Header -->
        <header class="header">

            <div class="user-info">
                @auth
                    <div class="user-avatar">{{ strtoupper(substr(auth()->user()->username, 0, 1)) }}</div>
                    <div class="user-details">
                        <div class="user-name">{{ auth()->user()->username }}</div>
                        <div class="user-role">{{ auth()->user()->role ?? 'User' }}</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn btn-link"
                            style="border:none;background:none;color:#667eea;cursor:pointer;">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link">Login</a>
                    <a href="{{ route('register') }}" class="nav-link">Register</a>
                @endauth
            </div>
        </header>

        <!-- Page Content -->
        <main class="main-content">
            @yield('content')
        </main>
    </div>
</body>

</html>