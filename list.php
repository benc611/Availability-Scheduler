<?php session_start(); 

include 'connect.php'; //contains the values of the variables $host $dbname $user and $password
$con = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
if (!$con)
{
	die("Could not connect: " .  pg_last_error());
}


include('header.php');
include('functions.php');


if ( isset($_REQUEST['action']) ) {
        switch ($_REQUEST['action'])
        {
                case "delete":
                        $sql = "DELETE FROM info WHERE pid = '" . $_REQUEST['pid'] . "';";
                        $sql .= "DELETE FROM hours WHERE pid = '" . $_REQUEST['pid'] . "';";
                        pg_query($sql);
//                      echo $sql;
						if (isset($_SESSION['msg']))
                        	$_SESSION['msg'] .= "Deleted user<br>";
                        else
                        	$_SESSION['msg'] = "Deleted user<br>";
                        break;

        }
}


echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
        echo "<br />" . $_SESSION['msg'];
        $_SESSION['msg'] = "";
}


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
	<a class="ico-delete"href="?action=delete&pid=<?php echo $array['pid'];?>">Delete</a>

	<?php
	echo "</tr>\n";
}
echo "</table>\n";

include('footer.php');
?>
