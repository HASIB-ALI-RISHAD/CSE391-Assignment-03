<?php
/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: index.php — Client Panel (Book Appointment, Mechanic Slots, My Bookings, Help)
   ========================================================================== */

session_start();
include 'db.php';

// 1. Only signed-in clients may use this page
if (!isset($_SESSION["user_role"])) {
    $db->close();
    header("Location: login.php?auth_required=1");
    exit();
}
if ($_SESSION["user_role"] === "admin") {
    $db->close();
    header("Location: admin.php");
    exit();
}

$user_id      = (int)$_SESSION["user_id"];
$user_name    = $_SESSION["user_name"];
$user_phone   = $_SESSION["user_phone"];
$user_address = $_SESSION["user_address"];
$today        = date("Y-m-d");

// 2. Default form values (name, phone and address come from the client's profile)
$client_name      = $user_name;
$phone            = $user_phone;
$address          = $user_address;
$car_license      = "";
$car_engine       = "";
$appointment_date = $today;
$mechanic_id      = 0;

$status_type    = "";
$status_title   = "";
$status_message = "";

// 3. Handle "Book Appointment" form (POST)
if (isset($_POST["book_submit"])) {
    $client_name      = trim($_POST["client_name"]);
    $phone            = trim($_POST["phone"]);
    $address          = trim($_POST["address"]);
    $car_license      = strtoupper(trim($_POST["car_license"]));
    $car_engine       = trim($_POST["car_engine"]);
    $appointment_date = trim($_POST["appointment_date"]);
    $mechanic_id      = isset($_POST["mechanic_id"]) ? (int)$_POST["mechanic_id"] : 0;

    // 3A. Server-side validation
    $errors = array();

    if ($client_name === "") {
        $errors[] = "Name is required.";
    }
    if ($phone === "") {
        $errors[] = "Phone number is required.";
    } elseif (!ctype_digit($phone)) {
        $errors[] = "Phone number must contain digits only.";
    }
    if ($address === "") {
        $errors[] = "Address is required.";
    }
    if ($car_license === "") {
        $errors[] = "Car license number is required.";
    }
    if ($car_engine === "") {
        $errors[] = "Car engine number is required.";
    } elseif (!ctype_digit($car_engine)) {
        $errors[] = "Car engine number must contain digits only.";
    }
    if (!is_valid_date($appointment_date)) {
        $errors[] = "Please choose a valid appointment date.";
    } elseif ($appointment_date < $today) {
        $errors[] = "The appointment date cannot be in the past.";
    }
    if ($mechanic_id <= 0) {
        $errors[] = "Please select a mechanic.";
    }

    if (count($errors) > 0) {
        $status_type    = "error";
        $status_title   = "Please check your details";
        $status_message = implode(" ", $errors);
    } else {
        $safe_name    = $db->real_escape_string($client_name);
        $safe_phone   = $db->real_escape_string($phone);
        $safe_address = $db->real_escape_string($address);
        $safe_license = $db->real_escape_string($car_license);
        $safe_engine  = $db->real_escape_string($car_engine);
        $safe_date    = $db->real_escape_string($appointment_date);

        $mech_res  = $db->query("SELECT * FROM mechanics WHERE mechanic_id = $mechanic_id");
        $mech_info = $mech_res ? $mech_res->fetch_assoc() : null;

        if (!$mech_info) {
            $status_type    = "error";
            $status_title   = "Mechanic not found";
            $status_message = "Please choose one of the listed mechanics.";
        } else {
            // Rule 1: one appointment per client per date
            $dup_sql = "SELECT a.appointment_id, m.name AS mechanic_name
                        FROM appointments AS a, mechanics AS m
                        WHERE a.mechanic_id = m.mechanic_id
                          AND a.appointment_date = '$safe_date'
                          AND (a.user_id = $user_id OR a.phone = '$safe_phone' OR a.car_license = '$safe_license')";
            $dup_res = $db->query($dup_sql);

            if ($dup_res && $dup_res->num_rows > 0) {
                $existing = $dup_res->fetch_assoc();
                $status_type    = "error";
                $status_title   = "You already have a booking that day";
                $status_message = "A client can book only one appointment per date. You are already booked with "
                                . $existing["mechanic_name"] . " on " . $appointment_date . ".";
            } else {
                // Rule 2: a mechanic can take at most 4 cars per day
                $cap_res    = $db->query("SELECT COUNT(*) AS booked FROM appointments
                                          WHERE mechanic_id = $mechanic_id AND appointment_date = '$safe_date'");
                $cap_row    = $cap_res->fetch_assoc();
                $max_limit  = (int)$mech_info["daily_capacity"];

                if ((int)$cap_row["booked"] >= $max_limit) {
                    $status_type    = "error";
                    $status_title   = "Mechanic is fully booked";
                    $status_message = $mech_info["name"] . " already has " . $max_limit . " cars on "
                                    . $appointment_date . ". Please pick another mechanic or date.";
                } else {
                    $insert_sql = "INSERT INTO appointments
                                   (user_id, client_name, address, phone, car_license, car_engine, appointment_date, mechanic_id)
                                   VALUES ($user_id, '$safe_name', '$safe_address', '$safe_phone',
                                           '$safe_license', '$safe_engine', '$safe_date', $mechanic_id)";

                    if ($db->query($insert_sql)) {
                        $status_type    = "success";
                        $status_title   = "Appointment confirmed";
                        $status_message = "Car " . $car_license . " is booked with " . $mech_info["name"]
                                        . " on " . $appointment_date . ".";
                        // clear only the car-specific fields
                        $car_license = "";
                        $car_engine  = "";
                        $mechanic_id = 0;
                    } else {
                        $status_type    = "error";
                        $status_title   = "Could not save booking";
                        $status_message = "A database error occurred. Please try again.";
                    }
                }
            }
        }
    }
}

// 4. Handle "Cancel booking" (POST) — a client can only cancel their own future bookings
if (isset($_POST["cancel_submit"])) {
    $cancel_id = (int)$_POST["appointment_id"];
    $db->query("DELETE FROM appointments
                WHERE appointment_id = $cancel_id AND user_id = $user_id AND appointment_date >= '$today'");
    if ($db->affected_rows > 0) {
        $status_type    = "success";
        $status_title   = "Booking cancelled";
        $status_message = "Appointment #" . $cancel_id . " was cancelled and the slot is free again.";
    } else {
        $status_type    = "error";
        $status_title   = "Could not cancel";
        $status_message = "Only your own upcoming appointments can be cancelled.";
    }
}

// 5. Date used for the "free places" cards
$selected_date = is_valid_date($appointment_date) ? $appointment_date : $today;
$safe_selected = $db->real_escape_string($selected_date);

// 6. The 5 mechanics with their free places on the selected date
$mechanics = array();
$mech_sql = "SELECT m.mechanic_id, m.name, m.specialty, m.phone, m.daily_capacity,
                    (SELECT COUNT(*) FROM appointments AS a
                     WHERE a.mechanic_id = m.mechanic_id AND a.appointment_date = '$safe_selected') AS booked
             FROM mechanics AS m
             ORDER BY m.mechanic_id";
$mech_res = $db->query($mech_sql);
while ($row = $mech_res->fetch_assoc()) {
    $row["booked"] = (int)$row["booked"];
    $free_places   = (int)$row["daily_capacity"] - $row["booked"];
    if ($free_places < 0) {
        $free_places = 0;
    }
    $row["free"] = $free_places;
    $mechanics[] = $row;
}

// 7. This client's own appointments (joined with mechanic name)
$my_bookings = array();
$my_sql = "SELECT a.appointment_id, a.car_license, a.car_engine, a.appointment_date, m.name AS mechanic_name
           FROM appointments AS a, mechanics AS m
           WHERE a.mechanic_id = m.mechanic_id AND a.user_id = $user_id
           ORDER BY a.appointment_date DESC, a.appointment_id DESC";
$my_res = $db->query($my_sql);
while ($row = $my_res->fetch_assoc()) {
    $my_bookings[] = $row;
}

$upcoming_count = 0;
$next_date      = "";
$next_mechanic  = "";
foreach ($my_bookings as $b) {
    if ($b["appointment_date"] >= $today) {
        $upcoming_count++;
        if ($next_date === "" || $b["appointment_date"] < $next_date) {
            $next_date     = $b["appointment_date"];
            $next_mechanic = $b["mechanic_name"];
        }
    }
}

// 8. Booked counts for every date, so script.js can refresh the free places instantly
$bookings_by_date = array();
$count_res = $db->query("SELECT appointment_date, mechanic_id, COUNT(*) AS cnt
                         FROM appointments GROUP BY appointment_date, mechanic_id");
while ($row = $count_res->fetch_assoc()) {
    $bookings_by_date[$row["appointment_date"]][(int)$row["mechanic_id"]] = (int)$row["cnt"];
}

$page_title = "Book Appointment";
include 'header.php';
?>

<!-- Welcome banner -->
<section class="page-hero">
    <div>
        <span class="tag">Client panel</span>
        <h1 class="page-title">Hello, <?php echo h($user_name); ?> 👋</h1>
        <p class="page-lead">Book a senior mechanic for your car, check live availability and manage your appointments.</p>
    </div>

    <div class="stat-row">
        <div class="stat">
            <span class="stat-num"><?php echo count($my_bookings); ?></span>
            <span class="stat-label">Total bookings</span>
        </div>
        <div class="stat">
            <span class="stat-num"><?php echo $upcoming_count; ?></span>
            <span class="stat-label">Upcoming</span>
        </div>
        <div class="stat stat-wide">
            <span class="stat-num stat-num-sm"><?php echo ($next_date !== "") ? h($next_date) : "—"; ?></span>
            <span class="stat-label"><?php echo ($next_date !== "") ? "Next visit · " . h($next_mechanic) : "No upcoming visit"; ?></span>
        </div>
    </div>
</section>

<?php if ($status_type !== ""): ?>
<div class="alert <?php echo ($status_type === 'success') ? 'alert-success' : 'alert-error'; ?>" role="alert">
    <strong><?php echo h($status_title); ?></strong>
    <span><?php echo h($status_message); ?></span>
</div>
<?php endif; ?>

<!-- Booking form + live mechanic availability -->
<div class="two-col">

    <section id="booking" class="card section-card">
        <div class="section-head">
            <h2>Book an appointment</h2>
            <p>Fill in your car details and pick a mechanic.</p>
        </div>

        <div id="js-form-error" class="alert alert-error" role="alert" hidden>
            <strong>Please check your details</strong>
            <span id="js-form-error-text"></span>
        </div>

        <form id="appointment-form" action="index.php#booking" method="POST" class="stack-form" novalidate>
            <div class="form-grid">
                <div class="field">
                    <label for="client_name" class="label">Name</label>
                    <input type="text" id="client_name" name="client_name" class="input"
                           value="<?php echo h($client_name); ?>">
                </div>
                <div class="field">
                    <label for="phone" class="label">Phone</label>
                    <input type="text" id="phone" name="phone" class="input" inputmode="numeric"
                           value="<?php echo h($phone); ?>">
                </div>
            </div>

            <div class="field">
                <label for="address" class="label">Address</label>
                <input type="text" id="address" name="address" class="input"
                       value="<?php echo h($address); ?>">
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="car_license" class="label">Car license number</label>
                    <input type="text" id="car_license" name="car_license" class="input"
                           placeholder="e.g. DHAKA METRO-GA-12-3456"
                           value="<?php echo h($car_license); ?>">
                </div>
                <div class="field">
                    <label for="car_engine" class="label">Car engine number</label>
                    <input type="text" id="car_engine" name="car_engine" class="input" inputmode="numeric"
                           placeholder="Digits only"
                           value="<?php echo h($car_engine); ?>">
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="appointment_date" class="label">Appointment date</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="input"
                           min="<?php echo $today; ?>" value="<?php echo h($appointment_date); ?>">
                </div>
                <div class="field">
                    <label for="mechanic_id" class="label">Mechanic</label>
                    <select id="mechanic_id" name="mechanic_id" class="input">
                        <option value="">Select a mechanic…</option>
                        <?php foreach ($mechanics as $m): ?>
                        <option value="<?php echo $m['mechanic_id']; ?>"
                                data-mech-name="<?php echo h($m['name']); ?>"
                                data-mech-cap="<?php echo $m['daily_capacity']; ?>"
                                <?php echo ($mechanic_id === (int)$m['mechanic_id']) ? 'selected' : ''; ?>
                                <?php echo ($m['free'] === 0) ? 'disabled' : ''; ?>>
                            <?php echo h($m['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="book_submit" value="1" class="btn btn-primary">Confirm appointment</button>
                <button type="reset" class="btn btn-outline">Reset</button>
            </div>
        </form>
    </section>

    <section id="mechanics" class="card section-card">
        <div class="section-head">
            <h2>Mechanic availability</h2>
            <p>Free places on <strong id="live-date-label"><?php echo h($selected_date); ?></strong></p>
        </div>

        <div class="mech-list">
            <?php foreach ($mechanics as $m):
                $percent = (int)round(($m["booked"] / $m["daily_capacity"]) * 100);
            ?>
            <article class="mech-card <?php echo ($m['free'] === 0) ? 'is-full' : 'is-free'; ?>"
                     data-mechanic-card="<?php echo $m['mechanic_id']; ?>"
                     data-capacity="<?php echo $m['daily_capacity']; ?>">
                <div class="mech-top">
                    <div class="mech-avatar"><?php echo h(substr($m["name"], 0, 1)); ?></div>
                    <div class="mech-info">
                        <h3><?php echo h($m["name"]); ?></h3>
                        <span class="mech-spec"><?php echo h($m["specialty"]); ?></span>
                    </div>
                    <span class="slot-pill <?php echo ($m['free'] === 0) ? 'slot-full' : 'slot-free'; ?>" data-slot-badge>
                        <?php echo ($m['free'] === 0) ? "Full" : $m['free'] . " free"; ?>
                    </span>
                </div>
                <div class="mech-bar"><span data-bar style="width: <?php echo $percent; ?>%"></span></div>
                <div class="mech-meta">
                    <span><strong data-booked-count><?php echo $m["booked"]; ?></strong> / <?php echo $m["daily_capacity"]; ?> cars booked</span>
                    <span><?php echo h($m["phone"]); ?></span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<!-- My bookings -->
<section id="my-bookings" class="card section-card">
    <div class="section-head">
        <h2>My bookings</h2>
        <p>Every appointment you have made with us.</p>
    </div>

    <?php if (count($my_bookings) === 0): ?>
        <div class="empty-state">
            <strong>No bookings yet</strong>
            <span>Use the form above to book your first appointment.</span>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Car license</th>
                    <th>Engine no.</th>
                    <th>Mechanic</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($my_bookings as $b):
                    $is_upcoming = ($b["appointment_date"] >= $today);
                ?>
                <tr>
                    <td><?php echo $b["appointment_id"]; ?></td>
                    <td><strong><?php echo h($b["appointment_date"]); ?></strong></td>
                    <td><?php echo h($b["car_license"]); ?></td>
                    <td><?php echo h($b["car_engine"]); ?></td>
                    <td><?php echo h($b["mechanic_name"]); ?></td>
                    <td>
                        <?php if ($b["appointment_date"] === $today): ?>
                            <span class="badge badge-today">Today</span>
                        <?php elseif ($is_upcoming): ?>
                            <span class="badge badge-upcoming">Upcoming</span>
                        <?php else: ?>
                            <span class="badge badge-past">Completed</span>
                        <?php endif; ?>
                    </td>
                    <td class="cell-action">
                        <?php if ($is_upcoming): ?>
                        <form action="index.php#my-bookings" method="POST" data-confirm="Cancel this appointment?">
                            <input type="hidden" name="appointment_id" value="<?php echo $b['appointment_id']; ?>">
                            <button type="submit" name="cancel_submit" value="1" class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<!-- Help -->
<section id="help" class="card section-card">
    <div class="section-head">
        <h2>Help &amp; guidelines</h2>
        <p>Everything you need to know about booking with us.</p>
    </div>

    <div class="help-grid">
        <div class="help-item">
            <span class="help-num">1</span>
            <h3>Choose a date</h3>
            <p>Pick the day you want to visit. The availability cards on the right update instantly for that date.</p>
        </div>
        <div class="help-item">
            <span class="help-num">2</span>
            <h3>Pick a mechanic</h3>
            <p>Each senior mechanic handles a maximum of 4 cars per day. Mechanics with no free places are disabled.</p>
        </div>
        <div class="help-item">
            <span class="help-num">3</span>
            <h3>Enter car details</h3>
            <p>Phone and engine number must be digits only. The car license can contain letters, numbers and dashes.</p>
        </div>
        <div class="help-item">
            <span class="help-num">4</span>
            <h3>One booking per day</h3>
            <p>You cannot book twice on the same date. To change a booking, cancel it from “My bookings” and book again.</p>
        </div>
    </div>
</section>

<script>
    // Booked cars per date and mechanic, read by script.js to refresh the free places instantly
    window.WORKSHOP_BOOKINGS_BY_DATE = <?php echo json_encode($bookings_by_date, JSON_FORCE_OBJECT); ?>;
</script>

<?php
include 'footer.php';
$db->close();
?>
