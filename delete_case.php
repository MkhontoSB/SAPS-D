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

    if ($check_stmt->fetch()) {
        $check_stmt->close();

        if ($status === 'Cold') {

            
            $conn->begin_transaction();

            try {
                
                $stmt1 = $conn->prepare("DELETE FROM protection_orders WHERE case_id = ?");
                $stmt1->bind_param("i", $case_id);
                $stmt1->execute();
                $stmt1->close();

                
                $stmt2 = $conn->prepare("DELETE FROM cases WHERE case_id = ?");
                $stmt2->bind_param("i", $case_id);
                $stmt2->execute();
                $stmt2->close();

                
                $conn->commit();
                $_SESSION['message'] = "Case and linked protection orders deleted successfully";

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = "Error deleting case: " . $e->getMessage();
            }

        } else {
            $_SESSION['error'] = "Only cold cases can be deleted";
        }

    } else {
        $_SESSION['error'] = "Case not found";
        $check_stmt->close();
    }

    $conn->close();
    header("Location: dashboard_commander.php");
    exit();
}
?>

