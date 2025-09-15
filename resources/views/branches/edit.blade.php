<!-- Author: Clive Lee Ee Xuan -->
@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-container">
        <h1><i class="fas fa-edit"></i> Edit Branch</h1>
        <p>Update branch information</p>

        <div class="settings-grid">
            <div class="settings-card">
                <h2><i class="fas fa-store"></i> Branch Information</h2>
                <form method="POST" action="{{ route('branches.update', ['branch' => $branch['id']]) }}">
                    @csrf
                    @method('PUT')

                    <!-- Branch Name -->
                    <div class="form-group">
                        <label for="name">Branch Name</label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name', $branch['name'] ?? '') }}" 
                               required>
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Location -->
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" 
                               name="location" 
                               id="location" 
                               value="{{ old('location', $branch['location'] ?? '') }}" 
                               required>
                        @error('location') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-theme btn-theme-primary">
                            <i class="fas fa-save"></i> Update Branch
                        </button>
                        <a href="{{ route('branches.index') }}" class="btn-theme btn-theme-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Reuse existing styles */
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

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.9rem;
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