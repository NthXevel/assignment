<!-- Author: Clive Lee Ee Xuan -->
@extends('layouts.app')

@section('content')
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<div class="settings-page">
    <div class="settings-container">
        <h1><i class="fas fa-plus-circle"></i> Add New Branch</h1>
        <p>Fill in the details to add a new branch into the system</p>

        <div class="settings-grid">
            <div class="settings-card">
                <h2><i class="fas fa-store"></i> Branch Information</h2>
                <form method="POST" action="{{ route('branches.store') }}">
                    @csrf

                    <!-- Branch Name -->
                    <div class="form-group">
                        <label for="name">Branch Name</label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}" 
                               required>
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Location -->
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" 
                               name="location" 
                               id="location" 
                               value="{{ old('location') }}" 
                               required>
                        @error('location') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-theme btn-theme-primary">
                            <i class="fas fa-save"></i> Save Branch
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
    .btn-theme{
    padding:8px 12px;border:none;border-radius:8px;font-weight:600;
    display:inline-flex;align-items:center;gap:6px;text-decoration:none;
    cursor:pointer;transition:.2s box-shadow,.2s transform
    }
    .btn-theme:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.15)}
    .btn-theme-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}
    .btn-theme-danger{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
    .btn-theme:disabled{opacity:.6;cursor:not-allowed;transform:none;box-shadow:none}

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
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    /* Custom checkbox style */
    .checkbox-container {
        display: block;
        position: relative;
        padding-left: 35px;
        margin-bottom: 12px;
        cursor: pointer;
        font-size: 0.9rem;
        user-select: none;
    }

    .checkbox-container input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 25px;
        width: 25px;
        background-color: #eee;
        border-radius: 4px;
    }

    .checkbox-container:hover input ~ .checkmark {
        background-color: #ccc;
    }

    .checkbox-container input:checked ~ .checkmark {
        background-color: #667eea;
    }

    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    .checkbox-container input:checked ~ .checkmark:after {
        display: block;
    }

    .checkbox-container .checkmark:after {
        left: 9px;
        top: 5px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
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