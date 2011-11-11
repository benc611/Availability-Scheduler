<?php session_start(); ?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="style.css" />

<script type="text/javascript">
function handlerTextChange(textbox){ //Changes textbox color based on input value
if (textbox.value == 3) textbox.style.background = '#a0ba42';
else if (textbox.value == 2) textbox.style.background = '#ffce00';
else if (textbox.value == 1) textbox.style.background = '#860e25';
else if (textbox.value.toLowerCase() == "o") 
{
textbox.style.background = '#9fa0a3';
textbox.value = textbox.value.toLowerCase();
}
else if (textbox.value.toLowerCase() == "c") 
{
textbox.style.background = '#beb69f';
textbox.value = textbox.value.toLowerCase();
}
else textbox.style.background = '#fff';
}

function validateForm() //Validates all input to make sure it is valid
{
	var username=document.submit.username.value;
	if (username==null || username==""){
		alert("Please fill out username");
		return false;
	}
	var name=document.submit.name.value;
	if (name==null || name==""){
		alert("Please fill out name");
		return false;
	}
	var desired=document.submit.desired.value; //Also checked by html5
	if (desired==null || desired=="" || desired > 10 || desired < 0 || desired%2){
		alert("Please input an even number between 0 and 10");
		return false;
	}

	for (hour=8;hour<=21;hour++)
	{
		for (day=0;day<=6;day++)
		{
			hour_name = "hours[" + day + "][" + hour + "]";
			if(!(document.forms["submit"][hour_name].value == "x" || document.forms["submit"][hour_name].value == "c" || document.forms["submit"][hour_name].value == "o" || document.forms["submit"][hour_name].value == "1" || document.forms["submit"][hour_name].value == "2" || document.forms["submit"][hour_name].value == "3")){
				alert("Please make sure all hours preferences are either c, x, o, or an integer 1 through 3");
				return false;
			}
		}
	}
	
	return true;
}

</script>
</head>

<body>
<?php

include 'connect.php'; //contains the values of the variables $host $dbname $user and $password

$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}

//Displayed if no input yet
if (!isset($_POST['user_check'])){
/*Check to see if the user already exists*/?>
	Please enter your Michigan Tech username
	<form action="index.php" method="post" autocomplete="on">
	<input type="text" name="user_check" autofocus="autofocus" /> <?php /*Store username to check if in DB*/?>
	</form> <?php
	}

else{
$user_check = pg_escape_string($_POST['user_check']);
$result = pg_query("SELECT username FROM info WHERE username='$user_check';");

//If user already exists in DB, edit
if (pg_num_rows($result))
{
	?>
	A schedule has already been submitted for <?php echo $user_check?>. <br />
	You may edit your information and hours below.

	<?php
	$username = $user_check;
	//Select based on username. Stores all info into array $info
	$sql = "SELECT * FROM info WHERE username='" . $username . "';";
	$resource = pg_query($sql);
	$info = pg_fetch_array($resource);

	//Using PID, get hours -> array
	$_SESSION['pid'] = $info['pid'];

	//Write form. Default = variable option
	?>
	<form name="submit" action="submit.php" onsubmit="return validateForm()" method="post" autocomplete="on">
		<table> <?php /*User information*/?>
		<tr><td>Username: </td> <td><input type="text" name="username" value="<?php echo $info['username']; ?>" autofocus="autofocus" /></td></tr>

		<tr><td>Name: </td> <td><input type="text" name="name" value="<?php echo $info['name']; ?>"/> </td></tr>

		<tr><td>Double Shifts:</td>
		<td>
		<select name="dubshift">
			<option value="Yes" <?php if ($info['dub']=="Yes  ") echo 'selected'; ?>>Willing to</option>
			<option value="Maybe" <?php if ($info['dub']=="Maybe") echo 'selected'; ?>>If Needed</option>
			<option value="No" <?php if ($info['dub']=="No   ") echo 'selected';?>>Not preferred</option>
		</select>
		</td>
		</tr>
		<tr><td>Split Shifts:</td>
		<td>
		<select name="splitshift">
		        <option value="Yes" <?php if($info['split']=="Yes  ") echo 'selected';?>>Willing to</option>
		        <option value="Maybe" <?php if($info['split']=="Maybe") echo 'selected';?>>If Needed</option>
		        <option value="No" <?php if($info['split']=="No") echo 'selected'?>>Not preferred</option>
		</select>
		</td>
		</tr>

		<tr><td>Position: </td>
		<td>
		<select name="position">
		        <option value="DR" <?php if($info['position']=="DR") echo 'selected';?>>Desk Receptionist</option>
		        <option value="AC" <?php if($info['position']=="AC") echo 'selected';?>>Assistant Coordinator</option>
		</select>
		</td>
		</tr>

		<tr><td>Hours desired: </td>
		<td>
		<input type="number" name="desired" min="0" max="14" value="<?php echo $info['desired'];?>"/> <br />
		</td>
		</tr>

		<tr><td>
		<input type = "submit" name="submit" value="Submit"/>
		</tr></td>
		</table>


	<?php
	//Store hour preferences in array $hours
	for ($day=0; $day<=6; $day++)
	{

		for ($hour=1;$hour<=21;$hour++) {
			$sql = 'SELECT rank FROM hours WHERE pid=' . $info['pid'] . ' AND day=' . $day . ' AND hour=' . $hour . ';';
			$resource = pg_query($sql);
			$array = pg_fetch_array($resource);
			$hours[$day][$hour] = $array['rank'];
		}
	}



	//This generates the table for selecting hours preferences
		//$days = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
		echo "<table border=\"1\"><tr><td>c = Scheduled Class</td><td>o = Scheduled organization meeting</td><td>1 = Available, not preferred</td><td> 2 = Available, would like shift</td> <td>3 = Best</td></tr></table>";
		echo '<table border="1">
			<tr>
				<th></th>
				<th>Sunday</th>
				<th>Monday</th>
				<th>Tuesday</th>
				<th>Wednesday</th>
				<th>Thursday</th>
				<th>Friday</th>
				<th>Saturday</th>
			</tr>
		';

		for ($hour=8; $hour<=21; $hour++) 
		{
			echo "	<tr>\n";
			if ($hour < 12)
				echo '		<td>' . $hour . '-' . ($hour+1) . ' AM</td>';
			else if ($hour == 12)
				echo '		<td>12-1 PM</td>';
			else 
				echo '		<td>' . ($hour-12) . '-' . ($hour-11) . ' PM</td>';

			for ($day=0; $day<=6; $day++)
			{
				echo '<td><input type="text" name="hours[' . $day . '][' . $hour . ']" ';
				echo 'class="rank' . $hours[$day][$hour] . '" ';
				echo 'size=10 value=' .  $hours[$day][$hour] . ' onChange="javascript:handlerTextChange(this)" > </td>';
				}
			echo '</tr>';
			}
		echo '</table>';
		//End hours table

	echo '</form>';	//End page form

	pg_close($con);
} //End edit user

//Create new user. Does not exist in DB
else{
	?>
	<br />
	You haven't yet submitted your schedule and preferences yet. <br />
	Please input your information below <br /> <br />
	<form name="submit" action="submit.php" onsubmit="return validateForm()" method="post" autocomplete="on">
		<table> <?php /*User information*/ ?>
		<tr><td>Username: </td> <td><input type="text" name="username" value=<?php echo $_POST['user_check'] ?> autofocus="autofocus" /></td></tr>

		<tr><td>Name: </td> <td><input type="text" name="name" /></td></tr>

		<tr><td>Double Shifts:</td>
		<td>
		<select name="dubshift">
			<option value="Yes" selected="selected">Willing to</option>
			<option value="Maybe">If Needed</option>
			<option value="No">Not preferred</option>
		</td>
		</tr>

		<tr><td>Split Shifts:</td>
		<td>
		<select name="splitshift">
			<option value="Yes" selected="selected">Willing to</option>
			<option value="Maybe">If Needed</option>
			<option value="No">Not preferred</option>
		</select>
		</td>
		</tr>

		<tr><td>Position: </td>
		<td>
		<select name="position">
			<option value="DR" selected="selected">Desk Receptionist</option>
			<option value="AC">Assistant Coordinator</option>
		</select>
		</td>
		</tr>

		<tr><td>Hours desired: </td>
		<td>
		<input type="number" name="desired" min="0" max="14" value="8"/> <br />
		</td>
		</tr>
		<tr><td>
		</tr></td>
		</table>
		<input type = "submit" name="submit" value="Create"/>

	<?php
		//This generates the table for selecting hours preferences
		//$days = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
		echo "<table border=\"1\"><tr><td>c = Scheduled Class</td><td>o = Scheduled organization meeting</td><td>1 = Available, not preferred</td><td> 2 = Available, would like shift</td> <td>3 = Best</td></tr></table>";

		echo '<table border="1">
			<tr>
				<th></th>
				<th>Sunday</th>
				<th>Monday</th>
				<th>Tuesday</th>
				<th>Wednesday</th>
				<th>Thursday</th>
				<th>Friday</th>
				<th>Saturday</th>
			</tr>
		';

		for ($hour=8; $hour<=21; $hour++) 
		{
			echo "	<tr>\n";
			if ($hour < 12)
				echo '		<td>' . $hour . '-' . ($hour+1) . ' AM</td>';
			else if ($hour == 12)
				echo '		<td>12-1 PM</td>';
			else 
				echo '		<td>' . ($hour-12) . '-' . ($hour-11) . ' PM</td>';

			for ($day=0; $day<=6; $day++)
			{
				echo "\n";
				echo '		<td><input type="text" name="hours[' . $day . '][' . $hour . ']" size=10 value="x" onChange="javascript:handlerTextChange(this);"/> </td>';
				}
			echo '	</tr>';
			}
		echo '</table>';
		//End hours table
	echo '</form>';	//End page form


	} //end else for user not found
} //end else

?>
</body>
</html>
