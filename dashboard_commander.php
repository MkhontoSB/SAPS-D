<?php
session_start();

// Redirect if not logged in or not a commander
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'commander') {
    header("Location: login.php");
    exit();
}

include 'db.php';

date_default_timezone_set('Africa/Johannesburg');

$user = $_SESSION['user'];
$badge_id = $user['badge_id'];
$name = $user['name'];
$rank = $user['rank'];

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch all cases with the assigned officer's name.
$cases = [];
$casesSql = "SELECT c.*, e.Name as officer_name
             FROM cases c
             LEFT JOIN employees e ON c.assigned_officer = e.Badge_ID
             ORDER BY c.last_updated DESC";
$casesResult = $conn->query($casesSql);

if ($casesResult) {
    while ($row = $casesResult->fetch_assoc()) {
        $cases[] = $row;
    }
    $casesResult->free();
}

// Fetch all officers.
$allOfficers = [];
$officersSql = "SELECT `Badge_ID`, `Name`, `Rank`
                FROM `employees`
                WHERE `role` = 'officer'
                ORDER BY `Name` ASC";
$officersResult = $conn->query($officersSql);

if (!$officersResult) {
    die("Officer query failed: " . $conn->error);
}

while ($row = $officersResult->fetch_assoc()) {
    $allOfficers[] = $row;
}
$officersResult->free();

// Determine which officers are currently assigned to active cases.
$assignedOfficerIds = [];
$assignedSql = "SELECT DISTINCT assigned_officer
                FROM cases
                WHERE assigned_officer IS NOT NULL
                AND status IN ('Ongoing','Cold')";
$assignedRes = $conn->query($assignedSql);

if ($assignedRes) {
    while ($r = $assignedRes->fetch_assoc()) {
        $assignedOfficerIds[] = (int)$r['assigned_officer'];
    }
    $assignedRes->free();
}

$availableOfficers = array_filter($allOfficers, function ($officer) use ($assignedOfficerIds) {
    return !in_array((int)$officer['Badge_ID'], $assignedOfficerIds);
});

// Dashboard statistics.
$stats = [
    'open' => 0,
    'ongoing' => 0,
    'cold' => 0,
    'closed' => 0
];

$statsSql = "
    SELECT
        COALESCE(SUM(CASE WHEN status IN ('Ongoing','Cold') THEN 1 ELSE 0 END),0) AS open_cases,
        COALESCE(SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END),0) AS ongoing_cases,
        COALESCE(SUM(CASE WHEN status = 'Cold' THEN 1 ELSE 0 END),0) AS cold_cases,
        COALESCE(SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END),0) AS closed_cases
    FROM cases
";

if ($res = $conn->query($statsSql)) {
    if ($row = $res->fetch_assoc()) {
        $stats['open'] = (int)$row['open_cases'];
        $stats['ongoing'] = (int)$row['ongoing_cases'];
        $stats['cold'] = (int)$row['cold_cases'];
        $stats['closed'] = (int)$row['closed_cases'];
    }
    $res->free();
}

$totalOfficers = count($allOfficers);
$availableOfficerCount = count($availableOfficers);
$initials = strtoupper(substr(trim($name), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander Dashboard - SAPS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="commander-dashboard">
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-mark">
                    <img src="logo.png" alt="SAPS Logo">
                </div>
                <div class="brand-copy">
                    <strong>SAPS</strong>
                    <span>Command Centre</span>
                </div>
            </div>

            <div class="sidebar-section-title">MAIN MENU</div>

            <nav class="sidebar-nav" aria-label="Main navigation">
                <a href="#dashboard" class="nav-item active">
                    <span class="nav-icon">⌂</span>
                    <span>Dashboard</span>
                </a>
                <a href="#case-management" class="nav-item">
                    <span class="nav-icon">▣</span>
                    <span>Case Management</span>
                </a>
                <a href="#officer-overview" class="nav-item">
                    <span class="nav-icon">♙</span>
                    <span>Officer Overview</span>
                </a>
            </nav>

            <div class="sidebar-section-title">SYSTEM</div>

            <nav class="sidebar-nav">
                <a href="#profile" class="nav-item">
                    <span class="nav-icon">⚙</span>
                    <span>Profile</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="sidebar-user">
                    <div class="avatar avatar-small"><?= htmlspecialchars($initials) ?></div>
                    <div class="sidebar-user-info">
                        <strong><?= htmlspecialchars($name) ?></strong>
                        <span><?= htmlspecialchars($rank) ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-link">
                    <span>↪</span>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-content" id="dashboard">
            <header class="topbar">
                <div class="page-heading">
                    <span class="eyebrow">Command Centre</span>
                    <h1>Dashboard</h1>
                    <p>Monitor active cases and manage officer assignments.</p>
                </div>

                <div class="topbar-actions">
                    <div class="system-status">
                        <span class="status-dot"></span>
                        System operational
                    </div>
                    <div class="topbar-divider"></div>
                    <div class="profile-summary" id="profile">
                        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                        <div>
                            <strong><?= htmlspecialchars($name) ?></strong>
                            <span><?= htmlspecialchars($rank) ?> · Badge <?= htmlspecialchars($badge_id) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">!</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Overview -->
            <section class="dashboard-section" aria-labelledby="overview-title">
                <div class="section-heading-row">
                    <div>
                        <span class="eyebrow">Overview</span>
                        <h2 id="overview-title">Case statistics</h2>
                    </div>
                    <span class="last-updated">Live database overview</span>
                </div>

                <div class="stats-grid">
                    <article class="stat-card stat-open">
                        <div class="stat-top">
                            <div class="stat-icon">▣</div>
                            <span class="stat-tag">Active</span>
                        </div>
                        <div class="stat-number"><?= number_format($stats['open']) ?></div>
                        <div class="stat-label">Open Cases</div>
                        <div class="stat-footer">Ongoing + cold cases</div>
                    </article>

                    <article class="stat-card stat-ongoing">
                        <div class="stat-top">
                            <div class="stat-icon">◷</div>
                            <span class="stat-tag">In progress</span>
                        </div>
                        <div class="stat-number"><?= number_format($stats['ongoing']) ?></div>
                        <div class="stat-label">Ongoing Cases</div>
                        <div class="stat-footer">Currently being investigated</div>
                    </article>

                    <article class="stat-card stat-cold">
                        <div class="stat-top">
                            <div class="stat-icon">◇</div>
                            <span class="stat-tag">Attention</span>
                        </div>
                        <div class="stat-number"><?= number_format($stats['cold']) ?></div>
                        <div class="stat-label">Cold Cases</div>
                        <div class="stat-footer">Requires continued monitoring</div>
                    </article>

                    <article class="stat-card stat-closed">
                        <div class="stat-top">
                            <div class="stat-icon">✓</div>
                            <span class="stat-tag">Completed</span>
                        </div>
                        <div class="stat-number"><?= number_format($stats['closed']) ?></div>
                        <div class="stat-label">Closed Cases</div>
                        <div class="stat-footer">Successfully concluded</div>
                    </article>
                </div>
            </section>

            <!-- Officer summary -->
            <section class="quick-summary" id="officer-overview">
                <div class="summary-item">
                    <div class="summary-icon">♙</div>
                    <div>
                        <span>Total officers</span>
                        <strong><?= number_format($totalOfficers) ?></strong>
                    </div>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-item">
                    <div class="summary-icon available">✓</div>
                    <div>
                        <span>Available officers</span>
                        <strong><?= number_format($availableOfficerCount) ?></strong>
                    </div>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-item summary-note">
                    <div>
                        <span>Command status</span>
                        <strong>Ready for assignment</strong>
                    </div>
                </div>
            </section>

            <!-- Case management -->
            <section class="dashboard-section" id="case-management">
                <div class="section-heading-row case-heading">
                    <div>
                        <span class="eyebrow">Operations</span>
                        <h2>Case management</h2>
                    </div>
                    <span class="case-count"><?= number_format(count($cases)) ?> total cases</span>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <div>
                            <strong>Recent cases</strong>
                            <span>Sorted by latest update</span>
                        </div>
                        <div class="table-legend">
                            <span><i class="legend-dot ongoing"></i> Ongoing</span>
                            <span><i class="legend-dot cold"></i> Cold</span>
                            <span><i class="legend-dot closed"></i> Closed</span>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="cases-table">
                            <thead>
                                <tr>
                                    <th>Case</th>
                                    <th>Reporter</th>
                                    <th>Crime type</th>
                                    <th>Status</th>
                                    <th>Assigned officer</th>
                                    <th>Date reported</th>
                                    <th class="actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cases)): ?>
                                    <tr>
                                        <td colspan="7" class="empty-state">
                                            <div class="empty-icon">✓</div>
                                            <strong>No cases found</strong>
                                            <span>There are currently no cases in the database.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cases as $case): ?>
                                        <tr>
                                            <td>
                                                <span class="case-id">#<?= htmlspecialchars($case['case_id']) ?></span>
                                            </td>
                                            <td>
                                                <div class="reporter-cell">
                                                    <div class="mini-avatar">
                                                        <?= htmlspecialchars(strtoupper(substr(trim($case['reporter_name']), 0, 1))) ?>
                                                    </div>
                                                    <span><?= htmlspecialchars($case['reporter_name']) ?></span>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($case['crime_type']) ?></td>
                                            <td>
                                                <?php
                                                    $status = $case['status'];
                                                    $statusClass = strtolower($status);
                                                ?>
                                                <span class="status-badge status-<?= htmlspecialchars($statusClass) ?>">
                                                    <i></i><?= htmlspecialchars($status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($case['assigned_officer']): ?>
                                                    <div class="officer-cell">
                                                        <strong><?= htmlspecialchars($case['officer_name'] ?? 'Unknown') ?></strong>
                                                        <span>Badge <?= htmlspecialchars($case['assigned_officer']) ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="unassigned">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="date-cell"><?= htmlspecialchars($case['date_reported']) ?></td>
                                            <td class="actions-cell">
                                                <?php if ($case['status'] === 'Cold'): ?>
                                                    <div class="button-row">
                                                        <button type="button" class="action-btn btn-success" onclick="reopenCase(<?= (int)$case['case_id'] ?>)">Reopen</button>
                                                        <button type="button" class="action-btn btn-danger" onclick="deleteCase(<?= (int)$case['case_id'] ?>)">Delete</button>
                                                    </div>
                                                <?php endif; ?>

                                                <form class="assign-form" action="assign_case.php" method="POST">
                                                    <input type="hidden" name="case_id" value="<?= (int)$case['case_id'] ?>">
                                                    <select name="officer_id" class="assign-select" required aria-label="Select officer for case <?= (int)$case['case_id'] ?>">
                                                        <option value="">Select officer</option>
                                                        <?php foreach ($allOfficers as $officer): ?>
                                                            <option value="<?= htmlspecialchars($officer['Badge_ID']) ?>"
                                                                <?= ($case['assigned_officer'] == $officer['Badge_ID']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($officer['Name']) ?> (<?= htmlspecialchars($officer['Rank']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="action-btn btn-primary">Assign</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <footer class="dashboard-footer">
                <span>© <?= date('Y') ?> SAPS Command Centre</span>
                <span>Secure case management system</span>
            </footer>
        </main>
    </div>

    <script>
        function deleteCase(caseId) {
            if (confirm('Are you sure you want to delete this case?')) {
                window.location.href = 'delete_case.php?case_id=' + caseId;
            }
        }

        function reopenCase(caseId) {
            if (confirm('Are you sure you want to reopen this case?')) {
                window.location.href = 'reopen_case.php?case_id=' + caseId;
            }
        }
    </script>
</body>
</html>