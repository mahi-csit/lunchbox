<?php
// get_delivery_stats.php
include "access_check.php"; // Ensure user is logged in
include "connect.php"; // Your database connection

header('Content-Type: application/json'); // Respond with JSON

// Set timezone for consistency
date_default_timezone_set('Asia/Kolkata');
$today = date("Y-m-d");

// Get delivery partner ID from GET request, fallback to session if not provided
$eid = isset($_GET['eid']) ? (int)$_GET['eid'] : (isset($_SESSION['eid']) ? (int)$_SESSION['eid'] : 0);

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

$stats = [
    'total_subscriptions' => $total_subs,
    'picked' => $picked_count,
    'not_picked' => $not_picked_count,
    'delivered' => $delivered_count
];

echo json_encode($stats);
exit();
?>