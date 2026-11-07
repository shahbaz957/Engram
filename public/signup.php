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
        // Role comes from the database default (student), not from the form.
        q($conn, 'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)', 'sss',
            $username, $email, password_hash($password, PASSWORD_DEFAULT));
        header('Location: login.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Create Account</h2>
    <?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="POST" action="signup.php">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <button type="submit">Create Account</button>
    </form>
    <p><a href="login.php">Already have an account?</a></p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
