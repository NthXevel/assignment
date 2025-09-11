@extends('layouts.app')

@section('content')
    <div class="settings-page">
        <div class="settings-container">
            <h1><i class="fas fa-user-edit"></i> Edit User</h1>
            <p>Update user account information and permissions</p>

            <div class="settings-grid">
                <div class="settings-card">
                    <h2>User Details</h2>

                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Username --}}
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}"
                                required>
                            @error('username')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Branch (only admin can change) --}}
                        @if(auth()->user()->role === 'admin')
                            <div class="form-group">
                                <label for="branch_id">Branch</label>
                                <select id="branch_id" name="branch_id">
                                    <option value="">-- Select Branch --</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        <div class="form-actions">
                            <button type="submit" class="btn-theme btn-theme-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="{{ route('users.index') }}" class="btn-theme btn-theme-danger">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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