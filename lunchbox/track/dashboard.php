<?php include "access_check.php"; ?>
<?php 
   include "connect.php";
   $partner = $_SESSION['name'];
   $eid = $_SESSION['eid'];
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

            <?php
            if (isset($_SESSION['drop_messages']) && is_array($_SESSION['drop_messages'])) {
                echo "<div class='alert alert-success'>";
                foreach ($_SESSION['drop_messages'] as $msg) {
                    echo "<div>$msg</div>";
                }
                echo "</div>";
                unset($_SESSION['drop_messages']);
            }
            ?>

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
$subs_res = ($eid == 0)
    ? mysqli_query($conn, "SELECT * FROM subscriptions ORDER BY serial")
    : mysqli_query($conn, "SELECT * FROM subscriptions WHERE delivery_partner=$eid ORDER BY serial");

$sno = 1;
$first_unpicked = false;
$today = date("Y-m-d");

while ($row = mysqli_fetch_array($subs_res)) {
    $pid = $row['pid'];
    $cname = $row['childname'];
    $school = $row['school'];
    $apartment = $row['apartment'];
    $address = $row['apartment'] . ", " . $row['address'] . ", " . $row['area'] . ", " . $row['pincode'];

    $status_res = mysqli_query($conn, "SELECT * FROM trips WHERE date='$today' AND pid='$pid' AND school='$school'");
    $status = 0;
    $dropped = 0;

    if (mysqli_num_rows($status_res) > 0) {
        $srow = mysqli_fetch_array($status_res);
        if (!empty($srow['pickup_time'])) {
            $status = 1;
            $pickup_time = date('g:i A', strtotime($srow['pickup_time']));
        }
        if (!empty($srow['drop_time'])) {
            $dropped = 1;
            $drop_time = date('g:i A', strtotime($srow['drop_time']));
        }
    }

    echo ($status == 0 && !$first_unpicked) ? "<tr id='scrollTarget'>" : "<tr>";
    $first_unpicked = $first_unpicked || $status == 0;

    echo "<td style='width: 70%'><a><b>$sno $cname, $apartment</b></a>
          <div style='font-size:10px;line-height:10px;'>$address</div></td>";
    $sno++;

    echo "<td id='gerr$pid'>";
    if ($status == 0) {
        echo "<a href='#' onclick='change_status($pid,\"$school\");'>
                <button type='button' class='mb-1 mt-1 mr-1 btn btn-xs btn-success'>PICK</button>
              </a>
              <div style='font-size:12px;'>Call Parent @<br>
                <a href='tel:$pid'><i class='fa fa-phone'></i> $pid</a>
              </div>";
    } else {
        if ($dropped) {
            echo "<div style='font-size:10px;'>Dropped at <i class='fa fa-clock'></i> $drop_time Successfully</div>";
        } else {
            echo "<div style='font-size:10px;'>Picked up at <i class='fa fa-clock'></i> $pickup_time</div>";
        }
        echo "<div style='font-size:12px;'>Call Parent @<br>
                <a href='tel:$pid'><i class='fa fa-phone'></i> $pid</a>
              </div>";
    }
    echo "</td></tr>";
}
?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="col-xl-3">
                    <h5 class="font-weight-semibold text-dark text-uppercase mb-3 mt-3">Today's Deliveries</h5>
                    <section class="card mt-4" style='height:350px;'>
                        <div class="card-body"><br>
                            <ul class="simple-bullet-list mb-3" id='my_games'>

<?php
$total_res = mysqli_query($conn, "SELECT pid FROM subscriptions");
$picked_res = mysqli_query($conn, "SELECT * FROM trips WHERE date='$today' AND pickup_time IS NOT NULL AND drop_time IS NULL");
$drop_res = mysqli_query($conn, "SELECT * FROM trips WHERE date='$today' AND pickup_time IS NOT NULL AND drop_time IS NOT NULL");

$total = mysqli_num_rows($total_res);
$picked = mysqli_num_rows($picked_res);
$delivered = mysqli_num_rows($drop_res);
$no_picked = $total - ($picked + $delivered);

echo "<h1 style='color:blue;'><b>$total</b></h1>Subscriptions";
echo "<h1 style='color:orange;'><b>$picked</b></h1>In Transit";
echo "<h1 style='color:red;'><b>$no_picked</b></h1>Not Picked Up";
echo "<h1 style='color:green;'><b>$delivered</b></h1>Delivered";

if ($_SESSION['mobile'] === '9010872333') {
    echo "<form method='post' action='drop_all.php'>
            <button type='submit' class='btn btn-danger btn-sm mt-2'>
                <i class='fa fa-truck'></i> DROP ALL
            </button>
          </form>";
}
?>
                            </ul>
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

<!-- Scripts -->
<script src="../vendor/jquery/jquery.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.js"></script>
<script src="../vendor/owl.carousel/owl.carousel.js"></script>
<script src="../js/theme.js"></script>
<script src="../js/theme.init.js"></script>

<script>
window.onload = function () {
    const el = document.getElementById("scrollTarget");
    if (el) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
    }
};

function change_status(pid, school) {
    const er = "gerr" + pid;
    const currentRow = document.getElementById(er).closest("tr");

    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 1) {
            document.getElementById(er).innerHTML = "Updating...";
        }
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById(er).innerHTML = xhr.responseText;

            setTimeout(() => {
                const nextRow = currentRow?.nextElementSibling;
                if (nextRow) {
                    nextRow.scrollIntoView({ behavior: "smooth", block: "center" });
                }
            }, 100);
        }
    };
    xhr.open("GET", "change_ostatus.php?pid=" + pid + "&school=" + school, true);
    xhr.send();
}
</script>

<?php include "footer.php"; ?>
</body>
</html>
