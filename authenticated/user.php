<!DOCTYPE html>
<?php session_start(); 

include('../header.php');

?>


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

  document.submit.username.value = document.submit.username.value.replace(/^\s*/, "").replace(/\s*$/, "");
  document.submit.name.value = document.submit.name.value.replace(/^\s*/, "").replace(/\s*$/, "");

  var username=document.submit.username;
  if (username.value==null || username.value==""){
    username.className = username.className + " err";
    return false;
  }
  

  var name=document.submit.name;
  if (name.value==null || name.value=="" || (name.value.search(/[a-zA-z]{1,}\s[a-zA-z]{1,}/) == -1)  ){
    alert("Please fill out first and last name");
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
  
document.submit.submit();
  return true;
}

</script>


<?php

include '../connect.php'; //contains the values of the variables $host $dbname $user and $password


$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
  die("Could not connect: " .  pg_last_error());
}

echo "<h1>Schedule Access</h1>";


$user_check = pg_escape_string($_SESSION['MTUISODN']);
$result = pg_query("SELECT username FROM info WHERE username='$user_check';");





//If user already exists in DB, edit
if (pg_num_rows($result))
{
  $exists = 1;
  ?>
  <h4>
  A schedule has already been submitted for <?php echo $user_check?>. <br />
  You may edit your information and hours below.
  </h4>

  <?php
  $username = $user_check;
  //Select based on username. Stores all info into array $info
  $sql = "SELECT * FROM info WHERE username='" . $username . "';";
  $resource = pg_query($sql);
  $info = pg_fetch_array($resource);

  //Using PID, get hours -> array
  $_SESSION['pid'] = $info['pid'];

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

  pg_close($con);
} //End edit user

//Create new user. Does not exist in DB
else{
  $exists = 0;
  ?>
  <br />
  <h4>
  You haven't yet submitted your schedule and preferences yet. <br />
  Please input your information below <br /> <br />
  </h4>

  <?php
  //Select based on username. Stores all info into array $info
  $info = array("pid" => "", "username" => $user_check, "name" => "", "dub" => "", "split" => "", "position" => "", "desired" => "");

  //Store hour preferences in array $hours
  for ($day=0; $day<=6; $day++)
  {
    for ($hour=1;$hour<=21;$hour++) {
      $hours[$day][$hour] = "x";
    }
  }

} //end else for user not found

?>
<fieldset>
  <legend>User Information</legend>
  <form name="submit" action="submit.php" onsubmit="return validateForm()" method="post" autocomplete="on">
  <p>
    <label for="inp-user">Username</label><br />
    <input type="text" size="40" name="username" value="<?php echo $info['username']; ?>" class="input-text" id="inp-user" onChange="" autofocus="autofocus"/><br />
  </p>
  <p>
    <label for="inp-name">Name</label><br />
    <input type="text" size="40" name="name" value="<?php echo $info['name']; ?>" class="input-text" id="inp-name" />
  </p>
  <p class="nomb">
    <label for="inp-dub">Double Shifts</label><br />
    <select name="dubshift" id="inp-dub" class="input-text">
      <option value="Yes" <?php if ($info['dub']=="Yes  ") echo 'selected'; ?>>Willing to</option>
      <option value="Maybe" <?php if ($info['dub']=="Maybe") echo 'selected'; ?>>If Needed</option>
      <option value="No" <?php if ($info['dub']=="No   ") echo 'selected';?>>Not preferred</option>
    </select><br />
    <span class="smaller low">Info:</span>
  </p>
  <p class="nomb">
    <label for="inp-split">Split Shifts</label><br />
    <select name="splitshift" id="inp-split" class="input-text">
      <option value="Yes" <?php if($info['split']=="Yes  ") echo 'selected';?>>Willing to</option>
      <option value="Maybe" <?php if($info['split']=="Maybe") echo 'selected';?>>If Needed</option>
      <option value="No" <?php if($info['split']=="No") echo 'selected'?>>Not preferred</option>
    </select><br />
    <span class="smaller low">Info:</span>
  </p>
  <p class="nomb">
    <label for="inp-pos">Position</label><br />
    <select name="position" id="inp-pos" class="input-text">
            <option value="DR" <?php if($info['position']=="DR") echo 'selected';?>>Desk Receptionist</option>
            <option value="AC" <?php if($info['position']=="AC") echo 'selected';?>>Assistant Coordinator</option>
    </select><br />
  </p>
  <p class="nomb">
    <label for="inp-pos">Hours Desired</label><br />
    <input type="number" name="desired" class="input-text" min="0" max="10" value="<?php echo $info['desired'];?>"/> <br />
  </p>

</fieldset>

  <p>
    <input type = "submit" name="submit" class="input-submit" value="<?php if ($exists==1) echo "Submit"; else echo "Create"; ?>"/>
  </p>


<h5>
c = Scheduled class <br/>
o = Scheduled organization meeting <br />
1 = Available, not preferred <br />
2 = Available, would like shift <br />
3 = Best <br />
</h5>


<?php 
    //This generates the table for selecting hours preferences
    //$days = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");

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
      echo "  <tr>\n";
      if ($hour < 12)
        echo '    <td>' . $hour . '-' . ($hour+1) . ' AM</td>';
      else if ($hour == 12)
        echo '    <td>12-1 PM</td>';
      else 
        echo '    <td>' . ($hour-12) . '-' . ($hour-11) . ' PM</td>';

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

  echo '</form>'; //End page form


include('footer.php');
?>
