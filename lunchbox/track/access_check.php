<?php

session_start();
if (isset($_POST['pin']) && !empty($_POST['pin']))
{

   $pin = $_POST['pin'];
    include "connect.php";

    // First: Check if it's a delivery/admin user
    $result = mysqli_query($conn, "SELECT * FROM team WHERE mobile = '$pin'");

    if (mysqli_num_rows($result) > 0) {
        $lrow = mysqli_fetch_array($result);

        $_SESSION['partner'] = $lrow[0];
        $_SESSION['eid'] = $lrow[0];
        $_SESSION['name'] = $lrow[1];
        $_SESSION['mobile'] = $lrow[2];

        // ✅ Let the user continue on dashboard.php (default)
        return;

    } else {
        // Next: Check if it's a parent (mobile in pid of subscriptions)
        $parent_result = mysqli_query($conn, "SELECT * FROM subscriptions WHERE pid = '$pin' LIMIT 1");

        if (mysqli_num_rows($parent_result) > 0) {
            $prow = mysqli_fetch_array($parent_result);

            $_SESSION['parent_mobile'] = $pin;
            $_SESSION['child_name'] = $prow['childname'];
            $_SESSION['sub_id'] = $prow['serial']; // or use another unique column

            // ✅ Redirect to parent dashboard
            header("Location: parent_dashboard.php");
            exit;
        } else {
            // Neither parent nor team user — invalid
            $_SESSION = array();
            session_destroy();
            header("Location: index.php?pwderror");
            exit;
        }
    }
}
else if(isset($_SESSION['partner']))
{
  $partner=$_SESSION['partner'];    	
}
else #if user access the page directly, redirect to login page
{
    //clear session from globals
    $_SESSION = array();
    //clear session from disk
    session_destroy();
	header("Location:index.php");
	exit;
}

?>
