<!--Author: Lee Kai Yi -->
@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Orders</h1>
        <p class="page-subtitle">Manage and track orders between branches</p>
    </div>

    @if (session('error'))
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ session('error') }}</span>
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                alert(@json(session('error')));
            });
        </script>
    @endif

    <div class="filter-add-container">
        {{-- Filter & Search Form --}}
        <form method="GET" action="{{ route('orders.index') }}" class="filter-form">
            {{-- Status Filter --}}
            <select name="status" onchange="this.form.submit()" class="form-control">
                <option value="">-- All Statuses --</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <input type="text" name="search" placeholder="Search branch" value="{{ request('search') }}"
                class="form-control">

            <button type="submit" class="btn-theme btn-theme-primary">
                <i class="fas fa-filter"></i> Filter
            </button>

            {{-- Create New Order --}}
            @php $role = auth()->user()->role; @endphp
            <a href="{{ route('orders.create') }}" class="btn-theme btn-theme-primary">
                <i class="fas fa-plus"></i> New Order
            </a>
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
                    <th>Priority</th>
                    <th>Total Amount</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $branchNameMap[$order->requesting_branch_id] ?? 'N/A' }}</td>
                        <td>{{ $branchNameMap[$order->supplying_branch_id] ?? 'N/A' }}</td>

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

                        {{-- Priority Badge --}}
                        @php
                            $priorityStyles = [
                                'urgent' => 'background:#fee2e2;color:#991b1b',
                                'standard' => 'background:#e0e7ff;color:#3730a3',
                            ];
                        @endphp
                        <td>
                            <span style="padding:6px 10px; border-radius:12px; font-weight:600; font-size:0.85rem;
                                            {{ $priorityStyles[$order->priority] ?? '' }}">
                                {{ ucfirst($order->priority ?? 'standard') }}
                            </span>
                        </td>

                        <td>RM {{ number_format($order->total_amount, 2) }}</td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>

                        {{-- Actions --}}
                        <td>
                            {{-- View is always visible --}}
                            <a href="{{ route('orders.show', $order) }}" class="btn-theme btn-theme-secondary btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>

                            {{-- Approve: only admin + stock_manager --}}
                            @if($order->status == 'pending' && in_array($role, ['admin', 'stock_manager']))
                                <form action="{{ route('orders.approve', $order) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn-theme btn-theme-success btn-sm"
                                        onclick="return confirm('Approve this order?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                            @endif

                            {{-- Ship: only supplying branch --}}
                            @if($order->status == 'approved' && auth()->user()->branch_id == $order->supplying_branch_id)
                                <form action="{{ route('orders.ship', $order) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn-theme btn-theme-info btn-sm" onclick="return confirm('Ship this order?')">
                                        <i class="fas fa-truck"></i> Ship
                                    </button>
                                </form>
                            @endif

                            {{-- Receive: only requesting branch --}}
                            @if($order->status == 'shipped' && auth()->user()->branch_id == $order->requesting_branch_id)
                                <form action="{{ route('orders.receive', $order) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn-theme btn-theme-warning btn-sm"
                                        onclick="return confirm('Mark as received?')">
                                        <i class="fas fa-box"></i> Receive
                                    </button>
                                </form>
                            @endif

                            {{-- Cancel: allowed for admin/branch_manager/stock_manager if pending or approved --}}
                            @if(in_array($order->status, ['pending', 'approved']) && in_array($role, ['admin', 'branch_manager', 'stock_manager']))
                                <form action="{{ route('orders.cancel', $order) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn-theme btn-theme-danger btn-sm"
                                        onclick="return confirm('Cancel this order?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Enhanced Pagination --}}
        <div class="pagination-container">
            <div class="pagination-buttons">
                @if (!$orders->onFirstPage())
                    <a href="{{ $orders->appends(request()->query())->previousPageUrl() }}" class="btn-theme btn-theme-secondary">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                @endif

                {{-- Page Numbers --}}
                @if($orders->hasPages())
                    <div class="page-numbers">
                        @foreach(range(1, min(5, $orders->lastPage())) as $page)
                            <a href="{{ $orders->appends(request()->query())->url($page) }}" 
                               class="page-btn {{ $orders->currentPage() == $page ? 'active' : '' }}">
                                {{ $page }}
                            </a>
                        @endforeach
                        
                        @if($orders->lastPage() > 5)
                            <span class="dots">...</span>
                            <a href="{{ $orders->appends(request()->query())->url($orders->lastPage()) }}" 
                               class="page-btn">{{ $orders->lastPage() }}</a>
                        @endif
                    </div>
                @endif

                @if ($orders->hasMorePages())
                    <a href="{{ $orders->appends(request()->query())->nextPageUrl() }}" class="btn-theme btn-theme-secondary">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </div>

            <div class="pagination-info">
                <strong>{{ $orders->firstItem() ?: 0 }} - {{ $orders->lastItem() ?: 0 }}</strong> of <strong>{{ $orders->total() }}</strong> orders
                @if(request()->has('search') && request('search'))
                    | Filtered by: <em>"{{ request('search') }}"</em>
                @endif
                @if(request()->has('branch') && request('branch'))
                    | Branch: <em>{{ $branches->find(request('branch'))->name ?? 'Unknown' }}</em>
                @endif
            </div>
        </div>
    </div>
    

    {{-- Enhanced CSS Styles --}}
    <style>
        /* Alert Messages with Icons */
        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Enhanced Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            max-height: 800px;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chart-title {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Enhanced Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            font-weight: 700;
            color: #2d3748;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Row Styling */
        .stock-row {
            transition: all 0.2s ease;
        }

        .stock-row:hover {
            background-color: rgba(102, 126, 234, 0.05);
            transform: translateX(2px);
        }

        tr.low-stock {
            background: linear-gradient(90deg, rgba(251, 191, 36, 0.1), transparent);
            border-left: 3px solid #f59e0b;
        }

        tr.zero-stock {
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.1), transparent);
            border-left: 3px solid #ef4444;
        }

        /* Button Themes */
        .btn-theme {
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-theme-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .btn-theme-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .btn-theme-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        .btn-theme-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
        }

        .btn-theme:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-theme:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 0.8rem;
        }

        /* Actions Cell Layout */
        .actions-cell {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            min-width: 280px;
        }

        .bulk-form,
        .direct-form {
            display: flex;
            align-items: center;
        }

        .bulk-controls,
        .direct-controls {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(255, 255, 255, 0.9);
            padding: 4px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Input Styling */
        .amount-input,
        .quantity-input,
        .direct-input {
            width: 50px;
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.85rem;
            text-align: center;
            font-weight: 600;
        }

        .direct-input {
            width: 60px;
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
        }

        .decrease-input {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
        }

        .increase-input {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        }

        .amount-input:focus,
        .quantity-input:focus,
        .direct-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        /* Quantity Badges */
        .quantity-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .quantity-badge.normal-stock {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #166534;
        }

        .quantity-badge.low-stock {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            color: #92400e;
        }

        .quantity-badge.zero-stock {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #991b1b;
        }

        /* Filter Container */
        .filter-add-container {
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-form select,
        .filter-form input {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            min-width: 200px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-form select:focus,
        .filter-form input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Sort Button */
        .btn-theme-sort {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            text-decoration: none;
            margin-left: 6px;
        }

        /* Enhanced Pagination */
        .pagination-container {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px;
            background: rgba(248, 250, 252, 0.8);
            border-radius: 12px;
            border-top: 2px solid #e2e8f0;
        }

        .pagination-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .pagination-buttons .btn-theme {
            min-width: 100px;
            padding: 10px 15px;
            font-weight: 600;
        }

        .page-numbers {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .page-btn {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .page-btn:hover,
        .page-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
            transform: translateY(-1px);
        }

        .dots {
            padding: 8px 4px;
            color: #6b7280;
        }

        .pagination-info {
            color: #4b5563;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .empty-state i {
            color: #d1d5db;
            margin-bottom: 15px;
        }

        .empty-state h4 {
            color: #4b5563;
            margin-bottom: 8px;
        }

        /* Warning Text */
        .text-warning {
            color: #f59e0b !important;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .actions-cell {
                flex-direction: column;
                gap: 6px;
                min-width: auto;
            }

            .bulk-controls,
            .direct-controls {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .filter-add-container {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form select,
            .filter-form input {
                min-width: auto;
                width: 100%;
            }

            .pagination-container {
                flex-direction: column;
                gap: 15px;
            }

            .pagination-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .table-container {
                overflow-x: auto;
                padding: 15px;
            }

            .table {
                min-width: 800px;
            }
        }

        /* Utility Classes */
        .text-center {
            text-align: center;
        }

        .text-warning {
            color: #f59e0b;
        }

        /* Scrollbar Styling */
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

        /* Animation for success messages */
        .alert {
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hover effects for forms */
        .bulk-form:hover .bulk-controls,
        .direct-form:hover .direct-controls {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }
    </style>
@endsection