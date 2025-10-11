<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['user_id'])) {
    header("Location: view_users.php");
    exit;
}

$user_id = intval($_GET['user_id']);
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-card {
            max-width: 800px;
            margin: 40px auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .profile-card .card-header {
            font-size: 22px;
            font-weight: bold;
        }
        .profile-item {
            margin-bottom: 10px;
        }
        .profile-item strong {
            width: 180px;
            display: inline-block;
        }
        .profile-pic {
            text-align: center;
            padding: 20px;
        }
        .profile-pic img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
        }
    </style>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container">
    <div class="card profile-card">
        <div class="card-header">User Profile</div>
        <div class="card-body">
            <div class="profile-pic">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="../images/<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="../images/default.png" alt="Default Profile">
                <?php endif; ?>
            </div>
            <div class="profile-item"><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></div>
            <div class="profile-item"><strong>Employee ID:</strong> <?= htmlspecialchars($user['employee_id']) ?></div>
            <div class="profile-item"><strong>User Type:</strong> <?= htmlspecialchars($user['usertype']) ?></div>
            <div class="profile-item"><strong>CNIC:</strong> <?= htmlspecialchars($user['cnic']) ?></div>
            <div class="profile-item"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></div>
            <div class="profile-item"><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></div>
            <div class="profile-item"><strong>Qualification:</strong> <?= htmlspecialchars($user['qualification']) ?></div>
            <div class="profile-item"><strong>Center Preference:</strong> <?= htmlspecialchars($user['center_preference']) ?></div>
            <div class="profile-item"><strong>Availability:</strong> <?= htmlspecialchars($user['availability_status']) ?></div>
            <div class="profile-item"><strong>Status:</strong> <?= htmlspecialchars($user['status']) ?></div>
            <div class="profile-item"><strong>Registered On:</strong> <?= date("d M Y H:i", strtotime($user['created_at'])) ?></div>
        </div>
    </div>
</div>
</body>
</html>
