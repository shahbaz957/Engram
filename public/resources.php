<?php
require_once __DIR__ . '/../includes/user_guard.php';

$uid = (int) $_SESSION['user_id'];
$types = ['Video', 'Article', 'Paper', 'Docs', 'Website'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url']);
    if (!in_array($_POST['type'], $types) || !preg_match('#^https?://#i', $url)) {
        $error = 'Choose a type and enter a valid http(s) URL.';
    } else {
        q($conn, "INSERT INTO resources (user_id, title, type, creator, url, topic, summary, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            'isssssss', $uid, trim($_POST['title']), $_POST['type'], trim($_POST['creator']), $url,
            trim($_POST['topic']), trim($_POST['summary']), tags($_POST['tags']));
        header('Location: resources.php');
        exit;
    }
}

$resources = q($conn, "SELECT r.*,
    (SELECT COUNT(*) FROM insights WHERE user_id = ? AND source_type = 'resource' AND source_id = r.id) +
    (SELECT COUNT(*) FROM quotes WHERE user_id = ? AND source_type = 'resource' AND source_id = r.id) AS absorbed
    FROM resources r WHERE r.user_id = ? ORDER BY r.created_at DESC", 'iii', $uid, $uid, $uid);

require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h2 class="panel-title">Add resource</h2>
    <?php if ($error !== ''): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <form class="form-row" method="POST">
        <input class="field" type="text" name="title" placeholder="Title" required>
        <select class="field" name="type" required>
            <option value="">Type…</option>
            <?php foreach ($types as $t): ?><option><?= $t ?></option><?php endforeach; ?>
        </select>
        <input class="field" type="text" name="creator" placeholder="Creator">
        <input class="field" type="url" name="url" placeholder="https://…" required>
        <input class="field" type="text" name="topic" placeholder="Topic">
        <textarea class="field" name="summary" placeholder="Summary"></textarea>
        <input class="field" type="text" name="tags" placeholder="Tags">
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</section>

<section class="panel">
    <h2 class="panel-title">Saved resources</h2>
    <?php while ($r = $resources->fetch_assoc()): ?>
        <article class="item-card">
            <h3 class="item-card__title">
                <span class="badge"><?= e($r['type']) ?></span>
                <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['title']) ?></a>
            </h3>
            <p class="item-card__meta">
                <?= e($r['creator']) ?> · <?= e($r['topic']) ?>
                <?php if ($r['absorbed'] == 0): ?><span class="badge badge-warn">Not absorbed</span><?php endif; ?>
            </p>
            <?php if ($r['summary']): ?><p class="text-muted"><?= e($r['summary']) ?></p><?php endif; ?>
            <div class="item-actions"><?= delBtn('resources', $r['id']) ?></div>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
