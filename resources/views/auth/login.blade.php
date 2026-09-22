<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart-Stock</title>
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
        }
        body.light-theme { background: #f1f5f9; }
        
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
            max-width: 420px;
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
            margin-bottom: 28px;
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
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        body.light-theme .form-group label { color: #475569; }
        .form-group input {
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
        body.light-theme .form-group input {
            background: #f8fafc;
            border-color: rgba(15,23,42,0.15);
            color: #0f172a;
        }
        .form-group input:focus {
            border-color: #3b82f6;
        }
        .form-group input::placeholder { color: #64748b; }
        body.light-theme .form-group input::placeholder { color: #94a3b8; }
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
        .field-error {
            display: block;
            color: #fca5a5;
            font-size: 12px;
            margin-top: 5px;
            min-height: 16px;
        }
        body.light-theme .field-error { color: #dc2626; }
        .form-group input.input-error {
            border-color: #ef4444;
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
        .success-message {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.3);
            color: #86efac;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        body.light-theme .success-message {
            background: rgba(22, 163, 74, 0.08);
            border-color: rgba(22, 163, 74, 0.25);
            color: #15803d;
        }
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .remember-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
            flex-shrink: 0;
        }
        .remember-row label {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 400;
            cursor: pointer;
            margin-bottom: 0;
            user-select: none;
        }
        body.light-theme .remember-row label { color: #64748b; }
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
        <a href="{{ route('home') }}" class="brand" title="Back to homepage">
            <img src="{{ asset('assets/stock-logo.png') }}" alt="Smart-Stock Logo" class="brand-mark">
            <div class="brand-text">
                <div class="brand-name">Smart-Stock</div>
                <div class="brand-subtitle">Inventory System</div>
            </div>
        </a>
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your inventory dashboard</p>
        </div>

        @if (session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" autocomplete="on" id="loginForm" novalidate>
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autofocus autocomplete="email">
                <span class="field-error" id="emailError"></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                <span class="field-error" id="passwordError"></span>
            </div>
            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <div class="footer-text">
            Don't have an account? <a href="{{ route('register') }}">Create one</a>
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

        // SS-42 + SS-60: Client-side form validation + remember last logged-in credentials
        (function () {
            const form          = document.getElementById('loginForm');
            if (!form) return;

            const emailInput    = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const rememberBox   = document.getElementById('remember');
            const emailError    = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // SS-60: On page load, restore saved credentials if "Remember me" was previously checked.
            // NOTE: Passwords stored in localStorage are readable by JS — this is a UX convenience
            // feature (same pattern as browser "remember password"), not a security mechanism.
            // The actual session security is handled server-side by Laravel.
            try {
                const savedEmail    = localStorage.getItem('smartStockLastEmail');
                const savedPassword = localStorage.getItem('smartStockLastPassword');
                const wasRemembered = localStorage.getItem('smartStockRemember') === '1';

                if (wasRemembered && savedEmail) {
                    // Only pre-fill when no server-side old() value is present
                    if (!emailInput.value.trim()) emailInput.value = savedEmail;
                    if (savedPassword)            passwordInput.value = savedPassword;
                    rememberBox.checked = true;
                }
            } catch (err) {}

            function setError(input, errorEl, msg) {
                errorEl.textContent = msg;
                input.classList.toggle('input-error', !!msg);
            }

            function clearError(input, errorEl) {
                setError(input, errorEl, '');
            }

            emailInput.addEventListener('input', function () {
                if (this.value.trim()) clearError(this, emailError);
            });

            passwordInput.addEventListener('input', function () {
                if (this.value.trim()) clearError(this, passwordError);
            });

            form.addEventListener('submit', function (e) {
                let valid = true;

                const emailVal = emailInput.value.trim();
                const passVal  = passwordInput.value.trim();

                if (!emailVal) {
                    setError(emailInput, emailError, 'Email address is required.');
                    valid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                    setError(emailInput, emailError, 'Please enter a valid email address.');
                    valid = false;
                } else {
                    clearError(emailInput, emailError);
                }

                if (!passVal) {
                    setError(passwordInput, passwordError, 'Password is required.');
                    valid = false;
                } else {
                    clearError(passwordInput, passwordError);
                }

                if (!valid) {
                    e.preventDefault();
                    return;
                }

                // SS-60: Save or clear credentials depending on checkbox state.
                try {
                    if (rememberBox && rememberBox.checked) {
                        localStorage.setItem('smartStockLastEmail',    emailVal);
                        localStorage.setItem('smartStockLastPassword', passwordInput.value);
                        localStorage.setItem('smartStockRemember',     '1');
                    } else {
                        localStorage.removeItem('smartStockLastEmail');
                        localStorage.removeItem('smartStockLastPassword');
                        localStorage.removeItem('smartStockRemember');
                    }
                } catch (err) {}
            });
        })();
    </script>
</body>
</html>
