<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif ($password !== ($_POST['confirm_password'] ?? '')) {
        $error = "Passwords do not match.";
    } elseif (q($conn, "SELECT id FROM users WHERE email = ? OR username = ?", "ss", $email, $username)->num_rows > 0) {
        $error = "Username or email already registered.";
    } else {
        q($conn, "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)", "sss",
            $username, $email, password_hash($password, PASSWORD_DEFAULT));
        header("Location: login.php");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Create Account</h2>
    <?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="POST">
        <label>Username</label> <input type="text" name="username" required>
        <label>Email</label> <input type="email" name="email" required>
        <label>Password</label> <input type="password" name="password" required>
        <label>Confirm Password</label> <input type="password" name="confirm_password" required>
        <button type="submit">Create Account</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>