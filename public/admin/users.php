<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$users = q($conn, 'SELECT id, username, email, role FROM users ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>
<section>
    <h1>Manage Users</h1>
    <p>Only administrators can view this page.</p>
    <p><a href="../index.php">Back to dashboard</a></p>

    <?php if (!$users): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int) $user['id'] ?></td>
                        <td><?= e($user['username']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['role']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
