<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroStore | Login</title>
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

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .login-container h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #667eea;
        }

        .login-container p {
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

        .btn-login {
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

        .btn-login:hover {
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
    <div class="login-container">
        <h1><i class="fas fa-bolt"></i> ElectroStore</h1>
        <p>Login to your account</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <span style="color:red; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password" required>
                @error('password')
                    <span style="color:red; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="form-group" style="display:flex; align-items:center;">
                <input type="checkbox" name="remember" id="remember" style="margin-right: 8px;" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Remember Me</label>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn-login">Login</button>

            <!-- Extra Links -->
            <div class="extra-links">
                @if (Route::has('password.request'))
                    <p><a href="{{ route('password.request') }}">Forgot Your Password?</a></p>
                @endif
                @if (Route::has('register'))
                    <p>Don’t have an account? <a href="{{ route('register') }}">Register</a></p>
                @endif
            </div>
        </form>
    </div>
</body>
</html>
