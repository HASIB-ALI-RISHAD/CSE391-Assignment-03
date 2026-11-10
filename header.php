<?php
/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: header.php — Shared HTML <head>, theme switch & top navigation bar
   ========================================================================== */

$role       = isset($_SESSION["user_role"]) ? $_SESSION["user_role"] : "";
$page_title = isset($page_title) ? $page_title : "Car Workshop";

$brand_link = "login.php";
if ($role === "admin") {
    $brand_link = "admin.php";
} elseif ($role === "client") {
    $brand_link = "index.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Online car workshop appointment system — CSE 391 Assignment 03 by Md. Hasib Ali Rishad">
    <meta name="author" content="Md. Hasib Ali Rishad">
    <title><?php echo htmlspecialchars($page_title); ?> | Car Workshop</title>

    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="icon" type="image/png" sizes="64x64" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon-256.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Martian+Mono:wght@400;500;600;700&amp;display=swap">
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body>

    <!-- Pure-CSS dark mode: this checkbox is the switch; style.css reacts to #theme-toggle:checked -->
    <input type="checkbox" id="theme-toggle" class="theme-toggle-input" aria-label="Toggle dark and light theme">
    <script>
        // Restore the saved theme immediately (stored in the browser's localStorage under "site-theme")
        if (localStorage.getItem("site-theme") === "dark") {
            document.getElementById("theme-toggle").checked = true;
        }
    </script>

    <div class="theme-wrapper">

        <header class="site-header">
            <div class="container header-inner">
                <a href="<?php echo $brand_link; ?>" class="brand" title="Car Workshop Appointment System">
                    <span class="brand-logo">HR</span>
                    <span class="brand-text">
                        <span class="brand-title">Car Workshop</span>
                    </span>
                </a>

                <?php if ($role !== ""): ?>
                <input type="checkbox" id="nav-toggle" class="nav-toggle-input" aria-label="Toggle navigation menu">

                <nav class="main-nav" aria-label="Primary navigation">
                    <?php if ($role === "client"): ?>
                        <a href="index.php#booking" class="nav-link">Book Appointment</a>
                        <a href="index.php#mechanics" class="nav-link">Mechanics</a>
                        <a href="index.php#my-bookings" class="nav-link">My Bookings</a>
                        <a href="index.php#help" class="nav-link">Help</a>
                    <?php elseif ($role === "admin"): ?>
                        <a href="admin.php#appointments" class="nav-link">Appointments</a>
                        <a href="admin.php#workload" class="nav-link">Mechanic Workload</a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>

                <div class="header-right">
                    <?php if ($role !== ""): ?>
                        <span class="user-chip" title="Signed in account">
                            <span class="user-dot"></span>
                            <?php echo htmlspecialchars(isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : $role); ?>
                            <span class="user-role"><?php echo ($role === "admin") ? "Admin" : "Client"; ?></span>
                        </span>
                    <?php endif; ?>

                    <label for="theme-toggle" class="theme-btn" title="Switch between dark and light mode">
                        <span class="theme-btn-dark">&#9790; <span class="hide-sm">Dark</span></span>
                        <span class="theme-btn-light">&#9728; <span class="hide-sm">Light</span></span>
                    </label>

                    <?php if ($role !== ""): ?>
                        <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>

                        <label for="nav-toggle" class="nav-hamburger" title="Menu">
                            <span class="hamburger-bar"></span>
                            <span class="hamburger-bar"></span>
                            <span class="hamburger-bar"></span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="container page-main">
