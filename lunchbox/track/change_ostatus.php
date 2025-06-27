<?php
// change_ostatus.php
include "access_check.php";
include "connect.php";
date_default_timezone_set('Asia/Kolkata'); // Set timezone to India Standard Time

$pid = $_GET['pid'];
$school = $_GET['school'];
$today = date("Y-m-d");

$td_content = ""; // Initialize content for the <td>

// Use prepared statement to check current status
$check_stmt = mysqli_prepare($conn, "SELECT pickup_time, drop_time FROM trips WHERE date=? AND pid=? AND school=?");
if (!$check_stmt) {
    error_log("Error preparing check statement: " . mysqli_error($conn));
    $td_content = "<div style='color:red;'>Database Error (check).</div>";
} else {
    mysqli_stmt_bind_param($check_stmt, "sss", $today, $pid, $school);
    mysqli_stmt_execute($check_stmt);
    $status_res = mysqli_stmt_get_result($check_stmt);
    $srow = mysqli_fetch_array($status_res);
    mysqli_stmt_close($check_stmt);

    if (!$srow) { // If no existing trip record for today (status is 0: Not Picked)
        // Perform INSERT for PICKUP using prepared statement
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO trips (pid, school, date, pickup_time) VALUES (?, ?, ?, NOW())");
        if (!$insert_stmt) {
            error_log("Error preparing insert statement: " . mysqli_error($conn));
            $td_content = "<div style='color:red;'>Database Error (insert).</div>";
        } else {
            mysqli_stmt_bind_param($insert_stmt, "sss", $pid, $school, $today);
            if (mysqli_stmt_execute($insert_stmt)) {
                $pickup_time = date("g:i A");
                // Output for "IN TRANSIT" state (no DROP button here)
                $td_content = "<h6 style='color:orange;'><b>IN TRANSIT</b></h6>
                               <div style='font-size:10px;'>Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> " . htmlspecialchars($pickup_time) . "</div>
                               <div style='font-size:12px;'>Call Parent @<br><a href='tel:" . htmlspecialchars($pid) . "'><i class='fa fa-phone'></i> " . htmlspecialchars($pid) . "</a></div>";
            } else {
                error_log("Error executing insert statement: " . mysqli_error($conn));
                $td_content = "<div style='color:red;'>Error picking up: " . mysqli_error($conn) . "</div>";
            }
            mysqli_stmt_close($insert_stmt);
        }
    } else { // If a trip record exists, it means it's already picked or dropped.
             // This script is only for "PICK" action. Return current status.
        if (!empty($srow['drop_time'])) { // Already dropped
            $pickup_time = date('g:i A', strtotime($srow['pickup_time']));
            $drop_time = date('g:i A', strtotime($srow['drop_time']));
            $td_content = "<h6 style='color:green;'><b>DELIVERY COMPLETED</b></h6>
                          <div style='font-size:10px;line-height:10px;'>
                            Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> " . htmlspecialchars($pickup_time) . "<br>
                            Delivered at <i class='fa fa-clock' style='font-size:10px;'></i> " . htmlspecialchars($drop_time) . "
                          </div>";
        } else { // Currently in transit (picked but not dropped)
            $pickup_time = date('g:i A', strtotime($srow['pickup_time']));
            $td_content = "<h6 style='color:orange;'><b>IN TRANSIT</b></h6>
                           <div style='font-size:10px;'>Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> " . htmlspecialchars($pickup_time) . "</div>
                           <div style='font-size:12px;'>Call Parent @<br><a href='tel:" . htmlspecialchars($pid) . "'><i class='fa fa-phone'></i> " . htmlspecialchars($pid) . "</a></div>";
        }
    }
}

echo $td_content; // Only echo the HTML for the <td>
exit();
?>