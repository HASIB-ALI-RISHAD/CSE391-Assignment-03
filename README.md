# CSE 391: Programming for the Internet — Assignment 03

### Car Workshop Online Appointment System (PHP & MySQL)
**Student:** Md. Hasib Ali Rishad  
**Student ID:** 23101389  
**Institution:** BRAC University — Department of Computer Science & Engineering  
**Course:** CSE 391 (Programming for the Internet) — Section 01, Fall 2026  
**Instructor:** Muhammad Abdur Rahman (ADR)  

---

## 🚀 How to Run (XAMPP)
1. Copy this folder to `xampp/htdocs/assgn03`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **http://localhost/assgn03/** — the database `cse391_workshop`, its tables and the 5 mechanics are created automatically (`db.php`). You can also import `database.sql` manually.

## 🔐 One Link, One Sign-In Page
Everything starts at `login.php`. The system decides where to send you:

| Username | Password | Opens |
| --- | --- | --- |
| `admin` | `Garage#391` | **Admin panel** (`admin.php`) |
| any registered client | their own password | **Client panel** (`index.php`) |

New clients press **Create a client account** → fill in Full name, Phone, Address, Username and Password (`signup.php`) → are sent back to the sign-in page to log in. Client passwords are stored hashed (`password_hash` / `password_verify`).

---

## 📁 Project Structure
```text
├── login.php        # Single sign-in page (admin → admin.php, everyone else → index.php)
├── signup.php       # Client registration
├── logout.php       # session_unset() + session_destroy()
├── index.php        # Client panel: booking form, live mechanic availability, my bookings, help
├── admin.php        # Admin panel: all appointments, change date / mechanic, delete, workload, filter
├── header.php       # Shared <head>, navigation bar & dark-mode switch (included by every page)
├── footer.php       # Shared footer & script include
├── db.php           # MySQLi connection, admin credentials, helper functions, automatic table setup
├── database.sql     # SQL schema + seed data (users, mechanics, appointments)
├── style.css        # ZenCrypto theme: pure-CSS dark mode, responsive layout
├── script.js        # Theme memory, live free places, booking-form validation, confirm boxes
├── docs/            # AI declaration, AI prompt log, assignment PDF
└── images/          # Favicons
```

## ✨ Features
**Client panel (`index.php`)**
- Booking form: Name, Phone, Address, Car License, Car Engine No., Date, Mechanic (name/phone/address pre-filled from the account).
- **Live free places** for the 5 senior mechanics — cards and dropdown refresh instantly when the date changes; full mechanics are disabled.
- Validation in **JavaScript and PHP**: no empty fields, phone & engine number digits only, no past dates.
- Business rules: **max 4 cars per mechanic per day** and **one appointment per client per date**.
- *My bookings* table (Upcoming / Today / Completed) with a Cancel button for future bookings, plus a Help section.

**Admin panel (`admin.php`)**
- Summary cards, mechanic workload for any date, and the full appointment table: **Client Name, Phone, Car Registration Number, Date, Mechanic**.
- **Edit** an appointment's **date** and/or **mechanic** (4-car and duplicate rules are enforced), **Delete**, and filter by date.

**Security & quality**
- PHP sessions guard every page (clients cannot open the admin page and vice-versa).
- All output escaped with `htmlspecialchars` (`h()` helper) and all inputs escaped with `real_escape_string`.
- `style.css` / `script.js` are loaded with a `?v=<file time>` version so the browser never shows an outdated design.

## 🌓 Dark / Light Mode
Pure CSS: a hidden checkbox `#theme-toggle` switches the colour variables (`#theme-toggle:checked ~ .theme-wrapper`). The choice is remembered in the browser's `localStorage` (key `site-theme`).
