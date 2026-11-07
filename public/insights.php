<?php
require_once __DIR__ . '/../includes/guard.php';

$uid = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$st, $sid] = parseSource($_POST['source'] ?? '');
    q($conn, "INSERT INTO insights (user_id, source_type, source_id, title, content, tags) VALUES (?, ?, ?, ?, ?, ?)",
        "isisss", $uid, $st, $sid, trim($_POST['title']), trim($_POST['content']), tags($_POST['tags']));
    header("Location: insights.php");
    exit;
}

$insights = withSource($conn, $uid, 'insights');
require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Add Insight</h2>
    <form method="POST">
        <input type="text" name="title" placeholder="Insight title" required>
        <textarea name="content" placeholder="What did you understand?" required></textarea>
        <select name="source"><?= sourceOptions($conn, $uid) ?></select>
        <input type="text" name="tags" placeholder="Tags (logic, ethics)">
        <button type="submit">Save Insight</button>
    </form>
</section>

<section>
    <h2>Insights</h2>
    <?php while ($in = $insights->fetch_assoc()): ?>
        <article>
            <strong><?= e($in['title']) ?></strong>
            <?php if ($in['source_title']): ?><em>(from <?= e($in['source_title']) ?>)</em><?php endif; ?>
            <p><?= e($in['content']) ?></p>
            <?= delBtn('insights', $in['id']) ?>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>