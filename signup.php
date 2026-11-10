<?php
/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: signup.php — Client Account Registration Page
   ========================================================================== */

session_start();
include 'db.php';

// Signed-in users do not need this page
if (isset($_SESSION["user_role"])) {
    $db->close();
    header("Location: " . ($_SESSION["user_role"] === "admin" ? "admin.php" : "index.php"));
    exit();
}

// Sticky form values (so the form keeps what the user typed after an error)
$username = "";
$fullname = "";
$phone    = "";
$address  = "";

$status_title   = "";
$status_message = "";

if (isset($_POST["signup_submit"])) {
    $username = trim($_POST["username"]);
    $fullname = trim($_POST["full_name"]);
    $phone    = trim($_POST["phone"]);
    $address  = trim($_POST["address"]);
    $password = $_POST["password"];
    $confirm  = $_POST["confirm_password"];

    $errors = array();

    if ($username === "") {
        $errors[] = "Username is required.";
    } elseif (strtolower($username) === strtolower(ADMIN_USERNAME)) {
        $errors[] = "That username is reserved. Please choose a different one.";
    } elseif (!preg_match("/^[A-Za-z0-9_.-]{3,40}$/", $username)) {
        $errors[] = "Username must be 3-40 characters: letters, numbers, _ . or -";
    }

    if ($fullname === "") {
        $errors[] = "Full name is required.";
    }

    if ($phone === "") {
        $errors[] = "Phone number is required.";
    } elseif (!ctype_digit($phone)) {
        $errors[] = "Phone number must contain digits only.";
    }

    if ($address === "") {
        $errors[] = "Address is required.";
    }

    if ($password === "") {
        $errors[] = "Please choose a password.";
    } elseif (strlen($password) < 4) {
        $errors[] = "Password must be at least 4 characters.";
    } elseif ($password !== $confirm) {
        $errors[] = "The two passwords do not match.";
    }

    if (count($errors) > 0) {
        $status_title   = "Please fix the following";
        $status_message = implode(" ", $errors);
    } else {
        $safe_user = $db->real_escape_string($username);

        $exists = $db->query("SELECT user_id FROM users WHERE username = '$safe_user'");
        if ($exists && $exists->num_rows > 0) {
            $status_title   = "Username already taken";
            $status_message = "Please choose a different username.";
        } else {
            $safe_name    = $db->real_escape_string($fullname);
            $safe_phone   = $db->real_escape_string($phone);
            $safe_address = $db->real_escape_string($address);
            $safe_pass    = $db->real_escape_string($password);

            $sql = "INSERT INTO users (username, full_name, phone, address, password)
                    VALUES ('$safe_user', '$safe_name', '$safe_phone', '$safe_address', '$safe_pass')";

            if ($db->query($sql)) {
                $db->close();
                header("Location: login.php?registered=1&user=" . urlencode($username));
                exit();
            } else {
                $status_title   = "Something went wrong";
                $status_message = "Your account could not be created. Please try again.";
            }
        }
    }
}

$page_title = "Create Account";
$nav_active = "signup";
include 'header.php';
?>

<section class="auth-shell auth-shell-single">
    <div class="auth-panel card auth-panel-wide">
        <span class="tag">New client</span>
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-sub">Tell us a little about yourself. You will use your username and password to sign in.</p>

        <?php if ($status_title !== ""): ?>
        <div class="alert alert-error" role="alert">
            <strong><?php echo htmlspecialchars($status_title); ?></strong>
            <span><?php echo htmlspecialchars($status_message); ?></span>
        </div>
        <?php endif; ?>

        <form action="signup.php" method="POST" class="stack-form">
            <div class="form-grid">
                <div class="field">
                    <label for="full_name" class="label">Full name</label>
                    <input type="text" id="full_name" name="full_name" class="input"
                           placeholder="e.g. Md. Hasib Ali Rishad"
                           value="<?php echo htmlspecialchars($fullname); ?>" required>
                </div>

                <div class="field">
                    <label for="phone" class="label">Phone number</label>
                    <input type="text" id="phone" name="phone" class="input"
                           placeholder="e.g. 01712345678" inputmode="numeric"
                           value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>
            </div>

            <div class="field">
                <label for="address" class="label">Address</label>
                <input type="text" id="address" name="address" class="input"
                       placeholder="e.g. House 14, Road 5, Dhanmondi, Dhaka"
                       value="<?php echo htmlspecialchars($address); ?>" required>
            </div>

            <div class="field">
                <label for="username" class="label">Username</label>
                <input type="text" id="username" name="username" class="input"
                       placeholder="e.g. hasib_391" autocomplete="username"
                       value="<?php echo htmlspecialchars($username); ?>" required>
                <small class="hint">3-40 characters: letters, numbers, _ . or -</small>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" class="input"
                           placeholder="At least 4 characters" autocomplete="new-password" required>
                </div>

                <div class="field">
                    <label for="confirm_password" class="label">Confirm password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="input"
                           placeholder="Type it again" autocomplete="new-password" required>
                </div>
            </div>

            <button type="submit" name="signup_submit" value="1" class="btn btn-primary btn-block">Create account &rarr;</button>
        </form>

        <p class="auth-switch">Already registered? <a href="login.php">Sign in here</a></p>
    </div>
</section>

<?php
include 'footer.php';
$db->close();
?>
