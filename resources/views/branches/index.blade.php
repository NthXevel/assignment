@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Branch Management</h1>
        <p class="page-subtitle">Manage and view all company branches</p>
    </div>

    @if(auth()->user()->role === 'admin')
        <div class="filter-add-container">
            {{-- Filter Form --}}
            <form method="GET" action="{{ route('branches.index') }}" class="filter-form">
                {{-- Only Admin can Add Branch --}}

                <a href="{{ route('branches.create') }}" class="btn-theme btn-theme-primary">
                    <i class="fas fa-plus-circle"></i> Add New Branch
                </a>

            </form>
        </div>
    @endif

    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-store"></i> All Branches</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Branch Name</th>
                        <th>Location</th>

                        @if(auth()->user()->role === 'admin')
                            <th>Users</th>
                        @endif

                        @if(in_array(auth()->user()->role, ['admin', 'stock_manager']))
                            <th>Stocks</th>
                        @endif

                        @if(auth()->user()->role === 'admin')
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr
                            class="{{ auth()->user()->role !== 'admin' && auth()->user()->role !== 'stock_manager' && auth()->user()->branch_id == $branch->id ? 'bg-indigo-50' : '' }}">
                            <td>#{{ $branch->id }}</td>
                            <td>
                                {{ $branch->name }}
                                @if($branch->id == auth()->user()->branch_id)
                                    <span class="btn-theme btn-theme-success btn-xs">
                                        <i class="fas fa-check-circle"></i> Current Branch
                                    </span>
                                @endif
                                @if($branch->is_main)
                                    <span class="btn-theme btn-theme-primary btn-xs">
                                        <i class="fas fa-star"></i> Main Branch
                                    </span>
                                @endif
                            </td>
                            <td>{{ $branch->location }}</td>

                            @if(auth()->user()->role === 'admin')
                                <td>{{ $branch->users_count }}</td>
                            @endif

                            @if(in_array(auth()->user()->role, ['admin', 'stock_manager']))
                                <td>{{ $branch->stocks_count }}</td>
                            @endif

                            @if(auth()->user()->role === 'admin')
                                <td style="display: flex; gap: 8px; align-items: center;">
                                    <a href="{{ route('branches.edit', $branch->id) }}" class="btn-theme btn-theme-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('branches.destroy', $branch->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf @method('DELETE')

                                        @if($branch->is_main)
                                            <button type="button" class="btn-theme btn-theme-danger btn-sm"
                                                onclick="alert('The main branch cannot be deleted!')">
                                                <i class="fas fa-ban"></i> Delete
                                            </button>
                                        @else
                                            <button class="btn-theme btn-theme-danger btn-sm"
                                                onclick="return confirm('Delete this branch? Stocks will be transferred to the main branch if available.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        @endif
                                    </form>

                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'admin' ? 6 : (in_array(auth()->user()->role, ['stock_manager']) ? 4 : 3) }}"
                                class="text-center">
                                No branches found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Enhanced Pagination --}}
        <div class="pagination-container">
            <div class="pagination-buttons">
                @if (!$branches->onFirstPage())
                    <a href="{{ $branches->appends(request()->query())->previousPageUrl() }}"
                        class="btn-theme btn-theme-secondary">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                @endif

                {{-- Page Numbers --}}
                @if($branches->hasPages())
                    <div class="page-numbers">
                        @foreach(range(1, min(5, $branches->lastPage())) as $page)
                            <a href="{{ $branches->appends(request()->query())->url($page) }}"
                                class="page-btn {{ $branches->currentPage() == $page ? 'active' : '' }}">
                                {{ $page }}
                            </a>
                        @endforeach

                        @if($branches->lastPage() > 5)
                            <span class="dots">...</span>
                            <a href="{{ $branches->appends(request()->query())->url($branches->lastPage()) }}"
                                class="page-btn">{{ $branches->lastPage() }}</a>
                        @endif
                    </div>
                @endif

                @if ($branches->hasMorePages())
                    <a href="{{ $branches->appends(request()->query())->nextPageUrl() }}" class="btn-theme btn-theme-secondary">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </div>

            <div class="pagination-info">
                <strong>{{ $branches->firstItem() ?: 0 }} - {{ $branches->lastItem() ?: 0 }}</strong> of
                <strong>{{ $branches->total() }}</strong> branches
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

        .btn-xs {
            padding: 4px 8px;
            font-size: 0.75rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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