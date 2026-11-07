<?php
require_once __DIR__ . '/../includes/user_guard.php';

$uid = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$st, $sid] = parseSource($_POST['source'] ?? '');
    q($conn, 'INSERT INTO insights (user_id, source_type, source_id, title, content, tags) VALUES (?, ?, ?, ?, ?, ?)',
        'isisss', $uid, $st, $sid, trim($_POST['title']), trim($_POST['content']), tags($_POST['tags']));
    header('Location: insights.php');
    exit;
}

$insights = withSource($conn, $uid, 'insights');
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h2 class="panel-title">Add insight</h2>
    <form class="form-row" method="POST">
        <input class="field" type="text" name="title" placeholder="Title" required>
        <textarea class="field" name="content" placeholder="What did you understand?" required></textarea>
        <select class="field" name="source"><?= sourceOptions($conn, $uid) ?></select>
        <input class="field" type="text" name="tags" placeholder="Tags">
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</section>

<section class="panel">
    <h2 class="panel-title">Insights</h2>
    <?php while ($in = $insights->fetch_assoc()): ?>
        <article class="item-card">
            <h3 class="item-card__title"><?= e($in['title']) ?></h3>
            <?php if ($in['source_title']): ?><p class="item-card__meta">From <?= e($in['source_title']) ?></p><?php endif; ?>
            <p><?= e($in['content']) ?></p>
            <div class="item-actions"><?= delBtn('insights', $in['id']) ?></div>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
