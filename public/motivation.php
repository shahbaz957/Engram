<?php
require_once __DIR__ . '/../includes/user_guard.php';

$uid = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$st, $sid] = parseSource($_POST['source'] ?? '');
    q($conn, 'INSERT INTO quotes (user_id, source_type, source_id, quote, author, tags) VALUES (?, ?, ?, ?, ?, ?)',
        'isisss', $uid, $st, $sid, trim($_POST['quote']), trim($_POST['author']) ?: 'Unknown', tags($_POST['tags']));
    header('Location: motivation.php');
    exit;
}

$quotes = withSource($conn, $uid, 'quotes');
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h2 class="panel-title">Save quote</h2>
    <form class="form-row" method="POST">
        <textarea class="field" name="quote" placeholder="Quote text…" required></textarea>
        <input class="field" type="text" name="author" placeholder="Author">
        <select class="field" name="source"><?= sourceOptions($conn, $uid) ?></select>
        <input class="field" type="text" name="tags" placeholder="Tags">
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</section>

<section class="panel">
    <h2 class="panel-title">Quotes</h2>
    <?php while ($q = $quotes->fetch_assoc()): ?>
        <article class="item-card">
            <blockquote>"<?= e($q['quote']) ?>" — <?= e($q['author']) ?></blockquote>
            <?php if ($q['source_title']): ?><p class="item-card__meta">From <?= e($q['source_title']) ?></p><?php endif; ?>
            <div class="item-actions"><?= delBtn('quotes', $q['id']) ?></div>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
