<?php
session_start();
include('config.php');

// Authentication Check
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// =========================================================================
// 1. Fetch Stats (Using prepared statements, which is good practice)
// =========================================================================

// Fetch User Stats
$total_users_sql = "SELECT COUNT(*) as count FROM users WHERE status = 'approved'";
$total_users_stmt = mysqli_prepare($conn, $total_users_sql);
mysqli_stmt_execute($total_users_stmt);
$total_users_result = mysqli_stmt_get_result($total_users_stmt);
$total_users = mysqli_fetch_assoc($total_users_result)['count'];

$total_superintendents_sql = "SELECT COUNT(*) as count FROM users WHERE usertype = 'superintendent' AND status = 'approved'";
$total_superintendents_stmt = mysqli_prepare($conn, $total_superintendents_sql);
mysqli_stmt_execute($total_superintendents_stmt);
$total_superintendents_result = mysqli_stmt_get_result($total_superintendents_stmt);
$total_superintendents = mysqli_fetch_assoc($total_superintendents_result)['count'];

$total_invigilators_sql = "SELECT COUNT(*) as count FROM users WHERE usertype = 'invigilator' AND status = 'approved'";
$total_invigilators_stmt = mysqli_prepare($conn, $total_invigilators_sql);
mysqli_stmt_execute($total_invigilators_stmt);
$total_invigilators_result = mysqli_stmt_get_result($total_invigilators_stmt);
$total_invigilators = mysqli_fetch_assoc($total_invigilators_result)['count'];

// Fetch Exam Stats
$total_exams_sql = "SELECT COUNT(*) as count FROM exams";
$total_exams_stmt = mysqli_prepare($conn, $total_exams_sql);
mysqli_stmt_execute($total_exams_stmt);
$total_exams_result = mysqli_stmt_get_result($total_exams_stmt);
$total_exams = mysqli_fetch_assoc($total_exams_result)['count'];

$total_centers_sql = "SELECT COUNT(*) as count FROM exam_centers";
$total_centers_stmt = mysqli_prepare($conn, $total_centers_sql);
mysqli_stmt_execute($total_centers_stmt);
$total_centers_result = mysqli_stmt_get_result($total_centers_stmt);
$total_centers = mysqli_fetch_assoc($total_centers_result)['count'];

// Fetch Pending Users
$pending_users_sql = "SELECT user_id, name, employee_id, usertype, email FROM users WHERE status = 'pending' LIMIT 5";
$pending_users_stmt = mysqli_prepare($conn, $pending_users_sql);
mysqli_stmt_execute($pending_users_stmt);
$pending_users_result = mysqli_stmt_get_result($pending_users_stmt);

// =========================================================================
// 2. Fetch Data for the Duty Assignment Chart (Dynamic Data)
// =========================================================================

// Assigned
$assigned_sql = "SELECT COUNT(*) as count FROM duty_assignments WHERE status = 'assigned'";
$assigned_result = mysqli_query($conn, $assigned_sql);
$assigned_count = mysqli_fetch_assoc($assigned_result)['count'];

// Completed
$completed_sql = "SELECT COUNT(*) as count FROM duty_assignments WHERE status = 'completed'";
$completed_result = mysqli_query($conn, $completed_sql);
$completed_count = mysqli_fetch_assoc($completed_result)['count'];

// Cancelled
$cancelled_sql = "SELECT COUNT(*) as count FROM duty_assignments WHERE status = 'cancelled'";
$cancelled_result = mysqli_query($conn, $cancelled_sql);
$cancelled_count = mysqli_fetch_assoc($cancelled_result)['count'];

// Array to be passed to JavaScript
$duty_data = [$assigned_count, $completed_count, $cancelled_count];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | VU Proctors-Diary</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script> 
    <style>
        .dashboard-card { border-left: 5px solid; border-radius: .35rem; padding: 1rem; margin-bottom: 1.5rem; }
        .card-users { border-left-color: #007bff; }
        .card-exams { border-left-color: #28a745; }
        .card-pending { border-left-color: #dc3545; }
        .card-header { background-color: #fff; border-bottom: none; font-weight: bold; }
        .table th, .table td { font-size: 0.85rem; }
        .table th { background-color: #e9ecef; }
        /* Adjusted container for better chart visibility */
        .chart-container { max-width: 450px; margin: 0 auto; padding: 20px; } 
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <a class="navbar-brand" href="admin_home.php">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="admin_home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="view_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="exam_center_add.php">Centers</a></li>
                <li class="nav-item"><a class="nav-link" href="exam_add.php">Exams</a></li>
                <li class="nav-item"><a class="nav-link" href="duty_assign_add.php">Assign Duties</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_attendance_verify.php">Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_leave_requests.php">Leave Requests</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_view_reports.php">Exam Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_users.php">Payments</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Administrator Dashboard</h2>

        <div class="row">
            <div class="col-md-12">
                <div class="dashboard-card bg-light card-users shadow-sm">
                    <h5 class="text-primary"><i class="fas fa-cog"></i> Quick Actions</h5>
                    <div class="mt-3">
                        <a href="view_users.php" class="btn btn-sm btn-outline-primary mr-2"><i class="fas fa-users"></i> Manage Users</a>
                        <a href="duty_assign_add.php" class="btn btn-sm btn-outline-success mr-2"><i class="fas fa-calendar-check"></i> Assign Duties</a>
                        <a href="exam_add.php" class="btn btn-sm btn-outline-info mr-2"><i class="fas fa-book"></i> Manage Exams</a>
                        <a href="exam_center_add.php" class="btn btn-sm btn-outline-warning mr-2"><i class="fas fa-building"></i> Manage Centers</a>
                        <a href="admin_view_reports.php" class="btn btn-sm btn-outline-danger mr-2"><i class="fas fa-file-alt"></i> View Reports</a>
                        <a href="admin_attendance_verify.php" class="btn btn-sm btn-outline-secondary mr-2"><i class="fas fa-check-square"></i> Verify Attendance</a>
                        <a href="admin_leave_requests.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-plane-departure"></i> Leave Requests</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card bg-light card-users shadow-sm">
                    <h5 class="text-primary"><i class="fas fa-users"></i> Total Users</h5>
                    <p class="mb-0 text-dark"><?php echo $total_users; ?> Approved</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card bg-light card-users shadow-sm">
                    <h5 class="text-primary"><i class="fas fa-user-tie"></i> Superintendents</h5>
                    <p class="mb-0 text-dark"><?php echo $total_superintendents; ?> Approved</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card bg-light card-users shadow-sm">
                    <h5 class="text-primary"><i class="fas fa-user-check"></i> Invigilators</h5>
                    <p class="mb-0 text-dark"><?php echo $total_invigilators; ?> Approved</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="dashboard-card bg-light card-exams shadow-sm">
                    <h5 class="text-success"><i class="fas fa-book"></i> Total Exams</h5>
                    <p class="mb-0 text-dark"><?php echo $total_exams; ?> Scheduled</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card bg-light card-exams shadow-sm">
                    <h5 class="text-success"><i class="fas fa-building"></i> Exam Centers</h5>
                    <p class="mb-0 text-dark"><?php echo $total_centers; ?> Registered</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Duty Assignment Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dutyPieChart"></canvas> 
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-user-clock"></i> Pending User Approvals</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($pending_users_result) > 0): ?>
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Employee ID</th>
                                        <th>Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($pending_users_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                            <td><?php echo ucfirst($row['usertype']); ?></td>
                                            <td>
                                                <form method="POST" action="approve_user.php" style="display:inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <a href="view_users.php" class="btn btn-sm btn-outline-danger float-right">View All Pending Users</a>
                        <?php else: ?>
                            <p class="text-center text-muted">No users awaiting approval.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Get the dynamic data from PHP
        const dutyData = <?php echo json_encode($duty_data); ?>; 

        // Check if data is available and canvas exists
        if (dutyData && document.getElementById('dutyPieChart')) {
            const ctx = document.getElementById('dutyPieChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Assigned', 'Completed', 'Cancelled'],
                    datasets: [{
                        data: dutyData, // Use the dynamic PHP data here
                        backgroundColor: ['#007bff', '#28a745', '#dc3545'],
                        borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#333',
                                font: { size: 12 }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Total Duty Assignments Breakdown'
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>