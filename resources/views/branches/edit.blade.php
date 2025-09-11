@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>Edit Branch</h1>
        <p class="page-subtitle">Update branch information</p>
    </div>

    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-store"></i> Branch Information</h3>
        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Branch Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $branch->name) }}" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" name="location" id="location" value="{{ old('location', $branch->location) }}" required>
                @error('location') <span class="error">{{ $message }}</span> @enderror
            </div>

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

    <style>
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2); }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .error { font-size: 0.8rem; color: #dc2626; }
    </style>
@endsection