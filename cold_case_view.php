<?php
require_once 'auth_check.php';
include 'db.php';

$id = $_GET['id'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE cases SET status=?, last_updated=NOW() WHERE case_id=?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: cold_case_view.php?id=$id"); 
    exit;
}

$stmt = $conn->prepare("SELECT case_id, crime_type, description, date_reported, reporter_address, suspect_name, status 
                        FROM cases WHERE case_id=? AND status='Cold'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$case = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cold Case <?= htmlspecialchars($id) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo">
    <h2>Cold Case Details</h2>

    <?php if($case): ?>
    <table border="1" style="width:100%; color:white;">
        <tr><td>Case ID</td><td><?= $case['case_id'] ?></td></tr>
        <tr><td>Crime Type</td><td><?= $case['crime_type'] ?></td></tr>
        <tr><td>Description</td><td><?= $case['description'] ?></td></tr>
        <tr><td>Date of Crime</td><td><?= $case['date_reported'] ?></td></tr>
        <tr><td>Place of Crime</td><td><?= $case['reporter_address'] ?></td></tr>
        <tr><td>Suspect</td><td><?= $case['suspect_name'] ?></td></tr>
        <tr><td>Status</td><td><?= $case['status'] ?></td></tr>
    </table>

    <h3>Update Status</h3>
    <form method="POST">
        <select name="status" required>
            <option value="">-- Select Status --</option>
            <option value="Ongoing">Ongoing</option>
            <option value="Cold">Cold</option>
            <option value="Closed">Closed</option>
        </select>
        <button type="submit" class="btn-primary">Update</button>
    </form>
    <?php else: ?>
    <p>No cold case found.</p>
    <?php endif; ?>

    <a href="cold_cases.php"><button class="btn-danger">Back</button></a>
</div>
</body>
</html>
