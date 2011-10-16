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

if ( !isset($_REQUEST['username'], $_REQUEST['name']) || $_REQUEST['name']=="" || $_REQUEST['username']=="" ) 
	echo 'An error has occured. Please <a href=index.php>return</a> to the previous page and try again. Error code: SE1';
else if ( !($_REQUEST['position'] == "DR" || $_REQUEST['position'] == "AC") )
	echo 'An error has occured. Please <a href=index.php>return</a> to the previous page and try again. Error code: SE2';
else if( !($_REQUEST['desired']<14 && $_REQUEST['desired']>0) )
	echo 'An error has occured. Please <a href=index.php>return</a> to the previous page and try again. Error code: SE3';
else if( !($_REQUEST['splitshift']=="Yes" || $_REQUEST['splitshift']=="Maybe" || $_REQUEST['splitshift']=="No") )
	echo 'An error has occured. Please <a href=index.php>return</a> to the previous page and try again. Error code: SE4';
else if( !($_REQUEST['dubshift']=="Yes" || $_REQUEST['dubshift']=="Maybe" || $_REQUEST['dubshift']=="No") )
	echo 'An error has occured. Please <a href=index.php>return</a> to the previous page and try again. Error code: SE5';
else{ //everything validates
$username = pg_escape_string($_REQUEST['username']); 
$name = pg_escape_string($_REQUEST['name']);
$position = $_REQUEST['position']; //No escape due to constrictions on input validated above
$desired = $_REQUEST['desired']; //No escape due to constrictions on input validated above
$splitshift = $_REQUEST['splitshift']; //No escape due to constrictions on input validated above
$dubshift = $_REQUEST['dubshift']; //No escape due to constrictions on input validated above
$hours = $_REQUEST['hours']; //Tested if equal to values when updating/creating entries later
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
