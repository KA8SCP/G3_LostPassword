 <?php
 
/*  Password Reset for ICOM D-Star Callsign Registration
 *  Initial code by Jim Moen- K6JM 
 *		with code from tutorials at http://talkerscode.com/webtricks/password-reset-system-using-php.php
 *      and https://www.johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

  function prep_stmt_exists($stmt_name) {
	  $conn = pg_pconnect("user=dstar dbname=dstar_global");
	  if ($conn) {
		  $result = pg_query($conn, "SELECT name FROM pg_prepared_statements WHERE name = '$stmt_name'");
		  if (pg_num_rows($result) > 0) {
			  return true;
	  	  } else {
		  	  return false;
	      }	
  	   }
   }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>D-Star Registration Reset Password Accepted</title>
</head>
<body>
<?php
$ini = parse_ini_file('/etc/resetpw.ini'); //config file for resetpw
$db_name = $ini['db_name'];
$db_user = $ini['db_user'];
$callsign = $_POST["callsign"];
$newpassword = trim($_POST["newpassword"]);
if(isset($callsign, $newpassword)) {
	// Update password to password_reset table
	// which is monitored by cron-run script looking for the following update
	// When found, the script, running under root, will run /dstar/tools/dstarpasswd 
	// to change this callsign's password
	$conn = pg_pconnect("user=$db_user dbname=$db_name");
	if (!$conn) {
  		$msg = "Connection error in resetpw3. Please contact the gateway's Administrator.";
	} else {
		// Update password_reset table with requested Password for this callsign		    
		if (!prep_stmt_exists("sql_5")) {
			$result = pg_prepare($conn, "sql_5", "UPDATE password_reset SET password = $1 WHERE callsign = $2");
		}
			$result = pg_execute($conn, "sql_5", array("$newpassword","$callsign"));
		if (!$result) {
	  		$msg = "UPDATE error in resetpw3. Please contact the gateway Administrator.";
		} else {
			$msg = "Thank you.  Your password update is being processed.<br>";
			$msg .= "You will receive a confirmation email when update is complete.";
		}
	}
} else {
	$msg = "System error in resetpw3.  Please notify the gateway Administrator.";
}
?>
<p align="center">
<b style="color: rgb(0, 0, 255); font-family: arial; font-size: 24px;">
D-STAR Gateway System (<?php echo $ini['gw_callsign']; ?>)</font></b></p>
<br>
<p align="center">
<span style="font-family: arial; font-size: 16px; color: #0000FF; letter-spacing: normal"><b>
<?php
echo "<br><br>" . $msg;
?>
</span></b></p>
</body>
</html>