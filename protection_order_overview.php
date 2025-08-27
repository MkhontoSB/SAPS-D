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


$stmt = $conn->prepare("SELECT order_id, reporter_fullname, perpetrator_name, date_filed, last_status_update, status 
                        FROM protection_orders WHERE order_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Protection Order <?= htmlspecialchars($id) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo">
    <h2>Protection Order Overview</h2>

    <?php if($order): ?>
    <table border="1" style="width:100%; color:white;">
        <tr><td>Case ID</td><td><?= $order['order_id'] ?></td></tr>
        <tr><td>Reporter</td><td><?= $order['reporter_fullname'] ?></td></tr>
        <tr><td>Perpetrator</td><td><?= $order['perpetrator_name'] ?></td></tr>
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
    <?php else: ?>
    <p>No protection order found.</p>
    <?php endif; ?>

    <a href="protection_orders.php"><button class="btn-danger">Back</button></a>
</div>
</body>
</html>
