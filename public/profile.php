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
<section class="panel panel--narrow">
    <h2 class="panel-title">Profile</h2>
    <p class="item-card__meta"><strong>Username</strong> · <?= e($user['username']) ?></p>
    <p class="item-card__meta"><strong>Email</strong> · <?= e($user['email']) ?></p>
    <p class="item-card__meta"><strong>Role</strong> · <span class="role-tag role-<?= e($user['role']) ?>"><?= e($user['role']) ?></span></p>
    <p class="item-card__meta"><strong>Joined</strong> · <?= e($user['created_at']) ?></p>
    <div class="item-actions item-actions--flush">
        <a class="btn btn-ghost" href="<?= isAdmin() ? 'admin/index.php' : 'index.php' ?>">Back to dashboard</a>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
