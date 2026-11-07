<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (q($conn, 'SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1', 'ss', $email, $username)->num_rows > 0) {
        $error = 'Username or email already registered.';
    } else {
        q($conn, 'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)', 'sss',
            $username, $email, password_hash($password, PASSWORD_DEFAULT));
        header('Location: login.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel auth-panel">
    <h2 class="panel-title">Create account</h2>
    <?php if ($error !== ''): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <form class="form-stack" method="POST" action="signup.php">
        <div>
            <label class="label" for="username">Username</label>
            <input class="field" type="text" id="username" name="username" required>
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input class="field" type="email" id="email" name="email" required>
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input class="field" type="password" id="password" name="password" required>
        </div>
        <div>
            <label class="label" for="confirm_password">Confirm password</label>
            <input class="field" type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button class="btn btn-primary" type="submit">Sign up</button>
    </form>
    <p class="text-muted text-center">Have an account? <a href="login.php">Login</a></p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
