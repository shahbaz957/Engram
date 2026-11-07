<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = q($conn, 'SELECT id, username, email, password_hash, role FROM users WHERE email = ? LIMIT 1', 's', $email)->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $dest = ($user['role'] === 'admin') ? 'admin/index.php' : 'index.php';
        header('Location: ' . $dest);
        exit;
    }
    $error = 'Invalid email or password.';
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel auth-panel">
    <h2 class="panel-title">Sign in</h2>
    <?php if ($error !== ''): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <form class="form-stack" method="POST" action="login.php">
        <div>
            <label class="label" for="email">Email</label>
            <input class="field" type="email" id="email" name="email" autocomplete="username" required>
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input class="field" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary" type="submit">Login</button>
    </form>
    <p class="text-muted text-center">No account? <a href="signup.php">Create one</a></p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
