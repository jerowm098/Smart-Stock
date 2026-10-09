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
    /* 8px, not 20px: the error line below each input already contributes
       ~21px of reserved space, so a 20px margin made the gap above the next
       label 37.7px against the 5.5px below it — the label read as a caption
       for the field above. 8px keeps the two gaps close while still leaving
       room between an error message and the label that follows it. */
    .login-container .form-group { margin-bottom: 8px; }
    /* The submit button has no label above it, so the plain 8px group margin
       left it crowding the password error. Every other element in the form is
       preceded by a label row (~17px tall), so the last group gets that same
       17px added on top of the 8px to keep the rhythm before the button. */
    .login-container .form-group:last-of-type { margin-bottom: 25px; }
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
    /* Leading icon inside the field — same treatment as the User Accounts
       form. The wrapper is what the icon positions against, so the input
       itself keeps its full width. */
    .login-container .input-wrap { position: relative; }
    .login-container .input-icon-left {
        position: absolute; top: 50%; left: 12px; transform: translateY(-50%);
        width: 16px; height: 16px; display: inline-flex;
        align-items: center; justify-content: center;
        color: #64748b; pointer-events: none;
    }
    .login-container .input-icon-left svg { width: 16px; height: 16px; display: block; }
    .login-container .input-wrap.has-icon-left input { padding-left: 40px; }
    body.light-theme .login-container .input-icon-left { color: #94a3b8; }
    /* Password reveal toggle — same affordance as the User Accounts form. */
    .login-container .password-wrap { position: relative; }
    .login-container .password-wrap input { padding-right: 38px; }
    .login-container .password-eye {
        position: absolute; top: 50%; right: 6px; transform: translateY(-50%);
        width: 28px; height: 28px; border-radius: 7px; border: none;
        background: transparent; color: #64748b; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0;
    }
    .login-container .password-eye:hover { background: rgba(148,163,184,0.15); color: #e2e8f0; }
    body.light-theme .login-container .password-eye { color: #94a3b8; }
    body.light-theme .login-container .password-eye:hover { background: rgba(15,23,42,0.06); color: #0f172a; }
    .login-container .password-eye svg { width: 16px; height: 16px; display: block; }
    /* inline-flex + justify-content:center rather than display:block with
       text-align:center — the lock icon and the label are two separate nodes
       now, and flex is what keeps them on one centred line. The homepage's
       own `.btn-primary` also sets inline-flex, so this matches its shape
       while the scoped rule still wins on the properties that matter. */
    .login-container .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
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
    .login-container .btn svg { width: 15px; height: 15px; display: block; flex-shrink: 0; }
    .login-container .btn:hover { opacity: 0.9; }
    .login-container .btn-primary {
        background: #2563eb;
        color: #fff;
    }
    /* The error line always reserves its height, so revealing a message never
       shifts the form. The reserved space is what the group's margin has to
       account for: the visible gap above the next label is
       (group margin) + (error margin-top) + (error height), which is why the
       margin below is smaller than the label's own 6px. */
    .login-container .field-error {
        display: block;
        color: #fca5a5;
        font-size: 12px;
        margin-top: 5px;
        min-height: 16px;
    }
    body.light-theme .login-container .field-error { color: #dc2626; }
    /* !important is required: the light-theme input rule above is more
       specific, so without it the red border never shows in light mode.
       Same approach as the User Accounts form. */
    .login-container .form-group input.input-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
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
             here. The standalone page has no dialog wrapper to reference it.
             Written as a conditional attribute rather than an interpolated
             string — `{{ }}` escapes quotes, which turned the id into the
             literal text `"loginModalHeading"` and broke the reference. --}}
        <h1 @if($asModal) id="loginModalHeading" @endif>Welcome Back</h1>
        <p>Sign in to your Username or Email Accounts</p>
    </div>

    {{-- Server-side rejections are surfaced on the field itself rather than in
         a banner or a popup: the message is handed to the script below via
         data-login-flash, which paints the field highlight and clears it again
         after the same 2.5s linger the client-side errors use. --}}
    <form method="POST" action="{{ route('login.post') }}" autocomplete="on" id="loginForm" novalidate
          @if ($errors->any()) data-login-flash="error" data-login-flash-message="{{ $errors->first() }}" @endif>
        @csrf
        <div class="form-group">
            <label for="username">Username or Email</label>
            {{-- BRD (Account Management): sign in uses the assigned username; email accepted as fallback. --}}
            <div class="input-wrap has-icon-left">
                <span class="input-icon-left" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="testadmin or testadmin@gmail.com" autocomplete="username">
            </div>
            <span class="field-error" id="usernameError"></span>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrap">
                <input type="password" id="password" name="password" placeholder="••••••" autocomplete="current-password">
                <button type="button" class="password-eye" onclick="toggleLoginPassword(this)" title="Show password" aria-label="Show password"></button>
            </div>
            <span class="field-error" id="passwordError"></span>
        </div>
        {{-- BRD (Account Management) Usability: "The login screen shall consist
             only of username, password, and a submit button with no distracting
             elements." The "Remember me" checkbox was removed to match. --}}
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Sign In
        </button>
    </form>

    {{-- BRD (Account Management): no self-service registration and no emailed
         password recovery — an Admin provisions accounts and resets passwords
         from the User Management screen. --}}
    <div class="footer-text">
        Need access? Ask the store administrator.
    </div>
</div>

<script>
    // Password reveal toggle. Kept local to this partial rather than reusing
    // the User Accounts helper, which lives in that page's own script block.
    const LOGIN_EYE_OPEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const LOGIN_EYE_CLOSED = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

    function paintLoginEye(btn) {
        const input = btn.parentElement ? btn.parentElement.querySelector('input') : null;
        const showing = input && input.type === 'text';
        btn.innerHTML = showing ? LOGIN_EYE_CLOSED : LOGIN_EYE_OPEN;
        btn.title = showing ? 'Hide password' : 'Show password';
        btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
    }

    function toggleLoginPassword(btn) {
        const input = btn.parentElement ? btn.parentElement.querySelector('input') : null;
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        paintLoginEye(btn);
        input.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.login-container .password-eye').forEach(paintLoginEye);
    });

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

        // Errors are a temporary cue rather than a sticky state: they clear the
        // moment the user clicks anywhere. Mirrors the guard on the User
        // Accounts form.
        function clearAllErrors() {
            clearError(usernameInput, usernameError);
            clearError(passwordInput, passwordError);
        }

        // Any click — a field, the card, or the dimmed backdrop — dismisses the
        // highlight. The click that submits runs before `submit`, so errors
        // raised by that same click still survive.
        document.addEventListener('click', clearAllErrors);

        usernameInput.addEventListener('input', function () {
            if (this.value.trim()) clearError(this, usernameError);
        });

        passwordInput.addEventListener('input', function () {
            if (this.value.trim()) clearError(this, passwordError);
        });

        form.addEventListener('submit', function (e) {
            const messages = [];

            const usernameVal = usernameInput.value.trim();
            const passVal     = passwordInput.value;

            if (!usernameVal) {
                setError(usernameInput, usernameError, 'Username or email is required.');
                messages.push('Username or email is required.');
            } else {
                clearError(usernameInput, usernameError);
            }

            if (!passVal.trim()) {
                setError(passwordInput, passwordError, 'Password is required.');
                messages.push('Password is required.');
            } else if (passVal.length < 6) {
                setError(passwordInput, passwordError, 'Password must be at least 6 characters.');
                messages.push('Password must be at least 6 characters.');
            } else {
                clearError(passwordInput, passwordError);
            }

            if (messages.length) {
                e.preventDefault();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const flash = document.querySelector('[data-login-flash]');
            if (!flash) return;
            const msg = (flash.getAttribute('data-login-flash-message') || '').trim();
            if (!msg) return;
            // A server-side rejection lands on a fresh page load, so the field
            // highlight has to be re-applied here.
            const first = document.querySelector('.login-container .field-error');
            if (first && !first.textContent.trim()) {
                const input = first.parentElement.querySelector('input');
                if (input) setError(input, first, msg);
            }
        });
    })();
</script>
