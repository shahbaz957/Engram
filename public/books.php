<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['book_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $status = in_array($_POST['status'], ['Want to Read', 'Reading', 'Completed']) ? $_POST['status'] : 'Want to Read';
        $total = (int)$_POST['total_pages'] ?: null;
        q($conn, "INSERT INTO books (user_id, title, author, status, total_pages, started_at, tags) VALUES (?, ?, ?, ?, ?, CURDATE(), ?)",
            "isssis", $uid, trim($_POST['title']), trim($_POST['author']), $status, $total, tags($_POST['tags']));
    } elseif ($action === 'log') {
        $pages = (int)$_POST['pages_read'];
        if ($pages > 0 && q($conn, "SELECT id FROM books WHERE id = ? AND user_id = ?", "ii", $id, $uid)->num_rows > 0) {
            q($conn, "INSERT INTO reading_logs (user_id, book_id, pages_read, log_date) VALUES (?, ?, ?, CURDATE())", "iii", $uid, $id, $pages);
            // MySQL runs SET left to right: status/finished_at already see the new current_page
            q($conn, "UPDATE books SET
                current_page = IF(total_pages IS NULL, current_page + ?, LEAST(total_pages, current_page + ?)),
                status = IF(total_pages IS NOT NULL AND current_page >= total_pages, 'Completed', 'Reading'),
                finished_at = IF(status = 'Completed', CURDATE(), NULL)
                WHERE id = ? AND user_id = ?", "iiii", $pages, $pages, $id, $uid);
        }
    } elseif ($action === 'abandon') {
        q($conn, "UPDATE books SET status = 'Abandoned' WHERE id = ? AND user_id = ?", "ii", $id, $uid);
    }
    header("Location: books.php");
    exit;
}

$pace = pace($conn, $uid);
$books = q($conn, "SELECT b.*,
    (SELECT COUNT(*) FROM insights WHERE user_id = ? AND source_type = 'book' AND source_id = b.id) +
    (SELECT COUNT(*) FROM quotes WHERE user_id = ? AND source_type = 'book' AND source_id = b.id) AS absorbed
    FROM books b WHERE b.user_id = ? ORDER BY b.updated_at DESC", "iii", $uid, $uid, $uid);

require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Add Book</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="author" placeholder="Author">
        <input type="number" name="total_pages" placeholder="Total Pages" min="1">
        <select name="status">
            <option>Want to Read</option><option>Reading</option><option>Completed</option>
        </select>
        <input type="text" name="tags" placeholder="Tags (logic, cs)">
        <button type="submit">Save Book</button>
    </form>
</section>

<section>
    <h2>Your Books <small>(velocity: <?= $pace ?> pages/day)</small></h2>
    <?php while ($b = $books->fetch_assoc()): ?>
        <article>
            <strong><?= e($b['title']) ?></strong> by <?= e($b['author']) ?>
            [<?= e($b['status']) ?>] (<?= $b['current_page'] ?> / <?= $b['total_pages'] ?? '?' ?> pages)

            <?php if ($b['status'] === 'Completed' && $b['absorbed'] == 0): ?>
                <span class="error">[Consumed but not absorbed — 0 insights/quotes!]</span>
            <?php endif; ?>

            <?php if ($b['status'] === 'Reading' && $b['total_pages'] > $b['current_page'] && $pace > 0): ?>
                | Est. finish: <?= date('d M Y', strtotime('+' . ceil(($b['total_pages'] - $b['current_page']) / $pace) . ' days')) ?>
            <?php endif; ?>

            <form method="POST" style="display:inline">
                <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                <input type="number" name="pages_read" placeholder="+ pages" min="1" style="width:80px" required>
                <button name="action" value="log">Log Reading</button>
                <?php if ($b['status'] === 'Reading'): ?><button name="action" value="abandon">Abandon</button><?php endif; ?>
            </form>
            <?= delBtn('books', $b['id']) ?>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>