<?php
require_once __DIR__ . '/../includes/guard.php';

$uid = (int) $_SESSION['user_id'];
// table => page to return to (also acts as the whitelist)
$pages = ['books' => 'books.php', 'resources' => 'resources.php', 'ideas' => 'ideas.php', 'insights' => 'insights.php',
          'quotes' => 'motivation.php', 'links' => 'links.php', 'idea_steps' => 'ideas.php'];
$table = $_POST['table'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pages[$table])) {
    $id = (int)$_POST['id'];
    q($conn, "DELETE FROM $table WHERE id = ? AND user_id = ?", "ii", $id, $uid);

    // polymorphic links have no foreign key, so clean them by hand
    if (!in_array($table, ['links', 'idea_steps'])) {
        $type = substr($table, 0, -1);
        q($conn, "DELETE FROM links WHERE user_id = ? AND ((source_type = ? AND source_id = ?) OR (target_type = ? AND target_id = ?))",
            "isisi", $uid, $type, $id, $type, $id);
    }
    header("Location: " . $pages[$table]);
    exit;
}
header("Location: index.php");
exit;