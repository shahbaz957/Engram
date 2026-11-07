<?php
// Layout header (auth + helpers should already be loaded).
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENGRAM — Second Brain</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <div>
        <a href="index.php">ENG<span>RAM</span></a>
        <nav aria-label="Main">
            <ul>
                <?php if (isLoggedIn()): ?>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="books.php">Books</a></li>
                    <li><a href="resources.php">Resources</a></li>
                    <li><a href="ideas.php">Ideas</a></li>
                    <li><a href="insights.php">Insights</a></li>
                    <li><a href="motivation.php">Quotes</a></li>
                    <li><a href="links.php">Links</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <li><a href="admin/users.php">Manage users</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout (<?= e($_SESSION['user_name'] ?? '') ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="signup.php">Signup</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main>
