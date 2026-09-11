<?php
require_once 'auth_check.php';
include 'db.php';


$case_result = $conn->query("SELECT case_id, crime_type FROM cases ORDER BY case_id DESC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_id = $_POST['case_id']; 
    $reporter_fullname = $_POST['name'];
    $reporter_id_number = $_POST['idnum'];
    $reporter_contact = $_POST['phone'];
    $reporter_address = $_POST['address'];
    $type_of_abuse = $_POST['abuse'];
    $date_of_incident = $_POST['date'];
    $description = $_POST['desc'];
    $perpetrator_name = $_POST['perpetrator'];
    $immediate_danger = $_POST['danger'];
    
    
    $date_filed = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO protection_orders 
        (case_id, reporter_fullname, reporter_id_number, reporter_contact, reporter_address, 
         type_of_abuse, date_of_incident, description, perpetrator_name, immediate_danger, date_filed) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issssssssss", $case_id, $reporter_fullname, $reporter_id_number, $reporter_contact, 
                     $reporter_address, $type_of_abuse, $date_of_incident, $description, 
                     $perpetrator_name, $immediate_danger, $date_filed);

    if ($stmt->execute()) {
        echo "<p style='color:lightgreen; text-align:center;'>Protection order for <b>$reporter_fullname</b> submitted successfully under Case ID: <b>$case_id</b>!</p>";
    } else {
        echo "<p style='color:red; text-align:center;'>Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Protection Order</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo">
    <h2>File Protection Order</h2>

    <form method="POST" action="">
        <!-- Dropdown for case_id -->
        <label for="case_id"><b>Select Related Case</b></label>
        <select name="case_id" required>
            <option value="">-- Select Case ID --</option>
            <?php 
            if ($case_result->num_rows > 0) {
                while ($row = $case_result->fetch_assoc()) {
                    echo "<option value='".$row['case_id']."'>Case ID: ".$row['case_id']." (".$row['crime_type'].")</option>";
                }
            } else {
                echo "<option value=''>No cases available</option>";
            }
            ?>
        </select>

        <input type="text" name="name" placeholder="Reporter Full Name" required>
        <input type="text" name="idnum" placeholder="Reporter ID Number (13 digits)" pattern="\d{13}" required>
        <input type="text" name="phone" placeholder="Reporter Contact Number (10 digits)" pattern="\d{10}" required>
        <input type="text" name="address" placeholder="Reporter Address" required>
        <input type="text" name="abuse" placeholder="Type of abuse/threat" required>
        <input type="date" name="date" required>
        <textarea name="desc" placeholder="Description" rows="4" required></textarea>
        <input type="text" name="perpetrator" placeholder="Name of Perpetrator" required>
        <select name="danger" required>
            <option value="">Immediate Danger?</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
        <button type="submit" class="btn-success">Submit</button>
    </form>

    <a href="dashboard.php"><button class="btn-danger">Back</button></a>
</div>
</body>
</html>