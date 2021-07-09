<?php

/*  Password Reset for ICOM D-Star Callsign Registration
 *  Initial code by Jim Moen- K6JM 
 *		with code from tutorials at http://talkerscode.com/webtricks/password-reset-system-using-php.php
 *      and https://www.johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
*/
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ini = parse_ini_file('/etc/resetpw.ini'); //config file for resetpw
$db_name = $ini['db_name'];
$db_user = $ini['db_user'];


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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>resetpw2</title>
</head>
<body>
<p align="center">
<b style="color: rgb(0, 0, 255); font-family: arial; font-size: 24px;">
D-STAR Gateway System (<?php echo $ini['gw_callsign']; ?>)</font></b></p>
<br>
<p align="center">
<span style="font-family: arial; font-size: 24px; color: #0000FF; letter-spacing: normal"><b>
Password Reset</span></b></p>
<?php
$linkselector = filter_input(INPUT_GET,'selector');
$linktoken = filter_input(INPUT_GET,'validator');
$linktokenhash = md5($linktoken);
$conn = pg_pconnect("user=$db_user dbname=$db_name");
if (!$conn) {
  echo "<br><br>resetpw3.php unable to open connection.<br><br>";
  exit;
}
$currtime = time();

if (!prep_stmt_exists("sql_4", $db_name, $db_user)) {
	$result = pg_prepare($conn, "sql_4", "SELECT email, callsign FROM password_reset
   WHERE selector = $1 AND md5(token) = $2 AND expires >= $3 ");
}
   $result = pg_execute($conn, "sql_4", array("$linkselector","$linktoken","$currtime"));    
   
if (!$result || pg_num_rows($result) != 1) {
  	$reset_url = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/resetpw.php";
	echo "<p align='center'>";
  	echo "Link in email is incorrect or expired.<br><br>Please <b><a href='$reset_url'>click here</a></b> and try again.</p> ";
    exit;
}
while ($row = pg_fetch_row($result)) {
	$email = $row[0];
	$callsign = $row[1];
}
$regisurl = $_SERVER["REQUEST_SCHEME"]."://" .$_SERVER["HTTP_HOST"]."/Dstar.do"; 
?>
<p align="center">
<form action="resetpw3.php" method="post" 
	onSubmit="var result = editNewPswd(this.newpassword.value); return result;">
    <input type="hidden" name="callsign" value="<?php echo $callsign; ?>">
    <p align="center"><label><strong>New Password for <?php echo $callsign; ?></strong><br>
    <input type="password" class="text" name="newpassword" placeholder="Enter your new password" required>
    </label>	
	<input type="submit" class="submit" value="Submit">
</form></p>
<p>
<SCRIPT language="JavaScript">
<!-- hide from old browsers

function editNewPswd(newpassword)
{
	var result = true;
	var firstchar = newpassword.substring(0,1);
	re = new RegExp("[a-zA-Z0-9]");
	if (newpassword == "")
	{
		alert("Please enter new password");
		result = false;		
	} else if (!re.test(firstchar))
		{
		alert("1st character of password should be a letter or number");
		result = false;
		}
	return result;
}
// end hiding javascript -->
</SCRIPT>
</body>
</html>