<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 40px 20px;
        }
        .wrap { max-width: 900px; margin: 0 auto; }
        h1 { color: #222; }
        a.back { color: #24b47e; text-decoration: none; }
        a.back:hover { text-decoration: underline; }

        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        th { background: #24b47e; color: #fff; text-align: left; padding: 12px 16px; }
        td { padding: 10px 16px; border-bottom: 1px solid #eee; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) td { background: #fafafa; }

        .empty { padding: 24px; text-align: center; color: #888; }
        .error-box { background: #fdecea; color: #c0392b; padding: 12px; border-radius: 6px; margin-bottom: 18px; }
        .count { color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class="wrap">
    <p><a class="back" href="{{ route('inputs') }}">&laquo; Back to input form</a></p>
    <h1>Submitted Input Records</h1>
    <span class="count">{{ count($rows) }} record(s) found in Supabase.</span>

    @if(session('error'))
        <div class="error-box">{{ session('error') }}</div>
    @endif

    @if(empty($rows))
        <table>
            <tr><th class="empty">No records yet</th></tr>
        </table>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Age</th>
                <th>Address</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['id'] }}</td>
                <td>{{ e($row['first_name']) }}</td>
                <td>{{ e($row['last_name']) }}</td>
                <td>{{ $row['age'] }}</td>
                <td>{{ e($row['address']) }}</td>
                <td>{{ $row['created_at'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
</body>
</html>
