<?php
include('config.php');
session_start();

// Only admin can access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Verify attendance
if (isset($_GET['verify'])) {
    $attendance_id = intval($_GET['verify']);
    $sql = "UPDATE attendance SET verification_status='verified' WHERE attendance_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $attendance_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Attendance verified successfully.";
    } else {
        $message = "Failed to verify attendance.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch all attendance records with user, exam, and center info
$sql = "
SELECT a.attendance_id, u.name, u.usertype, da.role_in_exam, e.exam_name, e.exam_date, c.center_name, 
       a.status, a.verification_status, a.marked_at
FROM attendance a
JOIN users u ON a.user_id = u.user_id
JOIN duty_assignments da ON a.duty_id = da.assignment_id
JOIN exams e ON da.exam_id = e.exam_id
JOIN exam_centers c ON e.center_id = c.center_id
ORDER BY a.marked_at DESC
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Attendance</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">


    <script>
        function confirmVerify(id) {
            if (confirm("Are you sure you want to verify this attendance?")) {
                window.location.href = "admin_attendance_verify.php?verify=" + id;
            }
        }
    </script>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <h2>Attendance Records</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>User Type</th>
                <th>Role</th>
                <th>Exam</th>
                <th>Date</th>
                <th>Status</th>
                <th>Verification</th>
                <th>Marked At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['attendance_id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo $row['usertype']; ?></td>
                <td><?php echo $row['role_in_exam']; ?></td>
                <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                <td><?php echo $row['exam_date']; ?></td>
                <td><?php echo ucfirst($row['status']); ?></td>
                <td>
                    <?php if($row['verification_status']=='pending'): ?>
                        <span style="color:red; font-weight:bold;">Pending</span>
                    <?php else: ?>
                        Verified
                    <?php endif; ?>
                </td>
                <td><?php echo $row['marked_at']; ?></td>
                <td>
                    <?php if($row['verification_status']=='pending'): ?>
                        <button onclick="confirmVerify(<?php echo $row['attendance_id']; ?>)" class="btn btn-sm btn-success">Verify</button>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
