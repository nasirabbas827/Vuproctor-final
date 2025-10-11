<?php
include('config.php');
session_start();

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Fetch all users
$user_sql = "SELECT user_id, name, usertype FROM users ORDER BY name ASC";
$user_result = mysqli_query($conn, $user_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Users List</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <h2>All Users</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>User Type</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($user = mysqli_fetch_assoc($user_result)): ?>
            <tr>
                <td><?php echo $user['user_id']; ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo $user['usertype']; ?></td>
                <td>
                    <a href="admin_view_duties.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">View Duty Details</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
