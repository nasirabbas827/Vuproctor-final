<?php
include('config.php');

$email = $password = $usertype = "";
$email_err = $password_err = $usertype_err = $login_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate usertype
    if (empty(trim($_POST["usertype"]))) {
        $usertype_err = "Please select user type.";
    } else {
        $usertype = trim($_POST["usertype"]);
    }

    // If no validation errors
    if (empty($email_err) && empty($password_err) && empty($usertype_err)) {
        $sql = "SELECT user_id, email, password_hash, usertype, status FROM users WHERE email = ? AND usertype = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $param_email, $param_usertype);
        $param_email = $email;
        $param_usertype = $usertype;
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $user_id, $db_email, $hashed_password, $db_usertype, $status);
            if (mysqli_stmt_fetch($stmt)) {
                if ($status !== 'approved') {
                    $login_msg = '<div class="alert alert-warning">Your account is not approved yet.</div>';
                } elseif (password_verify($password, $hashed_password)) {
                    session_start();
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["email"] = $db_email;
                    $_SESSION["usertype"] = $db_usertype;

                    // Redirect to dashboards based on usertype
                    if ($db_usertype == 'superintendent') {
                        header("Location: superintendent/superintendent_dashboard.php");
                    } elseif ($db_usertype == 'invigilator') {
                        header("Location: invigilator/invigilator_dashboard.php");
                    } else {
                        $login_msg = '<div class="alert alert-danger">Invalid user type assigned.</div>';
                    }
                    exit();
                } else {
                    $password_err = "The password you entered is incorrect.";
                }
            }
        } else {
            $email_err = "No account found with that email and selected user type.";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login - VU Proctors-Diary</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h2 class="text-center">User Login</h2>
                <p class="text-center">Please enter your credentials to log in.</p>

                <?php echo $login_msg; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" name="email" placeholder="Enter your email"
                            class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                            value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter your password"
                            class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>

                    <div class="form-group">
                        <label>User Type</label>
                        <select name="usertype" class="form-control <?php echo (!empty($usertype_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select user type</option>
                            <option value="superintendent" <?php echo ($usertype == "superintendent") ? 'selected' : ''; ?>>Superintendent</option>
                            <option value="invigilator" <?php echo ($usertype == "invigilator") ? 'selected' : ''; ?>>Invigilator</option>
                        </select>
                        <span class="invalid-feedback"><?php echo $usertype_err; ?></span>
                    </div>

                    <div class="form-group text-center">
                        <input type="submit" value="Log in" class="btn btn-primary">
                    </div>
                </form>

                <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
