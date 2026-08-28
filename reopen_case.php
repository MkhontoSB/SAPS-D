<?php
session_start();

// Check if user is commander
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'commander') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (isset($_GET['case_id'])) {
    $case_id = $_GET['case_id'];
    
    
    $check_stmt = $conn->prepare("SELECT status FROM cases WHERE case_id = ?");
    $check_stmt->bind_param("i", $case_id);
    $check_stmt->execute();
    $check_stmt->bind_result($status);
    $check_stmt->fetch();
    $check_stmt->close();
    
    if ($status === 'Cold') {
        $update_stmt = $conn->prepare("UPDATE cases SET status = 'Ongoing' WHERE case_id = ?");
        $update_stmt->bind_param("i", $case_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Case successfully reopened";
        } else {
            $_SESSION['error'] = "Error reopening case: " . $conn->error;
        }
        
        $update_stmt->close();
    } else {
        $_SESSION['error'] = "Only cold cases can be reopened";
    }
    
    $conn->close();
    
    header("Location: dashboard_commander.php");
    exit();
}
?>