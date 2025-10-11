<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Delete logic
if (isset($_GET['delete'])) {
    $exam_id = intval($_GET['delete']);
    $sql = "DELETE FROM Exams WHERE exam_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $exam_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Exam deleted successfully.";
    } else {
        $message = "Failed to delete exam.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch all exams with center name
$sql = "SELECT e.*, c.center_name FROM Exams e JOIN exam_centers c ON e.center_id = c.center_id ORDER BY exam_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Exams</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function confirmDelete(exam_id) {
            if (confirm("Are you sure you want to delete this exam?")) {
                window.location.href = "exam_view.php?delete=" + exam_id;
            }
        }
    </script>
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div>
        <a href="exam_add.php" class="btn btn-success float-right">Add New Exam</a>

        <h2 class="mb-4">Exams</h2>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Exam Name</th>
                    <th>Date</th>
                    <th>Center</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['exam_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                    <td><?php echo $row['exam_date']; ?></td>
                    <td><?php echo htmlspecialchars($row['center_name']); ?></td>
                    <td><?php echo $row['start_time']; ?></td>
                    <td><?php echo $row['end_time']; ?></td>
                    <td>
                        <a href="exam_edit.php?exam_id=<?php echo $row['exam_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <button onclick="confirmDelete(<?php echo $row['exam_id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    </div>
</div>
</body>
</html>
