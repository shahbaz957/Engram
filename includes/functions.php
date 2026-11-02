<?php
// Escape output (XSS protection)
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Prepare + bind + execute in one call. Returns a result for SELECT queries.
function q($conn, $sql, $types = '', ...$params) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// "logic, Ethics" -> ",logic,ethics,"
function tags($s) {
    $t = implode(',', array_filter(array_map('trim', explode(',', strtolower($s)))));
    return $t ? ",$t," : '';
}

// <option>s of books/resources, value like "book:3"
function sourceOptions($conn, $uid) {
    $out = '<option value="">No source</option>';
    foreach (['book' => 'books', 'resource' => 'resources'] as $type => $table) {
        $res = q($conn, "SELECT id, title FROM $table WHERE user_id = ? ORDER BY title", "i", $uid);
        while ($r = $res->fetch_assoc()) {
            $out .= '<option value="' . $type . ':' . $r['id'] . '">' . ucfirst($type) . ': ' . e($r['title']) . '</option>';
        }
    }
    return $out;
}

// "book:3" -> ['book', 3]
function parseSource($v) {
    $p = explode(':', $v . ':');
    return (in_array($p[0], ['book', 'resource']) && (int)$p[1] > 0) ? [$p[0], (int)$p[1]] : [null, null];
}

// Rows from insights/quotes/ideas with the title of their source book/resource
function withSource($conn, $uid, $table) {
    return q($conn, "SELECT t.*, COALESCE(b.title, r.title) AS source_title FROM $table t
        LEFT JOIN books b ON t.source_type = 'book' AND b.id = t.source_id
        LEFT JOIN resources r ON t.source_type = 'resource' AND r.id = t.source_id
        WHERE t.user_id = ? ORDER BY t.created_at DESC", "i", $uid);
}

// Every item in the vault (used by links and Connected Nodes)
function allItems($conn, $uid) {
    return q($conn, "SELECT 'book' AS type, id, title, tags FROM books WHERE user_id = ?
        UNION ALL SELECT 'resource', id, title, tags FROM resources WHERE user_id = ?
        UNION ALL SELECT 'idea', id, title, tags FROM ideas WHERE user_id = ?
        UNION ALL SELECT 'insight', id, title, tags FROM insights WHERE user_id = ?
        UNION ALL SELECT 'quote', id, LEFT(quote, 50), tags FROM quotes WHERE user_id = ?",
        "iiiii", $uid, $uid, $uid, $uid, $uid)->fetch_all(MYSQLI_ASSOC);
}

// Average pages/day over the last 7 days
function pace($conn, $uid) {
    $r = q($conn, "SELECT COALESCE(SUM(pages_read), 0) / 7 AS p FROM reading_logs
        WHERE user_id = ? AND log_date >= CURDATE() - INTERVAL 6 DAY", "i", $uid)->fetch_assoc();
    return round($r['p'], 1);
}

// Small POST delete button (handled by delete.php)
function delBtn($table, $id) {
    return '<form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm(\'Delete?\')">'
        . '<input type="hidden" name="table" value="' . $table . '"><input type="hidden" name="id" value="' . (int)$id . '">'
        . '<button>Delete</button></form>';
}