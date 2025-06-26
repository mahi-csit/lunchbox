<?php
include "access_check.php";
include "connect.php";
date_default_timezone_set('Asia/Kolkata');
$pid = $_GET['pid'];
$school = $_GET['school'];
$today = date("Y-m-d");

// For name and apartment info:
$sub_res = mysqli_query($conn, "SELECT * FROM subscriptions WHERE pid='$pid'");
$sub = mysqli_fetch_array($sub_res);
$cname = $sub['childname'];
$apartment = $sub['apartment'];
$address = $sub['apartment'].", ".$sub['address'].", ".$sub['area'].", ".$sub['pincode'];

// Check status
$status_res = mysqli_query($conn, "SELECT * FROM trips WHERE date='$today' AND pid='$pid' AND school=$school");

$status = 0;
$td_content = "";

if (mysqli_num_rows($status_res) == 0) {
    // INSERT PICKUP
    $query = "INSERT INTO trips (pid, school, date, pickup_time) VALUES ('$pid', $school, '$today', NOW())";
    mysqli_query($conn, $query);
    $status = 1;
    $pickup_time = date("g:i A");
    $td_content = "<a href='#' onclick='change_status($pid,$school);'>
            <button type='button' class='mb-1 mt-1 mr-1 btn btn-xs btn-warning'>DROP</button>
          </a>
          <div style='font-size:10px;'>Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> $pickup_time</div>
          <div style='font-size:12px;'>Call Parent @<br><a href='tel:$pid'><i class='fa fa-phone'></i> $pid</a></div>";
} else {
    // UPDATE DROP
    $srow = mysqli_fetch_array($status_res);
    if (!isset($srow['drop_time']) || $srow['drop_time'] == null) {
        $query = "UPDATE trips SET drop_time = NOW() WHERE pid='$pid' AND school=$school AND date='$today'";
        mysqli_query($conn, $query);

        $pickup_time = date('g:i A', strtotime($srow['pickup_time']));
        $drop_time = date('g:i A');
        $status = 2;
        $td_content = "<h6 style='color:green;'><b>DELIVERY COMPLETED</b></h6>
              <div style='font-size:10px;line-height:10px;'>
                Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> $pickup_time<br>
                Delivered at <i class='fa fa-clock' style='font-size:10px;'></i> $drop_time
              </div>";
    } else {
        $td_content = "<div style='color:gray;'>Already Delivered</div>";
    }
}

echo "<tr id='row$pid'>
        <td>
                Picked up at <i class='fa fa-clock' style='font-size:10px;'></i> $pickup_time<br>
        </td>
      </tr>";
?>
