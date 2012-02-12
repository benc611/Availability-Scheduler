<!DOCTYPE html>
<?php session_start(); 

include 'connect.php'; //contains the values of the variables $host $dbname $user and $password
$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}

if ( isset($_REQUEST['action']) ) {
	switch ($_REQUEST['action'])
	{
		case "clear": //Confirmed clear
			//pg_query('DELETE FROM hours; DELETE FROM info');
			$_SESSION['msg'] .= "There you go, it's all gone. I hope you're happy (Just kidding. The line is commented out right now)";
			header('Location: list.php');
			exit;
			break;
	}
}


include('header.php');
include('functions.php');


echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
        echo "<br />" . $_SESSION['msg'];
        $_SESSION['msg'] = "";
}



?>



Are you sure you want to clear the ENTIRE database? This means EVERY users data will be gone?<br/>
This is serious business. You're going to clear all user info and hours preferences. Are you positive?<br/><br/>
Totally positive?<br/><br/><br/><br/>
Okay...
<a href="?action=clear">Clear</a>


<?php


include('footer.php');
?>
