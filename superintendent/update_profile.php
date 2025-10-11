<?php
include('config.php');
session_start();

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$update_msg = "";

// Fetch current user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $qualification = trim($_POST["qualification"]);
    $center_preference = trim($_POST["center_preference"]);
    $availability_status = trim($_POST["availability_status"]);

    // Profile image upload
    $profile_image = $user['profile_image']; // keep old image if not changed
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "../images/";
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image = $image_name;
            }
        }
    }

    $sql_update = "UPDATE users 
                   SET name = ?, email = ?, phone = ?, qualification = ?, center_preference = ?, 
                       availability_status = ?, profile_image = ?, updated_at = NOW() 
                   WHERE user_id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "sssssssi", 
        $name, $email, $phone, $qualification, $center_preference, $availability_status, $profile_image, $user_id
    );

    if (mysqli_stmt_execute($stmt_update)) {
        $update_msg = '<div class="alert alert-success">Profile updated successfully.</div>';
        // Refresh user data
        $user["name"] = $name;
        $user["email"] = $email;
        $user["phone"] = $phone;
        $user["qualification"] = $qualification;
        $user["center_preference"] = $center_preference;
        $user["availability_status"] = $availability_status;
        $user["profile_image"] = $profile_image;
    } else {
        $update_msg = '<div class="alert alert-danger">Error updating profile. Try again.</div>';
    }
    mysqli_stmt_close($stmt_update);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Profile - VU Proctors-Diary</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ddd;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 700px;">
        <div class="card-body text-center">
            <h2>Update Profile</h2>
            <p>You can update your details here (except Employee ID, CNIC, User Type, and Status).</p>
            <?php echo $update_msg; ?>

            <!-- Profile Image -->
            <div class="mb-3">
                <img src="../images/<?php echo htmlspecialchars($user['profile_image']); ?>" class="profile-img" alt="Profile Image">
            </div>

            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="profile_image" class="form-control-file">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Employee ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['employee_id']); ?>" class="form-control" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>CNIC</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['cnic']); ?>" class="form-control" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>User Type</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['usertype']); ?>" class="form-control" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Status</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['status']); ?>" class="form-control" readonly>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                               placeholder="Enter full name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                               placeholder="Enter email address" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                               placeholder="03XXXXXXXXX" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Qualification</label>
                        <input type="text" name="qualification" value="<?php echo htmlspecialchars($user['qualification']); ?>" 
                               placeholder="Enter qualification" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Center Preference</label>
                        <input type="text" name="center_preference" value="<?php echo htmlspecialchars($user['center_preference']); ?>" 
                               placeholder="Enter preferred center" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Availability Status</label>
                        <select name="availability_status" class="form-control">
                            <option value="available" <?php echo ($user['availability_status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo ($user['availability_status'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
