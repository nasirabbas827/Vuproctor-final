<?php
include('config.php');
session_start();

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$msg = "";

// Handle new leave request
if (isset($_POST['add_leave'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $sql = "INSERT INTO leave_requests (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $start_date, $end_date, $reason);
    if (mysqli_stmt_execute($stmt)) {
        $msg = "Leave request added successfully!";
    } else {
        $msg = "Error adding leave request!";
    }
}

// Handle deletion of pending leave
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_sql = "DELETE FROM leave_requests WHERE leave_id = ? AND user_id = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $msg = "Pending leave request deleted successfully!";
    } else {
        $msg = "Cannot delete leave request!";
    }
}

// Fetch user's leave requests
$sql = "SELECT * FROM leave_requests WHERE user_id = ? ORDER BY requested_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-4">
        <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
    <h2>My Leave Requests</h2>
    <?php if (!empty($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

    <!-- Add Leave Request Form -->
            <form method="POST">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" name="add_leave" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td>
                            <?php 
                            if($row['status'] == 'pending') {
                                echo "<span style='color:red; font-weight:bold;'>Pending</span>";
                            } else {
                                echo ucfirst($row['status']);
                            }
                            ?>
                        </td>
                        <td>
                            <?php if($row['status'] == 'pending'): ?>
                                <a href="?delete_id=<?php echo $row['leave_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this pending leave?')">Delete</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No leave requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
