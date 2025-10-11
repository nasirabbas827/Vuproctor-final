<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Set processed_at to current timestamp
    $sql = "INSERT INTO payments (user_id, amount, description, status, processed_at) VALUES (?, ?, ?, 'pending', NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ids", $user_id, $amount, $description);

    if(mysqli_stmt_execute($stmt)){
        $_SESSION['message'] = "Payment request submitted successfully.";
    } else {
        $_SESSION['message'] = "Failed to submit payment request: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);

    header("Location: admin_view_duties.php?user_id=".$user_id);
    exit;
}
?>
