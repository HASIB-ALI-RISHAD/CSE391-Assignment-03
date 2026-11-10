<?php
/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: login.php — Single Sign-In Page (Admin → admin.php | Client → index.php)
   ========================================================================== */

session_start();
include 'db.php';

// 1. Already signed in? Send the user straight to their own page.
if (isset($_SESSION["user_role"]) && !isset($_GET["logged_out"])) {
    $db->close();
    if ($_SESSION["user_role"] === "admin") {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$username       = isset($_GET["user"]) ? trim($_GET["user"]) : "";
$status_type    = "";
$status_title   = "";
$status_message = "";

if (isset($_GET["logged_out"])) {
    $status_type    = "success";
    $status_title   = "Signed out";
    $status_message = "You have been logged out safely.";
} elseif (isset($_GET["registered"])) {
    $status_type    = "success";
    $status_title   = "Account created";
    $status_message = "Your account is ready. Please sign in with your new username and password.";
} elseif (isset($_GET["auth_required"])) {
    $status_type    = "error";
    $status_title   = "Please sign in";
    $status_message = "You need to sign in to open that page.";
}

// 2. Handle the sign-in form
if (isset($_POST["login_submit"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if ($username === "" || $password === "") {
        $status_type    = "error";
        $status_title   = "Missing details";
        $status_message = "Please enter both your username and password.";
    } elseif ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // Admin credentials matched -> open the Admin page
        $_SESSION["user_role"] = "admin";
        $_SESSION["user_name"] = "Admin";
        $db->close();
        header("Location: admin.php");
        exit();
    } else {
        // Anything else -> look for a registered client with this username and password
        $safe_user = $db->real_escape_string($username);
        $safe_pass = $db->real_escape_string($password);
        $result    = $db->query("SELECT * FROM users WHERE username = '$safe_user' AND password = '$safe_pass'");
        $user_row  = $result ? $result->fetch_assoc() : null;

        if ($user_row) {
            $_SESSION["user_role"]     = "client";
            $_SESSION["user_id"]       = (int)$user_row["user_id"];
            $_SESSION["user_username"] = $user_row["username"];
            $_SESSION["user_name"]     = $user_row["full_name"];
            $_SESSION["user_phone"]    = $user_row["phone"];
            $_SESSION["user_address"]  = $user_row["address"];
            $db->close();
            header("Location: index.php");
            exit();
        } else {
            $status_type    = "error";
            $status_title   = "Sign in failed";
            $status_message = "Incorrect username or password. New here? Create an account below.";
        }
    }
}

$page_title = "Sign In";
$nav_active = "login";
include 'header.php';
?>

<section class="auth-shell">

    <!-- Left: brand panel -->
    <div class="auth-aside">
        <span class="tag">Open 24 / 7</span>
        <h1 class="auth-heading">Service your car without the wait.</h1>
        <p class="auth-lead">
            Pick one of our 5 senior mechanics, choose a date and book online.
            Free places are shown live, so you always know who is available.
        </p>

        <ul class="auth-points">
            <li><span class="point-num">5</span> Senior mechanics, each with their own speciality</li>
            <li><span class="point-num">4</span> Cars per mechanic per day </li>
            <li><span class="point-num">1</span> Booking per client per date</li>
        </ul>
    </div>

    <!-- Right: sign-in card -->
    <div class="auth-panel card">
        <h2 class="auth-title">Sign in</h2>
        <p class="auth-sub">Enter your username and password to continue.</p>

        <?php if ($status_type !== ""): ?>
        <div class="alert <?php echo ($status_type === 'success') ? 'alert-success' : 'alert-error'; ?>" role="alert">
            <strong><?php echo htmlspecialchars($status_title); ?></strong>
            <span><?php echo htmlspecialchars($status_message); ?></span>
        </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="stack-form">
            <div class="field">
                <label for="username" class="label">Username</label>
                <input type="text" id="username" name="username" class="input"
                       placeholder="Enter your username" autocomplete="username"
                       value="<?php echo htmlspecialchars($username); ?>" required autofocus>
            </div>

            <div class="field">
                <label for="password" class="label">Password</label>
                <input type="password" id="password" name="password" class="input"
                       placeholder="Enter your password" autocomplete="current-password" required>
            </div>

            <button type="submit" name="login_submit" value="1" class="btn btn-primary btn-block">Sign in &rarr;</button>
        </form>

        <div class="auth-divider"><span>New to the workshop?</span></div>

        <a href="signup.php" class="btn btn-outline btn-block">Create a client account</a>
    </div>

</section>

<?php
include 'footer.php';
$db->close();
?>
