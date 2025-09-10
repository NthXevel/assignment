@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Orders</h1>
        <p class="page-subtitle">Manage and track orders between branches</p>
    </div>

    <div class="filter-add-container">
        {{-- Filter & Search Form --}}
        <form method="GET" action="{{ route('orders.index') }}" class="filter-form">
            {{-- Status Filter --}}
            <select name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            {{-- Search Input --}}
            <input type="text" name="search" placeholder="Search order # or branch..." value="{{ request('search') }}">
            <button type="submit" class="filter-btn">
                <i class="fas fa-search"></i> Search
            </button>

            {{-- Create New Order --}}
            @php $role = auth()->user()->role; @endphp
            @if(in_array($role, ['admin', 'branch_manager', 'stock_manager']))
                <a href="{{ route('orders.create') }}" class="btn-theme btn-theme-primary">
                    <i class="fas fa-plus"></i> New Order
                </a>
            @endif
        </form>


    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>From Branch</th>
                    <th>To Branch</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Created At</th>
                    @if(in_array($role, ['admin', 'branch_manager', 'stock_manager']))
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->requestingBranch->name ?? 'N/A' }}</td>
                        <td>{{ $order->supplyingBranch->name ?? 'N/A' }}</td>

                        {{-- Status Badge --}}
                        @php
                            $statusStyles = [
                                'pending' => 'background:#fff7ed;color:#92400e',
                                'approved' => 'background:#ecfdf5;color:#065f46',
                                'shipped' => 'background:#eff6ff;color:#1e3a8a',
                                'received' => 'background:#f3f4f6;color:#374151',
                                'cancelled' => 'background:#fff1f2;color:#991b1b',
                            ];
                        @endphp
                        <td>
                            <span style="padding:6px 10px; border-radius:12px; font-weight:600; font-size:0.85rem;
                                                                     {{ $statusStyles[$order->status] ?? '' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>

                        <td>RM {{ number_format($order->total_amount, 2) }}</td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>

                        {{-- Actions --}}
                        @if(in_array($role, ['admin', 'branch_manager', 'stock_manager']))
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="btn-theme btn-theme-secondary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>

                                @if($order->status == 'pending')
                                    <form action="{{ route('orders.approve', $order) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn-theme btn-theme-success btn-sm"
                                            onclick="return confirm('Approve this order?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                @endif

                                @if($order->status == 'approved')
                                    <form action="{{ route('orders.ship', $order) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn-theme btn-theme-info btn-sm" onclick="return confirm('Ship this order?')">
                                            <i class="fas fa-truck"></i> Ship
                                        </button>
                                    </form>
                                @endif

                                @if($order->status == 'shipped')
                                    <form action="{{ route('orders.receive', $order) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn-theme btn-theme-warning btn-sm"
                                            onclick="return confirm('Mark as received?')">
                                            <i class="fas fa-box"></i> Receive
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($order->status, ['pending', 'approved']))
                                    <form action="{{ route('orders.cancel', $order) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn-theme btn-theme-danger btn-sm"
                                            onclick="return confirm('Cancel this order?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{-- Pagination --}}
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
                Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} results
            </div>
        </div>

    </div>



    <style>
        /* Pagination */
        .pagination {
            font-size: 0.875rem;
        }

        .pagination li a,
        .pagination li span {
            padding: 0.25rem 0.5rem;
            min-width: auto;
        }

        /* Table Container */
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
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-theme-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }

        .btn-theme-success {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }

        .btn-theme-danger {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        .btn-xs {
            padding: 3px 8px;
            font-size: 0.7rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Filter Forms */
        .filter-add-container,
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 7px;
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
        }

        .filter-form button,
        .filter-form a.filter-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-form button:hover,
        .filter-form a.filter-btn:hover,
        .btn-theme-primary:hover,
        .btn-theme-success:hover,
        .btn-theme-danger:hover {
            opacity: 0.9;
        }
    </style>

@endsection