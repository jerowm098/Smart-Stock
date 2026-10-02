{{--
    The sign-in card — the single source of truth for the login form.

    Included once, by the homepage only:
      - Homepage modal:  @include('home-login-modal', ['asModal' => true])

    There is no standalone /login page — that route redirects to /home — so the
    modal is the only sign-in surface in the app. Posting to
    route('login.post') keeps credential handling, validation and the role-based
    redirect in AuthController.

    Only login-scoped classes live here — no body/* rules — so dropping this
    into the homepage cannot restyle the page behind the modal.

    BRD (Account Management): sign-in is username + password + submit only.
    No "remember me", no registration link, no password recovery — an Admin
    provisions accounts and resets passwords from User Management.
--}}
@php($asModal = $asModal ?? false)

<style>
    /* THEME TOGGLE — standalone page only. The homepage has its own in the
       sticky header, so a second control here would fight it over the same
       localStorage key. */
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
        /* Opaque, not translucent. The old rgba(255,255,255,0.03) only read as
           a raised panel because the standalone page put it over a flat
           #0f172a body. In the modal it sits over the blurred homepage, and 3%
           white over blurred content is effectively see-through. #1e293b is
           the app's own dark surface and composites to almost exactly what the
           old value looked like on the standalone page, so that screen is
           unchanged while the modal becomes solid. */
        background: #1e293b;
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 16px;
        padding: 40px;
        width: 100%;
        max-width: 420px;
    }
    body.light-theme .login-container {
        background: #ffffff;
        border-color: rgba(15,23,42,0.08);
    }
    /* Modal: the overlay centres this card, so it only needs to fill the
       overlay's padding and play the pop-in. Backgrounds are restated per
       theme because the homepage carries its own `body.light-theme` rules at
       far higher specificity — without this the card can be overridden
       translucent, and a see-through card over the blurred page is exactly
       what this guards against. */
    .login-container.as-modal {
        padding: 32px 28px 28px;
        background: #1e293b;
        border-color: rgba(255,255,255,0.10);
        animation: loginModalPop 0.25s cubic-bezier(0.34,1.56,0.64,1);
    }
    body.light-theme .login-container.as-modal {
        background: #ffffff;
        border-color: rgba(15,23,42,0.08);
    }
    @keyframes loginModalPop {
        from { opacity: 0; transform: scale(0.92) translateY(10px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    /* Close affordance replaces the theme toggle in modal mode. */
    .login-close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 32px; height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 1px solid transparent;
        color: #94a3b8;
        cursor: pointer;
    }
    .login-close:hover { background: rgba(255,255,255,0.06); color: #e2e8f0; }
    body.light-theme .login-close { color: #64748b; }
    body.light-theme .login-close:hover { background: rgba(15,23,42,0.05); color: #0f172a; }

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
    /* Same inline SVG mark as the site headers and hero, so the sign-in
       surface shows the identical logo. The old PNG needed an invert filter
       and a scale(1.5) to counter its internal padding. */
    .brand .brand-mark {
        width: 70px;
        height: 56px;
        flex: 0 0 auto;
        display: block;
    }
    .brand .brand-mark .mk-up { fill: #22c55e; }
    .brand .brand-mark .mk-down { fill: #ef4444; }
    .brand .brand-mark .mk-axis { fill: none; stroke: #94a3b8; stroke-width: 4; stroke-linecap: square; }
    .brand .brand-mark .mk-arrow { fill: none; stroke: #22c55e; stroke-width: 6; stroke-linecap: round; stroke-linejoin: round; }
    body.light-theme .brand .brand-mark .mk-up { fill: #16a34a; }
    body.light-theme .brand .brand-mark .mk-down { fill: #dc2626; }
    body.light-theme .brand .brand-mark .mk-axis { stroke: #64748b; }
    body.light-theme .brand .brand-mark .mk-arrow { stroke: #16a34a; }
    .brand .brand-text { text-align: left; }
    /* Both lines share one rule so they can never drift apart again. Only the
           text content differs — the second line reads "Stock". */
        .brand .brand-name,
        .brand .brand-subtitle {
            color: #f8fafc;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: 0.01em;
        }
        body.light-theme .brand .brand-name,
        body.light-theme .brand .brand-subtitle { color: #0f172a; }
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
    /* Scoped under .login-container on purpose. The bare `.btn` /
       `.btn-primary` names are generic enough to collide with the homepage's
       own hero buttons, and because the modal is injected into that page the
       later, broader rule would win — its `display: inline-flex` turned the
       submit label into a flex item pinned to the left edge instead of
       centred. Qualifying these selectors keeps the card self-contained no
       matter which page includes it. */
    .login-container .form-group { margin-bottom: 20px; }
    .login-container .form-group label {
        display: block;
        color: #cbd5e1;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 6px;
    }
    body.light-theme .login-container .form-group label { color: #475569; }
    .login-container .form-group input {
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
    body.light-theme .login-container .form-group input {
        background: #f8fafc;
        border-color: rgba(15,23,42,0.15);
        color: #0f172a;
    }
    .login-container .form-group input:focus {
        border-color: #3b82f6;
    }
    .login-container .form-group input::placeholder { color: #64748b; }
    body.light-theme .login-container .form-group input::placeholder { color: #94a3b8; }
    /* display:block + text-align:center rather than a flex box: the label is a
       single text node, and flex would need justify-content to centre it —
       which is exactly the property the homepage was clobbering. */
    .login-container .btn {
        display: block;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        transition: opacity 0.15s;
    }
    .login-container .btn:hover { opacity: 0.9; }
    .login-container .btn-primary {
        display: block;
        background: #2563eb;
        color: #fff;
    }
    .login-container .field-error {
        display: block;
        color: #fca5a5;
        font-size: 12px;
        margin-top: 5px;
        min-height: 16px;
    }
    body.light-theme .login-container .field-error { color: #dc2626; }
    .login-container .form-group input.input-error {
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
    body.light-theme .error-message {
        background: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.25);
        color: #b91c1c;
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

<div class="login-container{{ $asModal ? ' as-modal' : '' }}">
    @if ($asModal)
        {{-- Escape key and backdrop clicks both call closeLoginModal(), which
             the homepage defines. Never rendered on the standalone page. --}}
        <button type="button" class="login-close" onclick="closeLoginModal()" aria-label="Close sign in dialog">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    @else
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
    @endif

    <a href="{{ route('home') }}" class="brand" title="Back to homepage">
        <svg class="brand-mark" viewBox="0 0 100 80" role="img" aria-label="Smart Stock">
            <path class="mk-axis" d="M6 6v66h88"/>
            <rect class="mk-up" x="16" y="50" width="9" height="22" rx="1.5"/>
            <rect class="mk-up" x="29" y="38" width="9" height="34" rx="1.5"/>
            <rect class="mk-down" x="42" y="45" width="9" height="27" rx="1.5"/>
            <rect class="mk-up" x="55" y="31" width="9" height="41" rx="1.5"/>
            <rect class="mk-up" x="68" y="23" width="9" height="49" rx="1.5"/>
            <polyline class="mk-arrow" points="13,62 30,48 46,55 62,34 78,13"/>
            <polyline class="mk-arrow" points="67,11 80,11 80,24"/>
        </svg>
        <div class="brand-text">
            <div class="brand-name">SMART</div>
            <div class="brand-subtitle">Stock</div>
        </div>
    </a>

    <div class="login-header">
        {{-- id only in modal mode: the homepage overlay points aria-labelledby
             here. The standalone page has no dialog wrapper to reference it. --}}
        <h1{{ $asModal ? ' id="loginModalHeading"' : '' }}>Welcome Back</h1>
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
            <label for="username">Username</label>
            {{-- BRD (Account Management): sign in uses the assigned username. --}}
            <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Enter your username" autocomplete="username">
            <span class="field-error" id="usernameError"></span>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
            <span class="field-error" id="passwordError"></span>
        </div>
        {{-- BRD (Account Management) Usability: "The login screen shall consist
             only of username, password, and a submit button with no distracting
             elements." The "Remember me" checkbox was removed to match. --}}
        <button type="submit" class="btn btn-primary">Sign In</button>
    </form>

    {{-- BRD (Account Management): no self-service registration and no emailed
         password recovery — an Admin provisions accounts and resets passwords
         from the User Management screen. --}}
    <div class="footer-text">
        Need access? Ask the store administrator.
    </div>
</div>

<script>
    // SS-42: Client-side form validation.
    // BRD (Account Management) keeps the login screen to username, password and
    // a submit button, so there is no "remember me" state to persist here.
    // Runs in both modes — the modal is this same markup.
    (function () {
        const form          = document.getElementById('loginForm');
        if (!form) return;

        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const usernameError = document.getElementById('usernameError');
        const passwordError = document.getElementById('passwordError');

        function setError(input, errorEl, msg) {
            errorEl.textContent = msg;
            input.classList.toggle('input-error', !!msg);
        }

        function clearError(input, errorEl) {
            setError(input, errorEl, '');
        }

        usernameInput.addEventListener('input', function () {
            if (this.value.trim()) clearError(this, usernameError);
        });

        passwordInput.addEventListener('input', function () {
            if (this.value.trim()) clearError(this, passwordError);
        });

        form.addEventListener('submit', function (e) {
            let valid = true;

            const usernameVal = usernameInput.value.trim();
            const passVal     = passwordInput.value.trim();

            if (!usernameVal) {
                setError(usernameInput, usernameError, 'Username is required.');
                valid = false;
            } else {
                clearError(usernameInput, usernameError);
            }

            if (!passVal) {
                setError(passwordInput, passwordError, 'Password is required.');
                valid = false;
            } else {
                clearError(passwordInput, passwordError);
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    })();
</script>
