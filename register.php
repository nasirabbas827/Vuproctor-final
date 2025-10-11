<?php
include('config.php');

$name = $cnic = $email = $phone = $password = $usertype = $qualification = $center_preference = "";
$name_err = $cnic_err = $email_err = $phone_err = $password_err = $usertype_err = $qualification_err = $center_preference_err = "";
$registration_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Full Name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter full name.";
    } else {
        $name = trim($_POST["name"]);
    }

    // CNIC (13 digits unique)
    if (empty(trim($_POST["cnic"]))) {
        $cnic_err = "Please enter CNIC.";
    } elseif (!preg_match("/^[0-9]{13}$/", $_POST["cnic"])) {
        $cnic_err = "CNIC must be 13 digits only.";
    } else {
        $cnic = trim($_POST["cnic"]);
        $sql = "SELECT user_id FROM users WHERE cnic = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $cnic);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $cnic_err = "This CNIC is already registered.";
        }
        mysqli_stmt_close($stmt);
    }

    // Email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
        $sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $email_err = "This email is already registered.";
        }
        mysqli_stmt_close($stmt);
    }

    // Phone (11 digits unique)
    if (empty(trim($_POST["phone"]))) {
        $phone_err = "Please enter phone number.";
    } elseif (!preg_match("/^[0-9]{11}$/", $_POST["phone"])) {
        $phone_err = "Phone must be 11 digits.";
    } else {
        $phone = trim($_POST["phone"]);
        $sql = "SELECT user_id FROM users WHERE phone = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $phone_err = "This phone is already registered.";
        }
        mysqli_stmt_close($stmt);
    }

    // Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen($_POST["password"]) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // User Type
    if (empty(trim($_POST["usertype"]))) {
        $usertype_err = "Please select a user type.";
    } else {
        $usertype = trim($_POST["usertype"]);
    }

    // Qualification
    if (empty(trim($_POST["qualification"]))) {
        $qualification_err = "Please enter qualification.";
    } else {
        $qualification = trim($_POST["qualification"]);
    }

    // Center Preference
    if (empty(trim($_POST["center_preference"]))) {
        $center_preference_err = "Please enter center preference.";
    } else {
        $center_preference = trim($_POST["center_preference"]);
    }

    // If no errors → Insert
    if (empty($name_err) && empty($cnic_err) && empty($email_err) && empty($phone_err) && empty($password_err) && empty($usertype_err)) {
        
        // Generate unique employee_id
        $employee_id = uniqid("EMP");

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = "pending"; // default

        $sql = "INSERT INTO users (employee_id, name, cnic, email, phone, password_hash, usertype, qualification, center_preference, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssss", $employee_id, $name, $cnic, $email, $phone, $hashed_password, $usertype, $qualification, $center_preference, $status);

        if (mysqli_stmt_execute($stmt)) {
            $registration_msg = '<div class="alert alert-success">Registration successful! Your Employee ID is ' . $employee_id . '</div>';
            $name = $cnic = $email = $phone = $password = $usertype = $qualification = $center_preference = "";
        } else {
            $registration_msg = '<div class="alert alert-danger">Something went wrong. Please try again.</div>';
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - VU Proctors Diary</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-body">
            <h3 class="text-center mb-4">User Registration</h3>
            <?php echo $registration_msg; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="Enter your full name"
                               class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo $name; ?>">
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>CNIC</label>
                        <input type="text" name="cnic" placeholder="13 digits without dashes"
                               class="form-control <?php echo (!empty($cnic_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo $cnic; ?>">
                        <span class="invalid-feedback"><?php echo $cnic_err; ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Enter your email"
                               class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="11 digits e.g. 03001234567"
                               class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo $phone; ?>">
                        <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter a strong password"
                               class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>User Type</label>
                        <select name="usertype" class="form-control <?php echo (!empty($usertype_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select User Type</option>
                            <option value="superintendent" <?php echo ($usertype == "superintendent") ? 'selected' : ''; ?>>Superintendent</option>
                            <option value="invigilator" <?php echo ($usertype == "invigilator") ? 'selected' : ''; ?>>Invigilator</option>
                        </select>
                        <span class="invalid-feedback"><?php echo $usertype_err; ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Qualification</label>
                        <input type="text" name="qualification" placeholder="Enter your qualification"
                               class="form-control <?php echo (!empty($qualification_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo $qualification; ?>">
                        <span class="invalid-feedback"><?php echo $qualification_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Center Preference</label>
                        <input type="text" name="center_preference" placeholder="Preferred exam center"
                               class="form-control <?php echo (!empty($center_preference_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo $center_preference; ?>">
                        <span class="invalid-feedback"><?php echo $center_preference_err; ?></span>
                    </div>
                </div>

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-lg">Register Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
