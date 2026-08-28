<?php
require_once 'auth_check.php';
include 'db.php';

$id = $_GET['id'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE protection_orders 
                            SET status=?, last_status_update=NOW() 
                            WHERE order_id=?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: protection_order_overview.php?id=$id"); 
    exit;
}


$orders_result = $conn->query("SELECT order_id, case_id, reporter_fullname, status, date_filed 
                               FROM protection_orders 
                               ORDER BY order_id DESC");


if (!empty($id)) {
    $stmt = $conn->prepare("SELECT order_id, case_id, reporter_fullname, reporter_id_number, 
                                   reporter_contact, reporter_address, type_of_abuse, 
                                   date_of_incident, description, perpetrator_name, 
                                   immediate_danger, status, date_filed, last_status_update 
                            FROM protection_orders WHERE order_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Protection Orders Overview</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo">
    <h2>Protection Orders Overview</h2>

    
    <h3>All Protection Orders</h3>
    <?php if($orders_result->num_rows > 0): ?>
    <table border="1" style="width:100%; color:white;">
        <tr>
            <th>Order ID</th>
            <th>Case ID</th>
            <th>Reporter Name</th>
            <th>Date Filed</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $orders_result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['order_id'] ?></td>
            <td><?= $row['case_id'] ?? 'N/A' ?></td>
            <td><?= $row['reporter_fullname'] ?></td>
            <td><?= $row['date_filed'] ?></td>
            <td><?= $row['status'] ?></td>
            <td>
                <a href="protection_order_overview.php?id=<?= $row['order_id'] ?>">View Details</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>No protection orders found.</p>
    <?php endif; ?>

    
    <?php if(!empty($id) && isset($order)): ?>
    <h2>Protection Order Details</h2>

    <table border="1" style="width:100%; color:white;">
        <tr><td>Order ID</td><td><?= $order['order_id'] ?></td></tr>
        <tr><td>Case ID</td><td><?= $order['case_id'] ?? 'N/A' ?></td></tr>
        <tr><td>Reporter Full Name</td><td><?= $order['reporter_fullname'] ?></td></tr>
        <tr><td>Reporter ID Number</td><td><?= $order['reporter_id_number'] ?></td></tr>
        <tr><td>Reporter Contact</td><td><?= $order['reporter_contact'] ?></td></tr>
        <tr><td>Reporter Address</td><td><?= $order['reporter_address'] ?></td></tr>
        <tr><td>Type of Abuse</td><td><?= $order['type_of_abuse'] ?></td></tr>
        <tr><td>Date of Incident</td><td><?= $order['date_of_incident'] ?></td></tr>
        <tr><td>Description</td><td><?= $order['description'] ?></td></tr>
        <tr><td>Perpetrator Name</td><td><?= $order['perpetrator_name'] ?></td></tr>
        <tr><td>Immediate Danger</td><td><?= $order['immediate_danger'] ?></td></tr>
        <tr><td>Date Filed</td><td><?= $order['date_filed'] ?></td></tr>
        <tr><td>Last Updated</td><td><?= $order['last_status_update'] ?></td></tr>
        <tr><td>Status</td><td><?= $order['status'] ?></td></tr>
    </table>

    <h3>Update Status</h3>
    <form method="POST">
        <select name="status" required>
            <option value="">-- Select Status --</option>
            <option value="Active" <?= $order['status']=='Active'?'selected':'' ?>>Active</option>
            <option value="Pending" <?= $order['status']=='Pending'?'selected':'' ?>>Pending</option>
            <option value="Served" <?= $order['status']=='Served'?'selected':'' ?>>Served</option>
            <option value="Closed" <?= $order['status']=='Closed'?'selected':'' ?>>Closed</option>
        </select>
        <button type="submit" class="btn-primary">Update</button>
    </form>
    <?php elseif(!empty($id)): ?>
    <p>No protection order found with ID: <?= $id ?></p>
    <?php endif; ?>

    <a href="dashboard.php"><button class="btn-danger">Back</button></a>
</div>
</body>
</html>