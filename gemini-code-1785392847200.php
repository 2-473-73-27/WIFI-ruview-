<?php
session_start();

// 1. DATABASE CONFIGURATION
$db_host = 'localhost';
$db_user = 'root';     // Change to your database username
$db_pass = '';         // Change to your database password
$db_name = 'sms_panel_db';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("<div style='color:red; font-family:sans-serif; padding:20px;'>Database Connection Error: " . $e->getMessage() . "</div>");
}

// 2. LOGOUT LOGIC
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 3. LOGIN PROCESS
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $user_input = trim($_POST['username']);
    $pass_input = trim($_POST['password']);

    if (!empty($user_input) && !empty($pass_input)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$user_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass_input, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $login_error = "Invalid username or password!";
        }
    } else {
        $login_error = "Please fill in all fields.";
    }
}

// 4. DISPLAY LOGIN PAGE (IF NOT LOGGED IN)
if (!isset($_SESSION['user_id'])):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Panel - Login</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #0f172a; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 30px; border-radius: 12px; width: 100%; max-width: 380px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h2 { text-align: center; color: #38bdf8; margin-top: 0; }
        input { width: 100%; padding: 12px; margin: 8px 0; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px; }
        button { width: 100%; padding: 12px; margin-top: 15px; background: #0284c7; border: none; color: #fff; font-weight: bold; border-radius: 6px; cursor: pointer; }
        button:hover { background: #0369a1; }
        .alert { background: #7f1d1d; color: #fca5a5; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <h2>SMS Portal Login</h2>
    <?php if ($login_error): ?><div class="alert"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login_btn">Sign In</button>
    </form>
</div>
</body>
</html>
<?php 
exit;
endif;

// 5. FETCH DASHBOARD DATA (IF LOGGED IN)
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_ranges = $pdo->query("SELECT COUNT(*) FROM ranges")->fetchColumn();
$total_logs = $pdo->query("SELECT COUNT(*) FROM sms_logs")->fetchColumn();

$ranges = $pdo->query("SELECT * FROM ranges ORDER BY id DESC LIMIT 10")->fetchAll();
$logs = $pdo->query("SELECT * FROM sms_logs ORDER BY datetime DESC LIMIT 10")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Panel - Dashboard</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #0f172a; color: #e2e8f0; margin: 0; padding: 25px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 15px; margin-bottom: 25px; }
        .navbar h2 { margin: 0; color: #38bdf8; }
        .btn-logout { background: #ef4444; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #1e293b; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #334155; }
        .stat-card h3 { margin: 0; font-size: 32px; color: #38bdf8; }
        .stat-card p { margin: 5px 0 0; color: #94a3b8; font-size: 14px; }
        .table-title { margin-top: 30px; color: #f1f5f9; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #334155; font-size: 14px; }
        th { background: #1e293b; color: #38bdf8; font-weight: bold; }
        tr:hover { background: #334155; }
        .empty-row { text-align: center; color: #64748b; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>SMS Manager Portal</h2>
    <div>
        <span>User: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= ucfirst($_SESSION['role']) ?>)</span>
        <a href="index.php?action=logout" class="btn-logout" style="margin-left:15px;">Logout</a>
    </div>
</div>

<div class="grid">
    <div class="stat-card">
        <h3><?= $total_users ?></h3>
        <p>Total Registered Users</p>
    </div>
    <div class="stat-card">
        <h3><?= $total_ranges ?></h3>
        <p>Active Termination Ranges</p>
    </div>
    <div class="stat-card">
        <h3><?= $total_logs ?></h3>
        <p>Total SMS Logs Received</p>
    </div>
</div>

<h3 class="table-title">Active Termination Ranges</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Range Code</th>
            <th>Prefix</th>
            <th>Test Number</th>
            <th>Currency</th>
            <th>Payout</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($ranges as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['range_code']) ?></td>
            <td><?= htmlspecialchars($r['prefix']) ?></td>
            <td><?= htmlspecialchars($r['test_number']) ?></td>
            <td><?= htmlspecialchars($r['currency']) ?></td>
            <td>$<?= number_format($r['payouts'], 4) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($ranges)): ?>
        <tr><td colspan="6" class="empty-row">No ranges configured yet.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h3 class="table-title">Recent SMS Traffic (Logs)</h3>
<table>
    <thead>
        <tr>
            <th>Date / Time</th>
            <th>Range Code</th>
            <th>Phone Number</th>
            <th>CLI</th>
            <th>Message Content</th>
            <th>Payout</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
            <td><?= $l['datetime'] ?></td>
            <td><?= htmlspecialchars($l['range_code']) ?></td>
            <td><?= htmlspecialchars($l['phone_number']) ?></td>
            <td><?= htmlspecialchars($l['cli']) ?></td>
            <td><?= htmlspecialchars($l['message']) ?></td>
            <td>$<?= number_format($l['payout'], 4) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
        <tr><td colspan="6" class="empty-row">No SMS CDR logs found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>