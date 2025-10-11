<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $center_name = $_POST["center_name"];
    $location = $_POST["location"];

    $sql = "INSERT INTO Exam_Centers (center_name, location) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $center_name, $location);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Exam Center added successfully.";
    } else {
        $message = "Failed to add Exam Center.";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Exam Center</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2>Add Exam Center</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Center Name:</label>
                    <input type="text" name="center_name" required class="form-control" placeholder="Enter center name">
                </div>
                <div class="form-group">
                    <label>Location:</label>
                    <input type="text" name="location" required class="form-control" placeholder="Enter location">
                </div>
                <button type="submit" class="btn btn-success">Add Center</button>
                <a class="btn btn-dark" href="exam_center_view.php">View Centers</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
