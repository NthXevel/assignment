@extends('layouts.app')

@section('content')
    <div class="settings-page">
        <div class="settings-container">
            <h1><i class="fas fa-cog"></i> Settings</h1>
            <p>Manage your account preferences</p>

            <div class="settings-grid">
                <!-- Profile Section -->
                <div class="settings-card">
                    <h2><i class="fas fa-user"></i> Update Profile</h2>
                    <form action="{{ route('settings.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}"
                                class="form-control" required>
                            @error('username')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                                class="form-control" required>
                            @error('email')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn-action">Save Profile</button>
                    </form>
                </div>

                <!-- Password Section -->
                <div class="settings-card">
                    <h2><i class="fas fa-lock"></i> Change Password</h2>
                    <form action="{{ route('settings.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input id="current_password" type="password" name="current_password" class="form-control"
                                required>
                            @error('current_password')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input id="password" type="password" name="password" class="form-control" required>
                            @error('password')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                class="form-control" required>
                        </div>

                        <button type="submit" class="btn-action">Change Password</button>
                    </form>
                </div>

                <!-- Logout -->
                <div class="logout-card">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
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
            grid-template-columns: 1fr 1fr;
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
            margin-bottom: 15px;
            color: #333;
        }

        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #444;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            margin-top: 6px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .btn-action {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-logout {
            width: 100%;
            background: #e63946;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 6px 20px rgba(230, 57, 70, 0.3);
        }

        .btn-logout:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .logout-card {
            grid-column: span 2;
            text-align: center;
        }

        .error-text {
            color: red;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .logout-card {
                grid-column: span 1;
            }
        }
    </style>
@endsection