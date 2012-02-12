
<?php session_start(); 

include('header.php');

echo "<h1>Scheduling Management</h1>";


if ( isset($_SESSION['msg']) ) {
	echo "<br />" . $_SESSION['msg'];
	$_SESSION['msg'] = "";
}




include('footer.php');
?>
