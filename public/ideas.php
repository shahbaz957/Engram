<?php
require_once __DIR__ . '/../includes/user_guard.php';

$uid = (int) $_SESSION['user_id'];
$statuses = ['Seed', 'Sprouting', 'Harvested'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ideaId = (int)($_POST['idea_id'] ?? 0);

    if ($action === 'add_idea' && trim($_POST['next_action']) !== '') {
        [$st, $sid] = parseSource($_POST['source'] ?? '');
        q($conn, 'INSERT INTO ideas (user_id, source_type, source_id, title, description, next_action, tags) VALUES (?, ?, ?, ?, ?, ?, ?)',
            'isissss', $uid, $st, $sid, trim($_POST['title']), trim($_POST['description']), trim($_POST['next_action']), tags($_POST['tags']));
    } elseif ($action === 'add_step') {
        q($conn, 'INSERT INTO idea_steps (idea_id, user_id, step_text) SELECT id, user_id, ? FROM ideas WHERE id = ? AND user_id = ?',
            'sii', trim($_POST['step_text']), $ideaId, $uid);
        q($conn, 'UPDATE ideas SET updated_at = NOW() WHERE id = ? AND user_id = ?', 'ii', $ideaId, $uid);
    } elseif ($action === 'done_step') {
        q($conn, 'UPDATE idea_steps SET is_done = 1, completed_at = NOW() WHERE id = ? AND user_id = ?', 'ii', (int) $_POST['step_id'], $uid);
        q($conn, "UPDATE ideas SET status = IF(status = 'Seed', 'Sprouting', status), updated_at = NOW() WHERE id = ? AND user_id = ?", 'ii', $ideaId, $uid);
    } elseif ($action === 'set_status' && in_array($_POST['status'], $statuses, true)) {
        q($conn, 'UPDATE ideas SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?', 'sii', $_POST['status'], $ideaId, $uid);
    }
    header('Location: ideas.php');
    exit;
}

$ideas = withSource($conn, $uid, 'ideas');
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h2 class="panel-title">Capture idea</h2>
    <form class="form-row" method="POST">
        <input type="hidden" name="action" value="add_idea">
        <input class="field" type="text" name="title" placeholder="Title" required>
        <input class="field" type="text" name="next_action" placeholder="Next action" required>
        <textarea class="field" name="description" placeholder="Description"></textarea>
        <select class="field" name="source"><?= sourceOptions($conn, $uid) ?></select>
        <input class="field" type="text" name="tags" placeholder="Tags">
        <button class="btn btn-primary" type="submit">Capture</button>
    </form>
</section>

<section class="panel">
    <h2 class="panel-title">Pipeline</h2>
    <?php while ($idea = $ideas->fetch_assoc()): ?>
        <article class="item-card">
            <h3 class="item-card__title"><?= e($idea['title']) ?> <span class="badge"><?= e($idea['status']) ?></span></h3>
            <p class="item-card__meta">
                Next: <strong><?= e($idea['next_action']) ?></strong>
                <?php if ($idea['source_title']): ?> · <?= e($idea['source_title']) ?><?php endif; ?>
            </p>
            <ul class="item-list">
                <?php $steps = q($conn, 'SELECT * FROM idea_steps WHERE idea_id = ? AND user_id = ? ORDER BY id', 'ii', $idea['id'], $uid); ?>
                <?php while ($st = $steps->fetch_assoc()): ?>
                    <li>
                        <?= $st['is_done'] ? '<s>' . e($st['step_text']) . '</s>' : e($st['step_text']) ?>
                        <?php if (!$st['is_done']): ?>
                            <form class="form-inline" method="POST">
                                <input type="hidden" name="action" value="done_step">
                                <input type="hidden" name="step_id" value="<?= $st['id'] ?>">
                                <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                                <button class="btn btn-sm btn-ghost" type="submit">Done</button>
                            </form>
                        <?php endif; ?>
                        <?= delBtn('idea_steps', $st['id']) ?>
                    </li>
                <?php endwhile; ?>
            </ul>
            <div class="item-actions">
                <form class="form-inline" method="POST">
                    <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                    <input class="field" type="text" name="step_text" placeholder="New step" required>
                    <button class="btn btn-sm btn-primary" name="action" value="add_step" type="submit">Add step</button>
                </form>
                <form class="form-inline" method="POST">
                    <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                    <input type="hidden" name="action" value="set_status">
                    <select class="field" name="status">
                        <?php foreach ($statuses as $s): ?><option <?= $s === $idea['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-ghost" type="submit">Update status</button>
                </form>
                <?= delBtn('ideas', $idea['id']) ?>
            </div>
        </article>
    <?php endwhile; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
