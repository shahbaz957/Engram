<?php
require_once __DIR__ . '/../../includes/guard.php';

requireRole('admin');

$totalUsers = (int) q($conn, 'SELECT COUNT(*) AS c FROM users')->fetch_assoc()['c'];
$userAccounts = (int) q($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'user'")->fetch_assoc()['c'];
$newUsers7 = (int) q($conn, 'SELECT COUNT(*) AS c FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY')->fetch_assoc()['c'];
$newUsers30 = (int) q($conn, 'SELECT COUNT(*) AS c FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY')->fetch_assoc()['c'];

$platform = [];
foreach (['books', 'resources', 'ideas', 'insights', 'quotes', 'links'] as $table) {
    $platform[$table] = (int) q($conn, "SELECT COUNT(*) AS c FROM $table")->fetch_assoc()['c'];
}
$totalPages = (int) q($conn, 'SELECT COALESCE(SUM(pages_read), 0) AS c FROM reading_logs')->fetch_assoc()['c'];
$vaultTotal = array_sum($platform);
$avgItems = $userAccounts > 0 ? round($vaultTotal / $userAccounts, 1) : 0;

$userRows = q($conn, "SELECT u.id, u.username, u.email, u.role, u.created_at,
    (SELECT COUNT(*) FROM books WHERE user_id = u.id) AS books,
    (SELECT COUNT(*) FROM resources WHERE user_id = u.id) AS resources,
    (SELECT COUNT(*) FROM ideas WHERE user_id = u.id) AS ideas,
    (SELECT COUNT(*) FROM insights WHERE user_id = u.id) AS insights,
    (SELECT COUNT(*) FROM quotes WHERE user_id = u.id) AS quotes,
    (SELECT COUNT(*) FROM links WHERE user_id = u.id) AS links
    FROM users u ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$emptyVaults = 0;
$activeThisWeek = 0;
foreach ($userRows as &$ur) {
    $ur['items'] = $ur['books'] + $ur['resources'] + $ur['ideas'] + $ur['insights'] + $ur['quotes'];
    if ($ur['role'] === 'user' && $ur['items'] === 0) {
        $emptyVaults++;
    }
    if (strtotime($ur['created_at']) >= strtotime('-7 days')) {
        $activeThisWeek++;
    }
}
unset($ur);

$recent = array_slice($userRows, 0, 5);

$signupLabels = [];
$signupByDay = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $signupLabels[] = date('M j', strtotime($d));
    $signupByDay[$d] = 0;
}
$res = q($conn, 'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM users
    WHERE created_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d');
while ($row = $res->fetch_assoc()) {
    $signupByDay[$row['d']] = (int) $row['c'];
}
$signupSeries = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $signupSeries[] = $signupByDay[$d] ?? 0;
}

$leaderboard = $userRows;
usort($leaderboard, static fn ($a, $b) => $b['items'] <=> $a['items']);
$topUsers = array_values(array_filter(array_slice($leaderboard, 0, 8), static fn ($u) => $u['items'] > 0));

$base = '../';
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="dash-hero admin-hero">
    <p class="eyebrow">Administrator</p>
    <h1>Welcome, <?= e($_SESSION['user_name']) ?></h1>
    <p class="lead">Platform overview — users and activity across ENGRAM. Vault tools are for regular accounts only.</p>
    <div class="pill-row">
        <span class="pill pill-admin">Platform admin</span>
        <span class="pill"><?= $totalUsers ?> accounts</span>
        <span class="pill accent-pill"><?= $newUsers7 ?> new this week</span>
    </div>
</section>

<section aria-label="Platform statistics">
    <h2>At a glance</h2>
    <div class="kpi-grid">
        <article class="kpi-card">
            <strong><?= $userAccounts ?></strong>
            <span>Vault users</span>
        </article>
        <article class="kpi-card accent">
            <strong><?= $newUsers30 ?></strong>
            <span>Signups (30d)</span>
        </article>
        <article class="kpi-card">
            <strong><?= $vaultTotal ?></strong>
            <span>Total vault items</span>
        </article>
        <article class="kpi-card">
            <strong><?= $avgItems ?></strong>
            <span>Avg items / user</span>
        </article>
        <article class="kpi-card">
            <strong><?= number_format($totalPages) ?></strong>
            <span>Pages read (all)</span>
        </article>
        <article class="kpi-card">
            <strong><?= $emptyVaults ?></strong>
            <span>Empty vaults</span>
        </article>
    </div>
</section>

<div class="admin-split">
    <section class="chart-section">
        <h2>Registrations (14 days)</h2>
        <canvas id="signupChart" height="120"></canvas>
    </section>

    <section>
        <h2>Content mix</h2>
        <div class="kpi-grid compact">
            <?php foreach ($platform as $name => $count): ?>
                <article class="kpi-card mini">
                    <strong><?= $count ?></strong>
                    <span><?= ucfirst($name) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section>
    <h2>Latest registrations</h2>
    <ul class="leaderboard">
        <?php foreach ($recent as $u): ?>
            <li>
                <span class="name"><?= e($u['username']) ?></span>
                <span class="meta"><?= e($u['role']) ?> · <?= e(date('d M Y', strtotime($u['created_at']))) ?> · <?= (int) $u['items'] ?> items</span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php if ($topUsers): ?>
<section>
    <h2>Most active users</h2>
    <ul class="leaderboard">
        <?php foreach ($topUsers as $u): ?>
            <li>
                <span class="name"><?= e($u['username']) ?></span>
                <span class="meta"><?= (int) $u['items'] ?> items · <?= (int) $u['links'] ?> links</span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="users-panel">
    <h2>All users</h2>
    <p class="lead-small"><?= $totalUsers ?> registered · <?= $platform['links'] ?> knowledge links platform-wide</p>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Items</th>
                    <th>Links</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userRows as $u): ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="role-tag role-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                        <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                        <td><?= (int) $u['items'] ?></td>
                        <td><?= (int) $u['links'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('signupChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($signupLabels) ?>,
        datasets: [{
            label: 'Signups',
            data: <?= json_encode($signupSeries) ?>,
            backgroundColor: 'rgba(124, 108, 255, 0.55)',
            borderColor: '#7c6cff',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
