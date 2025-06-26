<?php include "access_check.php"; ?>
<?php 
   include "connect.php";
   $partner=$_SESSION['partner'];
   $eid=$_SESSION['eid'];
   $mobile=$_SESSION['mobile'];   
   $today=date("Y-m-d");
?>

<?php

 if ($_SERVER["REQUEST_METHOD"] == "POST") 
 {
    include "connect.php";
    $today=date("Y-m-d");
    $query = "update trips set drop_time=now() where school=$school and date='$today'";
    mysqli_query($conn, $query);
	echo '<script>alert("All Boxes Today Marked as Complete")</script>';
 }
?>
<!doctype html>
<html class="sidebar-light fixed sidebar-left-collapsed">
	<head>
     <?php include "head.php"; ?>
	 <style>
		  td{
 		  color:#000000;
	    }
		
	.ui-pnotify.red .ui-pnotify-container {
		background-color: #DC143C !important;
		color:#ffffff;
		border:0px;
		}

	.ui-pnotify.blue .ui-pnotify-container {
		background-color: #0088cc !important;
		color:#ffffff;
		border:0px;
		}
	 </style>
	 

    </head>
	<body>
		<section class="body">
            <?php include "header.php"; ?>
			<div class="inner-wrapper">
			<!-- start: sidebar -->
            <?php include "sidebar.php"; ?>
				<!-- end: sidebar -->
				<section role="main" class="content-body">
					<header class="page-header">
						<h2>BO LUNCH BOX</h2>
					</header>

					<!-- start: page -->
					<div class='row'>
					<div class="col-xl-3">
					<h5 class="font-weight-semibold text-dark text-uppercase mb-3 mt-3">Today's Deliveries</h5>
					<section class="card mt-4" style='height:350px;'>
									<div class="card-body"><br>
																		
							<ul class="simple-bullet-list mb-3" id='my_games'>
						
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
							 <button type='submit' class='mb-1 mt-1 mr-1 btn btn-xs btn-danger'>MARK ALL AS DELIVERED</button>
							 </form>
							<?php
							
                             $total_res=mysqli_query($conn, "SELECT pid from subscriptions;");
                             $picked_res=mysqli_query($conn, "SELECT * from trips where date='$today' and pickup_time IS NOT NULL and drop_time IS NULL;");
                             $drop_res=mysqli_query($conn, "SELECT * from trips where date='$today' and pickup_time IS NOT NULL and drop_time IS NOT NULL;");
                      
					         $total=mysqli_num_rows($total_res);
					         $picked=mysqli_num_rows($picked_res);
					         $delivered=mysqli_num_rows($drop_res);

                             $no_picked=$total-$picked;                             

							 echo "<h1 style='color:blue;'><b>".$total."</b></h1>Subscriptions";
							 echo "<h1 style='color:orange;'><b>".$picked."</b></h1>In Transit";
							 echo "<h1 style='color:red;'><b>".$no_picked."</b></h1>Not Picked Up";
							 echo "<h1 style='color:green;'><b>".$delivered."</b></h1>Delivered";
							
							?>
			 				
							</ul>
									</div>
								</section>
					</div>
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
 										  
        								  $subs_res=mysqli_query($conn, "SELECT * from subscriptions where pid=5;"); 		

										  $sno=1;
          								  while($row=mysqli_fetch_array($subs_res))
										    {
   	        								  $pid=$row['pid'];
   	        								  $cname=$row['childname'];
											  $school=$row['school'];
											  $address=$row['apartment'].", ".$row['address'].", ".$row['area'].", ".$row['pincode'];
											  echo "<tr>";
											  echo "<td style='width: 70%'><a><b>".$sno." ".$cname."</b></a>";
											  $sno++;
											  
											  $today=date("Y-m-d");
											  $status_res=mysqli_query($conn, "SELECT * from trips where date='$today' and pid='$pid' and school='$school';");
 											  
 											  
  											  $status=0;
          								      if(mysqli_num_rows($status_res)==0)
											  {
												echo " <i class='fa fa-circle' style='color:red;font-size:10px;'></i> <b style='color:red;font-size:10px;'>NOT PICKED UP</b>";   
											  }	
											  else
											  {
                                                $srow=mysqli_fetch_array($status_res);
												//if(isset($srow['pickup_time']) && isset($srow['drop_time']))
												  if(isset($srow['drop_time']))
												  {
													echo " <i class='fa fa-circle' style='color:green;font-size:10px;'></i> <b style='color:green;font-size:10px;'>DELIVERED</b>";
													$status=2;
  													
												  }
												  else
												  {
													echo " <i class='fa fa-circle' style='color:orange;font-size:10px;'></i> <b style='color:orange;font-size:10px;'>IN TRANSIT</b>";
													$status=1;
          								        													
												  }
											  }				  

  echo "<div style='font-size:10px;line-height:10px;'>".$address."</div></td>";	

  if($status == 0)
	{
	  echo "<td id='gerr$pid' style='width: 30%'><a href='#' onclick='change_status($pid,$school);'><button type='button' class='mb-1 mt-1 mr-1 btn btn-xs btn-success'>MARK AS PICKEDUP</button></a><div style='font-size:12px;'>Call Parent @<br><a href='tel:$pid'><i class='fa fa-phone'></i> ".$pid."</a></div></td>";
	}
  else if($status == 1)

	{
    	 echo "<td id='gerr$pid'><a href='#' onclick='change_status($pid,$school);'><button type='button' class='mb-1 mt-1 mr-1 btn btn-xs btn-warning'>MARK AS DELIVERED</button></a>";
		 
		 $pick_time=$srow['pickup_time'];
		 $pickup_time=date('g:i A', strtotime($pick_time));
		 
		 echo "<div style='font-size:10px;'>Pickedup at <i class='fa fa-clock' style='font-size:10px;'></i> $pickup_time</div><div style='font-size:12px;'>Call Parent @<br><a href='tel:$pid'><i class='fa fa-phone'></i> ".$pid."</a></div></td>";
	}
  else
	{
    	 echo "<td id='gerr$pid'><h6 style='color:green;'><b>DELIVERY COMPLETED</b></h6>";
		 $pick_time=$srow['pickup_time'];
		 $pickup_time=date('g:i A', strtotime($pick_time));
		 echo "<div style='font-size:10px;line-height:10px;'>Pickedup at <i class='fa fa-clock' style='font-size:10px;'></i> $pickup_time<br>";
		 $dp_time=$srow['drop_time'];
		 $drop_time=date('g:i A', strtotime($dp_time));		 
		 echo "Delivered at <i class='fa fa-clock' style='font-size:10px;'></i> $drop_time</div></td>";
	}

	echo "</tr>";
 }											
                                        ?>  
										</tbody>
									</table>
<!--                                 <div align='right'><a href=''>More...</a></div> -->
								</div>
							</section>
								

					</div>
                   </div>

					<div class='row'>
					 <div class="col-xl-12">
					 <h5 class="font-weight-semibold text-dark text-uppercase mb-3 mt-3">Our Partners</h5>
					  <div class="owl-carousel owl-theme" data-plugin-carousel data-plugin-options='{ "dots": false, "autoplay": true, "autoplayTimeout": 3000, "loop": true, "margin": 10, "nav": true, "responsive": {"0":{"items":2 }, "600":{"items":3 }, "1000":{"items":6 } }  }'>
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

		<!-- Vendor -->
		<script src="../vendor/jquery/jquery.js"></script>
		<script src="../vendor/jquery-browser-mobile/jquery.browser.mobile.js"></script>
		<script src="../vendor/popper/umd/popper.min.js"></script>
		<script src="../vendor/bootstrap/js/bootstrap.js"></script>
		<script src="../vendor/common/common.js"></script>
		<script src="../vendor/nanoscroller/nanoscroller.js"></script>
		<script src="../vendor/magnific-popup/jquery.magnific-popup.js"></script>
		<script src="../vendor/jquery-placeholder/jquery-placeholder.js"></script>
		
		<!-- Specific Page Vendor -->
		<script src="../vendor/jquery-ui/jquery-ui.js"></script>
		<script src="../vendor/jqueryui-touch-punch/jqueryui-touch-punch.js"></script>
		<script src="../vendor/jquery-appear/jquery-appear.js"></script>
		<script src="../vendor/owl.carousel/owl.carousel.js"></script>
		
		<!-- Theme Base, Components and Settings -->
		<script src="../js/theme.js"></script>
        <script src="../js/examples/examples.modals.js"></script>
        <script src="../vendor/pnotify/pnotify.custom.js"></script>
		<script src="../vendor/liquid-meter/liquid.meter.js"></script>
		
		<!-- Theme Custom -->
		<!--<script src="js/custom.js"></script>-->
		<!--<script src="js/housie.js"></script> -->
		
		<!-- Theme Initialization Files -->
		<script src="../js/theme.init.js"></script>


		<script>
		
		//BUY_TICKET......
		function change_status(pid,school)
          {
			var er = "gerr" + pid;	 
			if (window.XMLHttpRequest)
				{// code for IE7+, Firefox, Chrome, Opera, Safari
					xmlhttp=new XMLHttpRequest();
				}
			else
				{// code for IE6, IE5
					xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
				}

			xmlhttp.onreadystatechange=function()
				{
					if (xmlhttp.readyState==1)
						{
						 document.getElementById(er).innerHTML="Updating..";
						}
					if (xmlhttp.readyState==4 && xmlhttp.status==200)
						{
						 document.getElementById(er).innerHTML=xmlhttp.responseText;
						}
				}
				
			xmlhttp.open("GET","change_ostatus.php" + "?pid=" + pid + "&school=" + school, true);
			xmlhttp.send();
			location.reload();

		}


	</script>

	<br><br>
    <?php include "footer.php"; ?>
	</body>
</html>