<?php include "access_check.php"; ?>
<?php
   include "connect.php";
   $partner = $_SESSION['name'];
   $eid = $_SESSION['eid']; // 0 for Admin, specific ID for Delivery Agent
   $mobile = $_SESSION['mobile'];
?>
<!doctype html>
<html class="sidebar-light fixed sidebar-left-collapsed">
<head>
    <?php include "head.php"; ?>
    <style>
        td {
            color: #000000;
        }
        .ui-pnotify.red .ui-pnotify-container {
            background-color: #DC143C !important;
            color: #ffffff;
            border: 0px;
        }
        .ui-pnotify.blue .ui-pnotify-container {
            background-color: #0088cc !important;
            color: #ffffff;
            border: 0px;
        }
    </style>
</head>
<body>
<section class="body">
    <?php include "header.php"; ?>
    <div class="inner-wrapper">
        <?php include "sidebar.php"; ?>
        <section role="main" class="content-body">
            <header class="page-header">
                <h2>BO LUNCH BOX</h2>
            </header>
            <div class='row'>
                <div class="col-xl-9">
                    <h5 class="font-weight-semibold text-dark text-uppercase mb-3 mt-3">My Lunch Boxes</h5>
                    <section class="card mt-4">
                        <div class="card-body">
                            <table class="table table-responsive-md table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style='width: 70%'>CUSTOMER</th>
                                        <th style='width: 30%'>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php
// PHP to fetch subscriptions based on $eid (admin or agent)
$subs_query = ($eid == 0)
    ? "SELECT * FROM subscriptions ORDER BY serial"
    : "SELECT * FROM subscriptions WHERE delivery_partner=$eid ORDER BY serial";
$subs_res = mysqli_query($conn, $subs_query);

$sno = 1;
$first_unpicked = false;
date_default_timezone_set('Asia/Kolkata'); // Ensure timezone is set for date functions
$today = date("Y-m-d");

while ($row = mysqli_fetch_array($subs_res)) {
    $pid = $row['pid'];
    $cname = $row['childname'];
    $school = $row['school']; // Assuming school is an INT or similar unique identifier
    $apartment = $row['apartment'];
    $address = $apartment . ", " . $row['address'] . ", " . $row['area'] . ", " . $row['pincode'];

    // Check trip status for today for this specific lunch box using prepared statement
    $trip_stmt = mysqli_prepare($conn, "SELECT pickup_time, drop_time FROM trips WHERE date=? AND pid=? AND school=?");
    mysqli_stmt_bind_param($trip_stmt, "sss", $today, $pid, $school);
    mysqli_stmt_execute($trip_stmt);
    $status_res = mysqli_stmt_get_result($trip_stmt);
    $status_row = mysqli_fetch_array($status_res);
    mysqli_stmt_close($trip_stmt);

    $current_status = 0; // 0 = Not Picked, 1 = Picked (In Transit), 2 = Dropped
    $pickup_time_display = "";
    $drop_time_display = "";

    if ($status_row) {
        $pickup_time_display = date('g:i A', strtotime($status_row['pickup_time']));
        if (!empty($status_row['drop_time'])) { // Check if drop_time is set
            $current_status = 2; // Dropped
            $drop_time_display = date('g:i A', strtotime($status_row['drop_time']));
        } else {
            $current_status = 1; // Picked (In Transit)
        }
    }

    // Add scroll target for the first unpicked item
    echo ($current_status == 0 && !$first_unpicked) ? "<tr id='scrollTarget'>" : "<tr>";
    $first_unpicked = $first_unpicked || ($current_status == 0); // Mark true if first unpicked is found

    echo "<td style='width: 70%'><a><b>" . htmlspecialchars($sno) . " " . htmlspecialchars($cname) . ", " . htmlspecialchars($apartment) . "</b></a>
          <div style='font-size:10px;line-height:10px;'>" . htmlspecialchars($address) . "</div></td>";
    $sno++;

    // Display status based on $current_status - NO INDIVIDUAL DROP BUTTON
    echo "<td id='gerr" . htmlspecialchars($pid) . "'>"; // This TD will be updated by AJAX for single actions

    if ($current_status == 0) { // Not Picked
        echo "<a href='#' onclick='change_status(event, " . htmlspecialchars($pid) . "," . htmlspecialchars($school) . ");'>
                <button type='button' class='mb-1 mt-1 mr-1 btn btn-xs btn-success'>PICK</button>
              </a>";
    } else if ($current_status == 1) { // Picked (In Transit) - NO DROP BUTTON
        echo "<h6 style='color:orange;'><b>IN TRANSIT</b></h6>
              <div style='font-size:10px;'>Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> " . htmlspecialchars($pickup_time_display) . "</div>";
    } else { // status == 2 (Dropped/Completed)
        echo "<h6 style='color:green;'><b>DELIVERY COMPLETED</b></h6>
              <div style='font-size:10px;line-height:10px;'>Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> " . htmlspecialchars($pickup_time_display) . "<br>
              Delivered at <i class='fa fa-clock' style='font-size:10px;'></i> " . htmlspecialchars($drop_time_display) . "</div>";
    }
    // Always include the call parent section, regardless of status
    echo "<div style='font-size:12px;'>Call Parent @<br>
              <a href='tel:" . htmlspecialchars($pid) . "'><i class='fa fa-phone'></i> " . htmlspecialchars($pid) . "</a>
          </div>";
    echo "</td>"; // End of gerr$pid td

    echo "</tr>";
}
?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
                <div class="col-xl-3">
                    <h5 class="font-weight-semibold text-dark text-uppercase mb-3 mt-3">Today's Deliveries Summary</h5>
                    <section class="card mt-4" style='height:auto;'>
                        <div class="card-body"><br>
                            <ul class="simple-bullet-list mb-3" id='my_delivery_stats'>
<?php
// PHP to display initial counts. These will be updated by JavaScript

// --- Fetch Total Subscriptions ---
$total_subs_query = "SELECT pid FROM subscriptions";
if ($eid !== 0) { // If not admin, filter by delivery partner
    $total_subs_query .= " WHERE delivery_partner=$eid";
}
$total_subs_res = mysqli_query($conn, $total_subs_query);
$total_subs = mysqli_num_rows($total_subs_res);

// --- Fetch Picked (In Transit) Deliveries ---
$picked_query = "SELECT T.pid FROM trips T JOIN subscriptions S ON T.pid = S.pid WHERE T.date='$today' AND T.pickup_time IS NOT NULL AND T.drop_time IS NULL";
if ($eid !== 0) { // If not admin, filter by delivery partner
    $picked_query .= " AND S.delivery_partner=$eid";
}
$picked_res = mysqli_query($conn, $picked_query);
$picked_count = mysqli_num_rows($picked_res);

// --- Fetch Delivered Deliveries ---
$delivered_query = "SELECT T.pid FROM trips T JOIN subscriptions S ON T.pid = S.pid WHERE T.date='$today' AND T.pickup_time IS NOT NULL AND T.drop_time IS NOT NULL";
if ($eid !== 0) { // If not admin, filter by delivery partner
    $delivered_query .= " AND S.delivery_partner=$eid";
}
$delivered_res = mysqli_query($conn, $delivered_query);
$delivered_count = mysqli_num_rows($delivered_res);

// --- Calculate Not Picked Deliveries ---
$not_picked_count = $total_subs - ($picked_count + $delivered_count);

echo "<h1 style='color:blue;'><b><span id='total_subscriptions_count'>" . htmlspecialchars($total_subs) . "</span></b></h1>Subscriptions";
echo "<h1 style='color:orange;'><b><span id='picked_count'>" . htmlspecialchars($picked_count) . "</span></b></h1>In Transit";
echo "<h1 style='color:red;'><b><span id='not_picked_count'>" . htmlspecialchars($not_picked_count) . "</span></b></h1>Not Picked Up";
echo "<h1 style='color:green;'><b><span id='delivered_count'>" . htmlspecialchars($delivered_count) . "</span></b></h1>Delivered";
?>
                            </ul>
                            <div class="text-center mt-3">
                                <?php if ($eid == 0) { // Only show 'Drop All' button for Admin (eid = 0) ?>
                                    <button type="button" class="btn btn-primary" onclick="dropAll()">Drop All In-Transit</button>
                                <?php } ?>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class='row'>
                <div class="col-xl-12">
                    <h5 class="font-weight-semibold text-dark text-uppercase mb-3 mt-3">Our Partners</h5>
                    <div class="owl-carousel owl-theme" data-plugin-carousel data-plugin-options='{ "dots": false, "autoplay": true, "autoplayTimeout": 3000, "loop": true, "margin": 10, "nav": true, "responsive": {"0":{"items":2 }, "600":{"items":3 }, "1000":{"items":6 } } }'>
                        <div class="item"><img class="img-thumbnail" src="img/sponsors/bvrmol.jpg" alt=""></div>
                        <div class="item"><img class="img-thumbnail" src="img/sponsors/westberry.jpg" alt=""></div>
                        <div class="item"><img class="img-thumbnail" src="img/sponsors/eurokids.jpg" alt=""></div>
                        <div class="item"><img class="img-thumbnail" src="img/sponsors/bhavans.jpg" alt=""></div>
                        <div class="item"><img class="img-thumbnail" src="img/sponsors/srkrec.jpg" alt=""></div>
                        <div class="item"><img class="img-thumbnail" src="img/sponsors/mcr_web.jpg" alt=""></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>

<script src="../vendor/jquery/jquery.js"></script>
<script src="../vendor/jquery-browser-mobile/jquery.browser.mobile.js"></script>
<script src="../vendor/popper/umd/popper.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.js"></script>
<script src="../vendor/common/common.js"></script>
<script src="../vendor/nanoscroller/nanoscroller.js"></script>
<script src="../vendor/magnific-popup/jquery.magnific-popup.js"></script>
<script src="../vendor/jquery-placeholder/jquery-placeholder.js"></script>

<script src="../vendor/jquery-ui/jquery-ui.js"></script>
<script src="../vendor/jqueryui-touch-punch/jqueryui-touch-punch.js"></script>
<script src="../vendor/jquery-appear/jquery-appear.js"></script>
<script src="../vendor/owl.carousel/owl.carousel.js"></script>

<script src="../js/theme.js"></script>
<script src="../js/examples/examples.modals.js"></script>
<script src="../vendor/pnotify/pnotify.custom.js"></script>
<script src="../vendor/liquid-meter/liquid.meter.js"></script>

<script src="../js/theme.init.js"></script>

<script>
// Scroll to the first unpicked item on page load
window.onload = function () {
    const el = document.getElementById("scrollTarget");
    if (el) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    // Also update stats when the page first loads
    updateDeliveryStats();
};

// Function to handle single PICK action
function change_status(event, pid, school) {
    event.preventDefault(); // Prevent the default action of the anchor tag
    const er = "gerr" + pid; // Element ID to update
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 1) { // Loading
            document.getElementById(er).innerHTML = "Updating..";
        }
        if (xhr.readyState === 4 && xhr.status === 200) { // Request finished and successful
            // change_ostatus.php returns the new HTML content for the TD
            document.getElementById(er).innerHTML = xhr.responseText;
            updateDeliveryStats(); // Update summary stats after a single change
        }
    };
    xhr.open("GET", "change_ostatus.php?pid=" + pid + "&school=" + school, true);
    xhr.send();
}

// Function to handle "Drop All" button click (Admin only)
function dropAll() {
    if (confirm("Are you sure you want to mark ALL in-transit deliveries as completed for today? This action cannot be undone.")) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "drop_all_deliveries.php", true); // Call the new PHP script
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText); // Expect JSON response
                if (response.success) {
                    new PNotify({
                        title: 'Success!',
                        text: response.message,
                        type: 'success',
                        addclass: 'blue',
                        icon: 'fas fa-check'
                    });
                    // Refresh the main list and stats after successful drop all
                    location.reload(); // Reload the entire page to reflect all changes
                } else {
                    new PNotify({
                        title: 'Error!',
                        text: response.message,
                        type: 'error',
                        addclass: 'red',
                        icon: 'fas fa-times'
                    });
                }
            } else if (xhr.readyState === 4) { // Request finished but status is not 200 (e.g., 404, 500)
                new PNotify({
                    title: 'AJAX Error!',
                    text: 'Failed to connect to server for Drop All function. Status: ' + xhr.status,
                    type: 'error',
                    addclass: 'red',
                    icon: 'fas fa-times'
                });
            }
        };
        xhr.send(); // Send empty body as no specific data is needed on server-side for "drop all"
    }
}

// Function to update the delivery statistics box dynamically
function updateDeliveryStats() {
    const xhr = new XMLHttpRequest();
    // Pass the delivery partner ID to the PHP script to filter results
    // This is crucial for agents to see their specific stats, and for admin to see overall.
    xhr.open("GET", "get_delivery_stats.php?eid=<?php echo $eid; ?>", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const stats = JSON.parse(xhr.responseText);
                // Update the span elements with the new counts
                document.getElementById('total_subscriptions_count').innerText = stats.total_subscriptions;
                document.getElementById('picked_count').innerText = stats.picked;
                document.getElementById('not_picked_count').innerText = stats.not_picked;
                document.getElementById('delivered_count').innerText = stats.delivered;
            } catch (e) {
                console.error("Error parsing JSON response for stats:", e);
                console.error("Response text:", xhr.responseText);
            }
        }
    };
    xhr.send();
}
</script>

<?php include "footer.php"; ?>
</body>
</html>