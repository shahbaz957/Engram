<?php
require_once __DIR__ . '/../includes/guard.php';

$uid = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$st, $sid] = parseSource($_POST['source'] ?? '');
    q($conn, "INSERT INTO quotes (user_id, source_type, source_id, quote, author, tags) VALUES (?, ?, ?, ?, ?, ?)",
        "isisss", $uid, $st, $sid, trim($_POST['quote']), trim($_POST['author']) ?: 'Unknown', tags($_POST['tags']));
    header("Location: motivation.php");
    exit;
}

$quotes = withSource($conn, $uid, 'quotes');
require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Save Quote</h2>
    <form method="POST">
        <textarea name="quote" placeholder="Quote text..." required></textarea>
        <input type="text" name="author" placeholder="Author">
        <select name="source"><?= sourceOptions($conn, $uid) ?></select>
        <input type="text" name="tags" placeholder="Tags (stoicism, ethics)">
        <button type="submit">Save Quote</button>
    </form>
</section>

<section>
    <h2>Quotes</h2>
    <?php while ($q = $quotes->fetch_assoc()): ?>
        <article>
            <blockquote>"<?= e($q['quote']) ?>" — <?= e($q['author']) ?></blockquote>
            <?php if ($q['source_title']): ?><em>from <?= e($q['source_title']) ?></em><?php endif; ?>
            <?= delBtn('quotes', $q['id']) ?>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>