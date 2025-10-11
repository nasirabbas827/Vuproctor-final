<?php
include('config.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Fetch user payments
$sql = "SELECT * FROM payments WHERE user_id = ? ORDER BY processed_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-4">
    <h2>My Payment Records</h2>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Status</th>
                <th>Processed At</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['payment_id']; ?></td>
                        <td><?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>
                            <?php 
                                if($row['status'] == 'pending') {
                                    echo "<span class='text-warning font-weight-bold'>Pending</span>";
                                } else {
                                    echo "<span class='text-success font-weight-bold'>Processed</span>";
                                }
                            ?>
                        </td>
                        <td><?php echo $row['processed_at'] ? $row['processed_at'] : '-'; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No payments found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
