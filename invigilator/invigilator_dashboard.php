<?php
include('config.php');
session_start();

// ---------------------------------------------
// 1. Authentication Check
// ---------------------------------------------
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = "";
$user_role = ""; // Store the user's usertype/role

// ---------------------------------------------
// 2. Fetch User Details (Name and Role)
// ---------------------------------------------
$user_sql = "SELECT name, usertype FROM users WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
if ($user_data = mysqli_fetch_assoc($user_result)) {
    $user_name = htmlspecialchars($user_data['name']);
    $user_role = htmlspecialchars($user_data['usertype']);
}

// ---------------------------------------------
// 3. Fetch Latest 5 Assigned Duties
// ---------------------------------------------
$duties_sql = "
    SELECT da.assignment_id, da.role_in_exam, da.status AS duty_status,
           e.exam_name, e.exam_date, e.start_time, e.end_time,
           c.center_name, c.location
    FROM duty_assignments da
    INNER JOIN exams e ON da.exam_id = e.exam_id
    INNER JOIN exam_centers c ON e.center_id = c.center_id
    WHERE da.user_id = ?
    ORDER BY e.exam_date DESC, e.start_time DESC
    LIMIT 5
";
$duties_stmt = mysqli_prepare($conn, $duties_sql);
mysqli_stmt_bind_param($duties_stmt, "i", $user_id);
mysqli_stmt_execute($duties_stmt);
$duties_result = mysqli_stmt_get_result($duties_stmt);

// ---------------------------------------------
// 4. Fetch Latest 3 Payments (Notifications)
// ---------------------------------------------
$payments_sql = "
    SELECT amount, status, processed_at
    FROM payments
    WHERE user_id = ?
    ORDER BY processed_at DESC
    LIMIT 3
";
$payments_stmt = mysqli_prepare($conn, $payments_sql);
mysqli_stmt_bind_param($payments_stmt, "i", $user_id);
mysqli_stmt_execute($payments_stmt);
$payments_result = mysqli_stmt_get_result($payments_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $user_role; ?> Dashboard | VU Proctors-Diary</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Custom styles for dashboard look */
        .dashboard-card {
            border-left: 5px solid;
            border-radius: .35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .card-duties { border-left-color: #007bff; }
        .card-payments { border-left-color: #28a745; }
        .card-leaves { border-left-color: #ffc107; }
        .notification-list {
            list-style: none;
            padding: 0;
        }
        .notification-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .notification-list li:last-child {
            border-bottom: none;
        }
        .table-duties th, .table-duties td {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); // Assuming navbar.php includes the structure for a navigation bar ?>

    <div class="container mt-4">
        <h2 class="mb-4">Welcome, <?php echo $user_name; ?>! <span class="badge badge-primary"><?php echo ucfirst($user_role); ?></span></h2>

        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card bg-light card-duties shadow-sm">
                    <h5 class="text-primary"><i class="fas fa-calendar-check"></i> Latest Duties</h5>
                    <p class="mb-0 text-dark">Check your next 5 assignments.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card bg-light card-payments shadow-sm">
                    <h5 class="text-success"><i class="fas fa-money-check-alt"></i> Payments</h5>
                    <p class="mb-0 text-dark">Review recent payment transactions.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card bg-light card-leaves shadow-sm">
                    <h5 class="text-warning"><i class="fas fa-plane-departure"></i> Leave Requests</h5>
                    <p class="mb-0 text-dark">Manage your availability.</p>
                </div>
            </div>
        </div>
        
        <hr>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Latest Assigned Duties</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-sm table-duties">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Center</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($duties_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($duties_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['exam_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['start_time'])) . " - " . date('h:i A', strtotime($row['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['center_name']); ?></td>
                                            <td><?php echo ucfirst($row['role_in_exam']); ?></td>
                                            <td><span class="badge badge-<?php 
                                                if ($row['duty_status'] == 'completed') echo 'success'; 
                                                elseif ($row['duty_status'] == 'assigned') echo 'primary'; 
                                                else echo 'danger'; 
                                            ?>"><?php echo ucfirst($row['duty_status']); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No recent duties assigned.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <a href="assigned_duties.php" class="btn btn-sm btn-outline-info float-right">View All Duties</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-bell"></i> Payment Notifications</h5>
                    </div>
                    <div class="card-body">
                        <ul class="notification-list">
                            <?php if (mysqli_num_rows($payments_result) > 0): ?>
                                <?php while ($payment = mysqli_fetch_assoc($payments_result)): ?>
                                    <li>
                                        <i class="fas fa-circle text-success mr-2" style="font-size: 0.6rem;"></i> 
                                        Payment of **Rs. <?php echo number_format($payment['amount'], 2); ?>** <?php if ($payment['status'] == 'processed'): ?>
                                            has been **Processed**.
                                            <p class="text-muted small mb-0">Processed on: <?php echo date('M d, Y', strtotime($payment['processed_at'])); ?></p>
                                        <?php else: ?>
                                            is **<?php echo ucfirst($payment['status']); ?>**.
                                        <?php endif; ?>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="text-center text-muted">No recent payment records found.</li>
                            <?php endif; ?>
                        </ul>
                        <a href="view_payments.php" class="btn btn-sm btn-outline-success btn-block mt-3">View Payment History</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($user_role === 'superintendent'): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Superintendent Actions</h5>
                </div>
                <div class="card-body">
                    <p>As a Superintendent, you have the following administrative duties:</p>
                    <a href="superintendent_reports.php" class="btn btn-primary"><i class="fas fa-file-upload"></i> Upload Exam Report</a>
                    <a href="update_profile.php" class="btn btn-secondary"><i class="fas fa-users-cog"></i> Update Profile</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>