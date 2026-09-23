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
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        body.light-theme { background: #f3f4f6; }
        
        /* THEME TOGGLE */
        .theme-toggle-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.05);
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
        }
        .theme-toggle-btn:hover { background: rgba(255,255,255,0.1); color: #e2e8f0; }
        body.light-theme .theme-toggle-btn { border-color: rgba(15,23,42,0.1); background: rgba(15,23,42,0.05); color: #64748b; }
        body.light-theme .theme-toggle-btn:hover { background: rgba(15,23,42,0.1); color: #0f172a; }
        
        .login-container {
            position: relative;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 760px;
        }
        body.light-theme .login-container {
            background: #ffffff;
            border-color: rgba(15,23,42,0.08);
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
            text-decoration: none;
        }
        .brand:hover .brand-name { color: #cbd5e1; }
        body.light-theme .brand:hover .brand-name { color: #334155; }
        .brand .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: contain;
            display: block;
        }
        body:not(.light-theme) .brand-mark { filter: brightness(0) invert(1); }
        .brand .brand-text { text-align: left; }
        .brand .brand-name {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }
        body.light-theme .brand .brand-name { color: #0f172a; }
        .brand .brand-subtitle {
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.2;
        }
        body.light-theme .brand .brand-subtitle { color: #64748b; }
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
        body.light-theme .login-header h1 { color: #0f172a; }
        .login-header p {
            color: #94a3b8;
            font-size: 14px;
        }
        body.light-theme .login-header p { color: #64748b; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        body.light-theme .form-group label { color: #475569; }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #f8fafc;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.15s;
            outline: none;
        }
        body.light-theme .form-group input,
        body.light-theme .form-group select {
            background: #f8fafc;
            border-color: rgba(15,23,42,0.15);
            color: #0f172a;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3b82f6;
        }
        .form-group input::placeholder { color: #64748b; }
        body.light-theme .form-group input::placeholder { color: #94a3b8; }

        /* Two-column form layout */
        form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 18px;
        }
        .form-group.full,
        form .btn {
            grid-column: 1 / -1;
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
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
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
        body.light-theme .footer-text { color: #94a3b8; }
        .footer-text a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .footer-text a:hover { text-decoration: underline; }

        @media (max-width: 760px) {
            .login-container { max-width: 520px; padding: 32px; }
            form { grid-template-columns: 1fr; }
            .form-group.full,
            form .btn { grid-column: auto; }
        }
        @media (max-width: 480px) {
            body { padding: 16px; }
            .login-container { padding: 28px 20px; border-radius: 14px; }
            .brand { margin-bottom: 20px; }
            .login-header { margin-bottom: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <button type="button" class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle theme">
            <svg id="themeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
        </button>
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ asset('assets/stock-logo.png') }}" alt="Smart-Stock Logo" class="brand-mark">
            <div class="brand-text">
                <div class="brand-name">Smart-Stock</div>
                <div class="brand-subtitle">Inventory System</div>
            </div>
        </a>
        <div class="login-header">
            <h1>Create Account</h1>
            <p>Get started with your inventory dashboard</p>
        </div>

        @if ($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}" autocomplete="on">
            @csrf
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="John" required autofocus autocomplete="given-name">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" required autocomplete="family-name">
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="johndoe" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select a role</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="cashier" {{ old('role') == 'cashier' ? 'selected' : '' }}>Cashier</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
            </div>
            <div class="form-group full">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <div class="footer-text">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>

    <script>
        (function () {
            let saved = 'dark';
            try { saved = localStorage.getItem('smartStockTheme') || 'dark'; } catch (e) {}
            document.body.classList.toggle('light-theme', saved === 'light');
            updateThemeIcon(saved === 'light');
        })();

        function toggleTheme() {
            const isLight = document.body.classList.contains('light-theme');
            const newTheme = isLight ? 'dark' : 'light';
            document.body.classList.toggle('light-theme', !isLight);
            updateThemeIcon(!isLight);
            try { localStorage.setItem('smartStockTheme', newTheme); } catch (e) {}
        }

        function updateThemeIcon(isLight) {
            const icon = document.getElementById('themeIcon');
            if (!icon) return;
            if (isLight) {
                icon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
            } else {
                icon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
            }
        }
    </script>
</body>
</html>
