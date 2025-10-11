<?php
include('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Add assignment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $exam_id = $_POST["exam_id"];
    $user_id = $_POST["user_id"];
    $role_in_exam = $_POST["role_in_exam"];
    $status = $_POST["status"];

    $sql = "INSERT INTO duty_assignments (exam_id, user_id, role_in_exam, status) VALUES (?,?,?,?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $exam_id, $user_id, $role_in_exam, $status);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Duty assigned successfully.";

        // Fetch user email and name
        $user_stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE user_id=?");
        mysqli_stmt_bind_param($user_stmt, "i", $user_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_data = mysqli_fetch_assoc($user_result);
        mysqli_stmt_close($user_stmt);

        // Fetch exam details
        $exam_stmt = mysqli_prepare($conn, "SELECT exam_name, exam_date, start_time, end_time FROM exams WHERE exam_id=?");
        mysqli_stmt_bind_param($exam_stmt, "i", $exam_id);
        mysqli_stmt_execute($exam_stmt);
        $exam_result = mysqli_stmt_get_result($exam_stmt);
        $exam_data = mysqli_fetch_assoc($exam_result);
        mysqli_stmt_close($exam_stmt);

        // Send email notification
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';  // Replace with your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'nasiryt.827@gmail.com';
            $mail->Password   = 'ddgc zhft gohg bjmz';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('nasiryt.827@gmail.com', 'Vu Proctor - Exam Management');
            $mail->addAddress($user_data['email'], $user_data['name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Exam Duty Assigned';
            $mail->Body    = "
                <p>Dear {$user_data['name']},</p>
                <p>You have been assigned a new exam duty.</p>
                <ul>
                    <li>Exam: {$exam_data['exam_name']}</li>
                    <li>Date: {$exam_data['exam_date']}</li>
                    <li>Time: {$exam_data['start_time']} - {$exam_data['end_time']}</li>
                    <li>Role: {$role_in_exam}</li>
                    <li>Status: {$status}</li>
                </ul>
                <p>Please ensure to be available and follow the exam instructions.</p>
                <p>Regards,<br>Exam Management Team</p>
            ";

            $mail->send();
            $message .= " Notification email sent successfully.";
        } catch (Exception $e) {
            $message .= " Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        $message = "Failed to assign duty: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Fetch exams
$exams = mysqli_query($conn, "SELECT exam_id, exam_name, exam_date FROM exams ORDER BY exam_date ASC");

// Fetch users
$users = mysqli_query($conn, "SELECT user_id, name, usertype, availability_status, center_preference FROM users WHERE status='approved'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Duty</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 700px;">
        <div class="card-body">
            <h2 class="mb-4">Assign Duty</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Select Exam:</label>
                        <select name="exam_id" required class="form-control">
                            <option value="">-- Select Exam --</option>
                            <?php while($exam = mysqli_fetch_assoc($exams)): ?>
                                <option value="<?php echo $exam['exam_id']; ?>">
                                    <?php echo htmlspecialchars($exam['exam_name']) . " (" . $exam['exam_date'] . ")"; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Select User:</label>
                        <select name="user_id" required class="form-control">
                            <option value="">-- Select User --</option>
                            <?php 
                            mysqli_data_seek($users, 0); 
                            while($user = mysqli_fetch_assoc($users)): 
                                $leave_stmt = mysqli_prepare($conn, "SELECT start_date, end_date FROM leave_requests WHERE user_id=? AND status='approved' ORDER BY requested_at DESC LIMIT 5");
                                mysqli_stmt_bind_param($leave_stmt, "i", $user['user_id']);
                                mysqli_stmt_execute($leave_stmt);
                                $leave_result = mysqli_stmt_get_result($leave_stmt);
                                $leave_dates = [];
                                while($leave = mysqli_fetch_assoc($leave_result)) {
                                    $leave_dates[] = $leave['start_date'] . " to " . $leave['end_date'];
                                }
                                $leave_text = !empty($leave_dates) ? " | Approved Leaves: " . implode(", ", $leave_dates) : "";
                                mysqli_stmt_close($leave_stmt);
                            ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['name']) . " - " . $user['usertype'] . " | Pref: " . $user['center_preference'] . " | Status: " . $user['availability_status'] . $leave_text; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Role in Exam:</label>
                        <select name="role_in_exam" required class="form-control">
                            <option value="">-- Select Role --</option>
                            <option value="superintendent">Superintendent</option>
                            <option value="invigilator">Invigilator</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Status:</label>
                        <select name="status" class="form-control">
                            <option value="assigned">Assigned</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="form-row mt-3">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-success btn-block">Assign Duty</button>
                    </div>
                    <div class="col-md-6">
                        <a class="btn btn-dark btn-block" href="duty_assign_view.php">View Assignments</a>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
</body>
</html>
