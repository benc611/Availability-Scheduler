<!DOCTYPE html>
<?php session_start();?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body>
<?php

include 'connect.php'; //contains the values of the variables $host $dbname $user and $password

global $days, $desks;
$days = array(0 => "Sunday" , 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday"); //Translates day # to name
$desks = array(" " => " ", "d" => "DHH", "w" => "Wads", "m" => "McNair"); //Translates single letter to Desk name

$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}

?>

<form action="manage.php" method="post">
	<select name="option">
		<option value="ListUsers">List Users</option>
		<option value="score">Generate Availibility Ranks</option>
		<option value="GenerateAvail">Generate Master Availability Sheet</option>
		<option value="GenerateCSV">Export to CSV</option>
		<option value="place">Place at Desks</option>
		<option value="schedule">Schedule Deskies</option>
		<option value="ClearDB">Clear Database</option>
		<option value="test">Test purposes only</option>
	</select>
	<br /><input type = "submit" value="Submit"/>
</form>
<?php

if (isset($_REQUEST['option']) ) {
	$_SESSION['option'] = $_REQUEST['option'];
}
if ( isset($_SESSION['option']) ) {
	switch ($_SESSION['option'])
	{
		case "test":
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "ClearDB": //Clears the database?>
			Are you sure you want to clear the ENTIRE database? This means EVERY users data will be gone?<br/>
			This is serious business. You're going to clear all user info and hours preferences. Are you positive?<br/><br/>
			Totally positive?<br/><br/><br/><br/>
			Okay...
			
			<form action="manage.php" method="get">
			<input type = "submit" name="option" value="Clear">
			</form> <?php

			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "Clear": //Confirmed clear
			pg_query('DELETE FROM hours; DELETE FROM info');
			echo "It's all gone now. It's kind of lonely in here...<br/>";
			unset($_POST['Button2']);
			break;

		case "ListUsers": //Lists all info in the info table
				$sql = 'SELECT * from info;';
				print_select($sql);
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "GenerateAvail":
			if (isset($_REQUEST['DeskEdit']) ){ //Need a better way to implement this than posting back and checking
				$desk = $_REQUEST['desk']; //array $desk[$pid] = desk preference
				foreach($desk as $pid => $value) {//loop through each $pid
					$sql = "UPDATE info SET desk='" . $desk[$pid] . "' WHERE pid=" . intval($pid) . ";";
				//	echo $sql . "\n";
					pg_query($sql);
				}
				unset($_REQUEST['DeskEdit']); //Don't edit the desk again on a new page load.
			}
			hours_array();
			info_array(); 
			desk_sort(); //Sorts the array $info by desk. Need a new sort method

			
			//Create the tables
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
							echo '<td class="rank' . $hours[intval($pid)][$day][$hour] . '">' . $hours[intval($pid)][$day][$hour] . '</td>';
						}
						echo '</tr>';
					}
					echo '<tr/><tr/>';

				}?>
			</tr></table></td> <?php /*Closes the availibility table into the left cell*/?>


			<td valign="top"><table border=1> <?php /*Second table. Holds user and desk info*/?>
			<form action="manage.php" method="post"> <?php /*Form for editing desk placement*/
				foreach ($info as $pid=>$value) {
					echo '<tr><td class="pos' . $info[$pid]['position']  . '">' . $info[$pid]['name'] ?> </td>
                                        <td><select name=<?php echo '"desk[' . $pid  . ']"'?>> <br/>
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
			</table><?php /*End master table*/?>
			<?php
			break;

		case "GenerateCSV": //Similar functionality to Master. Outputs to a csv file and offers a link
			//Uses a new line to seperate lines, a comma to seperate cells, " " for text
			hours_array();
			info_array();
			desk_sort();

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
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;


		case "score":
			hours_array(); //Populate $hours array with info from hours table $hours[pid][day][hour]
			average_rank($hours); //Gather the average rank for each user and store it in the info table
			echo "<br/>Scheduling Scores have been updated.<br/>";
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "place":
			//hours_info_array(); //Deprecated. Check that everything works correctly first, before removing..
			hours_array();
			info_array();
			desk_place($info);
			echo "<br/>Deskies have been placed.<br/>";
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "schedule":
			?>
			<form action="manage.php" method="get">
			<input type = "submit" name="option" value="Schedule Wads">
			<input type = "submit" name="option" value="Schedule DHH">
			<input type = "submit" name="option" value="Schedule McNair">
			</form> 
			<?php
		break;
		
		case "Schedule Wads":
			schedule_desk("w");
			break;
		case "Schedule McNair":
			schedule_desk("m");
			break;
		case "Schedule DHH":
			schedule_desk("d");
			break;
		default: echo "Option Error";
	}

}

function print_select($sql) //Fix this up a bit soon.. foreach and whatnot
{
	$resource = pg_query($sql);
	echo "<table border=1>\n<tr>";

	//Print headers
	for ($field=0; $field<pg_num_fields($resource); $field++)
		echo "	<th>" . pg_field_name($resource, $field) . "</th>\n";

	for ($row=0; $row<pg_num_rows($resource); $row++){ //Loops per row from database
		echo "</tr>\n<tr>\n";
		$set = pg_fetch_array($resource); //Store next row into array $set
		for ($field=0; $field<pg_num_fields($resource); $field++) //Loops per column per row
			echo "	<td>" . $set[$field] . "</td>\n";
	}

}

//Pass the the array holding ranks, with the format pid|day|hour|rank
//Adds up the total ranks and divides by the total number of shifts, to give an average rank
function average_rank($hours)
{
	foreach($hours as $pid=>$array1){
		$totalrank = 0;
		$shifts = 0;
		foreach($array1 as $day=>$array2){
			foreach($array2 as $hour=>$rank){
				$totalrank += $rank;
				$shifts++;
			}
		}
		$average = intval(100*$totalrank/$shifts);
		$sql = "UPDATE info SET srank ='" . $average . "' WHERE pid='" . $pid . "'";
		pg_query($sql);
	}
}


//Deprecated. Use hours_array() and info_array() instead.
function hours_info_array()
{
	global $hours;
	global $info;
	$sql = 'SELECT * from info';
	$resource = pg_query($sql);
	//This creates two arrays $info[pid][name or desk] and $hours[pid][day][hour]
	for ($row=pg_num_rows($resource);$row>0;$row--) { //Loops each row of info (each PID)
		$set = pg_fetch_array($resource); //Stores next row to $set (temp array)
		$pid = $set['pid'];	//Just because it's annoying to type the array variable name and key
		$info[$pid . " "]['name'] = $set['name'];
		$info[$pid . " "]['desk'] = $set['desk'];
		$info[$pid . " "]['position']=$set['position'];
		$info[$pid . " "]['srank'] = $set['srank'];

		for ($hour=8; $hour<=21; $hour++) {	//Loops through all hours
			for ($day=0;$day<=6;$day++) {
				$sql = 'SELECT rank FROM hours WHERE pid=' . $pid . ' AND day=' . $day . ' AND hour=' . $hour . ';';
				$resource2 = pg_query($sql);
				$ranks = pg_fetch_array($resource2);
				$hours[$pid][$day][$hour] = $ranks['rank'];
			}
		}
	}

}

//Generates an array $hours with the format hours[pid][day][hour] = rank
function hours_array()
{
	global $hours;  //Make sure the array is available globally
	$sql = "SELECT DISTINCT pid FROM info"; //Get the list of PIDs in the info table
	$p_array = pg_fetch_all_columns(pg_query($sql), 0);
	foreach ($p_array as $pid){
		for ($hour=8; $hour<=21; $hour++) {	//Loops through all hours
			for ($day=0;$day<=6;$day++) { //And through each day
				$sql = 'SELECT rank FROM hours WHERE pid=' . $pid . ' AND day=' . $day . ' AND hour=' . $hour . ';';
				$ranks = pg_fetch_array(pg_query($sql));
				$hours[$pid][$day][$hour] = $ranks['rank'];
			}
		}
	}
}

//Generates a table with the format info[pid][info column] = value
//ie info[32][desk] = d
function info_array() 
{
	global $info;
	$sql = 'SELECT * from info';
	$resource = pg_query($sql);
	for ($row=pg_num_rows($resource);$row>0;$row--){ //Loops each row of info (each PID)
		$set = pg_fetch_array($resource);
		$pid = $set['pid'];	//Makes for nicer looking code in the next few lines
		$info[$pid . " "]['name'] = $set['name'];
		$info[$pid . " "]['desk'] = $set['desk'];
		$info[$pid . " "]['position']=$set['position'];
		$info[$pid . " "]['srank'] = $set['srank'];
		//If other info columns are desired, add them here like the above lines
	}
}


//Sorts by desk
function desk_sort()
{
	global $info;

	//Pull out array $desk_ar from $info. Column of desks.
	foreach ($info as $key => $row) {
		$desk_ar[$key] = $row['desk'];
	}

	array_multisort($desk_ar, $info);
}

//Places deskies at a desk based on their average rank starting high to low
//Loops placing Wads => McNair => DHH
function desk_place($info)
{
	//Pull out array $desk_ar from $info
	foreach ($info as $key => $row) {
		$srank_ar[$key] = $row['srank'];
	}
	array_multisort($srank_ar, SORT_DESC, $info);

	$count = 3;
	$desks = array("w", "m", "d");
	reset($info);
	foreach($info as $pid=>$row){
		if($info[$pid]['position'] == "DR"){
			echo $pid . "<br/>";
			$sql = "UPDATE info SET desk ='" . $desks[$count%3] . "' WHERE pid='" . intval($pid) . "'";
			pg_query($sql);
			$count++;
		}
	}
	echo "<br/>Placed at desks.<br/>";
}



function schedule_desk($desk) //Takes input $desk, for which desk to schedule, and attempts to generate a schedule
{
	//--------------
	//Select only deskies at this desk
	//--------------
	$sql = "SELECT pid, name, desired, srank, dub FROM info WHERE desk='" . $desk ."';";
	$resource = pg_fetch_all(pg_query($sql));
	foreach($resource as $set) {	//Pull one row at a time from the table
		//$info[$pid]['type'] = value of type
		$info[ $set['pid'] ]['srank'] = $set['srank']; //Person's average rank
		$info[ $set['pid'] ]['name'] = $set['name']; //User's full name
		$info[ $set['pid'] ]['desired'] = $set['desired']; //Hours to give this person
		$info[ $set['pid'] ]['count'] = 0; //Hours given to person
		
		//Sets a number for the day equal to willingness to double shifts
		//Rank for the day is decreased by one for each shift assigned to that day
		//A person who said yes will still have a .5 multiplier on rank. Maybe .25, No will have 0 after one shift
		switch($set['dub'])
		{
			case "Yes  ":
				$dub = 1.5; //Can take two shifts in one day
				break;
			case "Maybe":
				$dub = 1.25; //Willing to take two.
				break;
			case "No   ":
				$dub = 1; //Won't take more than 1
				break;
			default:
				echo "Scheduler error";
				break;
		}
		for($day=0;$day<=6;$day++)
		{
			$info[ $set['pid'] ][$day] = $dub;
		}
	}

	//--------------
	//Rank shifts by difficulty to cover
	//--------------
	for($day=0;$day<=6;$day++){
//		for ($hour=8;$hour<=21;$hour++){
		for ($hour=8;$hour<=20;$hour+=2){ //2 hour blocks
			$totalrank = 0;
			$shifts = 0;
			foreach ($info as $pid => $value){ //Each $pid of deskie in this desk
				$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . $hour . ";";
				$rank1 = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid

				//2 hour blocks
				$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . ($hour+1) . ";";
				$rank2 = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid
				if (intval($rank1) < intval($rank2)) //Takes the lowest of the two ranks. Intval makes c and o equal zero
					$rank = $rank1;
				else
					$rank = $rank2;
			
				//Totals up rank values for this shift
				$totalrank += $rank;
				$shifts++;	//Increments number of values for this shift
			}
			$average = intval(100*$totalrank/$shifts); //Averages, and ouputs a rank 0=>300. 300 being the easiest to cover.
			$dayhour = $day*100 + $hour; //Allows me to use it as an index and sort by rank.. Hack, but I don't know a better non-complicated way
			$difficulty[$dayhour] = $average;
		}
	}
	flush();
	asort($difficulty); //Sort from lowest rank to highest
	//$dayhour/100 = $day  $dayhour%100 = $hour

	//--------------
	//Start with the hardest shift, and start placing
	//--------------
	foreach($difficulty as $dayhour => $rank) //Loop through each shift
	{
		$day = intval($dayhour/100); //Get the first digit of dayhour
		$hour = $dayhour%100;	//Get the second and third digit of dayhour
		
		//--------------
		//Rank person's ability to cover
		//--------------
		foreach ($info as $pid => $value){ //Each $pid of deskie in this desk
			$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . $hour . ";";
			$shift_rank1 = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid

			//2 hour blocks
			$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . ($hour+1) . ";";
			$shift_rank2 = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid. 
			
//			echo "<br/>" . $dayhour . " " . $info[$pid]['name'] . " " . $shift_rank1 . " " . $shift_rank2 . "<br/>";

			if (intval($shift_rank1) < intval($shift_rank2)) //Takes the lowest of the two ranks. Intval makes c and o equal zero
				$shift_rank = $shift_rank1;
			else
				$shift_rank = $shift_rank2;			

			//Rating Logic. Rates a person's suitability for the shift
			$rating = 300*$shift_rank; //Makes the shift rank a three digit number, like srank, and weighted to only have class or org result negative
			$rating = $rating - $info[$pid]['srank']; //weighted shift rank - average. Higher is best choice. Negative is do not rank
			$rating = ($info[$pid]['desired']-$info[$pid]['count'])*$rating; //Desired, minus given. Remaining desired factors into rating.
			$rating = $rating*$info[$pid][$day];
			$ranking[$pid] = $rating;
		}

		//--------------
		//Place the person
		//--------------
		arsort($ranking);
		//$ranking is sorted best rating to worst. Use person with best rating for that shift.

		reset($ranking); //Put pointer at beginning of the array
		$pid = key($ranking);	//Get the key of the first element (the pid of the highest ranked person)

		global $days; //translates day number to word

		if ($ranking[$pid] > 0){	//If the best result isn't less than zero, give it to them
			$schedule[$day][$hour] = $pid;
			$schedule[$day][($hour+1)] = $pid; //Two hour blocks
//			echo "Yay. We covered day:" . $day . " hour:" . $hour . " with pid:" . $pid . ". Isn't that swell?";
			$info[$pid]['count']+=2; //Increase count of given hours by 2
			$info[$pid][$day]--; //Decrement dub counter
		}

		else{
			echo "Couldn't cover " . $days[$day] . " at " . $hour . ":00<br/>";?>
			<div id="shift_ranks">
				<table class="table1">
				<?php
				echo "\n<tr><td>Ranks</td><td>" . $days[$day] . " " . $hour . "</td></tr>\n";
				foreach($ranking as $pid => $value)
				{
					$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . $hour . ";";
					$person_rank =  pg_fetch_result(pg_query($sql),0,0);
					echo "<tr><td>" . $info[$pid]['name'] .  "</td><td class=\"center\">" . $person_rank . "</td></tr>\n";
				}?>
				</table>
				<table class="table2">
				<?php
				echo "\n<tr><td>Ranks</td><td>" . $days[$day] . " " . ($hour+1) . "</td></tr>\n";
				foreach($ranking as $pid => $value)
				{
					$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . ($hour+1) . ";";
					$person_rank =  pg_fetch_result(pg_query($sql),0,0);
					echo "<tr><td>" . $info[$pid]['name'] .  "</td><td class=\"center\">" . $person_rank . "</td></tr>\n";
				}?>
				</table>
			</div>
		<?php
		}

//		print_r($ranking); echo "<br/> \n";

		unset($ranking); //Clear $ranking
	}

	//--------------
	//Now display all of the data
	//--------------	
	//Create a shift not covered persona
	$info[0]['name'] = NULL; //If $pid is zero, the name is nobody.
	$info[0]['count'] = NULL;
	$info[0]['desired'] = NULL;
	//So we have this array, $schedule[$day][$hour] = pid
	//We also have this other array, $info[pid]['name'] = full name
	echo "\nColor of the cell is from the person's preference for that shift. Green, Yellow, Red are 3,2,1 respectively\n";
	echo "<table border=1>\n";
	echo"	<tr><td>Opening</td>";
	foreach ($days as $value)
	{
		echo "<th>" . $value . "</th>";
	}
	echo "</tr>";
	for($hour=8;$hour<=21;$hour++){
		echo "<tr><td>" . $hour . "</td>";
		for($day=0;$day<=6;$day++){
			if ( isset($schedule[$day][$hour]) ){
				$sql = "SELECT rank FROM hours WHERE pid=" . $schedule[$day][$hour] . " AND day=" . $day . " AND hour=" . $hour . ";";
				$rank = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid
				echo '<td class="rank' . $rank . '">' . $info[$schedule[$day][$hour]]['name'] . "</td>";
			}
			else
				echo "<td>----</td>";
		}
		echo "</tr>";
	}
	echo "</table>\n<table>\n<tr><th>Name</th><th>Shifts Given</th><th>Shifts Desired</th></tr>\n";
	foreach($info as $array)
	{
			echo "<tr><td>" . $array['name'] . "</td><td class=\"center\">" .  $array['count'] . "</td><td class=\"center\">" . $array['desired'] . "</td></tr>\n";
	}
	

}//end function

?>
</body>
</html>
