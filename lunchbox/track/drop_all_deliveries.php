<?php
// drop_all_deliveries.php
include "access_check.php"; // Ensure user is logged in and authorized
include "connect.php"; // Your database connection

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get today's date (current date and time in Bhimavaram, IST)
    date_default_timezone_set('Asia/Kolkata'); // Set timezone to India Standard Time
    $today = date("Y-m-d");
    $current_time = date("Y-m-d H:i:s"); // Capture current timestamp for drop_time

    // Start a transaction for atomicity
    mysqli_begin_transaction($conn);

    try {
        // Prepare the update query: Update ALL in-transit trips for today, regardless of partner.
        $update_query = "UPDATE trips
                         SET drop_time = ?
                         WHERE date = ?
                         AND pickup_time IS NOT NULL
                         AND drop_time IS NULL"; // Only update trips that are "in transit"

        $stmt = mysqli_prepare($conn, $update_query);
        if (!$stmt) {
            throw new Exception("MySQLi prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "ss", $current_time, $today);

        if (mysqli_stmt_execute($stmt)) {
            $rows_affected = mysqli_stmt_affected_rows($stmt);
            mysqli_commit($conn); // Commit the transaction
            $response['success'] = true;
            $response['message'] = $rows_affected . ' deliveries marked as completed across all partners.';
        } else {
            mysqli_rollback($conn); // Rollback on error
            $response['message'] = 'Error updating deliveries: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);

    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback on exception
        $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
?>