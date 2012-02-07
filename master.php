<!DOCTYPE html>
<?php session_start(); 

include ("connect.php"); //contains the values of the variables $host $dbname $user and $password
$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}

include('header.php');
include('functions.php');



echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
        echo "<br />" . $_SESSION['msg'];
        $_SESSION['msg'] = "";
}


?>

<a href="manage.php">Return</a>

<?php




//Allow editing desk
if (isset($_REQUEST['DeskEdit']) ){ 
	$desk = $_REQUEST['desk']; //array $desk[$pid] = desk preference
	foreach($desk as $pid => $value) {//loop through each $pid
		$sql = "UPDATE info SET desk='" . $desk[$pid] . "' WHERE pid=" . intval($pid) . ";";
	//	echo $sql . "\n";
		pg_query($sql);
	}
	unset($_REQUEST['DeskEdit']); //Don't edit the desk again on a new page load.
}


//Get all the info
$hours = hours_array();
$info = info_array();


//-----------------
//Generate CSV
//-----------------
$file_location = "files/master.csv"; //Location to save the file to
$file = fopen($file_location,"w");
fwrite($file, ',,,');
foreach($days as $value) {
	fwrite($file, '"' . $value . '",');
}
fwrite($file, "\nOpening,Name,Desk,\n");
for($hour=8;$hour<=21;$hour++) { //Loop from 8:00 to 21:00
	foreach ($info as $pid=>$value) { 	//Pull rows from $info (to access pid name and desk
		fwrite($file, $hour . '-' . ($hour+1) . ','); //Write the shift label for each row
		fwrite($file, $info[$pid]['name'] . ',' . $desks[$info[$pid]['desk']] . ','); //Name and desk (using the value of desk from info as the index of desks)
		for($day=0;$day<=6;$day++) { //Loop Sunday (0) through Saturday (6)
			fwrite($file, $hours[intval($pid)][$day][$hour] . ',');
		}
		fwrite($file, "\n");
	}
}
fclose($file);
echo "<a href=" . $file_location . ">Download CSV</a>"; //Offers the link for download



//-----------------
//Create the tables
//-----------------

?><table> <?php /*this table holds both the left and right tables*/?>
<tr><td>
<table border=1> <?php /*This table holds the master list*/?>
	<tr> <th/><th/><th/><th/><?php foreach($days as $value){echo "<th>" . $value . "</th>";}?></tr>
	<tr> <td>Opening</td><td>Name</td><td/><td>Desk</td><td/><td/><td/><td/><td/><td/><td/></tr>
	<?php
	for($hour=8;$hour<=21;$hour++) {
		foreach ($info as $pid=>$value) {
			echo '<tr><td>' . $hour . '-' . ($hour+1) . '</td>';
			echo '<td class="pos' . $info[$pid]['position']  . '">' . $info[$pid]['name'] . '</td>' . '<td>';
			echo $info[$pid]['srank'] . '</td>' . '<td>' . $desks[$info[$pid]['desk']] . '</td>';
			for($day=0;$day<=6;$day++) {
				echo '<td class="rank' . $hours[$pid][$day][$hour] . '">' . $hours[$pid][$day][$hour] . '</td>';
			}
			echo '</tr>';
		}

		echo '<tr/><tr/>';

	}?>
</tr></table></td> <?php /*Closes the availibility table into the left cell*/?>


<td valign="top"><table border=1> <?php /*Second table. Holds user and desk info*/?>
<form action="?" method="post"> <?php /*Form for editing desk placement*/
	foreach ($info as $pid=>$value) {
		echo '<tr><td class="pos' . $info[$pid]['position'] . '">' . $info[$pid]['name'] ?> </td>
		<td><select name=<?php echo '"desk[' . $pid . ']"'?>> <br/>
			<option value="" <?php if($info[$pid]['desk']=="") echo 'selected'?>></option>
			<option value="d" <?php if($info[$pid]['desk']=="d") echo 'selected'?>>DHH</option>
			<option value="w" <?php if($info[$pid]['desk']=="w") echo 'selected'?>>Wads</option>
			<option value="m" <?php if($info[$pid]['desk']=="m") echo 'selected'?>>McNair</option>
		</select>


		</td></tr>
	<?php } ?>
</td></tr></table> <?php /*End table with desk and user info*/?>
<input type = "submit" name="DeskEdit" value="Submit">
</form>
</table><?php //End master table



include('footer.php');
?>
