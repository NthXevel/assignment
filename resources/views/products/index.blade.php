@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Product Management</h1>
        <p class="page-subtitle">Manage all products across branches</p>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('products.index') }}" class="mb-4 flex gap-3">
        <select name="category" class="form-control" onchange="this.form.submit()">
            <option value="">-- All Categories --</option>
            @foreach($categories as $category)
                <option value="{{ $category->slug }}" 
                    {{ request('category') == $category->slug ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <input type="text" name="search" class="form-control" 
               placeholder="Search product..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <div class="table-container" style="max-height: 500px; overflow-y: auto;">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>#{{ $product->id }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>{{ number_format($product->cost_price, 2) }}</td>
                            <td>{{ number_format($product->selling_price, 2) }}</td>
                            <td>
                                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No products found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
