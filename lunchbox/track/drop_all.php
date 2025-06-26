<?php
session_start();
include "connect.php";

$mobile = $_SESSION['mobile'];
$today = date("Y-m-d");
$timeNow = date("Y-m-d H:i:s");

if ($mobile !== '9010872333') {
    die("Unauthorized");
}

// Get all picked but not yet dropped orders
$result = mysqli_query($conn, "SELECT * FROM trips WHERE date='$today' AND pickup_time IS NOT NULL AND drop_time IS NULL");

$messages = [];

while ($row = mysqli_fetch_array($result)) {
    $pid = $row['pid'];
    $school = $row['school'];

    // Update drop time
    mysqli_query($conn, "UPDATE trips SET drop_time = '$timeNow' WHERE pid='$pid' AND school='$school' AND date='$today'");

    // Store message for display
    $dropTimeFormatted = date('g:i A', strtotime($timeNow));
    $messages[] = "Dropped at $dropTimeFormatted Successfully for $pid";
}

// Pass messages back to dashboard
$_SESSION['drop_messages'] = $messages;

header("Location: dashboard.php");
exit;
