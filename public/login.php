<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = q($conn, "SELECT id, username, email, password_hash FROM users WHERE email = ?", "s", $email)->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        header("Location: index.php");
        exit;
    }
    $error = "Invalid email or password.";
}

require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Login</h2>
    <?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="POST">
        <label>Email</label> <input type="email" name="email" required>
        <label>Password</label> <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>