<?php
/**
 * info.php – Standalone listing of all submitted inputs from Supabase.
 *
 * This page connects directly to your Supabase project (no Laravel stack)
 * and displays every row stored in the `inputs` table that was populated
 * through the index.php form.
 */

$supabaseUrl = getenv('SUPABASE_URL') ?: 'https://vxmyfozpixzbobwnfziq.supabase.co';
$supabaseKey = getenv('SUPABASE_ANON_KEY') ?: '';

$inputs = [];
$error = '';

try {
    $endpoint  = rtrim($supabaseUrl, '/') . '/rest/v1/inputs';
    $ch = curl_init($endpoint);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: '        . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new RuntimeException('cURL error: ' . $curlErr);
    }
    if ($httpCode !== 200) {
        throw new RuntimeException("HTTP {$httpCode}: {$raw}");
    }

    $inputs = json_decode($raw, true) ?: [];
} catch (\Throwable $e) {
    $error = $e->getMessage();
}
?>
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

        /* Navigation link styled as button */
        a.back {
            color: #fff;
            text-decoration: none;
            background: #24b47e;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            display: inline-block;
        }
        a.back:hover {
            background: #1d9c6a;
            text-decoration: none;
        }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        th { background: #24b47e; color: #fff; text-align: left; padding: 12px 16px; }
        td { padding: 10px 16px; border-bottom: 1px solid #eee; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) td { background: #fafafa; }

        .empty { padding: 24px; text-align: center; color: #888; }
        .error-box { background: #fdecea; color: #c0392b; padding: 12px; border-radius: 6px; margin-bottom: 18px; }
        .count { color: #666; font-size: 14px; }
        .toolbar { margin-bottom: 18px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="toolbar">
        <a class="back" href="index.php">&laquo; Back to Input Form</a>
    </div>
    <h1>Submitted Input Records</h1>
    <span class="count"><?php echo count($inputs); ?> record(s) found in Supabase.</span>

    <?php if ($error): ?>
        <div class="error-box"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (empty($inputs)): ?>
        <table>
            <tr><th class="empty">No records yet</th></tr>
        </table>
    <?php else: ?>
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
            <?php foreach ($inputs as $i => $row): ?>
            <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo htmlspecialchars((string)$row['first_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int)$row['age']; ?></td>
                <td><?php echo htmlspecialchars((string)$row['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
