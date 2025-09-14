@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>User Management</h1>
        <p class="page-subtitle">Manage users across branches</p>
    </div>

    <div class="filter-add-container">
        {{-- Filter Form --}}
        <form method="GET" action="{{ route('users.index') }}" class="filter-form">
            {{-- Branch Filter (Admin only) --}}
            @if(auth()->user()->role === 'admin')
                <select name="branch" onchange="this.form.submit()" class="form-control">
                    <option value="">-- All Branches --</option>
                    @foreach($branches as $branch)
                    @php
                        $bid   = is_array($branch) ? ($branch['id'] ?? null)   : ($branch->id ?? null);
                        $bname = is_array($branch) ? ($branch['name'] ?? '')   : ($branch->name ?? '');
                    @endphp
                    <option value="{{ $bid }}" {{ (string)request('branch') === (string)$bid ? 'selected' : '' }}>
                        {{ $bname }}
                    </option>
                    @endforeach
                </select>
            @endif

            {{-- Role Filter --}}
            <select name="role" onchange="this.form.submit()" class="form-control">
                <option value="">-- All Roles --</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="search" placeholder="Search username or email..." value="{{ request('search') }}"
                class="form-control">

            <button type="submit" class="btn-theme btn-theme-primary">
                <i class="fas fa-filter"></i> Filter
            </button>

            {{-- Add User (Admin + Branch Manager only) --}}
            @if(in_array(auth()->user()->role, ['admin', 'branch_manager']))
                <a href="{{ route('users.create') }}" class="btn-theme btn-theme-primary">
                    <i class="fas fa-plus"></i> Add User
                </a>
            @endif
        </form>
    </div>

    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-users"></i> All Users</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        @if(in_array(auth()->user()->role, ['admin', 'branch_manager']))
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $uid    = is_array($user) ? ($user['id'] ?? null)       : ($user->id ?? null);
                            $uname  = is_array($user) ? ($user['username'] ?? '')   : ($user->username ?? '');
                            $uemail = is_array($user) ? ($user['email'] ?? '')      : ($user->email ?? '');
                            $urole  = is_array($user) ? ($user['role'] ?? '')       : ($user->role ?? '');
                            $branch = is_array($user)
                                ? (is_array($user['branch'] ?? null) ? ($user['branch']['name'] ?? '-') : '-')
                                : ($user->branch->name ?? '-');
                        @endphp

                        <tr>
                            <td>#{{ $uid }}</td>
                            <td>{{ $uname }}</td>
                            <td>{{ $uemail }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $urole)) }}</td>
                            <td>{{ $branch }}</td>

                            <td style="display:flex;gap:8px;align-items:center;">
                                <a href="{{ route('users.show', $uid) }}" class="btn-theme btn-theme-secondary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>

                                @if(in_array(auth()->user()->role, ['admin', 'branch_manager']))
                                    <a href="{{ route('users.edit', $uid) }}" class="btn-theme btn-theme-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('users.destroy', $uid) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button class="btn-theme btn-theme-danger btn-sm"
                                                onclick="return confirm('Delete this user?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Enhanced Pagination --}}
        <div class="pagination-container">
            <div class="pagination-buttons">
                @if (!$users->onFirstPage())
                    <a href="{{ $users->appends(request()->query())->previousPageUrl() }}"
                        class="btn-theme btn-theme-secondary">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                @endif

                {{-- Page Numbers --}}
                @if($users->hasPages())
                    <div class="page-numbers">
                        @foreach(range(1, min(5, $users->lastPage())) as $page)
                            <a href="{{ $users->appends(request()->query())->url($page) }}"
                                class="page-btn {{ $users->currentPage() == $page ? 'active' : '' }}">
                                {{ $page }}
                            </a>
                        @endforeach

                        @if($users->lastPage() > 5)
                            <span class="dots">...</span>
                            <a href="{{ $users->appends(request()->query())->url($users->lastPage()) }}"
                                class="page-btn">{{ $users->lastPage() }}</a>
                        @endif
                    </div>
                @endif

                @if ($users->hasMorePages())
                    <a href="{{ $users->appends(request()->query())->nextPageUrl() }}" class="btn-theme btn-theme-secondary">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </div>

            <div class="pagination-info">
                <strong>{{ $users->firstItem() ?: 0 }} - {{ $users->lastItem() ?: 0 }}</strong> of
                <strong>{{ $users->total() }}</strong> users
                @if(request()->has('search') && request('search'))
                    | Filtered by: <em>"{{ request('search') }}"</em>
                @endif
                @if(request()->filled('branch'))
                    @php
                        $selected = collect($branches)->first(function ($b) {
                            $id = is_array($b) ? ($b['id'] ?? null) : ($b->id ?? null);
                            return (string)$id === (string)request('branch');
                        });
                        $selectedName = is_array($selected) ? ($selected['name'] ?? 'Unknown')
                                        : ($selected->name ?? 'Unknown');
                    @endphp
                    | Branch: <em>{{ $selectedName }}</em>
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