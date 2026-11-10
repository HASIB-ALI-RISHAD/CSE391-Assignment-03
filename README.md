# CSE 391: Programming for the Internet — Assignment 03

### Car Workshop Online Appointment System (PHP & MySQL)
**Student:** Md. Hasib Ali Rishad  
**Student ID:** 23101389  
**Institution:** BRAC University — Department of Computer Science & Engineering  
**Course:** CSE 391 (Programming for the Internet) — Section 01, Fall 2026  
**Instructor:** Muhammad Abdur Rahman (ADR)  

---

## 🌐 Live Hosted Website
The web application is deployed online and accessible via any browser:
👉 **[https://hr-workshop.freehosting.dev/](https://hr-workshop.freehosting.dev/)**

---

## 🔐 One Link, Unified Sign-In Portal
The application features a single entry point (`login.php`). The system automatically routes users based on their sign-in credentials:

| Role | Username | Password | Destination |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `Garage#391` | **Admin Panel** (`admin.php`) |
| **Client** | Any registered username | Client's custom password | **Client Panel** (`index.php`) |

* **New Clients:** Click **"Create a client account"** on the login page, register with Full Name, Phone, Address, Username, and Password (`signup.php`), then log in.
* **Pre-filled Profile:** When a client logs in, their Name, Phone, and Address are automatically pre-filled into the booking form.

---

## 💻 Localhost Setup (XAMPP)
1. Ensure the folder is placed in or linked to `H:\database\xampp\htdocs\assgn03`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **[http://localhost/assgn03/](http://localhost/assgn03/)** in your browser.
4. Database tables (`users`, `mechanics`, `appointments`) and the 5 senior mechanics are created and seeded automatically on first load via `db.php`.

---

## 📁 Repository Structure
```text
├── login.php        # Single sign-in portal (Admin -> admin.php, Clients -> index.php)
├── signup.php       # Client account registration with client-side & server-side validation
├── logout.php       # Session termination (session_unset & session_destroy)
├── index.php        # Client panel: booking form, live mechanic availability, my bookings, guidelines
├── admin.php        # Admin panel: appointment list, reschedule date & mechanic, delete, workload
├── header.php       # Shared HTML head, responsive navbar, and pure-CSS dark mode toggle
├── footer.php       # Shared footer and script bundling
├── db.php           # MySQLi connection, auto-environment detection (Localhost vs. Live Host)
├── database.sql     # SQL DDL & initial seed dataset (users, mechanics, sample appointments)
├── style.css        # ZenCrypto design system: CSS custom properties, pure-CSS dark mode, responsive layout
├── script.js        # Dynamic date-based slot counters, client-side validation, theme persistence
├── docs/
│   ├── AI_Declaration.txt            # Official AI usage declaration form
│   ├── AI_Prompts.md                 # Chronological AI prompt & design decision log
│   └── CSE 391- Assignment 3 v3.pdf  # Assignment specification sheet
└── images/                           # Vector & raster favicons
```

---

## ✨ System Features & Business Logic

### 1. User / Client Panel (`index.php`)
* **Appointment Booking:** Client enters Name, Address, Phone, Car License Number, Car Engine Number, Date, and chooses a mechanic.
* **Live Mechanic Availability:** Displays real-time free places (`4 - booked`) for all 5 senior mechanics. Updating the date dynamically recalculates slots; fully booked mechanics are automatically disabled.
* **Client-Side & Server-Side Validation:** Ensures all required fields are filled, phone and engine numbers are digits only, and appointment dates are valid and not in the past.
* **Double-Booking Prevention:** A client cannot book more than one appointment on the same date across any mechanic.
* **Quota Enforcement:** Each mechanic takes a strict maximum of **4 active cars per day**.
* **Personal Booking Management:** Clients can review their bookings (Upcoming, Today, Completed) and cancel their upcoming visits.
* **Help & Guidelines:** Clear walkthrough explaining the booking quota rules.

### 2. Admin Management Panel (`admin.php`)
* **Live Operational Metrics:** Quick summary cards displaying Total Bookings, Today's Appointments, Upcoming Visits, and Registered Clients.
* **Today's Appointments by Default:** Shows today's schedule immediately upon login, with date filters and a "Show all" view.
* **Daily Mechanic Workload:** Real-time visual progress bars showing each senior mechanic's capacity for the selected date.
* **Appointment Rescheduling & Reassignment:** Admin can change the appointment date and/or reassign the car to any other available mechanic while enforcing daily quota limits.
* **Booking Cancellation:** Admin can delete an appointment, immediately freeing that slot for other clients.

---

## 🌓 Pure-CSS Dark / Light Mode
Theme switching is implemented via CSS custom properties and the sibling selector (`#theme-toggle:checked ~ .theme-wrapper`). The user's theme preference is saved across sessions in the browser's `localStorage` under the key `site-theme`.
