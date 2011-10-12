<!DOCTYPE html>
<?php session_start(); ?>
<html>
<body>

<?php

include 'connect.php'; //contains the values of the bariables $host $dbname $user and $password

$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}

//destroy variables at end to disallow refresh

if (!isset($_REQUEST['username'], $_REQUEST['name'], $_REQUEST['desired']) || $_REQUEST['name']=="" || $_REQUEST['username']=="") 
echo 'An error has occured. Please <a href=index.php>return</a> to the previous page and try again.';

else{
$username = pg_escape_string($_REQUEST['username']);
$name = pg_escape_string($_REQUEST['name']);
$position = pg_escape_string($_REQUEST['position']);
$desired = pg_escape_string($_REQUEST['desired']);
$splitshift = pg_escape_string($_REQUEST['splitshift']);
$dubshift = pg_escape_string($_REQUEST['dubshift']);
$hours = $_REQUEST['hours'];
$submit = $_REQUEST['submit'];
/*Just for testing
?>
This is the information you have submitted<br /><br />
Username: <?php echo $username?> <br />
Full name: <?php echo $name?><br />
Double Shift:<?php echo $dubshift?><br />
Split Shift: <?php echo $splitshift?><br />
Position: <?php echo $position?> <br />
Shifts Desired:<?php echo $desired?><br />
Submit: <?php echo $submit ?><br />
<?php
*/




switch($submit)
{
	case "Create": {
		//Add user
		$sql = "INSERT INTO info (username, name, position, desired, split, dub, desk) VALUES ('" . $username . "','" . $name . "','" . $position . "','" . $desired . "','" . $splitshift . "','" . $dubshift . "',' ') RETURNING pid;";
		$resource = pg_query($sql);
		$array = pg_fetch_array($resource);
		$id = $array['pid'];


		//Store hours
		//pid | day | hour | rank
		foreach( $hours as $day => $harray){
		        foreach( $harray as $hour => $rank) {
				$rank = strtolower(pg_escape_string($rank));
				if ( !(($rank<=3 && $rank>=1) || $rank == "x" || $rank == "c" || $rank == "o")){
					$rank = "x";
				}
				$sql = "INSERT INTO hours VALUES ('" . $id . "','" . $day . "','" . $hour . "','" . $rank . "');";
		                pg_query($sql);
		        }
		}
		echo '<br/>Added user<br/>';
	}//end case
	break;
	case "Submit": {
		//Update user

		$pid = $_SESSION['pid'];

		$sql = "UPDATE info SET username='" . $username . "', name='" . $name . "', position= '" . $position . "', desired='" . $desired . "', split='" . $splitshift . "', dub='" . $dubshift . "'";
		$sql.= "WHERE pid=" . $pid . ";";
		pg_query($sql);


		//Update hours
		foreach( $hours as $day => $harray){
			foreach( $harray as $hour => $rank) {
				$rank = strtolower(pg_escape_string($rank));
				if ( !(($rank<=3 && $rank>=1) || $rank == "x" || $rank == "c" || $rank == "o")){
					$rank = "x";
				}
				//One day hour and rank combo is updated on each loop
				$sql = "UPDATE hours SET rank='" . $rank  . "' WHERE pid=" . $pid . " AND day =" . $day . " AND hour =" . $hour . ";";
				pg_query($sql);
			}
		}
		echo '<br/>Updated user<br/>';
	}//end case
	break;
	default: echo "Option error";

}//end switch for edit or add
	pg_close($con);
	echo '<a href="index.php">Return</a>';
}//end else. variables were set properly

unset($_REQUEST['username']); //Just so it can't be refreshed.

?>
</body>
</html>
