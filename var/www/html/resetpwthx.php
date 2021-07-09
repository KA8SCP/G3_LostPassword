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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>D-Star Registration Reset Password Link Sent</title>
</head>
<body>

<p align="center">
<b style="color: rgb(0, 0, 255); font-family: arial; font-size: 24px;">
D-STAR Gateway System (<?php echo $ini['gw_callsign']; ?>)</font></b></p>
<br>
<p align="center">
<span style="font-family: arial; font-size: 16px; color: #0000FF; letter-spacing: normal"><b>
Thank you. Email with link has been sent.  To reset your password, please click on the link.</span></b></p>
</body>
</html>