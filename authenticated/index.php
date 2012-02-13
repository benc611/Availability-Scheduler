<?php session_start(); 

include_once('../header.php');

if(isset($_SESSION['MTUISODN']))
{
	header('Location: user.php');	
}


else
{	
	echo '<a href="login.php">Log In?</a>';
}

include('footer.php');
?>
