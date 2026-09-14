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
            padding: 28px 20px;
            background: #f4f6f8;
            font-family: Arial, sans-serif;
            color: #243447;
        }
        .wrap {
            max-width: 820px;
            margin: 0 auto;
        }
        .card {
            padding: 32px;
            background: #fff;
            border: 1px solid #e3e8ee;
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(25, 45, 66, 0.08);
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }
        .muted {
            margin: 0;
            color: #66788a;
            line-height: 1.55;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 170px;
        }
        button, a.button {
            padding: 11px 14px;
            border: 0;
            border-radius: 7px;
            background: #24b47e;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover, a.button:hover {
            background: #189868;
        }
        .danger {
            background: #d94b4b;
        }
        .danger:hover {
            background: #bd3838;
        }
        .secondary {
            background: #60758a;
        }
        .secondary:hover {
            background: #4f6276;
        }
        .alert {
            margin-bottom: 20px;
            padding: 13px 15px;
            border-radius: 7px;
            font-size: 14px;
        }
        .alert-success {
            background: #e6f7ef;
            color: #17734f;
            border: 1px solid #b8e5d0;
        }
        .alert-error {
            background: #fdecea;
            color: #a52d2d;
            border: 1px solid #f4c4c4;
        }
        .section {
            margin-top: 26px;
            padding-top: 24px;
            border-top: 1px solid #e3e8ee;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 19px;
        }
        .description {
            margin: 0 0 18px;
            color: #66788a;
            line-height: 1.55;
        }
        code {
            padding: 2px 5px;
            background: #eef2f5;
            border-radius: 4px;
            color: #34495e;
        }
        .warning {
            margin-top: 16px;
            padding: 13px 15px;
            background: #fff5e6;
            border: 1px solid #f2d39a;
            border-radius: 7px;
            color: #805614;
            line-height: 1.5;
            font-size: 14px;
        }
        form {
            margin-top: 18px;
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
        @media (max-width: 620px) {
            .header {
                display: block;
            }
            .actions {
                margin-top: 18px;
            }
            .card {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <div class="header">
                <div>
                    <h1>Supabase Schema Administration</h1>
                    <p class="muted">
                        Run the current <code>database/dev.sql</code> against Supabase. This is intended for
                        adding or changing tables and policies in development.
                    </p>
                </div>
                <div class="actions">
                    <form method="POST" action="{{ route('schema.update') }}">
                        @csrf
                        <button type="submit">Update Schema</button>
                    </form>
                    <form method="POST" action="{{ route('schema.reset') }}" onsubmit="return confirm('Reset the Supabase database? This deletes the public schema and all data in it.');">
                        @csrf
                        <button type="submit" class="danger">Reset Schema</button>
                    </form>
                    <form method="POST" action="{{ route('schema.logout') }}">
                        @csrf
                        <button type="submit" class="secondary">Sign out</button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <div class="section">
                <h2>What each action does</h2>
                <p class="description">
                    <strong>Update Schema</strong> executes the SQL in <code>database/dev.sql</code>.
                    Add new tables with <code>create table if not exists</code>, and add changes to existing
                    tables with <code>alter table ... add column if not exists</code>.
                </p>
                <p class="description">
                    <strong>Reset Schema</strong> deletes the entire <code>public</code> schema, recreates it,
                    and then executes <code>database/dev.sql</code>. This removes all application data in that schema.
                </p>
                <div class="warning">
                    Be careful: the reset action is destructive. Do not use it against a database that contains
                    data you need to keep.
                </div>
            </div>
        </section>
        <p class="small">Protected by the <code>SUPABASE_SCHEMA_ADMIN_TOKEN</code> administrator session.</p>
    </main>
</body>
</html>
