<?php
/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: db.php — Database Connection, Admin Credentials & Table Setup
   ========================================================================== */

date_default_timezone_set("Asia/Dhaka");
mysqli_report(MYSQLI_REPORT_OFF);

// Short helper: safely print user-supplied text inside HTML (prevents XSS)
function h($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, "UTF-8");
}

// Short helper: checks that a string is a real calendar date written as YYYY-MM-DD
function is_valid_date($date) {
    if (!preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $parts)) {
        return false;
    }
    return checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]);
}

// 1. Database Connection Settings (Auto-detects Local XAMPP vs. InfinityFree Hosting)
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1')) {
    // Local XAMPP Environment
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "cse391_workshop";
} else {
    // InfinityFree Live Hosting Environment
    $db_host = "sql211.infinityfree.com";
    $db_user = "if0_43400399";
    $db_pass = "hrworkshop";
    $db_name = "if0_43400399_workshop";
}

// 2. Fixed Workshop Admin Credentials (Admin logs in directly without Sign Up)
define("ADMIN_USERNAME", "admin");
define("ADMIN_PASSWORD", "Garage#391");

// 3. Connect to MySQL Server using mysqli (as taught in Lecture 06)
//    - On local XAMPP (root@localhost), auto-create the database if it doesn't exist yet.
//    - On online shared hosting (InfinityFree, etc.), connect directly to $db_name.
if ($db_host === "localhost" && $db_user === "root") {
    $db = new mysqli($db_host, $db_user, $db_pass);
    if ($db->connect_errno) {
        die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
    }
    $db->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $db->select_db($db_name);
} else {
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($db->connect_errno) {
        die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
    }
}

$db->set_charset("utf8mb4");

// 4. Create the 'users' table (client accounts; password is stored as a secure hash)
$db->query("CREATE TABLE IF NOT EXISTS users (
    user_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
)");

// 5. Create the 'mechanics' table
$db->query("CREATE TABLE IF NOT EXISTS mechanics (
    mechanic_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    daily_capacity INT NOT NULL DEFAULT 4,
    PRIMARY KEY (mechanic_id)
)");

// 6. Seed the 5 Senior Mechanics if the 'mechanics' table is empty
$is_fresh_install = false;
$check_mechanics = $db->query("SELECT COUNT(*) AS total FROM mechanics");
if ($check_mechanics) {
    $row_count = $check_mechanics->fetch_assoc();
    if ((int)$row_count["total"] === 0) {
        $is_fresh_install = true;
        $db->query("INSERT INTO mechanics (mechanic_id, name, specialty, phone, daily_capacity) VALUES
            (1, 'Rafiqul Islam', 'Engine & Transmission', '01711223344', 4),
            (2, 'Tanvir Ahmed', 'Electrical & ECU Diagnostics', '01711223355', 4),
            (3, 'Kamrul Hasan', 'Brakes, Suspension & Steering', '01711223366', 4),
            (4, 'Mahmudul Karim', 'Hybrid & Fuel Injection', '01711223377', 4),
            (5, 'Sajjad Hossain', 'AC, Cooling & General Overhaul', '01711223388', 4)");
    }
    $check_mechanics->close();
}

// 7. Create the 'appointments' table (user_id links a booking to the client account)
$db->query("CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    client_name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    car_license VARCHAR(50) NOT NULL,
    car_engine VARCHAR(50) NOT NULL,
    appointment_date DATE NOT NULL,
    mechanic_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_id),
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(mechanic_id)
)");

// Seed 3 initial sample appointments for TODAY on a brand-new database install
if ($is_fresh_install) {
    $db->query("INSERT INTO appointments (user_id, client_name, address, phone, car_license, car_engine, appointment_date, mechanic_id) VALUES
        (NULL, 'Nusrat Jahan', 'House 12, Road 7, Uttara, Dhaka', '01811223344', 'DHAKA METRO-GA-19-4421', '48291034', CURDATE(), 1),
        (NULL, 'Imran Hossain', 'Plot 5, Block C, Banani, Dhaka', '01911223355', 'DHAKA METRO-KHA-14-8890', '77301928', CURDATE(), 2),
        (NULL, 'Sabbir Rahman', 'Road 9, Dhanmondi, Dhaka', '01611223366', 'DHAKA METRO-GHA-22-1105', '65019283', CURDATE(), 4)");
}

// Upgrade older databases: add 'user_id' if the appointments table was created without it
$col_check = $db->query("SHOW COLUMNS FROM appointments LIKE 'user_id'");
if ($col_check && $col_check->num_rows === 0) {
    $db->query("ALTER TABLE appointments ADD COLUMN user_id INT NULL AFTER appointment_id");
}
?>
