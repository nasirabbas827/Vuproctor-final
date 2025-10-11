<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';


$message = "";

if (!isset($_GET['assignment_id'])) {
    header("Location: duty_assign_view.php");
    exit;
}

$assignment_id = intval($_GET['assignment_id']);

// Fetch current assignment
$sql = "SELECT * FROM duty_assignments WHERE assignment_id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    header("Location: duty_assign_view.php");
    exit;
}

// Fetch exams
$exams = mysqli_query($conn, "SELECT exam_id, exam_name, exam_date FROM exams ORDER BY exam_date ASC");

// Fetch users
$users = mysqli_query($conn, "SELECT user_id, name, email, usertype, availability_status, center_preference 
                              FROM users WHERE status='approved'");

// Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $exam_id = $_POST["exam_id"];
    $user_id = $_POST["user_id"];
    $role_in_exam = $_POST["role_in_exam"];
    $status = $_POST["status"];

    $sql = "UPDATE duty_assignments SET exam_id=?, user_id=?, role_in_exam=?, status=? WHERE assignment_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iissi", $exam_id, $user_id, $role_in_exam, $status, $assignment_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Assignment updated successfully.";

        // Fetch user email
        $user_query = mysqli_query($conn, "SELECT email, name FROM users WHERE user_id = $user_id");
        $user_data = mysqli_fetch_assoc($user_query);
        $user_email = $user_data['email'];
        $user_name  = $user_data['name'];

        // Fetch exam details
        $exam_query = mysqli_query($conn, "SELECT exam_name, exam_date FROM exams WHERE exam_id = $exam_id");
        $exam_data = mysqli_fetch_assoc($exam_query);
        $exam_name = $exam_data['exam_name'];
        $exam_date = $exam_data['exam_date'];

        // Send Email Notification
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';  // Replace with your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'nasiryt.827@gmail.com';
            $mail->Password   = 'ddgc zhft gohg bjmz';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('nasiryt.827@gmail.com', 'Vu Proctor - Exam Management');
            $mail->addAddress($user_email, $user_name);

            $mail->isHTML(true);
            $mail->Subject = "Updated Exam Duty Assignment";
            $mail->Body    = "
                Hello $user_name,<br><br>
                Your exam duty has been updated.<br>
                <strong>Exam:</strong> $exam_name<br>
                <strong>Date:</strong> $exam_date<br>
                <strong>Role:</strong> $role_in_exam<br>
                <strong>Status:</strong> $status<br><br>
                Please check your portal for more details.<br><br>
                Regards,<br>
                Admin
            ";

            $mail->send();
            $message .= " Email notification sent.";
        } catch (Exception $e) {
            $message .= " Email could not be sent.";
        }

    } else {
        $message = "Failed to update assignment.";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Duty Assignment</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-body">
            <h2>Edit Duty Assignment</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Select Exam:</label>
                        <select name="exam_id" required class="form-control">
                            <?php while($exam = mysqli_fetch_assoc($exams)): ?>
                                <option value="<?php echo $exam['exam_id']; ?>" 
                                    <?php if($assignment['exam_id'] == $exam['exam_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($exam['exam_name'])." (".$exam['exam_date'].")"; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Select User:</label>
                        <select name="user_id" required class="form-control">
                            <?php mysqli_data_seek($users, 0); ?>
                            <?php while($user = mysqli_fetch_assoc($users)): ?>
                                <option value="<?php echo $user['user_id']; ?>" 
                                    <?php if($assignment['user_id'] == $user['user_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($user['name'])." - ".$user['usertype']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Role in Exam:</label>
                        <select name="role_in_exam" required class="form-control">
                            <option value="superintendent" <?php if($assignment['role_in_exam']=='superintendent') echo 'selected'; ?>>Superintendent</option>
                            <option value="invigilator" <?php if($assignment['role_in_exam']=='invigilator') echo 'selected'; ?>>Invigilator</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Status:</label>
                        <select name="status" class="form-control">
                            <option value="assigned" <?php if($assignment['status']=='assigned') echo 'selected'; ?>>Assigned</option>
                            <option value="completed" <?php if($assignment['status']=='completed') echo 'selected'; ?>>Completed</option>
                            <option value="cancelled" <?php if($assignment['status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Assignment</button>
                <a href="duty_assign_view.php" class="btn btn-dark">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
