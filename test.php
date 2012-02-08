
<?php session_start(); 


if (isset($_SESSION['MTUISODN'])){
	echo "yay";
}	
else
	echo "failed";

?>
