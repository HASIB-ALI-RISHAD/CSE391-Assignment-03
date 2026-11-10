/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: script.js — Theme memory, confirm boxes, live free places & form checker
   ========================================================================== */


/* ==========================================================================
   1. Dark / Light theme memory
   The switch itself is pure CSS (a hidden checkbox). Here we only REMEMBER
   the choice in the browser's localStorage under the key "site-theme".
   ========================================================================== */
const themeToggle = document.getElementById("theme-toggle");

if (themeToggle) {
    themeToggle.addEventListener("change", function () {
        if (themeToggle.checked) {
            localStorage.setItem("site-theme", "dark");
        } else {
            localStorage.setItem("site-theme", "light");
        }
    });
}


/* ==========================================================================
   2. Mobile menu: close it after a link is clicked
   ========================================================================== */
const navToggle = document.getElementById("nav-toggle");
const navLinks = document.querySelectorAll(".main-nav .nav-link");

for (let i = 0; i < navLinks.length; i++) {
    navLinks[i].addEventListener("click", function () {
        if (navToggle) {
            navToggle.checked = false;
        }
    });
}


/* ==========================================================================
   3. "Are you sure?" box for forms that have a data-confirm message
   ========================================================================== */
const confirmForms = document.querySelectorAll("form[data-confirm]");

for (let i = 0; i < confirmForms.length; i++) {
    confirmForms[i].addEventListener("submit", function (event) {
        const message = this.getAttribute("data-confirm");
        if (!confirm(message)) {
            event.preventDefault();
        }
    });
}


/* ==========================================================================
   4. Live "free places" when the date changes
   window.WORKSHOP_BOOKINGS_BY_DATE looks like { "2026-11-12": { "1": 3, "2": 4 } }
   (date -> mechanic id -> number of cars already booked). PHP prints it.
   ========================================================================== */
function getBooked(dateString, mechanicId) {
    const allDays = window.WORKSHOP_BOOKINGS_BY_DATE || {};
    const day = allDays[dateString] || {};
    return day[mechanicId] ? Number(day[mechanicId]) : 0;
}

// 4A. Client page: update the mechanic cards and the dropdown
const appointmentDate = document.getElementById("appointment_date");
const mechanicSelect = document.getElementById("mechanic_id");
const mechanicCards = document.querySelectorAll("[data-mechanic-card]");
const liveDateLabel = document.getElementById("live-date-label");

function refreshClientSlots() {
    const dateString = appointmentDate.value;
    if (dateString === "") {
        return;
    }

    if (liveDateLabel) {
        liveDateLabel.textContent = dateString;
    }

    // cards
    for (let i = 0; i < mechanicCards.length; i++) {
        const card = mechanicCards[i];
        const mechId = card.getAttribute("data-mechanic-card");
        const capacity = Number(card.getAttribute("data-capacity"));
        const booked = getBooked(dateString, mechId);
        let free = capacity - booked;
        if (free < 0) {
            free = 0;
        }

        const badge = card.querySelector("[data-slot-badge]");
        const bookedText = card.querySelector("[data-booked-count]");
        const bar = card.querySelector("[data-bar]");

        bookedText.textContent = booked;
        bar.style.width = Math.round((booked / capacity) * 100) + "%";

        if (free === 0) {
            card.className = "mech-card is-full";
            badge.className = "slot-pill slot-full";
            badge.textContent = "Full";
        } else {
            card.className = "mech-card is-free";
            badge.className = "slot-pill slot-free";
            badge.textContent = free + " free";
        }
    }

    // dropdown
    const options = mechanicSelect.querySelectorAll("option[data-mech-name]");
    for (let j = 0; j < options.length; j++) {
        const opt = options[j];
        const capacity = Number(opt.getAttribute("data-mech-cap"));
        let free = capacity - getBooked(dateString, opt.value);
        if (free < 0) {
            free = 0;
        }

        opt.textContent = opt.getAttribute("data-mech-name");
        if (free === 0) {
            opt.disabled = true;
            if (opt.selected) {
                mechanicSelect.value = "";
            }
        } else {
            opt.disabled = false;
        }
    }
}

if (appointmentDate && mechanicSelect) {
    appointmentDate.addEventListener("change", refreshClientSlots);
}

// 4B. Admin edit form: disable mechanics that have no free places on the chosen date
const editDate = document.getElementById("edit-date");
const editMechanic = document.getElementById("edit-mechanic");

function refreshAdminLabels() {
    const dateString = editDate.value;
    const editing = window.WORKSHOP_EDITING || { date: "", mechanic: 0 };
    const options = editMechanic.querySelectorAll("option[data-mech-name]");

    for (let i = 0; i < options.length; i++) {
        const opt = options[i];
        const capacity = Number(opt.getAttribute("data-mech-cap"));
        let booked = getBooked(dateString, opt.value);

        // the appointment being edited is not counted against itself
        if (dateString === editing.date && Number(opt.value) === editing.mechanic) {
            booked = booked - 1;
        }

        let free = capacity - booked;
        if (free < 0) {
            free = 0;
        }

        opt.textContent = opt.getAttribute("data-mech-name");
        opt.disabled = (free === 0);
    }
}

if (editDate && editMechanic) {
    refreshAdminLabels();
    editDate.addEventListener("change", refreshAdminLabels);
}


/* ==========================================================================
   5. Booking form checker (runs before the form is sent to the server)
   - nothing may be empty
   - phone and engine number must contain digits 0-9 only
   - date cannot be in the past
   ========================================================================== */
const appointmentForm = document.getElementById("appointment-form");
const jsErrorBox = document.getElementById("js-form-error");
const jsErrorText = document.getElementById("js-form-error-text");

// returns true only if every character is a digit 0-9
function isDigitsOnly(text) {
    if (text.length === 0) {
        return false;
    }
    for (let i = 0; i < text.length; i++) {
        const ch = text.charAt(i);
        if (ch < "0" || ch > "9") {
            return false;
        }
    }
    return true;
}

function showError(input, message) {
    jsErrorText.textContent = message;
    jsErrorBox.hidden = false;
    input.classList.add("input-invalid");
    input.focus();
    jsErrorBox.scrollIntoView({ behavior: "smooth", block: "center" });
}

function clearErrors() {
    jsErrorBox.hidden = true;
    const marked = appointmentForm.querySelectorAll(".input-invalid");
    for (let i = 0; i < marked.length; i++) {
        marked[i].classList.remove("input-invalid");
    }
}

if (appointmentForm && jsErrorBox) {
    appointmentForm.addEventListener("submit", function (event) {
        clearErrors();

        const nameInput = document.getElementById("client_name");
        const phoneInput = document.getElementById("phone");
        const addressInput = document.getElementById("address");
        const licenseInput = document.getElementById("car_license");
        const engineInput = document.getElementById("car_engine");
        const dateInput = document.getElementById("appointment_date");
        const mechanicInput = document.getElementById("mechanic_id");

        const today = new Date();
        const todayText = today.getFullYear() + "-" +
            String(today.getMonth() + 1).padStart(2, "0") + "-" +
            String(today.getDate()).padStart(2, "0");

        let problem = null;   // the first input that has an error
        let message = "";

        if (nameInput.value.trim() === "") {
            problem = nameInput;
            message = "Please enter your name.";
        } else if (phoneInput.value.trim() === "") {
            problem = phoneInput;
            message = "Please enter your phone number.";
        } else if (!isDigitsOnly(phoneInput.value.trim())) {
            problem = phoneInput;
            message = "Phone number must contain digits only (0-9).";
        } else if (addressInput.value.trim() === "") {
            problem = addressInput;
            message = "Please enter your address.";
        } else if (licenseInput.value.trim() === "") {
            problem = licenseInput;
            message = "Please enter your car license number.";
        } else if (engineInput.value.trim() === "") {
            problem = engineInput;
            message = "Please enter your car engine number.";
        } else if (!isDigitsOnly(engineInput.value.trim())) {
            problem = engineInput;
            message = "Car engine number must contain digits only (0-9).";
        } else if (dateInput.value === "") {
            problem = dateInput;
            message = "Please choose an appointment date.";
        } else if (dateInput.value < todayText) {
            problem = dateInput;
            message = "The appointment date cannot be in the past.";
        } else if (mechanicInput.value === "") {
            problem = mechanicInput;
            message = "Please select a mechanic.";
        }

        if (problem !== null) {
            event.preventDefault();
            showError(problem, message);
        }
    });

    appointmentForm.addEventListener("reset", function () {
        clearErrors();
        setTimeout(refreshClientSlots, 0);
    });
}
