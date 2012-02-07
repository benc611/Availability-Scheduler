<!DOCTYPE html>
<?php session_start(); 

include ("connect.php"); //contains the values of the variables $host $dbname $user and $password
$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}

include('header.php');
include('functions.php')


echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
        echo "<br />" . $_SESSION['msg'];
        $_SESSION['msg'] = "";
}



?>


<a href="manage.php">Return</a>


<br>


<fieldset>
	<legend>Range</legend>
	<form name="range" action="?" method="get">
	<p>
		<label for="inp-minhour">From (Hour)</label><br />
		<input type="number" name="minhour" class="input-text" min="8" max="21" id="inp-minhour" value="<?php if (isset($_REQUEST['minhour'])) echo $_REQUEST['minhour']; else echo "8";?>"/> <br />
	</p>
	<p>
		<label for="inp-maxhour">To (Hour)</label><br />
		<input type="number" name="maxhour" class="input-text" min="8" max="21" id="inp-maxhour" value="<?php if (isset($_REQUEST['maxhour'])) echo $_REQUEST['maxhour']; else echo "21";?>"/> <br />
	</p>
	<p>
		<label for="inp-minday">From (Day, as a number)</label><br />
		<input type="number" name="minday" class="input-text" min="0" max="6" id="inp-minday" value="<?php if (isset($_REQUEST['minday'])) echo $_REQUEST['minday']; else echo "0";?>"/> <br />
	</p>
	<p>
		<label for="inp-maxday">To (Day, as a number)</label><br />
		<input type="number" name="maxday" class="input-text" min="0" max="6" id="inp-maxday" value="<?php if (isset($_REQUEST['maxday'])) echo $_REQUEST['maxday']; else echo "6";?>"/> <br />
	</p>
	<p>
		<label for="inp-sh-unavailable">Show unavailable shifts</label><br />
		<input type="checkbox" name="sh-unavailable" value="1" id="inp-sh-unavailable" <?php if (isset($_REQUEST['sh-unavailable'])) echo "checked" ?>/>
	</p>
	<p>
		<label for="inp-desk-sel">Desks to show</label><br />
		<select name="desk-sel[]" multiple>
			<option value="d" selected>DHH</option>
			<option value="w" selected>Wads</option>
			<option value="m" selected>McNair</option>
			<option value="" selected>Unplaced</option>
		</select>
	</p>

</fieldset>

	<p>
		<input type = "submit" name="submit" class="input-submit" value="Submit"/>
	</p>

	</form>


<?php

$minhour = 10;
$maxhour = 13;
$minday = 0;
$maxday = 6;

if (isset($_REQUEST['minhour']))
	$minhour = $_REQUEST['minhour'];
else $minhour = 10;

if (isset($_REQUEST['maxhour']))
	$maxhour = $_REQUEST['maxhour'];
else $maxhour = 13;

if (isset($_REQUEST['minday']))
	$minday = $_REQUEST['minday'];
else $minday = 0;

if (isset($_REQUEST['maxday']))
	$maxday = $_REQUEST['maxday'];
else $maxday = 6;

//Generate the SQL
$sql = "SELECT hours.pid, info.name, hours.rank, hours.day, hours.hour FROM hours ";
$sql .= "LEFT JOIN info ON info.pid=hours.pid ";
$sql .= "WHERE ";
$sql .= "hours.hour>=" . $minhour . " AND hours.hour<=" . $maxhour . " ";
$sql .= "AND hours.day>=" . $minday . " AND hours.day<=" . $maxday . " " ;
if(isset($_REQUEST['desk-sel']))
{
	$sql .= "AND ( ";
	foreach($_REQUEST['desk-sel'] as $value)
		$sql .= "desk='" . $value . "' OR ";
	$sql .= "desk ='garbage' ) ";
}
$sql .= "ORDER BY hours.pid, hours.day, hours.hour;";



echo "Displaying availability BETWEEN " . $minhour . " and " . ($maxhour+1);
echo " from " . $days[$minday] . " to " . $days[$maxday];
echo " at these desks: ";
if (isset($_REQUEST['desk-sel']))
	foreach($_REQUEST['desk-sel'] as $value)
		echo $desks[$value] . ", ";
else
	echo "DHH, Wads, McNair, Unplaced";
echo "<br/>";

$resource = pg_query($sql);


//Begin table, and day headers
echo "<table><tr><th/>";
for($x=$minday;$x<=$maxday;$x++)
	echo "<th>" . $days[$x] . "</th>";

$last_pid = 90210; 
$last_day = $minday;
//probably use a !isset later


while ($object= pg_fetch_object($resource) ) {
	//If last pid isnt the same as the new one, start a new row (new person)
	
	
	//New cell for a new day
	if ($last_day != $object->day)
	{ 
		if(isset($_REQUEST['sh-unavailable']) || $low_rank > 0)
		{
			echo "<td class=rank" . $low_rank . ">";
			foreach ($ranks as $key=>$value)
				echo $key . "|" . $value . " ";
			echo "</td>";
		}
		else echo "<td/>";
		unset($low_rank);
		unset($ranks);
	}

	//New row when new person
	if ($last_pid != $object->pid)
		echo "</tr><tr><td>" . $object->name . "</td>";

	//put rank into ranks
	$ranks[$object->hour] = $object->rank;
	if (!isset($low_rank) || $object->rank < $low_rank || $object->rank == 'c' || $object->rank == 'o')
		$low_rank = $object->rank;

	//New cell for each day
	

	$last_pid = $object->pid; //part of the hour change hack
	$last_day = $object->day;
	$x++;
}

echo "<td class=rank" . $low_rank . ">";
	foreach ($ranks as $key=>$value)
		echo $key . "|" . $value . " ";
echo "</td>";

echo "</tr></table>";

  
include('footer.php');
?>
