@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Product Management</h1>
        <p class="page-subtitle">Manage all products across branches</p>
    </div>

    <div class="filter-add-container">
        {{-- Filter Form --}}
        <form method="GET" action="{{ route('products.index') }}" class="filter-form">
            <select name="category" onchange="this.form.submit()">
                <option value="">-- All Categories --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}" {{ request('category') == $category->slug ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="search" placeholder="Search product..." value="{{ request('search') }}">
            <button type="submit" class="filter-btn">Filter</button>
        </form>

        {{-- Add Product --}}
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('products.create') }}" class="btn-action">
                <i class="fas fa-plus"></i> Add Product
            </a>

            {{-- Add Category (inline form) --}}
            <form method="POST" action="{{ route('products.store-category') }}" class="filter-form">
                @csrf
                <input type="text" name="name" placeholder="New category..." required>
                <input type="text" name="description" placeholder="Description (optional)">
                <button type="submit" class="btn-action">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </form>
        @endif
    </div>
    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-mobile-alt"></i> All Products</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Cost Price (RM)</th>
                        <th>Selling Price (RM)</th>
                        @if(auth()->user()->role === 'admin')
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>#{{ $product->id }}</td>
                            <td>
                                <a href="{{ route('products.show', $product->id) }}" class="text-blue-600 hover:underline">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>{{ number_format($product->cost_price, 2) }}</td>
                            <td>{{ number_format($product->selling_price, 2) }}</td>

                            @if(auth()->user()->role === 'admin')
                                <td style="display: flex; gap: 8px; align-items: center;">
                                    <a href="{{ route('products.edit', $product->id) }}" class="btn-theme btn-theme-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button class="btn-theme btn-theme-danger btn-sm"
                                            onclick="return confirm('Delete this product?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No products found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3 flex justify-between items-center">
            {{-- Previous / Next buttons --}}
            <div class="space-x-1">
                @if (!$products->onFirstPage())
                    <a href="{{ $products->previousPageUrl() }}" class="btn btn-primary">« Previous</a>
                @endif

                @if ($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}" class="btn btn-primary">Next »</a>
                @endif
            </div>

            {{-- Showing results --}}
            <div>
                Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} results
            </div>
        </div>

    </div>

    {{-- Styles --}}
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
            /* base padding for all buttons */
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
            /* just color changes */
            color: white;
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
            /* for <a> */
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

        .btn-action {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-action:hover {
            opacity: 0.9;
        }
    </style>
@endsection