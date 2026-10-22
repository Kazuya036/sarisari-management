<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT id, name, username, password_hash, role, active FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && (int)$user['active'] === 1 && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        log_action($pdo, 'login', 'user', (int)$user['id'], 'Successful login');
        header('Location: index.php'); exit;
    }
    $error = 'Incorrect username or password, or the account is inactive.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in | TindahanHub</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login-page"><div class="login-art"><div class="login-brand"><span class="brand-mark large">T</span><span><strong>TindahanHub</strong><small>Everyday store operations, made simpler.</small></span></div><div class="login-message"><span class="pill">YOUR STORE, IN CONTROL</span><h1>A smarter way to run your <em>tindahan.</em></h1><p>Keep products organized, track sales, and understand your store at a glance.</p><div class="login-stats"><div><strong>Sales</strong><small>Quick checkout</small></div><div><strong>Stock</strong><small>Inventory clarity</small></div><div><strong>Reports</strong><small>Better decisions</small></div></div></div><div class="art-footer">Built for the heart of every neighborhood.</div></div>
<div class="login-panel"><form class="auth-card" method="post"><div class="mobile-brand"><span class="brand-mark">T</span><strong>TindahanHub</strong></div><span class="eyebrow">WELCOME BACK</span><h2>Sign in to your store</h2><p class="muted">Enter your account details to continue.</p><?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required autofocus placeholder="Enter your username"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password"><button class="btn primary full" type="submit">Sign in <span>→</span></button><p class="auth-note">Local store management · Secure sign-in</p></form></div></body></html>
