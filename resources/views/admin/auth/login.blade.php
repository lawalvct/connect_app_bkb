<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - Connect App</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: #FAFAFA;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        .login-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 48px 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            margin-bottom: 0;
        }

        .logo-top-center {
            position: absolute;
            top: 48px;
            left: 0;
            right: 0;
            z-index: 2;
            pointer-events: none;
        }

        .logo {
            max-width: 80px;
            height: auto;
            margin-top: -20px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .page-title {
            color: #333333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;

        }
        .page-subtitle {
            color: #666666;
            font-size: 14px;
            margin-bottom: 32px;
            text-align: center;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: center;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333333;
            font-size: 14px;
            text-align: left;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(162, 0, 48, 0.05);
            color: #333333;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #A20030;
            background: rgba(162, 0, 48, 0.08);
            box-shadow: 0 0 0 2px rgba(162, 0, 48, 0.1);
        }

        .form-input::placeholder {
            color: #999999;
        }

        .password-input-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #999999;
            font-size: 16px;
        }

        .forgot-password {
            text-align: center;
            margin-bottom: 24px;
        }

        .forgot-password a {
            color: #A20030;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            background: #A20030;
            color: #FFFFFF;
            border: none;
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: #8B0028;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(162, 0, 48, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            background: #A20030;
        }

        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid #FFFFFF;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: rgba(162, 0, 48, 0.05);
            border: 1px solid rgba(162, 0, 48, 0.2);
            color: #A20030;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: center;
        }

        /* Focus states for accessibility */
        .form-input:focus-visible {
            outline: 2px solid #A20030;
            outline-offset: 2px;
        }

        .login-btn:focus-visible {
            outline: 2px solid #A20030;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container logo-top-center">
            <img src="{{ asset('images/connect_logo.png') }}" alt="Connect App Logo" class="logo">
        </div>
        <div class="login-card">
            <h1 class="page-title">Admin Login</h1>
            <p class="page-subtitle">

                You will receive an OTP in your email after logging in.
            </p>

            @if(session('error'))
                <div class="error-message">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="error-message">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form id="loginForm" action="{{ route('admin.auth.login.post') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        required
                        autofocus
                        value="{{ old('email') }}"
                        placeholder="Techyabbey@gmail.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-container">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            required
                            placeholder="••••••••"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <span id="toggleIcon">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="alert('Contact administrator to reset password')">Forgot Password?</a>
                </div>

                <button type="submit" id="loginBtn" class="login-btn">
                    <div id="spinner" class="spinner"></div>
                    <span id="btnText">Login</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const btn = document.getElementById('loginBtn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');

            form.addEventListener('submit', function(e) {
                btn.disabled = true;
                spinner.style.display = 'inline-block';
                btnText.textContent = 'Logging in...';
            });

            window.addEventListener('pageshow', function() {
                btn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Login';
            });
        });

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>
