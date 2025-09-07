@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Stock Management</h1>
        <p class="page-subtitle">View all stock across branches</p>
    </div>

    <div class="filter-add-container">
        {{-- Filter by Branch --}}
        <form method="GET" action="{{ route('stocks.index') }}" class="filter-form">
            <select name="branch" onchange="this.form.submit()">
                <option value="">-- All Branches --</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ ($branchFilter ?? null) == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="search" placeholder="Search stock..." value="{{ request('search') }}">

            <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-warehouse"></i> All Stock</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>Quantity</th>
                        <th>Cost Price (RM)</th>
                        <th>Selling Price (RM)</th>
                        @if(auth()->user()->role === 'admin')
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        <tr>
                            <td>#{{ $stock->id }}</td>
                            <td>{{ $stock->product->name ?? '-' }}</td>
                            <td>{{ $stock->branch->name ?? '-' }}</td>
                            <td>{{ $stock->quantity }}</td>
                            <td>{{ number_format($stock->product->cost_price ?? 0, 2) }}</td>
                            <td>{{ number_format($stock->product->selling_price ?? 0, 2) }}</td>

                            @if(auth()->user()->role === 'admin')
                                <td style="display: flex; gap: 8px; align-items: center;">
                                    <a href="{{ route('stocks.edit', $stock->id) }}" class="btn-theme btn-theme-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No stock found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3 flex justify-between items-center">
            <div class="space-x-1">
                @if (!$stocks->onFirstPage())
                    <a href="{{ $stocks->previousPageUrl() }}" class="btn btn-primary">« Previous</a>
                @endif

                @if ($stocks->hasMorePages())
                    <a href="{{ $stocks->nextPageUrl() }}" class="btn btn-primary">Next »</a>
                @endif
            </div>

            <div>
                Showing {{ $stocks->firstItem() }} to {{ $stocks->lastItem() }} of {{ $stocks->total() }} results
            </div>
        </div>
    </div>

    {{-- Styles --}}
    <style>
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            max-height: 700px;
            overflow-y: auto;
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

        .table th,
        .table td {
            padding: 10px 12px;
        }

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
        }

        .btn-theme-danger {
            background: #ef4444;
        }

        .btn-theme:hover {
            opacity: 0.9;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .filter-form select,
        .filter-form input,
        .filter-form button {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid rgba(102, 126, 234, 0.3);
            margin-bottom: 15px; 
        }
    </style>
@endsection