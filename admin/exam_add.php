<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $exam_name = $_POST["exam_name"];
    $exam_date = $_POST["exam_date"];
    $center_id = $_POST["center_id"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];

    $sql = "INSERT INTO Exams (exam_name, exam_date, center_id, start_time, end_time) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssiss", $exam_name, $exam_date, $center_id, $start_time, $end_time);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Exam added successfully.";
    } else {
        $message = "Failed to add Exam.";
    }
    mysqli_stmt_close($stmt);
}

// fetch centers
$centers = mysqli_query($conn, "SELECT * FROM exam_centers");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Exam</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2>Add Exam</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Exam Name:</label>
                    <input type="text" name="exam_name" required class="form-control" placeholder="Enter exam name">
                </div>
                <div class="form-group">
                    <label>Exam Date:</label>
                    <input type="date" name="exam_date" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Center:</label>
                    <select name="center_id" class="form-control" required>
                        <option value="">Select Center</option>
                        <?php while ($c = mysqli_fetch_assoc($centers)) { ?>
                            <option value="<?php echo $c['center_id']; ?>"><?php echo $c['center_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Time:</label>
                    <input type="time" name="start_time" required class="form-control">
                </div>
                <div class="form-group">
                    <label>End Time:</label>
                    <input type="time" name="end_time" required class="form-control">
                </div>
                <button type="submit" class="btn btn-success">Add Exam</button>
                <a class="btn btn-dark" href="exam_view.php">View Exams</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
