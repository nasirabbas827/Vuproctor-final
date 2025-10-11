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
    $center_id = intval($_GET['delete']);
    $sql = "DELETE FROM exam_centers WHERE center_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $center_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Exam center deleted successfully.";
    } else {
        $message = "Failed to delete exam center.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch all exam centers
$sql = "SELECT * FROM exam_centers ORDER BY center_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Exam Centers</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function confirmDelete(center_id) {
            if (confirm("Are you sure you want to delete this exam center?")) {
                window.location.href = "exam_center_view.php?delete=" + center_id;
            }
        }
    </script>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <a href="exam_center_add.php" class="btn btn-success float-right">Add New Center</a>

    <h2 class="mb-4">Exam Centers</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Center Name</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['center_id']; ?></td>
                <td><?php echo htmlspecialchars($row['center_name']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td>
                    <a href="exam_center_edit.php?center_id=<?php echo $row['center_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <button onclick="confirmDelete(<?php echo $row['center_id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>
</body>
</html>
