<?php
include('config.php');
session_start();

// Only admin can access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Fetch all reports with user, exam, and center info
$sql = "
SELECT r.report_id, r.report_file, r.remarks, r.submitted_at,
       u.name AS uploader_name, u.usertype AS uploader_type,
       da.role_in_exam, e.exam_name, e.exam_date, c.center_name
FROM reports r
JOIN users u ON r.uploaded_by = u.user_id
JOIN duty_assignments da ON r.duty_id = da.assignment_id
JOIN exams e ON da.exam_id = e.exam_id
JOIN exam_centers c ON e.center_id = c.center_id
ORDER BY r.submitted_at DESC
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Uploaded Reports</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <h2>Uploaded Exam Reports</h2>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Uploader</th>
                <th>Role</th>
                <th>Exam</th>
                <th>Date</th>
                <th>Center</th>
                <th>Remarks</th>
                <th>Submitted At</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['report_id']; ?></td>
                <td><?php echo htmlspecialchars($row['uploader_name']); ?></td>
                <td><?php echo $row['role_in_exam']; ?></td>
                <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                <td><?php echo $row['exam_date']; ?></td>
                <td><?php echo htmlspecialchars($row['center_name']); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                <td><?php echo $row['submitted_at']; ?></td>
                <td>
                    <a href="../uploads/<?php echo $row['report_file']; ?>" download class="btn btn-sm btn-primary">Download</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="11">No reports uploaded yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
