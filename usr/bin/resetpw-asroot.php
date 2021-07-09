 <?php
 
/*  This script invokes the D-Star utility dstarpasswd to update the encrypted password 
 *  It should be run repetitively (recommend to run every second) by cron under root
 *
 *  Password Reset for ICOM D-Star Callsign Registration
 *  Initial code by Jim Moen- K6JM 
 *		with code from tutorials at http://talkerscode.com/webtricks/password-reset-system-using-php.php
 *      and https://www.johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

$ini = parse_ini_file('/etc/resetpw.ini'); //config file for resetpw
$db_name = $ini['db_name'];
$db_user = $ini['db_user'];
$gw_callsign = $ini['gw_callsign'];
$bcc = $ini['mail_bcc'];
require '/var/www/html/resetpwmail.php'; // Uses PHPMailer, contains function sendemail

$conn = pg_pconnect("user=$db_user dbname=$db_name");
if (!$conn) {
  echo "resetpw-asroot.php: Unable to open connection.<br><br>";
  exit;
}

$result = pg_query($conn, "SELECT * FROM password_reset WHERE password IS NOT NULL");
if (!$result) {
  echo "resetpw-asroot.php: Error in select statement.<br><br>";
  exit;
}

if (pg_num_rows($result) > 0) {
	while ($row = pg_fetch_row($result)) {
		$callsign = $row[2];
		$newpassword = $row[5];
		$email = $row[1];
		$cmd = "/dstar/tools/dstarpasswd " . $callsign . " " . $newpassword;
		exec ($cmd, $output, $return);
		if ($return == 0) {
			$log  = date("m/d/y G.i:s") . " (UTC) Password successfully changed for Callsign: ".$callsign . PHP_EOL;
			file_put_contents('/var/log/resetdstarpw.txt', $log, FILE_APPEND);
		} else {
			$log = date("m/d/y G.i:s") ."(UTC) resetpw-asroot: Problem changing password for " . $callsign . "\n" . print_r($output, true) . PHP_EOL;
			file_put_contents('/var/log/resetdstarpw.txt', $log, FILE_APPEND);
		}

	$result = pg_query($conn, "DELETE FROM password_reset WHERE email = '$email'");
	if (!$result) {
		$log = date("m/d/y G.i:s") ."(UTC) resetpw-asroot: Error in delete statement" . PHP_EOL;
		file_put_contents('/var/log/resetdstarpw.txt', $log, FILE_APPEND);
		exit;
	}

	$subject = "Your D-Star password has been reset on the $gw_callsign Gateway";
	$message = "Congratulations,<br><br>";
	$message .= $log;
	$message .= "<br><br>Thanks!<br><br>";
	$message .= "$gw_callsign D-Star Gateway Administrators";

	$sent = sendemail($email, $bcc, $subject, $message);
    if (!$sent) {
		$log = "resetpw-asroot: Error sending email.";
        file_put_contents('/var/log/resetdstarpw.txt', $log, FILE_APPEND);	
	}	
  }
  // Delete any expired rows
  $now = time();
  $result = pg_query($conn, "DELETE FROM password_reset WHERE expires < '$now'");
	if (!$result) {
		$log = date("m/d/y G.i:s") ."(UTC) resetpw-asroot: Error in delete statement" .PHP_EOL;
		file_put_contents('/var/log/resetdstarpw.txt', $log, FILE_APPEND);
	}
}
?>