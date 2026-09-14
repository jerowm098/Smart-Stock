<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inputs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
        }

        h1 {
            margin-top: 0;
            font-size: 24px;
            color: #222;
            text-align: center;
        }

        label {
            display: block;
            margin: 14px 0 6px;
            font-size: 14px;
            color: #444;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #24b47e;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            background: #24b47e;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1d9c6a;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .alert-success {
            background: #e6f7ef;
            color: #1d9c6a;
            border: 1px solid #b3e5d0;
        }

        .alert-error {
            background: #fdecea;
            color: #c0392b;
            border: 1px solid #f5c6c2;
        }

        .error {
            color: #c0392b;
            font-size: 12px;
            margin-top: 4px;
        }

        /* Navigation buttons at bottom of card */
        .btn-view-records {
            display: block;
            text-align: center;
            margin-top: 10px;
            padding: 10px 20px;
            background: #3a8fd4;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-view-records:hover {
            background: #2d7abf;
            color: #fff;
            text-decoration: none;
        }

        .btn-schema {
            display: block;
            text-align: center;
            margin-top: 10px;
            padding: 10px 20px;
            background: #e67e22;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-schema:hover {
            background: #d35400;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <div class="card">
        <h1>Inputs</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('inputs.store') }}" autocomplete="on">
            @csrf

            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name">
            @error('first_name') <div class="error">{{ $message }}</div> @enderror

            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
            @error('last_name') <div class="error">{{ $message }}</div> @enderror

            <label for="age">Age</label>
            <input type="number" id="age" name="age" value="{{ old('age') }}" min="1" max="150" required autocomplete="off">
            @error('age') <div class="error">{{ $message }}</div> @enderror

            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3" required autocomplete="street-address">{{ old('address') }}</textarea>
            @error('address') <div class="error">{{ $message }}</div> @enderror

            <button type="submit">Submit</button>
        </form>

        <a href="info.php" class="btn-view-records">View Submitted Records &raquo;</a>

        <a href="{{ route('schema.login') }}" class="btn-schema">Update database (seeded data)</a>
    </div>
</body>
</html>