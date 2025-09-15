<!-- Author: Ho Jie Han -->
@extends('layouts.app')

@section('content')
    <div class="settings-page">
        <div class="settings-container">
            <h1><i class="fas fa-box-open"></i> Product Details</h1>
            <p>View full details of the product</p>

            <div class="settings-grid">
                <div class="settings-card">
                    <h2><i class="fas fa-info-circle"></i> Information</h2>

                    <!-- Product Name -->
                    <div class="form-group">
                        <label>Product Name</label>
                        <p>{{ $product->name }}</p>
                    </div>

                    <!-- Model -->
                    <div class="form-group">
                        <label>Model</label>
                        <p>{{ $product->model }}</p>
                    </div>

                    <!-- SKU -->
                    <div class="form-group">
                        <label>SKU</label>
                        <p>{{ $product->sku }}</p>
                    </div>

                    <!-- Category -->
                    <div class="form-group">
                        <label>Category</label>
                        <p>{{ $product->category->name ?? '-' }}</p>
                    </div>

                    <!-- Cost Price -->
                    <div class="form-group">
                        <label>Cost Price (RM)</label>
                        <p>{{ number_format($product->cost_price, 2) }}</p>
                    </div>

                    <!-- Selling Price -->
                    <div class="form-group">
                        <label>Selling Price (RM)</label>
                        <p>{{ number_format($product->selling_price, 2) }}</p>
                    </div>

                    <!-- Specifications -->
                    <div class="form-group">
                        <label>Specifications</label>
                        @php
                            $specs = is_string($product->specifications) ? json_decode($product->specifications, true) : $product->specifications;
                        @endphp

                        @if($specs && is_array($specs))
                            <ul>
                                @foreach($specs as $key => $value)
                                    <li><strong>{{ $key }}:</strong> {{ $value }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>-</p>
                        @endif
                    </div>


                    <!-- Actions -->
                    <div class="form-actions">
                        <a href="{{ route('products.index') }}" class="btn-theme btn-theme-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('products.edit', $product) }}" class="btn-theme btn-theme-primary">
                                <i class="fas fa-edit"></i> Edit Product
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        .settings-page {
            display: flex;
            justify-content: center;
            padding: 30px;
        }

        .settings-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 900px;
        }

        .settings-container h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #667eea;
        }

        .settings-container p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 25px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .settings-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .settings-card h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #555;
        }

        .form-group p,
        .form-group ul {
            background: #f7f7f7;
            padding: 10px;
            border-radius: 6px;
            margin: 0;
        }

        .form-group ul {
            list-style: disc inside;
        }

        .btn-theme {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-theme-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-theme-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-theme-secondary {
            background: linear-gradient(135deg, #6B7280, #4B5563);
            color: white;
        }

        .btn-theme-secondary:hover {
            background: linear-gradient(135deg, #4B5563, #6B7280);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-start;
            align-items: center;
        }
    </style>
@endsection