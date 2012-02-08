
<?php session_start(); 

include('header.php');

echo "<h1>Login</h1>";

$_SESSION['debug'] = TRUE;

error_reporting(E_ERROR | E_WARNING | E_PARSE);

//Credit for most of the code in this section goes to DJ Cole. This is adapated from his iCos ISO login script

if ( isset($_SESSION['msg']) ) {
	echo "<br />" . $_SESSION['msg'];
	$_SESSION['msg'] = "";
}


if(isset($_GET['return']))
{
	if ($_GET['return']) 
		$_SESSION['return'] = $_GET['return'];
}


if(isset($_GET['logout']))
{
	if ($_GET['logout']){
		unset($_SESSION['MTUISODN']);
		unset($_SESSION['ldap']);
		session_destroy();	
	}


	echo '<b>Logout Sucsessful</b><br><br>';
	echo 'You have been logged out of this application.<br><br>';
	echo 'To logout of the entire ISO system, click <a href="https://www.login.mtu.edu/tools/public/login/logout.cgi">here</a>.<br>';
	exit;
}

sleep(1);

$mtuiso['appid'] = '334'; 
$mtuiso['cookie'] = 'ORBGLB';
$mtuiso['hostname'] = 'dhh-236-13.resnet.mtu.edu'; 	
$mtuiso['seconds'] = '0';   
$mtuiso['delport'] = '0';   
$mtuiso['https'] = FALSE;


//NEED A COMMENT HERE
///
if (isset($_SESSION['MTUISODN']))
{
	if ($_SESSION['MTUISODN'])
		header("Location: dhh-236-13.resnet.mtu.edu" . $_SESSION['return']);
}	
					
if (isset($_REQUEST['cookie']))
{
	echo "test";
	$crumbs =  base64_decode($_REQUEST['cookie']);


	if ($crumbs){ //cookie set succesfully

		$_SESSION['redirectcounter'] = 0;

		if ( $_SESSION['debug'] === TRUE ){
			
			echo 'Processing the application global cookie\n';
			echo $crumbs;
			
		}	
		
		$mtuiso_cert_info = openssl_x509_parse($crumbs);  
		
		if ( $_SESSION['debug'] === TRUE ){
			
			echo 'Parsing the cookie payload and seeking a DN\n';
			echo print_r($mtuiso_cert_info);
			
		}	  
		
		if ($mtuiso_cert_info["subject"]["CN"][2]){ 
			$isoun = $mtuiso_cert_info["subject"]["CN"];
		}
		else{
			$isoun = $mtuiso_cert_info["subject"]["CN"][0];
			$pidm = $mtuiso_cert_info["subject"]["CN"][1];
		} 
		

			if ($isoun){	 
			
				$pub_key[0] = './mtuca.crt';   
				
				$iso_cert = openssl_x509_read($crumbs);			
				
				$verify = openssl_x509_checkpurpose($iso_cert,X509_PURPOSE_ANY,$pub_key);

				if ( $_SESSION['debug'] === TRUE ){
			
					echo 'Checking the payload is current and from mtuca' . time() . '  <br>';
					echo $verify;
					
				}	
				
			
				
				if ($verify === 1){	
				
					if ( $_SESSION['debug'] === TRUE ){
				
						echo 'Payload checks out\n';
						echo 'Checking the cross check number<br>';
						
					}	
				
					 	if (TRUE == TRUE){
					
						if ( $_SESSION['debug'] === TRUE ){
			
								echo 'Cross check correct<br>';
								echo 'Session cross check is :\'' . trim($_SESSION['mtuiso_crosscheck'],"\x09\x16") . '\'<br>';
								echo 'Certificate cross check is :\'' . trim($mtuiso_cert_info['extensions']['UNDEF'],"\x09\x16") . '\'<br>';
														
						}	
					

						$_SESSION['MTUISODN'] = trim($isoun);
						$_SESSION['MTUPIDM'] = trim($pidm);
						
						header("Location: https://secure.icospro.com"  . $_SESSION['return']);
					}else{
					
						// being here means that cross check failed		
						
							if ( $_SESSION['debug'] === TRUE ){
			
								echo 'Cross check invalid\n';
								echo 'Session cross check is :\'' . trim($_SESSION['mtuiso_crosscheck'],"\x09\x16") . '\'<br>';
								echo 'Certificate cross check is :\'' . trim($mtuiso_cert_info['extensions']['UNDEF'],"\x09\x16") . '\'<br>';
							
							}
					}	
					
					
				}else{ 
				 			// Getting here means that there is something wrong with the certificate
						if ( $_SESSION['debug'] === TRUE ){
			
								echo 'Certificate failed inspection<br>';
							
						} 
						
						
						?>
						 <html>
					<head>
					<title>Authorization Error</title>
					</head>
					<body>
					<b>Authorization Error</b><br>
					<br>
					There was a problem verifying your credentails issued from the authorizarion server. Please try agian.<br><br>If the problem still occurs please contact the system administrator.<br>
					<br>
					<a href="index.php">You can still view public information here.</a>
					</body>
					</html>
						<?php
						exit;

				}
			
			}else{
			// No dn present fron the certificate  
					if ( $_SESSION['debug'] === TRUE ){
						echo 'Didn\'t parse certificate properly <br>';
					}
			}
		ob_end_flush(); // fix some wierd error					
	}
}

else{ 
		if ($_SESSION['redirectcounter'] < 5){
		
			//	check to make sure we haven't redirected to the iso server more than five times
			//	which indicates that the user dosen't have cookies enables and redirects over
			//	five indicates and issue per RFCs			   
			//	-- can also indicate that the time on the server is not correct	
			

				$uri = 'https://www.login.mtu.edu:11443/tools/public/login/index.cgi?';

			
			// include the appid
			$uri .= 'appid=' . $mtuiso['appid']; 
			
			if ($mtuiso['delport'] === '1'){
			 
				// include the delport
				$uri .= '&delport=1';
						
			}
			
			if ($mtuiso['seconds'] > -1){
			 
				// include the delport
				$uri .= '&seconds=' . $mtuiso['seconds'];
						
			}  	  
			
			if ($mtuiso['backname']){
			 
				// include the delport
				$uri .= '&backname=' . $mtuiso['backname'];
						
			} 
			
			if ($_SERVER['HTTPS'] == "on") {
				// should we use https?
				
				$uri .= '&httpsonly=0&globalsecure=0';
				
			}

			else{

				$uri .= '&httpsonly=0&globalsecure=0';
				
			}

			$uri .= '&globalname=' . $mtuiso['cookie'];

				$back = 'https://';
				
		
			$back .= '';
			
			$back .= $_SERVER['REQUEST_URI'];

			$uri .= '&back=http://dhh-236-13.resnet.mtu.edu/availability/themed/test.php'; 
			
			//header('Location:' . $uri);	
			
			exit; 
		}

	else{
	echo 'A problem has occured. Please try agian.';
	$_SESSION['redirectcounter'] = 0;
	}
}

include('footer.php');
?>
