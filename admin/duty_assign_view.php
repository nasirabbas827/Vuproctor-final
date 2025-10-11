<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Delete assignment
if (isset($_GET['delete'])) {
    $assignment_id = intval($_GET['delete']);
    $sql = "DELETE FROM duty_assignments WHERE assignment_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Assignment deleted successfully.";
    } else {
        $message = "Failed to delete assignment.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch assignments with join
$sql = "SELECT da.assignment_id, e.exam_name, e.exam_date, u.name, u.usertype, u.center_preference, u.availability_status, da.role_in_exam, da.status
        FROM duty_assignments da
        JOIN exams e ON da.exam_id = e.exam_id
        JOIN users u ON da.user_id = u.user_id
        ORDER BY da.assignment_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Duty Assignments</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this duty assignment?")) {
                window.location.href = "duty_assign_view.php?delete=" + id;
            }
        }
    </script>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <a href="duty_assign_add.php" class="btn btn-success float-right">Assign New Duty</a>

    <h2>Duty Assignments</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Exam</th>
                <th>User</th>
                <th>User Type</th>
                <th>Preference</th>
                <th>Availability</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['assignment_id']; ?></td>
                <td><?php echo htmlspecialchars($row['exam_name'])." (".$row['exam_date'].")"; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo $row['usertype']; ?></td>
                <td><?php echo $row['center_preference']; ?></td>
                <td><?php echo $row['availability_status']; ?></td>
                <td><?php echo $row['role_in_exam']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td>
                    <a href="duty_assign_edit.php?assignment_id=<?php echo $row['assignment_id']; ?>" class="mb-2 btn btn-sm btn-primary">Edit</a>
                    <button onclick="confirmDelete(<?php echo $row['assignment_id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>
</body>
</html>
