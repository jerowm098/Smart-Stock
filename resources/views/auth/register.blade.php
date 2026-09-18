<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Smart-Stock</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;auto/chat
            margin-bottom: 28px;
        }
        .brand .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: contain;
            display: block;
        }
        .brand .brand-text {
            text-align: left;
        }
        .brand .brand-name {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }
        .brand .brand-subtitle {
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.2;
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-header h1 {
            color: #f8fafc;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #94a3b8;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #f8fafc;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .form-group input::placeholder {
            color: #64748b;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn:hover { opacity: 0.9; }
        .btn:active { transform: scale(0.98); }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.15);
            margin-top: 12px;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: #64748b;
            font-size: 13px;
        }
        .footer-text a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .footer-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand">
            <img src="{{ asset('assets/stock-logo.png') }}" alt="Smart-Stock Logo" class="brand-mark">
            <div class="brand-text">
                <div class="brand-name">Smart-Stock</div>
                <div class="brand-subtitle">Inventory System</div>
            </div>
        </div>
        <div class="login-header">
            <h1>Create Account</h1>
            <p>Get started with your inventory dashboard</p>
        </div>

        @if ($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}">
            @csrf
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <div class="footer-text">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>
</body>
</html>
