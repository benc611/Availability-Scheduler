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



echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
        echo "<br />" . $_SESSION['msg'];
        $_SESSION['msg'] = "";
}



average_rank();
$info = info_array();

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

echo "<br/><h4>Deskies have been placed.</h4><br/>";
unset($_SESSION['option']); //Unsets the option variable so that a refresh will not repeat the action





include('footer.php');

?>
