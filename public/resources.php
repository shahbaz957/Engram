<?php
require_once __DIR__ . '/../includes/user_guard.php';

$uid = (int) $_SESSION['user_id'];
$types = ['Video', 'Article', 'Paper', 'Docs', 'Website'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url']);
    if (!in_array($_POST['type'], $types) || !preg_match('#^https?://#i', $url)) {
        $error = "Choose a type and enter a valid http(s) URL.";
    } else {
        q($conn, "INSERT INTO resources (user_id, title, type, creator, url, topic, summary, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            "isssssss", $uid, trim($_POST['title']), $_POST['type'], trim($_POST['creator']), $url,
            trim($_POST['topic']), trim($_POST['summary']), tags($_POST['tags']));
        header("Location: resources.php");
        exit;
    }
}

$resources = q($conn, "SELECT r.*,
    (SELECT COUNT(*) FROM insights WHERE user_id = ? AND source_type = 'resource' AND source_id = r.id) +
    (SELECT COUNT(*) FROM quotes WHERE user_id = ? AND source_type = 'resource' AND source_id = r.id) AS absorbed
    FROM resources r WHERE r.user_id = ? ORDER BY r.created_at DESC", "iii", $uid, $uid, $uid);

require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Add Resource</h2>
    <?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="POST">
        <input type="text" name="title" placeholder="Title" required>
        <select name="type" required>
            <option value="">Select type...</option>
            <?php foreach ($types as $t): ?><option><?= $t ?></option><?php endforeach; ?>
        </select>
        <input type="text" name="creator" placeholder="Creator">
        <input type="url" name="url" placeholder="https://..." required>
        <input type="text" name="topic" placeholder="Topic">
        <textarea name="summary" placeholder="Summary — what did you learn?"></textarea>
        <input type="text" name="tags" placeholder="Tags (logic, ethics)">
        <button type="submit">Save Resource</button>
    </form>
</section>

<section>
    <h2>Saved Resources</h2>
    <?php while ($r = $resources->fetch_assoc()): ?>
        <article>
            <strong>[<?= e($r['type']) ?>] <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['title']) ?></a></strong>
            by <?= e($r['creator']) ?> (<?= e($r['topic']) ?>)
            <?php if ($r['absorbed'] == 0): ?><span class="warn">[Not absorbed yet — 0 insights/quotes]</span><?php endif; ?>
            <p><?= e($r['summary']) ?></p>
            <?= delBtn('resources', $r['id']) ?>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>