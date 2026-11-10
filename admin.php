<?php
/* ==========================================================================
   CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
   Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
   File: admin.php — Admin Panel (Appointment List, Change Date / Mechanic)
   ========================================================================== */

session_start();
include 'db.php';

// 1. Only the admin may open this page
if (!isset($_SESSION["user_role"])) {
    $db->close();
    header("Location: login.php?auth_required=1");
    exit();
}
if ($_SESSION["user_role"] !== "admin") {
    $db->close();
    header("Location: index.php");
    exit();
}

$today           = date("Y-m-d");
$status_type     = "";
$status_title    = "";
$status_message  = "";
$edit_id         = isset($_GET["edit"]) ? (int)$_GET["edit"] : 0;
$updated_to_date = "";

// 2. Handle "Save changes" — change appointment date and/or mechanic (POST)
if (isset($_POST["update_submit"])) {
    $appointment_id  = (int)$_POST["appointment_id"];
    $new_date        = trim($_POST["new_date"]);
    $new_mechanic_id = (int)$_POST["new_mechanic_id"];
    $edit_id         = $appointment_id;   // keep the edit panel open if something is wrong

    if (!is_valid_date($new_date) || $new_mechanic_id <= 0) {
        $status_type    = "error";
        $status_title   = "Invalid details";
        $status_message = "Please choose a valid date and a mechanic.";
    } else {
        $cur_res   = $db->query("SELECT * FROM appointments WHERE appointment_id = $appointment_id");
        $cur_app   = $cur_res ? $cur_res->fetch_assoc() : null;
        $mech_res  = $db->query("SELECT * FROM mechanics WHERE mechanic_id = $new_mechanic_id");
        $mech_info = $mech_res ? $mech_res->fetch_assoc() : null;

        if (!$cur_app || !$mech_info) {
            $status_type    = "error";
            $status_title   = "Record not found";
            $status_message = "That appointment or mechanic no longer exists.";
        } else {
            $safe_date    = $db->real_escape_string($new_date);
            $safe_phone   = $db->real_escape_string($cur_app["phone"]);
            $safe_license = $db->real_escape_string($cur_app["car_license"]);
            $owner_id     = ($cur_app["user_id"] === null) ? 0 : (int)$cur_app["user_id"];

            // Check 1: the same client must not already have another booking on the new date
            $dup_res = $db->query("SELECT appointment_id FROM appointments
                                   WHERE appointment_date = '$safe_date'
                                     AND appointment_id != $appointment_id
                                     AND (phone = '$safe_phone' OR car_license = '$safe_license'
                                          OR (user_id IS NOT NULL AND user_id = $owner_id))");
            if ($dup_res && $dup_res->num_rows > 0) {
                $status_type    = "error";
                $status_title   = "Duplicate booking";
                $status_message = $cur_app["client_name"] . " already has another appointment on " . $new_date . ".";
            } else {
                // Check 2: the mechanic must have a free place on the new date (max 4)
                $cap_res   = $db->query("SELECT COUNT(*) AS booked FROM appointments
                                         WHERE mechanic_id = $new_mechanic_id
                                           AND appointment_date = '$safe_date'
                                           AND appointment_id != $appointment_id");
                $cap_row   = $cap_res->fetch_assoc();
                $max_limit = (int)$mech_info["daily_capacity"];

                if ((int)$cap_row["booked"] >= $max_limit) {
                    $status_type    = "error";
                    $status_title   = "Mechanic is fully booked";
                    $status_message = $mech_info["name"] . " already has " . $max_limit . " cars on " . $new_date . ".";
                } else {
                    $ok = $db->query("UPDATE appointments
                                      SET appointment_date = '$safe_date', mechanic_id = $new_mechanic_id
                                      WHERE appointment_id = $appointment_id");
                    if ($ok) {
                        $status_type     = "success";
                        $status_title    = "Appointment updated";
                        $status_message  = "#" . $appointment_id . " (" . $cur_app["client_name"] . ") is now on "
                                         . $new_date . " with " . $mech_info["name"] . ".";
                        $edit_id         = 0;
                        $updated_to_date = $new_date;
                    } else {
                        $status_type    = "error";
                        $status_title   = "Update failed";
                        $status_message = "A database error occurred. Please try again.";
                    }
                }
            }
        }
    }
}

// 3. Handle "Delete" (POST)
if (isset($_POST["delete_submit"])) {
    $del_id = (int)$_POST["appointment_id"];
    $db->query("DELETE FROM appointments WHERE appointment_id = $del_id");
    if ($db->affected_rows > 0) {
        $status_type    = "success";
        $status_title   = "Appointment deleted";
        $status_message = "Appointment #" . $del_id . " was removed and the mechanic's place is free again.";
    }
    $edit_id = 0;
}

// 4. Date filter for the table and the workload cards (GET)
//    Defaults to today's date ($today) at start; pass ?filter_date=all to view every date.
$raw_filter = isset($_GET["filter_date"]) ? trim($_GET["filter_date"]) : "";
if ($raw_filter === "all") {
    $filter_date = "";
} elseif (is_valid_date($raw_filter)) {
    $filter_date = $raw_filter;
} elseif ($updated_to_date !== "") {
    $filter_date = $updated_to_date;
} else {
    $filter_date = $today;
}
$workload_date = ($filter_date !== "") ? $filter_date : $today;
$safe_workload = $db->real_escape_string($workload_date);

// 5. Mechanics + how many cars each has on the workload date
$mechanics = array();
$mech_res = $db->query("SELECT m.*,
                               (SELECT COUNT(*) FROM appointments AS a
                                WHERE a.mechanic_id = m.mechanic_id AND a.appointment_date = '$safe_workload') AS booked
                        FROM mechanics AS m ORDER BY m.mechanic_id");
while ($row = $mech_res->fetch_assoc()) {
    $row["booked"] = (int)$row["booked"];
    $free_places   = (int)$row["daily_capacity"] - $row["booked"];
    if ($free_places < 0) {
        $free_places = 0;
    }
    $row["free"] = $free_places;
    $mechanics[] = $row;
}

// 6. Appointment list joined with mechanic name (Lecture 07 join syntax)
$where = "";
if ($filter_date !== "") {
    $where = " AND a.appointment_date = '" . $db->real_escape_string($filter_date) . "' ";
}
$list_sql = "SELECT a.appointment_id, a.client_name, a.phone, a.car_license, a.car_engine,
                    a.appointment_date, a.mechanic_id, m.name AS mechanic_name
             FROM appointments AS a, mechanics AS m
             WHERE a.mechanic_id = m.mechanic_id $where
             ORDER BY a.appointment_date ASC, a.appointment_id ASC";
$list_res     = $db->query($list_sql);
$appointments = array();
while ($row = $list_res->fetch_assoc()) {
    $appointments[] = $row;
}

// 7. Summary numbers for the top cards
$res_all      = $db->query("SELECT COUNT(*) AS c FROM appointments");
$row_all      = $res_all->fetch_assoc();
$total_all    = (int)$row_all["c"];

$res_today    = $db->query("SELECT COUNT(*) AS c FROM appointments WHERE appointment_date = '$today'");
$row_today    = $res_today->fetch_assoc();
$total_today  = (int)$row_today["c"];

$res_future   = $db->query("SELECT COUNT(*) AS c FROM appointments WHERE appointment_date > '$today'");
$row_future   = $res_future->fetch_assoc();
$total_future = (int)$row_future["c"];

$res_users    = $db->query("SELECT COUNT(*) AS c FROM users");
$row_users    = $res_users->fetch_assoc();
$total_users  = (int)$row_users["c"];

// 8. The appointment being edited (if any) + booked counts for live labels in script.js
$edit_app = null;
if ($edit_id > 0) {
    $edit_res = $db->query("SELECT a.*, m.name AS mechanic_name
                            FROM appointments AS a, mechanics AS m
                            WHERE a.mechanic_id = m.mechanic_id AND a.appointment_id = $edit_id");
    $edit_app = $edit_res ? $edit_res->fetch_assoc() : null;
}

$bookings_by_date = array();
$count_res = $db->query("SELECT appointment_date, mechanic_id, COUNT(*) AS cnt
                         FROM appointments GROUP BY appointment_date, mechanic_id");
while ($row = $count_res->fetch_assoc()) {
    $bookings_by_date[$row["appointment_date"]][(int)$row["mechanic_id"]] = (int)$row["cnt"];
}

$page_title = "Admin Panel";
include 'header.php';
?>

<!-- Page title + summary cards -->
<section class="page-hero">
    <div>
        <span class="tag">Admin panel</span>
        <h1 class="page-title">Appointment management</h1>
        <p class="page-lead">See every client booking and move an appointment to another date or mechanic.</p>
    </div>

    <div class="stat-row">
        <div class="stat">
            <span class="stat-num"><?php echo $total_all; ?></span>
            <span class="stat-label">All bookings</span>
        </div>
        <div class="stat">
            <span class="stat-num"><?php echo $total_today; ?></span>
            <span class="stat-label">Today</span>
        </div>
        <div class="stat">
            <span class="stat-num"><?php echo $total_future; ?></span>
            <span class="stat-label">Upcoming</span>
        </div>
        <div class="stat">
            <span class="stat-num"><?php echo $total_users; ?></span>
            <span class="stat-label">Clients</span>
        </div>
    </div>
</section>

<?php if ($status_type !== ""): ?>
<div class="alert <?php echo ($status_type === 'success') ? 'alert-success' : 'alert-error'; ?>" role="alert">
    <strong><?php echo h($status_title); ?></strong>
    <span><?php echo h($status_message); ?></span>
</div>
<?php endif; ?>

<!-- Edit panel (shown after pressing "Edit" on a row) -->
<?php if ($edit_app): ?>
<section id="edit-panel" class="card section-card edit-card">
    <div class="section-head">
        <h2>Edit appointment #<?php echo $edit_app["appointment_id"]; ?></h2>
        <p>
            <strong><?php echo h($edit_app["client_name"]); ?></strong> &bull; Car <?php echo h($edit_app["car_license"]); ?>
            &bull; currently <?php echo h($edit_app["appointment_date"]); ?> with <?php echo h($edit_app["mechanic_name"]); ?>
        </p>
    </div>

    <form action="admin.php<?php echo ($raw_filter === 'all') ? '?filter_date=all' : ''; ?>" method="POST" class="stack-form">
        <input type="hidden" name="appointment_id" value="<?php echo $edit_app['appointment_id']; ?>">

        <div class="form-grid">
            <div class="field">
                <label for="edit-date" class="label">New appointment date</label>
                <input type="date" id="edit-date" name="new_date" class="input"
                       value="<?php echo h(isset($_POST['new_date']) ? $_POST['new_date'] : $edit_app['appointment_date']); ?>" required>
            </div>
            <div class="field">
                <label for="edit-mechanic" class="label">Mechanic</label>
                <select id="edit-mechanic" name="new_mechanic_id" class="input" required>
                    <?php
                    $chosen = isset($_POST["new_mechanic_id"]) ? (int)$_POST["new_mechanic_id"] : (int)$edit_app["mechanic_id"];
                    foreach ($mechanics as $m): ?>
                    <option value="<?php echo $m['mechanic_id']; ?>"
                            data-mech-name="<?php echo h($m['name']); ?>"
                            data-mech-cap="<?php echo $m['daily_capacity']; ?>"
                            <?php echo ($chosen === (int)$m['mechanic_id']) ? 'selected' : ''; ?>>
                        <?php echo h($m['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="update_submit" value="1" class="btn btn-primary">Save changes</button>
            <a href="admin.php?filter_date=<?php echo ($filter_date !== '') ? h($filter_date) : 'all'; ?>#appointments" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>
<?php endif; ?>

<!-- Mechanic workload -->
<section id="workload" class="card section-card">
    <div class="section-head section-head-row">
        <div>
            <h2>Mechanic workload</h2>
            <p>Cars assigned on <strong id="live-date-label"><?php echo h($workload_date); ?></strong> (max 4 per mechanic)</p>
        </div>
    </div>

    <div class="workload-grid">
        <?php foreach ($mechanics as $m):
            $percent = (int)round(($m["booked"] / $m["daily_capacity"]) * 100);
        ?>
        <article class="mech-card <?php echo ($m['free'] === 0) ? 'is-full' : 'is-free'; ?>">
            <div class="mech-top">
                <div class="mech-avatar"><?php echo h(substr($m["name"], 0, 1)); ?></div>
                <div class="mech-info">
                    <h3><?php echo h($m["name"]); ?></h3>
                    <span class="mech-spec"><?php echo h($m["specialty"]); ?></span>
                </div>
            </div>
            <div class="mech-bar"><span style="width: <?php echo $percent; ?>%"></span></div>
            <div class="mech-meta">
                <span><strong><?php echo $m["booked"]; ?></strong> / <?php echo $m["daily_capacity"]; ?> cars</span>
                <span class="slot-pill <?php echo ($m['free'] === 0) ? 'slot-full' : 'slot-free'; ?>">
                    <?php echo ($m['free'] === 0) ? "Full" : $m['free'] . " free"; ?>
                </span>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Appointment list -->
<section id="appointments" class="card section-card">
    <div class="section-head section-head-row">
        <div>
            <h2><?php echo ($filter_date === $today) ? "Today's appointments" : (($filter_date !== "") ? "Appointments by date" : "All appointments"); ?></h2>
            <p>
                <?php if ($filter_date !== ""): ?>
                    Showing <?php echo count($appointments); ?> appointment(s) on <strong><?php echo h($filter_date); ?></strong><?php echo ($filter_date === $today) ? " (Today)" : ""; ?>
                <?php else: ?>
                    Showing all <?php echo count($appointments); ?> appointment(s) across all dates, earliest first
                <?php endif; ?>
            </p>
        </div>

        <form action="admin.php#appointments" method="GET" class="filter-form">
            <input type="date" name="filter_date" class="input input-sm"
                   value="<?php echo h($filter_date !== '' ? $filter_date : $today); ?>"
                   onchange="this.form.submit()" aria-label="Filter by date">
            <button type="submit" class="btn btn-sm btn-dark">Filter</button>
            <?php if ($filter_date !== $today): ?>
                <a href="admin.php#appointments" class="btn btn-sm btn-outline">Today</a>
            <?php endif; ?>
            <?php if ($filter_date !== ""): ?>
                <a href="admin.php?filter_date=all#appointments" class="btn btn-sm btn-outline">Show all (<?php echo $total_all; ?>)</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (count($appointments) === 0): ?>
        <div class="empty-state">
            <strong>No appointments on <?php echo ($filter_date !== "") ? h($filter_date) : "record"; ?></strong>
            <span>
                <?php if ($filter_date !== "" && $total_all > 0): ?>
                    There are no bookings on <?php echo h($filter_date); ?>, but there are <?php echo $total_all; ?> booking(s) on other dates.
                    <a href="admin.php?filter_date=all#appointments">Show all bookings &rarr;</a>
                <?php elseif ($filter_date !== ""): ?>
                    Nothing is booked on <?php echo h($filter_date); ?> yet.
                <?php else: ?>
                    Clients have not booked anything yet.
                <?php endif; ?>
            </span>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client name</th>
                    <th>Phone</th>
                    <th>Car registration</th>
                    <th>Date</th>
                    <th>Mechanic</th>
                    <th class="th-action">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a):
                    $filter_param = ($filter_date !== "") ? h($filter_date) : "all";
                    $query = "edit=" . $a["appointment_id"] . "&amp;filter_date=" . $filter_param;
                ?>
                <tr class="<?php echo ($edit_app && (int)$edit_app['appointment_id'] === (int)$a['appointment_id']) ? 'row-editing' : ''; ?>">
                    <td><?php echo $a["appointment_id"]; ?></td>
                    <td><strong><?php echo h($a["client_name"]); ?></strong></td>
                    <td><?php echo h($a["phone"]); ?></td>
                    <td><?php echo h($a["car_license"]); ?></td>
                    <td>
                        <?php echo h($a["appointment_date"]); ?>
                        <?php if ($a["appointment_date"] === $today): ?><span class="badge badge-today">Today</span><?php endif; ?>
                    </td>
                    <td><?php echo h($a["mechanic_name"]); ?></td>
                    <td class="cell-action">
                        <a href="admin.php?<?php echo $query; ?>#edit-panel" class="btn btn-sm btn-dark">Edit</a>
                        <form action="admin.php?filter_date=<?php echo $filter_param; ?>#appointments"
                              method="POST" data-confirm="Delete this appointment permanently?">
                            <input type="hidden" name="appointment_id" value="<?php echo $a['appointment_id']; ?>">
                            <button type="submit" name="delete_submit" value="1" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php if ($edit_app): ?>
<script>
    // Lets script.js show correct free places while the admin changes the date in the edit form
    window.WORKSHOP_BOOKINGS_BY_DATE = <?php echo json_encode($bookings_by_date, JSON_FORCE_OBJECT); ?>;
    window.WORKSHOP_EDITING = { date: "<?php echo h($edit_app['appointment_date']); ?>", mechanic: <?php echo (int)$edit_app["mechanic_id"]; ?> };
</script>
<?php endif; ?>

<?php
include 'footer.php';
$db->close();
?>
