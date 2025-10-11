<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['center_id'])) {
    header("Location: exam_center_view.php");
    exit;
}

$center_id = intval($_GET['center_id']);
$message = "";

// Fetch center details
$sql = "SELECT * FROM exam_centers WHERE center_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $center_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$center = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$center) {
    echo "<div class='alert alert-danger'>Exam center not found.</div>";
    exit;
}

// Update logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $center_name = trim($_POST["center_name"]);
    $location = trim($_POST["location"]);

    $sql = "UPDATE exam_centers SET center_name = ?, location = ? WHERE center_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $center_name, $location, $center_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Exam Center updated successfully.";
        $center['center_name'] = $center_name;
        $center['location'] = $location;
    } else {
        $message = "Failed to update Exam Center.";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Exam Center</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2>Edit Exam Center</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Center Name:</label>
                    <input type="text" name="center_name" required class="form-control" 
                           placeholder="Enter center name"
                           value="<?php echo htmlspecialchars($center['center_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Location:</label>
                    <input type="text" name="location" required class="form-control" 
                           placeholder="Enter location"
                           value="<?php echo htmlspecialchars($center['location']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update Center</button>
                <a class="btn btn-dark" href="exam_center_view.php">Back to Centers</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
