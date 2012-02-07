<!DOCTYPE html>
<?php session_start(); 

include 'connect.php'; //contains the values of the variables $host $dbname $user and $password
$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}

include('header.php');
include('functions.php');


$days = array(0 => "Sunday" , 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday"); //Translates day # to name
$desks = array(" " => " ", "d" => "DHH", "w" => "Wads", "m" => "McNair"); //Translates single letter to Desk name



echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
        echo "<br />" . $_SESSION['msg'];
        $_SESSION['msg'] = "";
}


?>

<a href="manage.php">Return</a>

<form action="?" method="get">
	<input type = "submit" name="option" value="Schedule Wads" class="input-submit">
	<input type = "submit" name="option" value="Schedule DHH" class="input-submit">
	<input type = "submit" name="option" value="Schedule McNair" class="input-submit">
</form>

<?php

if(isset($_REQUEST['option']))
{
	switch ($_REQUEST['option'])
	{
		case "Schedule Wads":
			schedule_desk("w");
			break;
		case "Schedule McNair":
			schedule_desk("m");
			break;
		case "Schedule DHH":
			schedule_desk("d");
			break;
		
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
}



include('footer.php');
?>
