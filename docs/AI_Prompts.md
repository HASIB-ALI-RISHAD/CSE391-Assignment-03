# AI Prompt Log & Iterative Development Record

**Student:** Md. Hasib Ali Rishad  
**Student ID:** 23101389  
**Course:** CSE 391 — *Programming for the Internet* (Section 01, Fall 2026)  
**Instructor:** Muhammad Abdur Rahman (ADR)  
**AI Tool Used:** Google Gemini (Antigravity Pair-Programming Assistant)  

---

## Overview

This document records the prompts and implementation architecture used while developing **Assignment 03: PHP and MySQL Project (Car Workshop Online Appointment System)**. All PHP and MySQL code follows the foundational `mysqli`, form-checking, and relational SQL concepts taught in CSE 391 Lectures 05, 06, and 07.

---

## Phase 1: Specification Analysis, Lecture Alignment & Full-Stack Implementation

### Prompt 1.1 — Car Workshop PHP & MySQL System with ZenCrypto Theme
> *"Check the `assgn 03` folder and complete the project according to the instructions given in `CSE 391- Assignment 3 v3.pdf`. Also check Lecture 05 (and MySQL lectures) from `Lecture Resource` to match what was taught in class. Keep the website theme consistent with Assignment 01 and Assignment 02 (ZenCrypto aesthetic), and since this is my first PHP assignment, keep the PHP and MySQL code basic and easy to explain during viva."*

**Outcome:**
1. **Relational MySQL Database ([`database.sql`](../database.sql) & [`db.php`](../db.php)):**
   - Created two normalized tables: `mechanics` (storing the 5 senior mechanics with `daily_capacity = 4`) and `appointments` (storing `client_name`, `address`, `phone`, `car_license`, `car_engine`, `appointment_date`, and `mechanic_id` foreign key).
   - Configured [`db.php`](../db.php) using `new mysqli(...)` (as taught in Lecture 06) with automatic database/table initialization and mechanic seeding for seamless local XAMPP and remote hosting setup.
2. **User Panel ([`index.php`](../index.php)):**
   - Displays real-time free places (`4 - booked_slots`) for all 5 senior mechanics on the selected date, both in visual status cards and inside the mechanic `<select>` dropdown.
   - Enforces server-side PHP validation and workshop rules before inserting into MySQL:
     - Blocks duplicate appointments if the same client (`phone` or `car_license`) already has a booking on that date.
     - Blocks bookings if the chosen mechanic already has `4` active cars assigned on that date.
   - Includes a dedicated **Basic Help & Guidelines (`#help`)** section required by Part 1.
3. **Admin Panel ([`admin.php`](../admin.php)):**
   - Displays the complete client appointment list showing **Client Name**, **Phone**, **Car Registration Number**, **Appointment Date**, and **Mechanic Name**.
   - Provides inline controls allowing the Admin to change a client's **Appointment Date** and/or reassign their **Mechanic** to any other available mechanic while verifying the 4-car daily limit.
4. **Client-Side Validation & ZenCrypto Styling ([`script.js`](../script.js) & [`style.css`](../style.css)):**
   - Added beginner-friendly JavaScript validation (`isDigitsOnly` loop checking numeric Phone and Car Engine Number, required field checks, and live date-based free slot updates) and styled both panels with the ZenCrypto Light/Dark theme.

---

## Phase 2: Client Registration, Sign-In & Admin Authentication Portal

### Prompt 2.1 — Sign-Up / Sign-In Portal for Clients & Preset Admin Login
> *"Make a sign-in page where a client/user first signs up with their necessary information and creates their own password, then logs in to book appointments. The Admin should be able to log in directly without needing to sign up using a preset creative password."*

**Outcome:**
1. **Added `users` Table ([`database.sql`](../database.sql) & [`db.php`](../db.php)):**
   - Stores registered client accounts (`user_id`, `username` unique, `full_name`, `phone`, `address`, `password`) and defines the fixed Admin credentials (`ADMIN_USERNAME = "admin"`, `ADMIN_PASSWORD = "Garage#391"`).
2. **Created Centered Dispatch Login Console ([`login.php`](../login.php)), Dedicated Client Registration ([`signup.php`](../signup.php)), & Session Logout ([`logout.php`](../logout.php)):**
   - Uses `session_start()`, `$_SESSION`, `session_unset()`, and `session_destroy()` directly aligned with CSE 391 Lecture 05 (`session1.php`–`session3.php`).
   - **Dedicated Client Sign Up ([`signup.php`](../signup.php)):** Validates unique Username, Full Name, digits-only Phone Number, Address, and matching Password before inserting into `users` and redirecting back to [`login.php`](../login.php).
   - **Unified Dispatch Sign In ([`login.php`](../login.php)):**
     - If credentials are `admin` / `Garage#391`, authenticates as Admin and opens [`admin.php`](../admin.php).
     - Otherwise, authenticates the client's `username` + `password` against `users`, stores their profile in `$_SESSION`, and opens [`index.php`](../index.php) with their Name, Phone, and Address pre-filled.


---

## Phase 3: Full Redesign, Shared Layout & End-to-End Testing

### Prompt 3.1 — Professional Clean-Up of the Whole Website
> *"The first page looks horrible and the whole website needs work. I don't know what the 'Preview User Panel' in the admin page is for. Be a professional developer and fix the whole website top to bottom."*

**Outcome:**
1. **Root cause of the broken first page:** the browser was showing an old cached `style.css`. All pages now load `style.css?v=<file time>` and `script.js?v=<file time>` so an outdated design can never appear.
2. **Shared layout:** new [`header.php`](../header.php) and [`footer.php`](../footer.php) are included by every page, so the navigation bar, dark-mode switch and footer are identical everywhere. The navigation changes with the signed-in role (guest / client / admin).
3. **Redesigned pages in the same ZenCrypto theme:** split-panel [`login.php`](../login.php) and [`signup.php`](../signup.php); client dashboard [`index.php`](../index.php) with summary cards, live mechanic availability and a *My bookings* table; admin dashboard [`admin.php`](../admin.php) with summary cards, mechanic workload, date filter and an Edit panel.
4. **Removed the confusing "Preview User Panel" link.** Role routing is now strict: admin ⇄ `admin.php`, clients ⇄ `index.php`.
5. **Improvements:** passwords stored with `password_hash()`; bookings linked to the client account (`appointments.user_id`); no past dates; clients can cancel upcoming bookings; admin can delete bookings and sees workload per date.
6. **Verification:** 35 automated HTTP checks (sign-up/sign-in rules, access control, 4-cars-per-day limit, one-booking-per-day rule, cancel, admin edit/delete/filter, logout) and headless-browser checks of the live free-places and form-validation JavaScript.
