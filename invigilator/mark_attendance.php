<?php
include('config.php');
session_start();

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Handle attendance submission
if (isset($_POST['mark_attendance'])) {
    $duty_id = intval($_POST['duty_id']);
    $status = $_POST['status'];

    // Check if attendance already exists
    $check_sql = "SELECT * FROM attendance WHERE duty_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $duty_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) > 0) {
        // Update existing attendance
        $update_sql = "UPDATE attendance SET status = ?, marked_at = NOW(), verification_status = 'pending' WHERE duty_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sii", $status, $duty_id, $user_id);
        mysqli_stmt_execute($stmt);
    } else {
        // Insert new attendance
        $insert_sql = "INSERT INTO attendance (duty_id, user_id, status) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iis", $duty_id, $user_id, $status);
        mysqli_stmt_execute($stmt);
    }

    $msg = "Attendance marked successfully!";
}

// Fetch assigned duties
$sql = "
    SELECT da.assignment_id, da.role_in_exam, da.status AS duty_status,
           e.exam_name, e.exam_date, e.start_time, e.end_time,
           c.center_name, c.location,
           a.status AS attendance_status , a.verification_status As verification_status
    FROM duty_assignments da
    INNER JOIN exams e ON da.exam_id = e.exam_id
    INNER JOIN exam_centers c ON e.center_id = c.center_id
    LEFT JOIN attendance a ON da.assignment_id = a.duty_id AND a.user_id = ?
    WHERE da.user_id = ?
    ORDER BY e.exam_date ASC, e.start_time ASC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mark Attendance</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class="container mt-4">
        <h2>Mark Your Attendance</h2>
        <?php if (!empty($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Exam</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Center</th>
                    <th>Location</th>
                    <th>Role</th>
                    <th>Attendance Status</th>
                    <th>Verification Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['exam_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['start_time']) . " - " . htmlspecialchars($row['end_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['center_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo ucfirst($row['role_in_exam']); ?></td>
                            <td><?php echo $row['attendance_status'] ? ucfirst($row['attendance_status']) : 'Not Marked'; ?></td>
                            <td><?php echo htmlspecialchars($row['verification_status']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="duty_id" value="<?php echo $row['assignment_id']; ?>">
                                    <select name="status" class="form-control mb-1">
                                        <option value="present" <?php if($row['attendance_status']=='present') echo 'selected'; ?>>Present</option>
                                        <option value="absent" <?php if($row['attendance_status']=='absent') echo 'selected'; ?>>Absent</option>
                                    </select>
                                    <button type="submit" name="mark_attendance" class="btn btn-primary btn-sm">Submit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No duties assigned yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
