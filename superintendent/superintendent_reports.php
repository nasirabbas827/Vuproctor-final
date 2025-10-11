<?php
include('config.php');
session_start();

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Check if user is superintendent
$check_sql = "SELECT usertype FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if ($user['usertype'] != 'superintendent') {
    die("Access denied. Only superintendents can upload reports.");
}

// Handle report upload
$msg = "";
if (isset($_POST['upload_report'])) {
    $duty_id = intval($_POST['duty_id']);
    $remarks = $_POST['remarks'];

    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
        $allowed = ['pdf','doc','docx'];
        $file_ext = pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($file_ext), $allowed)) {
            $file_name = time() . '_' . basename($_FILES['report_file']['name']);
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target_file)) {
                // Insert into database
                $insert_sql = "INSERT INTO reports (duty_id, uploaded_by, report_file, remarks) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "iiss", $duty_id, $user_id, $file_name, $remarks);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = "Report uploaded successfully!";
                } else {
                    $msg = "Database error while saving report.";
                }
            } else {
                $msg = "Failed to upload file.";
            }
        } else {
            $msg = "Invalid file type. Only PDF, DOC, DOCX allowed.";
        }
    } else {
        $msg = "Please select a file to upload.";
    }
}

// Fetch superintendent's assigned duties
$sql = "
    SELECT da.assignment_id, e.exam_name, e.exam_date, c.center_name
    FROM duty_assignments da
    INNER JOIN exams e ON da.exam_id = e.exam_id
    INNER JOIN exam_centers c ON e.center_id = c.center_id
    WHERE da.user_id = ? AND da.role_in_exam = 'superintendent'
    ORDER BY e.exam_date ASC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$duties_result = mysqli_stmt_get_result($stmt);

// Fetch uploaded reports
$reports_sql = "
    SELECT r.report_id, r.report_file, r.remarks, r.submitted_at, da.assignment_id, e.exam_name
    FROM reports r
    INNER JOIN duty_assignments da ON r.duty_id = da.assignment_id
    INNER JOIN exams e ON da.exam_id = e.exam_id
    WHERE r.uploaded_by = ?
    ORDER BY r.submitted_at DESC
";
$stmt = mysqli_prepare($conn, $reports_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$reports_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Exam Reports</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-4">
            <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
    <h2>Upload Exam Reports</h2>
    <?php if (!empty($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

    <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Assigned Duty</label>
                    <select name="duty_id" class="form-control" required>
                        <option value="">Select Duty</option>
                        <?php while ($duty = mysqli_fetch_assoc($duties_result)): ?>
                            <option value="<?php echo $duty['assignment_id']; ?>">
                                <?php echo $duty['exam_name'] . " - " . $duty['exam_date'] . " (" . $duty['center_name'] . ")"; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Report File (PDF/DOC/DOCX)</label>
                    <input type="file" name="report_file" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" name="upload_report" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>

    <!-- Uploaded Reports Table -->
    <h3>My Uploaded Reports</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Exam</th>
                <th>Report File</th>
                <th>Remarks</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($reports_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($reports_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                        <td><a href="../uploads/<?php echo $row['report_file']; ?>" target="_blank"><?php echo htmlspecialchars($row['report_file']); ?></a></td>
                        <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                        <td><?php echo $row['submitted_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No reports uploaded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
