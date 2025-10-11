<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

if (!isset($_GET["exam_id"])) {
    header("Location: exam_view.php");
    exit;
}

$exam_id = intval($_GET["exam_id"]);

// fetch exam
$sql = "SELECT * FROM Exams WHERE exam_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $exam_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$exam = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$exam) {
    header("Location: exam_view.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $exam_name = $_POST["exam_name"];
    $exam_date = $_POST["exam_date"];
    $center_id = $_POST["center_id"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];

    $sql = "UPDATE Exams SET exam_name=?, exam_date=?, center_id=?, start_time=?, end_time=? WHERE exam_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssissi", $exam_name, $exam_date, $center_id, $start_time, $end_time, $exam_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Exam updated successfully.";
    } else {
        $message = "Failed to update Exam.";
    }
    mysqli_stmt_close($stmt);
}

// fetch centers
$centers = mysqli_query($conn, "SELECT * FROM exam_centers");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Exam</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2>Edit Exam</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Exam Name:</label>
                    <input type="text" name="exam_name" value="<?php echo htmlspecialchars($exam['exam_name']); ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Exam Date:</label>
                    <input type="date" name="exam_date" value="<?php echo $exam['exam_date']; ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Center:</label>
                    <select name="center_id" class="form-control" required>
                        <?php while ($c = mysqli_fetch_assoc($centers)) { ?>
                            <option value="<?php echo $c['center_id']; ?>" <?php if ($c['center_id'] == $exam['center_id']) echo "selected"; ?>>
                                <?php echo $c['center_name']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Time:</label>
                    <input type="time" name="start_time" value="<?php echo $exam['start_time']; ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>End Time:</label>
                    <input type="time" name="end_time" value="<?php echo $exam['end_time']; ?>" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Update Exam</button>
                <a class="btn btn-dark" href="exam_view.php">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
