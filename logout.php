<?php
/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: logout.php — Clears PHP Session and Redirects to Sign-In Page
   ========================================================================== */

session_start();
session_unset();
session_destroy();

header("Location: login.php?logged_out=1");
exit();
?>
