<?php
require_once 'auth_check.php';
include 'db.php';

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'All';


$sql = "SELECT order_id, reporter_fullname, perpetrator_name, status 
        FROM protection_orders WHERE 1=1";


if ($search) {
    $sql .= " AND order_id LIKE '%$search%'";
}


if ($status_filter != "All") {
    $sql .= " AND status = '$status_filter'";
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Protection Orders</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo">
    <h2>Protection Orders</h2>

    <form method="GET" style="margin-bottom:15px;">
        <input type="text" name="search" placeholder="Search by Case ID" value="<?= htmlspecialchars($search) ?>">

        <select name="status">
            <option value="All" <?= $status_filter=="All"?'selected':'' ?>>All</option>
            <option value="Active" <?= $status_filter=="Active"?'selected':'' ?>>Active</option>
            <option value="Pending" <?= $status_filter=="Pending"?'selected':'' ?>>Pending</option>
            <option value="Served" <?= $status_filter=="Served"?'selected':'' ?>>Served</option>
            <option value="Closed" <?= $status_filter=="Closed"?'selected':'' ?>>Closed</option>
        </select>
       
        <button type="submit" class="btn-primary">Filter</button>
    </form>
<!--
 <a href="file_proyection_order.php"><button class="btn-primary">File Protection Order</button></a>
-->
    <ul style="list-style:none; padding:0;">
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <li>
                    <a href="protection_order_overview.php?id=<?= $row['order_id'] ?>">
                        Case #<?= $row['order_id'] ?> - <?= htmlspecialchars($row['status']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li>No protection orders found.</li>
        <?php endif; ?>
    </ul>

    <a href="dashboard.php"><button class="btn-danger">Back</button></a>
</div>
</body>
</html>
