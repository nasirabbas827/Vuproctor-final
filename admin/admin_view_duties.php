<?php
include('config.php');
session_start();

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['user_id'])){
    die("User not specified.");
}

$user_id = intval($_GET['user_id']);

// Fetch user info
$user_stmt = mysqli_prepare($conn, "SELECT name, usertype FROM users WHERE user_id=?");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

// Fetch duties for user
$duties_sql = "
SELECT da.assignment_id, da.role_in_exam, da.status AS duty_status,
       e.exam_name, e.exam_date, e.start_time, e.end_time,
       c.center_name, c.location
FROM duty_assignments da
JOIN exams e ON da.exam_id = e.exam_id
JOIN exam_centers c ON e.center_id = c.center_id
WHERE da.user_id=?
ORDER BY e.exam_date ASC, e.start_time ASC
";
$stmt = mysqli_prepare($conn, $duties_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$duties_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Fetch attendance for user
$attendance_sql = "
SELECT a.attendance_id, da.assignment_id, e.exam_name, e.exam_date, c.center_name, c.location,
       a.status AS attendance_status, a.verification_status, a.marked_at
FROM attendance a
JOIN duty_assignments da ON a.duty_id = da.assignment_id
JOIN exams e ON da.exam_id = e.exam_id
JOIN exam_centers c ON e.center_id = c.center_id
WHERE a.user_id=?
ORDER BY e.exam_date ASC
";
$stmt = mysqli_prepare($conn, $attendance_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Count eligible duties for payment
$payment_count_sql = "
SELECT COUNT(*) AS total
FROM duty_assignments da
JOIN attendance a ON da.assignment_id = a.duty_id AND da.user_id = a.user_id
WHERE da.user_id=? 
AND (da.status='assigned' OR da.status='completed')
AND a.status='present' AND a.verification_status='verified'
";
$stmt = mysqli_prepare($conn, $payment_count_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$payment_count_result = mysqli_stmt_get_result($stmt);
$payment_count = mysqli_fetch_assoc($payment_count_result)['total'];
mysqli_stmt_close($stmt);

// Fetch existing payments
$payments_sql = "SELECT * FROM payments WHERE user_id=? ORDER BY payment_id DESC";
$stmt = mysqli_prepare($conn, $payments_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$payments_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Duty & Attendance Details - <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <?php
if(isset($_SESSION['message'])){
    echo '<div class="alert alert-info">'.$_SESSION['message'].'</div>';
    unset($_SESSION['message']);
}
?>

    <h2>Details for <?php echo htmlspecialchars($user['name']); ?> (<?php echo $user['usertype']; ?>)</h2>
    <a href="admin_users.php" class="btn btn-secondary mb-3">Back to Users</a>

    <!-- Payment Request -->
    <div class="mb-3">
        <p>Total eligible duties for payment: <strong><?php echo $payment_count; ?></strong></p>
        <?php if($payment_count > 0): ?>
            <button class="btn btn-success" data-toggle="modal" data-target="#paymentModal">Payment Request</button>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>No Eligible Duties</button>
        <?php endif; ?>
    </div>

    <!-- Duty Assignments Table -->
    <h3>Duty Assignments</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Assignment ID</th>
                <th>Exam</th>
                <th>Date</th>
                <th>Time</th>
                <th>Center</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if(mysqli_num_rows($duties_result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($duties_result)): ?>
                <tr>
                    <td><?php echo $row['assignment_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                    <td><?php echo $row['exam_date']; ?></td>
                    <td><?php echo $row['start_time'] . " - " . $row['end_time']; ?></td>
                    <td><?php echo htmlspecialchars($row['center_name'])." (".$row['location'].")"; ?></td>
                    <td><?php echo ucfirst($row['role_in_exam']); ?></td>
                    <td><?php echo ucfirst($row['duty_status']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No duties found for this user.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Attendance Table -->
    <h3 class="mt-5">Attendance Records</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Attendance ID</th>
                <th>Assignment ID</th>
                <th>Exam</th>
                <th>Date</th>
                <th>Center</th>
                <th>Status</th>
                <th>Verification</th>
                <th>Marked At</th>
            </tr>
        </thead>
        <tbody>
        <?php if(mysqli_num_rows($attendance_result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($attendance_result)): ?>
                <tr>
                    <td><?php echo $row['attendance_id']; ?></td>
                    <td><?php echo $row['assignment_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                    <td><?php echo $row['exam_date']; ?></td>
                    <td><?php echo htmlspecialchars($row['center_name'])." (".$row['location'].")"; ?></td>
                    <td><?php echo ucfirst($row['attendance_status']); ?></td>
                    <td><?php echo ucfirst($row['verification_status']); ?></td>
                    <td><?php echo $row['marked_at']; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No attendance records found for this user.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Existing Payments Table -->
    <h3 class="mt-5">Payment Requests</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Status</th>
                <th>Processed At</th>
            </tr>
        </thead>
        <tbody>
        <?php if(mysqli_num_rows($payments_result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($payments_result)): ?>
                <tr>
                    <td><?php echo $row['payment_id']; ?></td>
                    <td><?php echo number_format($row['amount'],2); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td>
                        <?php 
                        if($row['status']=='pending'){
                            echo '<span class="text-warning">'.ucfirst($row['status']).'</span>';
                        } else {
                            echo '<span class="text-success">'.ucfirst($row['status']).'</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo $row['processed_at'] ?? '-'; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No payment requests found for this user.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form action="process_payment.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLabel">Payment Request</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
                <div class="form-group">
                    <label>Total Duties Eligible: <?php echo $payment_count; ?></label>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Submit Payment Request</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          </div>
        </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS & dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
