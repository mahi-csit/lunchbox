<?php

session_start();
if (isset($_POST['pin']) && !empty($_POST['pin']))
{

   $pin=$_POST['pin'];

   include "connect.php";  
  
   $result=mysqli_query($conn, "select * from team where mobile='$pin'");
   $lrow=mysqli_fetch_array($result);

   if(mysqli_num_rows($result)>0)
	 {
       $_SESSION['partner']=$lrow[0];    	
       $_SESSION['eid']=$lrow[0];    	
       $_SESSION['name']=$lrow[1];    	
       $_SESSION['mobile']=$lrow[2];    	      
	 }	
  else
   {
    //clear session from globals
    $_SESSION = array();
    //clear session from disk
    session_destroy();
    header("Location:index.php?pwderror");
    exit;
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
