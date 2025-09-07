{{-- resources/views/orders/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Order Management</h1>
        <p class="page-subtitle">Manage branch transfer orders</p>
    </div>

    <div class="filter-add-container">
        {{-- Filters + Create Order on same line (uses same style as product page) --}}
        <form method="GET" action="{{ route('orders.index') }}" class="filter-form">
            <select name="status" onchange="this.form.submit()">
                <option value="">-- All Statuses --</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'stock_manager')
                <select name="branch" onchange="this.form.submit()">
                    <option value="">-- All Branches --</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <input type="text" name="search" placeholder="Search order NO. or user" value="{{ request('search') }}">

            <button type="submit" class="filter-btn">Filter</button>

            {{-- Create Order button styled like filter --}}
            <a href="{{ route('orders.create') }}" class="filter-btn">
                <i class="fas fa-plus"></i> Create Order
            </a>
        </form>
    </div>

    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-list"></i> All Orders</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Requesting Branch</th>
                        <th>Supplying Branch</th>
                        <th>Status</th>
                        <th>Total Amount (RM)</th>
                        <th>Created By</th>
                        <th>Date</th>
                        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'branch_manager')
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>{{ $order->requestingBranch->name ?? '-' }}</td>
                            <td>{{ $order->supplyingBranch->name ?? '-' }}</td>
                            <td>
                                <span style="padding:6px 10px; border-radius:12px; font-weight:600; font-size:0.85rem;
                                            {{ $order->status == 'pending' ? 'background:#fff7ed;color:#92400e' : '' }}
                                            {{ $order->status == 'approved' ? 'background:#ecfdf5;color:#065f46' : '' }}
                                            {{ $order->status == 'shipped' ? 'background:#eff6ff;color:#1e3a8a' : '' }}
                                            {{ $order->status == 'received' ? 'background:#f3f4f6;color:#374151' : '' }}
                                            {{ $order->status == 'cancelled' ? 'background:#fff1f2;color:#991b1b' : '' }}
                                        ">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>RM {{ number_format($order->total_amount ?? 0, 2) }}</td>
                            <td>{{ $order->creator->username ?? '-' }}</td>
                            <td>{{ $order->created_at->format('Y-m-d') }}</td>

                            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'branch_manager')
                                <td style="display: flex; gap: 8px; align-items: center;">
                                    <a href="{{ route('orders.show', $order) }}" class="btn-theme btn-theme-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>

                                    @if($order->status == 'pending' && auth()->user()->role !== 'branch_manager')
                                        <form action="{{ route('orders.approve', $order) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn-theme btn-theme-success btn-sm"
                                                onclick="return confirm('Approve this order?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if($order->status == 'approved' && auth()->user()->role !== 'branch_manager')
                                        <form action="{{ route('orders.ship', $order) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn-theme btn-theme-primary btn-sm"
                                                onclick="return confirm('Mark as shipped?')">
                                                <i class="fas fa-truck"></i> Ship
                                            </button>
                                        </form>
                                    @endif

                                    @if($order->status == 'shipped')
                                        <form action="{{ route('orders.receive', $order) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn-theme btn-theme-primary btn-sm"
                                                onclick="return confirm('Mark as received?')">
                                                <i class="fas fa-box-open"></i> Receive
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (auth()->user()->role === 'admin' || auth()->user()->role === 'branch_manager') ? 8 : 7 }}"
                                class="text-center">No orders found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (hide disabled) --}}
        <div class="mt-3 flex justify-between items-center">
            <div class="space-x-1">
                @if (!$orders->onFirstPage())
                    <a href="{{ $orders->previousPageUrl() }}" class="btn btn-primary">« Previous</a>
                @endif

                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" class="btn btn-primary">Next »</a>
                @endif
            </div>

            <div>
                @if($orders->total())
                    Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} results
                @else
                    Showing 0 results
                @endif
            </div>
        </div>
    </div>

    {{-- Styles (copied from your product page theme) --}}
    <style>
        .pagination {
            font-size: 0.875rem;
        }

        .pagination li a,
        .pagination li span {
            padding: 0.25rem 0.5rem;
            min-width: auto;
        }

        /* Container */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            max-height: 700px;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.4);
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.6);
        }

        /* Table */
        .table th,
        .table td {
            padding: 10px 12px;
        }

        /* Buttons */
        .btn-theme {
            padding: 8px 15px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-theme i {
            margin-right: 4px;
        }

        .btn-theme-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }

        .btn-theme-primary:hover {
            opacity: 0.9;
        }

        .btn-theme-success {
            background: #10b981;
            color: white;
            border: none;
        }

        .btn-theme-success:hover {
            opacity: 0.9;
        }

        .btn-theme-danger {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-theme-danger:hover {
            opacity: 0.9;
        }

        /* Filter Form */
        .filter-add-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .filter-form select,
        .filter-form input,
        .filter-form button,
        .filter-form a.filter-btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid rgba(102, 126, 234, 0.3);
            outline: none;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            background: white;
            color: #333;
        }

        .filter-form button,
        .filter-form a.filter-btn,
        .filter-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-form button:hover,
        .filter-form a.filter-btn:hover,
        .filter-btn:hover {
            opacity: 0.9;
        }

        /* small helpers */
        .btn-sm {
            padding: 6px 10px;
            font-size: 0.85rem;
            border-radius: 6px;
        }
    </style>
@endsection