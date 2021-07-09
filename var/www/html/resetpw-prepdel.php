<?php
 
/*   Script to delete any existing prepared SQL statements/plans
 *   Used for G2/G3 Registration Page Logon Password Reset feature
 *   Normally never needed, but available just in case 
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>testprep</title>
</head>
<body>

<?php

echo "<p><b>W6CX resetpw-prepdel</b></p><br><br>";
$conn = pg_pconnect("user=dstar dbname=dstar_global");
if (!$conn) {
  echo "resetpw-prepdel.php: Unable to open connection.<br><br>";
  exit;
}

$result = pg_query($conn, "SELECT * FROM pg_prepared_statements");
if (!$result) {
  echo "resetpw-prepdel.php: Error in select statement.<br><br>";
  exit;
}
if (pg_num_rows($result) > 0) {
	while ($row = pg_fetch_row($result)) {
		if (substr($row[0],0,4) == "sql_") {
			$result1 = pg_query($conn, "DEALLOCATE " . $row[0]);
			if (!$result1) {
  				echo "resetpw-prepdel.php: Error in deallocate statement.<br><br>";
			} else {
				echo "resetpw-prepdel.php: Deallocated plan " . $row[0] . "<br><br>" ; 
			}
		} else {
			echo "resetpw-prepdel.php: No plans to Deallocate<br><br>";
		} 		
//  		echo "name: $row[0]  statement: $row[1]  prepare_time: $row[2] parameter_types: $row[3]  from_sql: $row[4]";
//  		echo "<br><br>";
	}
} 
 
?>
