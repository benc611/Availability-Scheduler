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
		case "delete": 
			$sql = "DELETE FROM info WHERE pid = '" . $_REQUEST['pid'] . "';";
			$sql .= "DELETE FROM hours WHERE pid = '" . $_REQUEST['pid'] . "';";
			pg_query($sql);
//			echo $sql;
			$_SESSION['msg'] .= "Deleted user";
			header('Location: manage.php?option=' . $_REQUEST['option']);
			exit;
			break;

		case "clear": //Confirmed clear
			//pg_query('DELETE FROM hours; DELETE FROM info');
			$_SESSION['msg'] .= "There you go, it's all gone. I hope you're happy";
			header('Location: manage.php?option=' . $_REQUEST['option']);
			exit;
			break;
	}
}



include('header.php');

?>


<?php


global $days, $desks;
$days = array(0 => "Sunday" , 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday"); //Translates day # to name
$desks = array(" " => " ", "d" => "DHH", "w" => "Wads", "m" => "McNair"); //Translates single letter to Desk name


echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
	echo "<br />" . $_SESSION['msg'];
	$_SESSION['msg'] = "";
}

?>

<form action="?" method="get">
	<p>
		<select name="option" class="input-text">
			<option value="ListUsers">List Users</option>
			<option value="score">Generate Availibility Ranks</option>
			<option value="GenerateAvail">Generate Master Availability Sheet</option>
			<option value="place">Place at Desks</option>
			<option value="schedule">Schedule Deskies</option>
			<option value="ClearDB">Clear Database</option>
			<option value="ByHour">Availability by hour</option>
			<option value="test">Test purposes only</option>
		</select>
	<input type = "submit" value="Submit" class="input-submit"/>
	</p>
</form>
<?php

if (isset($_REQUEST['option']) ) {
	$_SESSION['option'] = $_REQUEST['option'];
}
if ( isset($_SESSION['option']) ) {
	switch ($_SESSION['option'])
	{
		case "test":
			$sql = "SELECT * from info ORDER BY desk, name";
			$resource = pg_query($sql);
			while (	$array = pg_fetch_object($resource) ) {
				echo $array->pid . "<br/>";
			}
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "ByHour": ?>
			Select the start and end time, and this will display the people who are available to work, sorted by most available. Also, it will include people who can only work part of the shift
			<br>
			Also, we're going to make this look better, and offer filter by options..
			<?php
			$minhour = 14;
			$maxhour = 16;
			$day = 2;
			$sql = "SELECT pid, rank, hour FROM hours WHERE hour>=" . $minhour . " AND hour<=" . $maxhour . " AND day=" . $day . "ORDER BY hour,pid;";
			$resource = pg_query($sql);
			$info = info_array(); //replace this with an SQL join
			$lasthour = 0; //initialize last hour, so we don't get an isset issue
			echo "<table><tr>"; //...Ugly. Fix.
			while ($object= pg_fetch_object($resource) ) {
				if ($object->hour != $lasthour) //A hack to check if the hour has changed
					echo "\n</tr><tr><th>" . $object->hour . "</th>";
				echo "<td class=rank" . $object->rank  . ">" . $info[$object->pid]['name']. $object->hour .  "</td>";
				$lasthour = $object->hour; //part of the hour change hack
			}
			echo "</table></tr>";
			break;

		case "ClearDB": /*Clears the database*/?>
			Are you sure you want to clear the ENTIRE database? This means EVERY users data will be gone?<br/>
			This is serious business. You're going to clear all user info and hours preferences. Are you positive?<br/><br/>
			Totally positive?<br/><br/><br/><br/>
			Okay...
			<a href="?action=clear&option=ListUsers">Clear</a>
			<?php

			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "ListUsers": //Lists all info in the info table
				$sql = 'SELECT * from info;';
				$resource = pg_fetch_all(pg_query($sql));

				//Print headers
				echo "<table border='1'><tr>";
				$keys = array_keys($resource[0]);
				foreach ($keys as $value){
					echo "<th>" . $value . "</th>";
				}
				echo "</tr>\n";

				//Print data
				foreach($resource as $array){
					echo "<tr>";
					foreach($array as $value){
						echo "<td>" . $value . "</td>";
					}
					echo "<td>"; 
					?>
					<a class="ico-delete"href="?action=delete&option=ListUsers&pid=<?php echo $array['pid'];?>">Delete</a>

					<?php
					echo "</tr>\n";
				}
				echo "</table>\n";

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
			<form action="manage.php" method="post"> <?php /*Form for editing desk placement*/
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
			break;

		case "score":
			average_rank(); //Gather the average rank for each user and store it in the info table
			echo "<br/><h4>Scheduling Scores have been updated.</h4><br/>";
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "place":
			average_rank();
			$info = info_array();
			desk_place($info);
			echo "<br/><h4>Deskies have been placed.</h4><br/>";
			unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action
			break;

		case "schedule":
			average_rank();			
			?>
			<form action="manage.php" method="get">
			<input type = "submit" name="option" value="Schedule Wads" class="input-submit">
			<input type = "submit" name="option" value="Schedule DHH" class="input-submit">
			<input type = "submit" name="option" value="Schedule McNair" class="input-submit">
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

//Pass the the array holding ranks, with the format pid|day|hour|rank
//Adds up the total ranks and divides by the total number of shifts, to give an average rank
function average_rank()
{
	$sql = "SELECT pid, 100*sum(CASE WHEN rank ~ '[a-z]' THEN 0 ELSE rank::integer END)/count(rank) as average FROM hours GROUP BY pid";
	$resource =  pg_query($sql);
	
	while (	$array = pg_fetch_object($resource) ) 
	{
			$sql = "UPDATE info SET srank ='" . $array->average . "' WHERE pid='" . $array->pid . "'";
			pg_query($sql);
	}
}



//Generates an array $hours with the format hours[pid][day][hour] = rank
function hours_array()
{
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
	return $hours;
}

//Generates a table with the format info[pid][info column] = value
//ie info[32][desk] = d
function info_array() 
{
	global $info;
	$sql = 'SELECT * from info ORDER BY desk, name';
	$resource = pg_query($sql);
	for ($row=pg_num_rows($resource);$row>0;$row--){ //Loops each row of info (each PID)
		$set = pg_fetch_array($resource);
		$pid = $set['pid'];	//Makes for nicer looking code in the next few lines
		$info[$pid]['name'] = $set['name']; 
		$info[$pid]['desk'] = $set['desk'];
		$info[$pid]['position']=$set['position'];
		$info[$pid]['srank'] = $set['srank'];
		//If other info columns are desired, add them here like the above lines
	}
	return $info;
}

//Places deskies at a desk based on their average rank starting high to low
//Loops placing Wads => McNair => DHH
function desk_place($info)
{
	//Pull out array $srank_ar from $info
	foreach ($info as $key => $row) {
		$srank_ar[$key] = $row['srank'];
	}
	array_multisort($srank_ar, SORT_DESC, $info); //apply same order resulting from sorting ranks high to low to info

	$count = 3;
	$desks = array("w", "m", "d");
	reset($info);
	foreach($info as $pid=>$row){
		if($info[$pid]['position'] == "DR"){
			$sql = "UPDATE info SET desk ='" . $desks[$count%3] . "' WHERE pid='" . intval($pid) . "'";
			pg_query($sql);
			$count++;
		}
	}
}



function schedule_desk($desk) //Takes input $desk, for which desk to schedule, and attempts to generate a schedule
{

	$mailhour = 12; //What time mailshift should be

	average_rank();
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
		for ($hour=10;$hour<=20;$hour+=2){ //2 hour blocks
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

		//Add hour of 25 and 26 for wads mail shift, except sunday equal to $difficulty[$dayhour] where hour = 12 and 1 respectively
		if ($day > 0 && $desk == "w") {	//day is not sunday
			$difficulty[($day*100 + 25)] = $difficulty[($day*100+$mailhour)]; //diff of mailshift for that day = diff of mailhour that day
		}

	}
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

			//if $hour = 25 (mail shift), select the rank for the $mailhour
			if ($hour == 25) 
				$hour_sel = $mailhour;
			else
				$hour_sel = $hour;

			$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . $hour_sel . ";";
			$shift_rank1 = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid

			//2 hour blocks.
			$sql = "SELECT rank FROM hours WHERE pid=" . $pid . " AND day=" . $day . " AND hour=" . ($hour_sel+1) . ";";
			$shift_rank2 = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid. 

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
			//If 25, it is a mail shift, and should be labeled as such.
			if ($hour == 25)
				echo "Couldn't cover " . $days[$day] . " mail shift at " . $mailhour . ":00<br/>";
			else
				echo "Couldn't cover " . $days[$day] . " at " . $hour . ":00<br/>";

?>
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
	for($hour=10;$hour<=21;$hour++){
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
	
	//For the mailshift, yo
	$hour = 25;
	echo "<tr><td>Mailshift</td>";
	for($day=0;$day<=6;$day++){
		if ( isset($schedule[$day][$hour]) ){
			$sql = "SELECT rank FROM hours WHERE pid=" . $schedule[$day][$hour] . " AND day=" . $day . " AND hour=" . $mailhour . ";";
			$rank = pg_fetch_result(pg_query($sql),0,0); //Gets the rank of shift for $pid
			echo '<td class="rank' . $rank . '">' . $info[$schedule[$day][$hour]]['name'] . "</td>";
		}
		else
			echo "<td>----</td>";
	}
	echo "</tr>";


	echo "</table>\n";
	echo "<div class=\"hours_table\"><table>\n<tr><th>Name</th><th>Hours Given</th><th>Hours Desired</th></tr>\n";
	foreach($info as $array)
	{
			echo "<tr><td>" . $array['name'] . "</td><td class=\"center\">" .  $array['count'] . "</td><td class=\"center\">" . $array['desired'] . "</td></tr>\n";
	}
	echo "</table></div>";
	//--------------
	//Write to CSV
	//--------------	
	$file_location = "files/" . $desk . "_schedule.csv"; //Location to save the file to
	$file = fopen($file_location,"w");
	fwrite($file, "Opening,");
		
	foreach ($days as $value)
		{
			fwrite($file, $value . ",");
		}
		fwrite($file, "\n");
		for($hour=10;$hour<=21;$hour++){
			fwrite($file, $hour . ",");
			for($day=0;$day<=6;$day++){
				if ( isset($schedule[$day][$hour]) ){
					fwrite($file, $info[ $schedule[$day][$hour] ]['name'] . ",");
				}
				else
					fwrite($file, "----,");
			}
			fwrite($file, "\n");
		}
		fwrite($file, "\n\n\nName,Hours Given,Hours Desired\n");
		foreach($info as $array)
		{
			fwrite($file, $array['name'] . "," . $array['count'] . "," . $array['desired'] . "\n");	
		}
	fclose($file);
	echo "<div class\"down_link\"><a href=" . $file_location . ">Download CSV</a></div>"; //Offers the link for download
}//end function

include('footer.php');
?>
