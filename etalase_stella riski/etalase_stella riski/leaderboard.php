<?php
require_once 'config/kuis_db.php';
require_once 'middleware/auth_check.php';

check_login();

// Semua user (LEFT JOIN => yang belum upload tetap tampil dengan jumlah 0)
$rows = $pdo->query("SELECT u.id, u.nama, u.role, COUNT(p.id) AS total
                     FROM users u
                     LEFT JOIN projects p ON p.user_id = u.id
                     GROUP BY u.id, u.nama, u.role
                     ORDER BY total DESC, u.nama ASC")->fetchAll();

// Peringkat: jumlah upload sama => peringkat sama (1, 1, 3, ...)
$rank = 0;
$prevTotal = null;
foreach ($rows as $i => &$r) {
    $r['total'] = (int) $r['total'];
    if ($r['total'] !== $prevTotal) {
        $rank = $i + 1;
        $prevTotal = $r['total'];
    }
    $r['rank'] = $rank;
}
unset($r);

$totalProject = array_sum(array_column($rows, 'total'));
$myId = (int) $_SESSION['user_id'];
$myRow = null;
foreach ($rows as $r) {
    if ((int) $r['id'] === $myId) { $myRow = $r; break; }
}

// Podium hanya untuk yang punya upload (maks. 3 teratas)
$podium = array_values(array_filter(array_slice($rows, 0, 3), fn($r) => $r['total'] > 0));

$base   = '';
$active = 'leaderboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Etalase Digital Stella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/leaderboard.css">
</head>
<body>

<?php require __DIR__ . '/includes/navbar.php'; ?>

<div class="container my-4 my-md-5" style="max-width: 760px;">
    <div class="mb-4">
        <h2 class="fw-bold mb-1"><i class="bi bi-trophy-fill text-warning"></i> Leaderboard</h2>
        <p class="text-muted mb-0">Peringkat berdasarkan jumlah project yang diunggah</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="stat-box">
                <div class="stat-num"><?= count($rows) ?></div>
                <div class="stat-label">Peserta</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-box">
                <div class="stat-num"><?= $totalProject ?></div>
                <div class="stat-label">Total Project</div>
            </div>
        </div>
    </div>

    <?php if ($myRow): ?>
        <div class="my-rank mb-4">
            <i class="bi bi-person-circle"></i>
            Peringkat kamu: <b>#<?= $myRow['rank'] ?></b> &middot; <b><?= $myRow['total'] ?></b> project
        </div>
    <?php endif; ?>

    <?php if ($podium): ?>
        <div class="podium mb-4">
            <?php foreach ($podium as $r): ?>
                <div class="podium-item podium-<?= min($r['rank'], 3) ?>">
                    <div class="podium-medal"><?= $r['rank'] === 1 ? '🥇' : ($r['rank'] === 2 ? '🥈' : '🥉') ?></div>
                    <div class="podium-name"><?= e($r['nama']) ?></div>
                    <div class="podium-score"><?= $r['total'] ?> <small>project</small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card card-lb">
        <ul class="list-group list-group-flush lb-list">
            <?php foreach ($rows as $r): ?>
                <li class="list-group-item lb-row <?= (int) $r['id'] === $myId ? 'lb-me' : '' ?>">
                    <span class="lb-rank <?= $r['total'] > 0 && $r['rank'] <= 3 ? 'lb-rank-' . $r['rank'] : '' ?>"><?= $r['rank'] ?></span>
                    <span class="lb-name">
                        <?= e($r['nama']) ?>
                        <?php if ((int) $r['id'] === $myId): ?><span class="badge bg-primary ms-1">Kamu</span><?php endif; ?>
                    </span>
                    <span class="lb-count <?= $r['total'] === 0 ? 'lb-zero' : '' ?>"><?= $r['total'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
