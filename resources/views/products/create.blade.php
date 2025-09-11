@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-container">
        <h1><i class="fas fa-plus-circle"></i> Add New Product</h1>
        <p>Fill in the details to add a new product into the system</p>

        <div class="settings-grid">
            <div class="settings-card">
                <h2><i class="fas fa-box"></i> Product Information</h2>
                <form method="POST" action="{{ route('products.store') }}">
                    @csrf

                    <!-- Product Name -->
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required>
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Auto-generated Model -->
                    <div class="form-group">
                        <label for="model">Model (Auto-generated)</label>
                        <input type="text" id="model_display" readonly>
                        <input type="hidden" name="model" id="model">
                    </div>

                    <!-- Auto-generated SKU -->
                    <div class="form-group">
                        <label for="sku">SKU (Auto-generated)</label>
                        <input type="text" id="sku_display" readonly>
                        <input type="hidden" name="sku" id="sku">
                    </div>

                    <!-- Category -->
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Cost Price -->
                    <div class="form-group">
                        <label for="cost_price">Cost Price (RM)</label>
                        <input type="number" step="0.01" name="cost_price" id="cost_price" value="{{ old('cost_price') }}" required>
                        @error('cost_price') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Selling Price -->
                    <div class="form-group">
                        <label for="selling_price">Selling Price (RM)</label>
                        <input type="number" step="0.01" name="selling_price" id="selling_price" value="{{ old('selling_price') }}" required>
                        @error('selling_price') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description">{{ old('description') }}</textarea>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Specifications -->
                    <div class="form-group">
                        <label for="specifications">Specifications</label>
                        <div id="specifications-container"></div>
                        <button type="button" class="btn-theme btn-theme-primary" onclick="addSpecificationRow()">
                            <i class="fas fa-plus"></i> Add Specification
                        </button>
                        <input type="hidden" name="specifications" id="specifications">
                        @error('specifications') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-theme btn-theme-primary">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        <a href="{{ route('products.index') }}" class="btn-theme btn-theme-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-generate Model & SKU
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value.trim();

        if(name.length > 0){
            const model = name.replace(/[^A-Za-z0-9]/g, '').substring(0,5).toUpperCase() + Math.floor(100 + Math.random()*900);
            document.getElementById('model_display').value = model;
            document.getElementById('model').value = model;

            const sku = name.substring(0,3).toUpperCase() + '-' + Math.floor(1000 + Math.random()*9000);
            document.getElementById('sku_display').value = sku;
            document.getElementById('sku').value = sku;
        } else {
            document.getElementById('model_display').value = '';
            document.getElementById('model').value = '';
            document.getElementById('sku_display').value = '';
            document.getElementById('sku').value = '';
        }
    });

    // Dynamic Specifications
    let specIndex = 0;

    function addSpecificationRow(key = '', value = '') {
        const container = document.getElementById('specifications-container');
        const row = document.createElement('div');
        row.classList.add('spec-row');

        row.innerHTML = `
            <input type="text" placeholder="Key" class="spec-key" value="${key}">
            <input type="text" placeholder="Value" class="spec-value" value="${value}">
            <button type="button" class="btn-theme btn-theme-danger btn-sm" onclick="removeRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        `;

        container.appendChild(row);
        updateSpecificationsJSON();
        specIndex++;
    }

    function removeRow(button) {
        button.parentElement.remove();
        updateSpecificationsJSON();
    }

    function updateSpecificationsJSON() {
        const keys = document.querySelectorAll('.spec-key');
        const values = document.querySelectorAll('.spec-value');
        let specs = {};

        keys.forEach((keyInput, i) => {
            const key = keyInput.value.trim();
            const value = values[i].value.trim();
            if(key && value) specs[key] = value;
        });

        document.getElementById('specifications').value = JSON.stringify(specs);
    }

    document.addEventListener('input', function(e) {
        if(e.target.classList.contains('spec-key') || e.target.classList.contains('spec-value')) {
            updateSpecificationsJSON();
        }
    });

    window.onload = function () {
        addSpecificationRow();
    };
</script>

<style>
    .spec-row {
        display: flex;
        gap: 10px;
        margin-bottom: 8px;
    }

    .spec-row input {
        flex: 1;
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .spec-row button {
        background: linear-gradient(135deg, #f87171, #dc2626);
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
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

    .btn-theme-danger {
        background: linear-gradient(135deg, #f87171, #dc2626);
        color: white;
    }

    .btn-theme-danger:hover {
        background: linear-gradient(135deg, #dc2626, #f87171);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.875rem;
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

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .error {
        font-size: 0.8rem;
        color: #dc2626;
    }
</style>
@endsection
