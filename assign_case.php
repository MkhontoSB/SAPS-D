<?php
session_start();

// Check if user is commander
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'commander') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id = $_POST['case_id'];
    $officer_id = $_POST['officer_id'];
    
    
    $stmt = $conn->prepare("UPDATE cases SET assigned_officer = ? WHERE case_id = ?");
    $stmt->bind_param("ii", $officer_id, $case_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Case successfully assigned to officer";
    } else {
        $_SESSION['error'] = "Error assigning case: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: dashboard_commander.php");
    exit();
}
?>