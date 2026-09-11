<?php
require_once 'auth_check.php';
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['reporter'];
    $address = $_POST['address'];
    $suspect = $_POST['suspect'];
    $date = $_POST['date'];
    $crime = $_POST['crime'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO cases (reporter_name, reporter_address, suspect_name, date_reported, crime_type, description, status) VALUES (?, ?, ?, ?, ?, ?, 'Ongoing')");
    $stmt->bind_param("ssssss", $name, $address, $suspect, $date, $crime, $description);
    
    if ($stmt->execute()) {
        echo "<p style='color:lightgreen; text-align:center;'>Case for <b>$name</b> submitted successfully!</p>";
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
    <title>Open Case</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="SAPS Logo" class="logo">
        <h2>Opening cases</h2>

        <form method="POST" action="">
            <input type="text" name="reporter" placeholder="Reporter (Full Name)" required>
            <input type="text" name="address" placeholder="Reporter address" required>
            <input type="text" name="suspect" placeholder="Suspect">
            <input type="date" name="date" required>
            <select name="crime" required>
                <option value="">Select crime type</option>
                <option value="Theft">Theft</option>
                <option value="Assault">Assault</option>
                <option value="Fraud">Fraud</option>
                <option value="Other">Other</option>
            </select>
            <textarea name="description" placeholder="Enter case description here..." rows="4" required></textarea>
            <button type="submit" class="btn-success">Submit</button>
        </form>

        <a href="dashboard.php"><button class="btn-danger">Back</button></a>
    </div>
</body>
</html>
