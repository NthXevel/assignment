@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-container">
        <h1><i class="fas fa-edit"></i> Edit Product</h1>
        <p>Update product details</p>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="settings-grid">
            <div class="settings-card">
                <h2><i class="fas fa-info-circle"></i> Product Information</h2>

                <form action="{{ route('products.update', $product->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Product Name -->
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required>
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Model (readonly) -->
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" value="{{ $product->model }}" readonly>
                    </div>

                    <!-- SKU (readonly) -->
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" readonly>
                    </div>

                    <!-- Category -->
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Cost Price -->
                    <div class="form-group">
                        <label for="cost_price">Cost Price (RM)</label>
                        <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" required>
                        @error('cost_price') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Selling Price -->
                    <div class="form-group">
                        <label for="selling_price">Selling Price (RM)</label>
                        <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price', $product->selling_price) }}" step="0.01" required>
                        @error('selling_price') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description">{{ old('description', $product->description) }}</textarea>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Specifications -->
                    <div class="form-group">
                        <label>Specifications</label>
                        <div id="specifications-wrapper">
                            @php
                                $specs = is_string($product->specifications) ? json_decode($product->specifications, true) : $product->specifications;
                            @endphp

                            @if($specs && is_array($specs))
                                @foreach($specs as $key => $value)
                                <div class="spec-row">
                                    <input type="text" name="specifications[key][]" value="{{ $key }}" placeholder="Key" required>
                                    <input type="text" name="specifications[value][]" value="{{ $value }}" placeholder="Value" required>
                                    <button type="button" class="remove-spec">Remove</button>
                                </div>
                                @endforeach
                            @else
                                <div class="spec-row">
                                    <input type="text" name="specifications[key][]" placeholder="Key" required>
                                    <input type="text" name="specifications[value][]" placeholder="Value" required>
                                    <button type="button" class="remove-spec">Remove</button>
                                </div>
                            @endif
                        </div>
                        <button type="button" id="add-spec">Add Specification</button>
                        @error('specifications') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-theme btn-theme-primary"><i class="fas fa-save"></i> Save</button>
                        <a href="{{ route('products.index') }}" class="btn-theme btn-theme-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<style>
    .alert {
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 6px;
    }
    .alert-success { background-color: #d4edda; color: #155724; }
    .alert-danger { background-color: #f8d7da; color: #721c24; }

    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 8px 10px;
        margin-top: 4px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }
    .form-group .error { color: #f44336; font-size: 0.875rem; }

    .spec-row {
        display: flex;
        gap: 10px;
        margin-bottom: 8px;
    }
    .spec-row input { flex: 1; }
    .spec-row button {
        background: #f44336;
        color: #fff;
        border: none;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
    }
    #add-spec {
        margin-top: 10px;
        background: #667eea;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
    }
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

</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.getElementById('specifications-wrapper');
        const addBtn = document.getElementById('add-spec');

        addBtn.addEventListener('click', function() {
            const div = document.createElement('div');
            div.classList.add('spec-row');
            div.innerHTML = `
                <input type="text" name="specifications[key][]" placeholder="Key" required>
                <input type="text" name="specifications[value][]" placeholder="Value" required>
                <button type="button" class="remove-spec">Remove</button>
            `;
            wrapper.appendChild(div);
        });

        wrapper.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-spec')) {
                e.target.parentNode.remove();
            }
        });
    });
</script>
@endsection
