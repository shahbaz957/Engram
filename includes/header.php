<?php
$base = $base ?? '';
$home = $base . (isLoggedIn() && isAdmin() ? 'admin/index.php' : 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENGRAM — <?= isLoggedIn() && isAdmin() ? 'Admin' : 'Second Brain' ?></title>
    <link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css">
</head>
<body class="<?= isLoggedIn() && isAdmin() ? 'layout-admin' : 'layout-user' ?>">
<header>
    <div>
        <a href="<?= e($home) ?>">ENG<span>RAM</span></a>
        <nav aria-label="Main">
            <ul>
                <?php if (isLoggedIn() && isAdmin()): ?>
                    <li><a href="<?= e($base) ?>admin/index.php">Dashboard</a></li>
                    <li><a href="<?= e($base) ?>profile.php">Profile</a></li>
                    <li><a href="<?= e($base) ?>logout.php">Logout (<?= e($_SESSION['user_name'] ?? '') ?>)</a></li>
                <?php elseif (isLoggedIn()): ?>
                    <li><a href="<?= e($base) ?>index.php">Dashboard</a></li>
                    <li><a href="<?= e($base) ?>books.php">Books</a></li>
                    <li><a href="<?= e($base) ?>resources.php">Resources</a></li>
                    <li><a href="<?= e($base) ?>ideas.php">Ideas</a></li>
                    <li><a href="<?= e($base) ?>insights.php">Insights</a></li>
                    <li><a href="<?= e($base) ?>motivation.php">Quotes</a></li>
                    <li><a href="<?= e($base) ?>links.php">Links</a></li>
                    <li><a href="<?= e($base) ?>profile.php">Profile</a></li>
                    <li><a href="<?= e($base) ?>logout.php">Logout (<?= e($_SESSION['user_name'] ?? '') ?>)</a></li>
                <?php else: ?>
                    <li><a href="<?= e($base) ?>login.php">Login</a></li>
                    <li><a href="<?= e($base) ?>signup.php">Signup</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main>
