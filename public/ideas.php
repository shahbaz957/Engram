<?php
require_once __DIR__ . '/../includes/guard.php';

$uid = (int) $_SESSION['user_id'];
$statuses = ['Seed', 'Sprouting', 'Harvested'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ideaId = (int)($_POST['idea_id'] ?? 0);

    if ($action === 'add_idea' && trim($_POST['next_action']) !== '') {
        [$st, $sid] = parseSource($_POST['source'] ?? '');
        q($conn, "INSERT INTO ideas (user_id, source_type, source_id, title, description, next_action, tags) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "isissss", $uid, $st, $sid, trim($_POST['title']), trim($_POST['description']), trim($_POST['next_action']), tags($_POST['tags']));
    } elseif ($action === 'add_step') {
        // INSERT...SELECT makes sure the idea belongs to this user
        q($conn, "INSERT INTO idea_steps (idea_id, user_id, step_text) SELECT id, user_id, ? FROM ideas WHERE id = ? AND user_id = ?",
            "sii", trim($_POST['step_text']), $ideaId, $uid);
        q($conn, "UPDATE ideas SET updated_at = NOW() WHERE id = ? AND user_id = ?", "ii", $ideaId, $uid);
    } elseif ($action === 'done_step') {
        q($conn, "UPDATE idea_steps SET is_done = 1, completed_at = NOW() WHERE id = ? AND user_id = ?", "ii", (int)$_POST['step_id'], $uid);
        // finishing a step moves a Seed to Sprouting and "touches" the idea (resets the dusty timer)
        q($conn, "UPDATE ideas SET status = IF(status = 'Seed', 'Sprouting', status), updated_at = NOW() WHERE id = ? AND user_id = ?", "ii", $ideaId, $uid);
    } elseif ($action === 'set_status' && in_array($_POST['status'], $statuses)) {
        q($conn, "UPDATE ideas SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?", "sii", $_POST['status'], $ideaId, $uid);
    }
    header("Location: ideas.php");
    exit;
}

$ideas = withSource($conn, $uid, 'ideas');
require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Capture Idea</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_idea">
        <input type="text" name="title" placeholder="Idea title" required>
        <input type="text" name="next_action" placeholder="Mandatory next action" required>
        <textarea name="description" placeholder="Description"></textarea>
        <select name="source"><?= sourceOptions($conn, $uid) ?></select>
        <input type="text" name="tags" placeholder="Tags (cs, startup)">
        <button type="submit">Capture Idea</button>
    </form>
</section>

<section>
    <h2>Idea Execution Pipeline</h2>
    <?php while ($idea = $ideas->fetch_assoc()): ?>
        <article>
            <h3><?= e($idea['title']) ?> [<?= e($idea['status']) ?>]</h3>
            <p><strong>Next action:</strong> <?= e($idea['next_action']) ?>
               <?php if ($idea['source_title']): ?> | <em>Source: <?= e($idea['source_title']) ?></em><?php endif; ?></p>

            <ul>
                <?php $steps = q($conn, "SELECT * FROM idea_steps WHERE idea_id = ? AND user_id = ? ORDER BY id", "ii", $idea['id'], $uid); ?>
                <?php while ($st = $steps->fetch_assoc()): ?>
                    <li>
                        <?= $st['is_done'] ? '<s>' . e($st['step_text']) . '</s>' : e($st['step_text']) ?>
                        <?php if (!$st['is_done']): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="done_step">
                                <input type="hidden" name="step_id" value="<?= $st['id'] ?>">
                                <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                                <button>Mark Done</button>
                            </form>
                        <?php endif; ?>
                        <?= delBtn('idea_steps', $st['id']) ?>
                    </li>
                <?php endwhile; ?>
            </ul>

            <form method="POST" style="display:inline">
                <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                <input type="text" name="step_text" placeholder="New execution step..." required>
                <button name="action" value="add_step">+ Step</button>
            </form>
            <form method="POST" style="display:inline">
                <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                <input type="hidden" name="action" value="set_status">
                <select name="status">
                    <?php foreach ($statuses as $s): ?><option <?= $s === $idea['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
                <button>Set Status</button>
            </form>
            <?= delBtn('ideas', $idea['id']) ?>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>