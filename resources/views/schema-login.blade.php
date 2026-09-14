<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supabase Schema Administration</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f4f6f8;
            font-family: Arial, sans-serif;
            color: #243447;
        }
        .card {
            width: 100%;
            max-width: 520px;
            padding: 34px;
            background: #fff;
            border: 1px solid #e3e8ee;
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(25, 45, 66, 0.08);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }
        .muted {
            margin: 0 0 26px;
            color: #66788a;
            line-height: 1.5;
        }
        label {
            display: block;
            margin: 0 0 7px;
            font-size: 14px;
            font-weight: 700;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #cfd8e3;
            border-radius: 7px;
            font-size: 15px;
        }
        input:focus {
            outline: 3px solid rgba(36, 180, 126, 0.18);
            border-color: #24b47e;
        }
        button, a.button {
            width: 100%;
            margin-top: 16px;
            padding: 12px 15px;
            border: 0;
            border-radius: 7px;
            background: #24b47e;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover, a.button:hover {
            background: #189868;
        }
        .small {
            margin-top: 18px;
            color: #718096;
            font-size: 13px;
            text-align: center;
        }
        .small a {
            color: #24b47e;
        }
        .btn-back {
            display: inline-block;
            margin-top: 16px;
            padding: 8px 16px;
            background: #6c757d;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Supabase Schema Administration</h1>
        <p class="muted">Sign in with your Supabase anon key to update or reset the Supabase schema from <code>database/dev.sql</code>.</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('schema.authenticate') }}" autocomplete="off">
            @csrf
            <label for="admin_token">Supabase Anon Key</label>
            <input id="admin_token" name="admin_token" type="password" required autofocus>
            @error('admin_token')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit">Sign in</button>
        </form>
        <p class="small">The token is stored only in an HTTP-only session cookie.</p>

        <a href="{{ route('inputs') }}" class="btn-back">← Back to Inputs</a>
    </main>
</body>
</html>
