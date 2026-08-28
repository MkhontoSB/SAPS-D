
<?php
require_once 'auth_check.php';

include 'db.php';


$search = $_GET['search'] ?? '';
$sql = "SELECT case_id, crime_type, description, date_reported, reporter_address, suspect_name 
        FROM cases WHERE status='Cold'";
if ($search) {
    $sql .= " AND case_id LIKE '%$search%'";
}
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cold Cases</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo">
    <h2>Cold Cases</h2>

    <form method="GET">
        <input type="text" name="search" placeholder="Search by Case ID" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-primary">Search</button>
    </form>

    <ul style="list-style:none; padding:0;">
        <?php while($row = $result->fetch_assoc()): ?>
            <li><a href="cold_case_view.php?id=<?= $row['case_id'] ?>">Case #<?= $row['case_id'] ?></a></li>
        <?php endwhile; ?>
    </ul>

    <a href="dashboard.php"><button class="btn-danger">Back</button></a>
</div>
</body>
</html>

