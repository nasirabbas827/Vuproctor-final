<?php
include('config.php');
session_start();

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Fetch assigned duties with exam and center details
$sql = "
    SELECT da.assignment_id, da.role_in_exam, da.status, 
           e.exam_name, e.exam_date, e.start_time, e.end_time, 
           c.center_name, c.location
    FROM duty_assignments da
    INNER JOIN exams e ON da.exam_id = e.exam_id
    INNER JOIN exam_centers c ON e.center_id = c.center_id
    WHERE da.user_id = ?
    ORDER BY e.exam_date ASC, e.start_time ASC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Superintendent Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="container">
        <h2>My Assigned Duties</h2>
    <table class="table table-bordered table-striped">

            <thead>
                <tr>
                    <th>Exam</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Center</th>
                    <th>Location</th>
                    <th>Role</th>
                    <th>Status</th>
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
                            <td><?php echo ucfirst($row['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No duties assigned yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
