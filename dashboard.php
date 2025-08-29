<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// DB connection
// Make sure db.php defines $conn = new mysqli(...)
// and (optionally) sets charset to utf8mb4.
include 'db.php';

// Set timezone to match your deployment (South Africa)
date_default_timezone_set('Africa/Johannesburg');

// Extract user info
$user    = $_SESSION['user'];
$badge_id = $user['badge_id'];
$name     = $user['name'];
$rank     = $user['rank'];

// ---------- Fetch crime stats (from cases) ----------
$stats = [
    'open'    => 0,
    'ongoing' => 0,
    'cold'    => 0,
    'closed'  => 0
];

$statsSql = "
    SELECT
        COALESCE(SUM(CASE WHEN status IN ('Ongoing','Cold') THEN 1 ELSE 0 END),0) AS open_cases,
        COALESCE(SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END),0)          AS ongoing_cases,
        COALESCE(SUM(CASE WHEN status = 'Cold' THEN 1 ELSE 0 END),0)             AS cold_cases,
        COALESCE(SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END),0)           AS closed_cases
    FROM cases
";
if ($res = $conn->query($statsSql)) {
    if ($row = $res->fetch_assoc()) {
        $stats['open']    = (int)$row['open_cases'];
        $stats['ongoing'] = (int)$row['ongoing_cases'];
        $stats['cold']    = (int)$row['cold_cases'];
        $stats['closed']  = (int)$row['closed_cases'];
    }
    $res->free();
}

// ---------- Recent activity (cases + protection orders/summons) ----------
$recent = [];
$recentSql = "
    SELECT 'Case' AS type, case_id AS id, status, updated_at
    FROM cases
    UNION ALL
    SELECT 'Protection Order' AS type, summons_id AS id, status, updated_at
    FROM summons
    ORDER BY updated_at DESC
    LIMIT 5
";
if ($ra = $conn->query($recentSql)) {
    while ($r = $ra->fetch_assoc()) {
        $recent[] = $r;
    }
    $ra->free();
}

// Helper to format "time ago"
function time_ago($datetime) {
    $t = new DateTime($datetime);
    $now = new DateTime('now');
    $diff = $now->getTimestamp() - $t->getTimestamp();

    if ($diff < 60)   return $diff . ' sec ago';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    if ($diff < 172800) return 'Yesterday';
    if ($diff < 2592000) return floor($diff/86400) . ' days ago';
    return $t->format('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SAPS Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #163a67; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2.5rem; font-weight: bold; margin: 10px 0; color: #4CAF50; }
        .stat-label { font-size: 0.9rem; color: #bbb; }
        .welcome-header { display:flex; justify-content: space-between; align-items:center; margin-bottom:20px; }
        .user-info { text-align:right; font-size:0.9rem; }
        .quick-actions { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:15px; margin:25px 0; }
        .action-card { background:#1d4b85; padding:15px; border-radius:8px; text-align:center; transition: transform 0.2s; color: #fff; text-decoration: none; }
        .action-card:hover { transform: translateY(-5px); background:#2568c7; }
        .recent-activity { margin-top:30px; background:#122544; padding:20px; border-radius:8px; }
        .activity-item { padding:10px; border-bottom:1px solid #2a4a75; display:flex; justify-content:space-between; color:#eee; }
        .activity-item:last-child { border-bottom:none; }
        .logo { height: 48px; vertical-align: middle; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        a.btn-danger { display:inline-block; background:#b33; color:#fff; padding:10px 14px; border-radius:6px; text-decoration:none; }
    </style>
</head>
<body>
<div class="container">
    <div class="welcome-header">
        <div>
            <img src="logo.png" alt="SAPS Logo" class="logo">
            <h2>Dashboard</h2>
        </div>
        <div class="user-info">
            <p>Welcome, <strong><?= htmlspecialchars($name) ?></strong> (<?= htmlspecialchars($rank) ?>)</p>
            <p>Badge ID: <?= htmlspecialchars($badge_id) ?></p>
            <a href="logout.php" style="color:#bbb;font-size:0.8rem;">Logout</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['open']) ?></div>
            <div class="stat-label">Open Cases</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['ongoing']) ?></div>
            <div class="stat-label">Ongoing Cases</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['cold']) ?></div>
            <div class="stat-label">Cold Cases</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['closed']) ?></div>
            <div class="stat-label">Closed Cases</div>
        </div>
    </div>

    <div class="quick-actions">
        <a href="open_case.php" class="action-card"><h3>Open New Case</h3><p>Create a new case file</p></a>
        <a href="ongoing_cases.php" class="action-card"><h3>Ongoing Cases</h3><p>View active investigations</p></a>
        <a href="cold_cases.php" class="action-card"><h3>Cold Cases</h3><p>Review unsolved cases</p></a>
        <a href="closed_cases.php" class="action-card"><h3>Closed Cases</h3><p>View resolved cases</p></a>
        <a href="protection_orders.php" class="action-card"><h3>Protection Orders</h3><p>Manage protection orders</p></a>
        <a href="file_protection_order.php" class="action-card"><h3>File New Order</h3><p>Create protection order</p></a>
    </div>

    <div class="recent-activity">
        <h3>Recent Activity</h3>
        <?php if (!empty($recent)): ?>
            <?php foreach ($recent as $item): ?>
                <div class="activity-item">
                    <span>
                        <?= htmlspecialchars($item['type']) ?> #<?= (int)$item['id'] ?>
                        updated (status: <?= htmlspecialchars($item['status']) ?>)
                    </span>
                    <span><?= htmlspecialchars(time_ago($item['updated_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="activity-item"><span>No recent activity.</span><span>-</span></div>
        <?php endif; ?>
    </div>

    <p style="margin-top:20px;">
        <a href="dashboard.php" class="btn-danger">Refresh</a>
    </p>
</div>
</body>
</html>