
<!-- Author: Lee Kai Yi -->
@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-container">
        <h1><i class="fas fa-plus-circle"></i> New Order</h1>
        <p>Create a new order by selecting products and quantities</p>

        <div class="settings-grid">
            <div class="settings-card">
                <h2><i class="fas fa-shopping-cart"></i> Order Information</h2>
                <form action="{{ route('orders.store') }}" method="POST">
                    @csrf

                    <!-- Product -->
                    <div class="form-group">
                        <label for="product_id">Product</label>
                        <select id="product_id" name="items[0][product_id]" class="form-control" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} ({{ $product->category_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('items.0.product_id') <span class="error">{{ $message }}</span> @enderror
                        <small id="current-branch-stock" class="current-stock-info"></small>
                    </div>

                    <!-- Branch -->
                    <div class="form-group">
                        <label for="supplying_branch_id">Supplying Branch</label>
                        <select id="supplying_branch_id" name="supplying_branch_id" class="form-control" required>
                            <option value="">Select a branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('supplying_branch_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Quantity -->
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input id="quantity" type="number" name="items[0][quantity]" class="form-control" min="1" required>
                        <small id="available-stock" class="stock-info"></small>
                        @error('items.0.quantity') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Priority -->
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" class="form-control" required>
                            <option value="standard">Standard</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        @error('priority') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                        @error('notes') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-theme btn-theme-primary">
                            <i class="fas fa-save"></i> Create Order
                        </button>
                        <a href="{{ route('orders.index') }}" class="btn-theme btn-theme-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const currentBranchId = {{ auth()->user()->branch_id ?? 'null' }};
const productSelect   = document.getElementById('product_id');
const branchSelect    = document.getElementById('supplying_branch_id');
const quantityInput   = document.getElementById('quantity');
const stockInfo       = document.getElementById('available-stock');
const currentStockInfo= document.getElementById('current-branch-stock');

async function fetchAvailability(productId) {
  const url = `{{ route('orders.branches_with_stock') }}?product_id=${encodeURIComponent(productId)}`;
  const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
  if (!res.ok) throw new Error('Failed to load availability');
  return await res.json();
}

function resetBranchAndQty() {
    branchSelect.innerHTML = '<option value="">Select a branch</option>';
    quantityInput.value = '';
    quantityInput.removeAttribute('max');
    stockInfo.textContent = '';
}

productSelect.addEventListener('change', async function() {
    resetBranchAndQty();
    currentStockInfo.textContent = '';
    currentStockInfo.className = 'current-stock-info';

    const productId = this.value;
    if (!productId) return;

    try {
        const rows = await fetchAvailability(productId);

        // Show current branch stock
        const mine = rows.find(r => String(r.branch_id) === String(currentBranchId));
        if (mine) {
            currentStockInfo.innerHTML = `<i class="fas fa-info-circle"></i> Current stock in your branch: <strong>${mine.available_quantity}</strong>`;
            currentStockInfo.className = 'current-stock-info show';
        } else {
            currentStockInfo.innerHTML = `<i class="fas fa-exclamation-triangle"></i> No stock available in your branch`;
            currentStockInfo.className = 'current-stock-info show no-stock';
        }

        // Populate supplier branches (exclude current branch + only >0)
        const others = rows.filter(r => String(r.branch_id) !== String(currentBranchId) && Number(r.available_quantity) > 0);
        for (const r of others) {
            const opt = document.createElement('option');
            opt.value = r.branch_id;
            opt.textContent = `${r.branch_name} (Available: ${r.available_quantity})`;
            opt.setAttribute('data-stock', r.available_quantity);
            branchSelect.appendChild(opt);
        }

        if (branchSelect.children.length === 1) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.disabled = true;
            opt.textContent = 'No other branches have this product in stock';
            branchSelect.appendChild(opt);
        }
    } catch (e) {
        console.error(e);
        currentStockInfo.textContent = 'Failed to load stock availability';
        currentStockInfo.className = 'current-stock-info show no-stock';
    }
});

branchSelect.addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const available = selected ? selected.getAttribute('data-stock') : null;
    if (available) {
        quantityInput.max = available;
        stockInfo.textContent = `Maximum available: ${available}`;
    } else {
        quantityInput.removeAttribute('max');
        stockInfo.textContent = '';
    }
});
</script>

<style>
    /* Reuse existing styles from products/create.blade.php */
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

    .stock-info {
        display: block;
        margin-top: 4px;
        color: #666;
        font-size: 0.8rem;
    }

    .current-stock-info {
        display: block;
        margin-top: 8px;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .current-stock-info.show {
        opacity: 1;
        max-height: 50px;
        background: #e0f2fe;
        color: #0277bd;
        border: 1px solid #b3e5fc;
    }

    .current-stock-info.show.no-stock {
        background: #fff3e0;
        color: #f57c00;
        border: 1px solid #ffcc02;
    }

    .current-stock-info i {
        margin-right: 6px;
    }

    .error {
        color: #dc2626;
        font-size: 0.8rem;
        margin-top: 4px;
        display: block;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
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
    }

    .btn-theme-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-theme-danger {
        background: linear-gradient(135deg, #f87171, #dc2626);
        color: white;
    }
</style>
@endsection