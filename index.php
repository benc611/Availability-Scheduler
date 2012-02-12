<!DOCTYPE html>
<?php session_start(); 

include('header.php');

if(isset($_SESSION['MTUISODN']))
{
	header('Location: index2.php');	
}


else
{	
	echo '<a href="login.php">Log In?</a>';
}

include('footer.php');
?>
