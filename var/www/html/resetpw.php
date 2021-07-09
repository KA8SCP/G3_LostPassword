<?php
 
/*  Password Reset for ICOM D-Star Callsign Registration
 *  Initial code by Jim Moen- K6JM 
 *		with code from tutorials at http://talkerscode.com/webtricks/password-reset-system-using-php.php
 *      and https://www.johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
 *
 * Should have a link to this script from G2/G3 Registration page, e.g. https://w6cx.dstargateway.org/Dstar.do
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('UTC');

require 'resetpwmail.php'; // Uses PHPMailer
?>

<?PHP
  // form handler
  function validateFeedbackForm($arr, $gw_callsign, $db_name, $db_user)
  {
	$errmsg = NULL;
	extract($arr); // key/value pairs from form
	
    if(!$callsign) {
		$errmsg = "Please enter Callsign<br>";
	} else {
    	$callsign = strtoupper(trim($callsign)); //remove spaces before & after, then upcase
    }
    $email = trim($email);
    if(!preg_match("/^\S+@\S+$/", $email)) {
      $errmsg = $errmsg . "Please enter a valid Email address<br>";
    }
    if (isset($errmsg)) return $errmsg;
	// Check that callsign is registered in this gateway's database    
    $conn = pg_pconnect("user=$db_user dbname=$db_name");
    
	if (!$conn) {
  		$errmsg = $errmsg . "Unable to open connection<br>";
	} else {
	  	if (!prep_stmt_exists("sql_1", $db_name, $db_user)) {
			$result = pg_prepare($conn, "sql_1", "SELECT e_mail, user_name FROM unsync_user_mng WHERE user_cs = $1");
		}
		$result = pg_execute($conn, "sql_1", array($callsign));
		if ((pg_num_rows($result) == 0)) {
  			$errmsg = $errmsg . "$callsign is not registered at this D-Star gateway<br>";
		} else {
			while ($row = pg_fetch_row($result)) {
				if ($row[0] != $email) {
					$errmsg = $errmsg . "Email address not on file for this callsign<br>";
				} else {
					$name = $row[1];
				}
		}
	  }
	}
 	if (isset($errmsg)) return $errmsg; //if any errors so far, redisplay form and errmsg
	
    // otherwise, create and save token, then send email and redirect to success page

	// Create tokens - code adapted from John Morris 
	//   https://www.johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
	$selector = bin2hex(mt_rand(10000000,99999999)); // returns 8-digit psuedo-random integer
	$token = bin2hex(mt_rand(10000000,99999999)); // returns another psuedo-random integer
	$tokenhash = md5(strval($token));

	$abs_url = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/";
	$url = sprintf('%sresetpw2.php?%s', $abs_url, http_build_query([
    	'selector' => $selector,
    	'validator' => $tokenhash]));
	$expires = time() + (60 * 30); // current time in seconds plus 30 minutes

	$conn = pg_pconnect("user=$db_user dbname=$db_name");
	if (!$conn) {
  		echo "Unable to open connection.\n";
  		exit;
	}
	
	// Delete any existing tokens for this user
  	if (!prep_stmt_exists("sql_2", $db_name, $db_user)) {
		$result = pg_prepare($conn, "sql_2", "DELETE FROM password_reset WHERE email = $1");
	}
	$result = pg_execute($conn, "sql_2", array("'$email'"));
	if (!$result) {
	  echo "Error in delete statement.\n";
	  exit;
	}
	  
	// Insert reset token into database
	if (!prep_stmt_exists("sql_3", $db_name, $db_user)) {
		$result = pg_prepare($conn, "sql_3", "INSERT INTO password_reset (email, callsign, selector, token, expires)
		VALUES($1, $2, $3, $4, $5)");
	}
	$result = pg_execute($conn, "sql_3", array("$email","$callsign","$selector","$token","$expires"));
	if (!$result) {
	  echo "Error in delete statement.\n";
	  exit;
	}
	  	
	// Send the email
	$to = $email;
	$bcc = "none";
	$subject = $gw_callsign . " D-Star Password Reset";
	$message = "$name - $callsign,<br><br>";
	$message .= "We received your password reset request. Here is the link to reset your password.<br><br>";
	$message .= "If you did not make this request, you can ignore this email.<br><br>";
	$message .= "<b><a href=" . $url . ">Click this link to reset your password.</a></b><br><br>";
	$message .= "Thanks!<br><br>";
	$message .= $gw_callsign . " D-Star Gateway Administrators";
		$sent = sendemail($to, $bcc, $subject, $message); //sendemail is function defined in resetpwmail.php	
    if (!$sent) {
		$errmsg = "Error sending email.  Please report this error to the Gateway Admin";
        return $errmsg;	
	}	
	$returl = "resetpwthx.php";
	header("Location: $returl");
	exit;
  }

  function prep_stmt_exists($stmt_name, $db_name, $db_user) {
	  $conn = pg_pconnect("user=$db_user dbname=$db_name");
	  if ($conn) {
		  $result = pg_query($conn, "SELECT name FROM pg_prepared_statements WHERE name = '$stmt_name'");
		  if (pg_num_rows($result) > 0) {
			  return true;
	  	  } else {
		  	  return false;
	      }	
  	   }
   }
	
// execution starts here ********************************************
$ini = parse_ini_file('/etc/resetpw.ini'); //config file for resetpw
// if config file wants ssl but user did not use it, redirect using https
if ($ini['gw_ssl'] == "yes" && $_SERVER["REQUEST_SCHEME"] != "https") {
	$returl = "https://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"];
	header("Location: $returl");
}
if(isset($_POST['requestpwreset'])) {
    // call form handler, if no errors, send email & return success msg
    $errorMsg = validateFeedbackForm($_POST, $ini['gw_callsign'], $ini['db_name'], $ini['db_user']);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>D-Star Registration Reset Password</title>
</head>
<body>

<p align="center">
<b style="color: rgb(0, 0, 255); font-family: arial; font-size: 24px;">
D-STAR Gateway System (<?php echo $ini['gw_callsign']; ?>)</font></b></p>
<br>
<p align="center">
<span style="font-family: arial; font-size: 24px; color: #0000FF; letter-spacing: normal"><b>
Password Reset</span></b></p>
<p align="center">
<br>
<font face="arial" size="2">
This page is <b>only</b> for users whose callsign is registered at this D-Star Gateway <br>
and who wish to reset their password.
<br><br>
Enter your registered callsign and the email address we have on file for you.<br>
Then click Submit and we'll email you a link to complete the reset.</p>
</font>
<font face="arial" color="#0000FF">
<b><font face="arial">
<form method="POST" action="<?PHP echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
  accept-charset="UTF-8">
<?PHP
  if(isset($errorMsg)) {
	echo "<p style=\"color: red; text-align: center;\">",$errorMsg,"</p>\n\n";
  }
?>
  <p align="center"><label>Call Sign<strong>*</strong><br>
  <input type="text" size="8" name="callsign" 
    value="<?PHP if(isset($_POST['callsign'])) echo htmlspecialchars($_POST['callsign']); ?>">
    </label></p>
  <p align="center"><label>Email Address<strong>*</strong><br>
  <input type="email" size="48" name="email" 
    value="<?PHP if(isset($_POST['email'])) echo htmlspecialchars($_POST['email']); ?>">
    </label></p>
  <p align="center"><input type="submit" name="requestpwreset" value="Submit"></p>
</form>
</font></b>
</body>
</html>

