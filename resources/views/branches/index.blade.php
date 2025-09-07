@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Branch Management</h1>
        <p class="page-subtitle">Manage and view all company branches</p>
    </div>

    <div class="filter-add-container">
        <form method="GET" action="{{ route('branches.index') }}" class="filter-form">
            {{-- Only Admin can Add Branch --}}
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('branches.create') }}" class="filter-btn">
                    <i class="fas fa-plus"></i> Add Branch
                </a>
            @endif
        </form>
    </div>

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
                                @if(auth()->user()->role !== 'admin' && auth()->user()->role !== 'stock_manager' && auth()->user()->branch_id == $branch->id)
                                    <span class="btn-theme btn-theme-success btn-xs">
                                        <i class="fas fa-check-circle"></i> Current Branch
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
                                        <button class="btn-theme btn-theme-danger btn-sm"
                                            onclick="return confirm('Delete this branch and all related stocks?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
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

        {{-- Pagination --}}
        <div class="mt-3 flex justify-between items-center">
            <div class="space-x-1">
                @if (!$branches->onFirstPage())
                    <a href="{{ $branches->previousPageUrl() }}" class="btn btn-primary">« Previous</a>
                @endif

                @if ($branches->hasMorePages())
                    <a href="{{ $branches->nextPageUrl() }}" class="btn btn-primary">Next »</a>
                @endif
            </div>

            <div>
                Showing {{ $branches->firstItem() }} to {{ $branches->lastItem() }} of {{ $branches->total() }} results
            </div>
        </div>
    </div>

    {{-- Styles copied from product page --}}
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
        }

        .btn-theme-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-theme-primary:hover,
        .btn-theme-success:hover,
        .btn-theme-danger:hover {
            opacity: 0.9;
        }

        .btn-theme-success {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-theme-danger {
            background: #ef4444;
            color: white;
        }

        .btn-xs {
            padding: 3px 8px;
            font-size: 0.7rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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
        .filter-form a.filter-btn:hover {
            opacity: 0.9;
        }
    </style>
@endsection