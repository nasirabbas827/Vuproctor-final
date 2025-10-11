<?php
include('config.php');
session_start();

// Only admin can access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Update leave status
if (isset($_GET['update']) && isset($_GET['status'])) {
    $leave_id = intval($_GET['update']);
    $status = $_GET['status'];
    $allowed_status = ['pending','approved','rejected'];

    if (in_array($status, $allowed_status)) {
        $sql = "UPDATE leave_requests SET status=? WHERE leave_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $leave_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Leave status updated successfully.";
        } else {
            $message = "Failed to update leave status.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all leave requests with user info
$sql = "
SELECT lr.leave_id, u.name, u.usertype, lr.start_date, lr.end_date, lr.reason, lr.status, lr.requested_at
FROM leave_requests lr
JOIN users u ON lr.user_id = u.user_id
ORDER BY lr.requested_at DESC
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Leave Requests</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script>
        function updateStatus(leave_id, status) {
            if(confirm("Are you sure you want to change status to " + status + "?")) {
                window.location.href = "admin_leave_requests.php?update=" + leave_id + "&status=" + status;
            }
        }
    </script>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <h2>Leave Requests</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>User Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Requested At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['leave_id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo $row['usertype']; ?></td>
                <td><?php echo $row['start_date']; ?></td>
                <td><?php echo $row['end_date']; ?></td>
                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                <td>
                    <?php 
                        if($row['status']=='pending') {
                            echo "<span style='color:red; font-weight:bold;'>Pending</span>";
                        } else {
                            echo ucfirst($row['status']);
                        }
                    ?>
                </td>
                <td><?php echo $row['requested_at']; ?></td>
                <td>
                    <?php if($row['status'] != 'approved'): ?>
                        <button onclick="updateStatus(<?php echo $row['leave_id']; ?>,'approved')" class="btn btn-sm btn-success mb-1">Approve</button>
                    <?php endif; ?>
                    <?php if($row['status'] != 'rejected'): ?>
                        <button onclick="updateStatus(<?php echo $row['leave_id']; ?>,'rejected')" class="btn btn-sm btn-danger mb-1">Reject</button>
                    <?php endif; ?>
                    <?php if($row['status'] != 'pending'): ?>
                        <button onclick="updateStatus(<?php echo $row['leave_id']; ?>,'pending')" class="btn btn-sm btn-warning mb-1">Set Pending</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
