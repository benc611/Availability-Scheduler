<?php 

global $days, $desks;
$desks = array("" => "Unplaced", "d" => "DHH", "w" => "Wads", "m" => "McNair", " " => "Unplaced"); //Translates single letter to Desk name
$days = array(0 => "Sunday" , 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday"); //Translates day # to name


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




?>