<?php
require_once __DIR__ . '/../includes/user_guard.php';

$uid = (int) $_SESSION['user_id'];
$map = [];   // "book:3" => "Book: Title"
foreach (allItems($conn, $uid) as $i) $map[$i['type'] . ':' . $i['id']] = ucfirst($i['type']) . ': ' . $i['title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = $_POST['from'] ?? '';
    $to = $_POST['to'] ?? '';
    if ($from !== $to && isset($map[$from], $map[$to])) {
        if (strcmp($from, $to) > 0) [$from, $to] = [$to, $from];   // A-B and B-A become the same link
        [$ft, $fi] = explode(':', $from);
        [$tt, $ti] = explode(':', $to);
        q($conn, "INSERT IGNORE INTO links (user_id, source_type, source_id, target_type, target_id) VALUES (?, ?, ?, ?, ?)",
            "isisi", $uid, $ft, $fi, $tt, $ti);
    }
    header("Location: links.php");
    exit;
}

$links = q($conn, "SELECT * FROM links WHERE user_id = ? ORDER BY created_at DESC", "i", $uid);
require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Connect Two Notes</h2>
    <form method="POST">
        <select name="from" required>
            <option value="">From...</option>
            <?php foreach ($map as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <select name="to" required>
            <option value="">To...</option>
            <?php foreach ($map as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button type="submit">Link</button>
    </form>
</section>

<section>
    <h2>Your Knowledge Network</h2>
    <ul>
        <?php while ($l = $links->fetch_assoc()): ?>
            <li><?= e($map[$l['source_type'] . ':' . $l['source_id']] ?? '(deleted)') ?> ↔
                <?= e($map[$l['target_type'] . ':' . $l['target_id']] ?? '(deleted)') ?>
                <?= delBtn('links', $l['id']) ?></li>
        <?php endwhile; ?>
    </ul>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>