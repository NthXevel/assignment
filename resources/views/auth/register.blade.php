<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroStore | Register</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .register-container h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #667eea;
        }

        .register-container p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #444;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            margin-top: 8px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .btn-register {
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

        .btn-register:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .extra-links {
            margin-top: 20px;
            font-size: 0.85rem;
        }

        .extra-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <h1><i class="fas fa-bolt"></i> ElectroStore</h1>
        <p>Create your account</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            {{-- Username --}}
            <div class="form-group">
                <label for="username">{{ __('Username') }}</label>
                <input id="username" type="text" class="form-control @error('username') is-invalid @enderror"
                    name="username" value="{{ old('username') }}" required autofocus>
                @error('username')
                    <span style="color:red; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>

            {{-- Email --}}
            <div class="form-group">
                <label for="email">{{ __('Email Address') }}</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                    value="{{ old('email') }}" required>
                @error('email')
                    <span style="color:red; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>

            {{-- Branch --}}
            <div class="form-group">
                <label for="branch_id">{{ __('Branch') }}</label>
                <select id="branch_id" name="branch_id" class="form-control @error('branch_id') is-invalid @enderror"
                    required>
                    <option value="">-- Select Branch --</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <span style="color:red; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>

            {{-- Role --}}
            <div class="form-group">
                <label for="role">{{ __('Role') }}</label>
                <select id="role" name="role" class="form-control @error('role') is-invalid @enderror" required>
                    <option value="">-- Select Role --</option>
                    <option value="stock_manager" {{ old('role') == 'stock_manager' ? 'selected' : '' }}>Stock Manager
                    </option>
                    <option value="branch_manager" {{ old('role') == 'branch_manager' ? 'selected' : '' }}>Branch Manager
                    </option>
                    <option value="order_creator" {{ old('role') == 'order_creator' ? 'selected' : '' }}>Order Creator
                    </option>
                </select>
                @error('role')
                    <span style="color:red; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>

            {{-- Password --}}
            <div class="form-group">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                    name="password" required>
                @error('password')
                    <span style="color:red; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="form-group">
                <label for="password-confirm">{{ __('Confirm Password') }}</label>
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn-register">{{ __('Register') }}</button>

            <div class="extra-links">
                <p>Already have an account? <a href="{{ route('login') }}">Login</a></p>
            </div>
        </form>
    </div>
</body>

</html>