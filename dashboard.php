<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Extract user info
$user = $_SESSION['user'];
$badge_id = $user['badge_id'];
$name = $user['name'];
$rank = $user['rank'];
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
        .action-card { background:#1d4b85; padding:15px; border-radius:8px; text-align:center; transition: transform 0.2s; }
        .action-card:hover { transform: translateY(-5px); background:#2568c7; }
        .recent-activity { margin-top:30px; background:#122544; padding:20px; border-radius:8px; }
        .activity-item { padding:10px; border-bottom:1px solid #2a4a75; display:flex; justify-content:space-between; }
        .activity-item:last-child { border-bottom:none; }
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
            <p>Welcome, <strong><?= $name ?></strong> (<?= $rank ?>)</p>
            <p>Badge ID: <?= $badge_id ?></p>
            <a href="logout.php" style="color:#bbb;font-size:0.8rem;">Logout</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number">24</div><div class="stat-label">Open Cases</div></div>
        <div class="stat-card"><div class="stat-number">12</div><div class="stat-label">Ongoing Cases</div></div>
        <div class="stat-card"><div class="stat-number">8</div><div class="stat-label">Cold Cases</div></div>
        <div class="stat-card"><div class="stat-number">42</div><div class="stat-label">Closed Cases</div></div>
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
        <div class="activity-item"><span>Case #2456 updated</span><span>2 hours ago</span></div>
        <div class="activity-item"><span>New protection order filed</span><span>5 hours ago</span></div>
        <div class="activity-item"><span>Case #2451 closed</span><span>Yesterday</span></div>
        <div class="activity-item"><span>Case #2448 status changed to Ongoing</span><span>2 days ago</span></div>
    </div>
</div>
</body>
</html>
