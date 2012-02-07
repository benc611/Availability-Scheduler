<?php 

session_start();

include('header.php');

session_destroy();

echo "<h1>Logged out</h1>";
echo "<p>You have looged out succesfully. Click <a href='index.php'>here</a> to return";

include ('footer.php');

?>

