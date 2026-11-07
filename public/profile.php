<?php
require_once __DIR__ . '/../includes/guard.php';

$userId = (int) $_SESSION['user_id'];
$user = q($conn, 'SELECT id, username, email, role, created_at FROM users WHERE id = ? LIMIT 1', 'i', $userId)->fetch_assoc();

if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h1>My Profile</h1>
    <p><strong>Username:</strong> <?= e($user['username']) ?></p>
    <p><strong>Email:</strong> <?= e($user['email']) ?></p>
    <p><strong>Role:</strong> <?= e($user['role']) ?></p>
    <p><strong>Member since:</strong> <?= e($user['created_at']) ?></p>
    <p><a href="index.php">Back to dashboard</a></p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
