<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = $_SESSION['user_id'];

// 1. Vault counts
$counts = [];
foreach (['books', 'resources', 'ideas', 'insights', 'quotes'] as $t) {
    $counts[$t] = q($conn, "SELECT COUNT(*) AS c FROM $t WHERE user_id = ?", "i", $uid)->fetch_assoc()['c'];
}

// 2. "1% Better" heatmap: activity per day over the last 84 days
$res = q($conn, "SELECT d, COUNT(*) AS c FROM (
        SELECT log_date AS d FROM reading_logs WHERE user_id = ?
        UNION ALL SELECT DATE(updated_at) FROM ideas WHERE user_id = ?
        UNION ALL SELECT DATE(updated_at) FROM insights WHERE user_id = ?
        UNION ALL SELECT DATE(updated_at) FROM quotes WHERE user_id = ?
        UNION ALL SELECT DATE(completed_at) FROM idea_steps WHERE user_id = ? AND completed_at IS NOT NULL
    ) a WHERE d >= CURDATE() - INTERVAL 83 DAY GROUP BY d", "iiiii", $uid, $uid, $uid, $uid, $uid);
$days = [];
while ($r = $res->fetch_assoc()) $days[$r['d']] = $r['c'];

$todayScore = $days[date('Y-m-d')] ?? 0;
$streak = 0;
$i = $todayScore > 0 ? 0 : 1;   // a streak is still alive if you were active yesterday
while (isset($days[date('Y-m-d', strtotime("-$i day"))])) { $streak++; $i++; }

// 3. Reading velocity (+ data for the Chart.js bar chart)
$res = q($conn, "SELECT log_date, SUM(pages_read) AS p FROM reading_logs
    WHERE user_id = ? AND log_date >= CURDATE() - INTERVAL 6 DAY GROUP BY log_date", "i", $uid);
$byDay = [];
while ($r = $res->fetch_assoc()) $byDay[$r['log_date']] = (int)$r['p'];
$labels = $data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $labels[] = date('D', strtotime($d));
    $data[] = $byDay[$d] ?? 0;
}
$pace = round(array_sum($data) / 7, 1);

// 4. Reading slump: Reading books with no log for 14+ days
$stalled = q($conn, "SELECT b.id, b.title FROM books b WHERE b.user_id = ? AND b.status = 'Reading'
    AND COALESCE((SELECT MAX(r.log_date) FROM reading_logs r WHERE r.book_id = b.id), b.started_at, DATE(b.created_at))
        < CURDATE() - INTERVAL 14 DAY", "i", $uid);

// 5. Dusty ideas: Seeds untouched for 30+ days
$dusty = q($conn, "SELECT title, next_action FROM ideas WHERE user_id = ? AND status = 'Seed'
    AND updated_at < NOW() - INTERVAL 30 DAY LIMIT 3", "i", $uid);

// 6. Daily revisit: one quote/insight, same one all day (seeded by the date)
$revisit = q($conn, "SELECT * FROM (
        SELECT 'Quote' AS type, quote AS body, author AS meta FROM quotes WHERE user_id = ?
        UNION ALL SELECT 'Insight', content, title FROM insights WHERE user_id = ?
    ) r ORDER BY RAND(TO_DAYS(CURDATE())) LIMIT 1", "ii", $uid, $uid)->fetch_assoc();

// 7. Connected nodes: a shared tag between two different kinds of notes
$byTag = [];
foreach (allItems($conn, $uid) as $it) {
    foreach (array_filter(explode(',', (string)$it['tags'])) as $t) $byTag[$t][$it['type']][] = $it;
}
$cands = [];
foreach ($byTag as $tag => $kinds) {
    if (count($kinds) > 1) {
        $k = array_rand($kinds, 2);
        $cands[] = [$tag, $kinds[$k[0]][0], $kinds[$k[1]][0]];
    }
}
$pair = $cands ? $cands[array_rand($cands)] : null;

require_once __DIR__ . '/../includes/header.php';
?>
<section>
    <h1>Welcome, <?= e($_SESSION['user_name']) ?></h1>
    <p>
        <strong>1% Better Today:</strong>
        <?= $todayScore > 0 ? "Active ($todayScore actions today)" : "No activity yet — log 5 pages or finish 1 idea step!" ?>
        | <strong>Streak:</strong> <?= $streak ?> day(s)
        | <strong>Reading velocity:</strong> <?= $pace ?> pages/day
    </p>
</section>

<section aria-label="Vault statistics">
    <h2>Vault Statistics</h2>
    <div>
        <?php foreach ($counts as $name => $c): ?>
            <article><strong><?= $c ?></strong> <span><?= ucfirst($name) ?></span></article>
        <?php endforeach; ?>
    </div>
</section>

<section>
    <h2>Last 12 Weeks</h2>
    <div class="heat">
        <?php for ($i = 83; $i >= 0; $i--): $d = date('Y-m-d', strtotime("-$i day")); $c = $days[$d] ?? 0; ?>
            <span class="cell l<?= min($c, 4) ?>" title="<?= $d ?>: <?= $c ?> actions"></span>
        <?php endfor; ?>
    </div>
    <h3>Pages read (last 7 days)</h3>
    <canvas id="pagesChart" height="70"></canvas>
</section>

<?php if ($revisit): ?>
<section>
    <h2>Daily Revisit (<?= e($revisit['type']) ?>)</h2>
    <p>"<?= e($revisit['body']) ?>" — <strong><?= e($revisit['meta']) ?></strong></p>
</section>
<?php endif; ?>

<?php if ($pair): ?>
<section>
    <h2>Connected Nodes (#<?= e($pair[0]) ?>)</h2>
    <p><?= e(ucfirst($pair[1]['type'])) ?>: <strong><?= e($pair[1]['title']) ?></strong>
       ↔ <?= e(ucfirst($pair[2]['type'])) ?>: <strong><?= e($pair[2]['title']) ?></strong></p>
    <p><a href="links.php">Link them</a></p>
</section>
<?php endif; ?>

<?php if ($stalled->num_rows > 0): ?>
<section>
    <h2>Reading Slump Alert (stalled 14+ days)</h2>
    <ul>
        <?php while ($sb = $stalled->fetch_assoc()): ?>
            <li><?= e($sb['title']) ?> — <a href="books.php">Resume or Abandon</a></li>
        <?php endwhile; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($dusty->num_rows > 0): ?>
<section>
    <h2>Dusty Ideas (untouched 30+ days)</h2>
    <ul>
        <?php while ($di = $dusty->fetch_assoc()): ?>
            <li><strong><?= e($di['title']) ?></strong> — next action: <?= e($di['next_action']) ?> (<a href="ideas.php">act on it</a>)</li>
        <?php endwhile; ?>
    </ul>
</section>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('pagesChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($labels) ?>, datasets: [{ label: 'Pages read', data: <?= json_encode($data) ?> }] }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>