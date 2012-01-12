<?php 
include_once("inc/config.php");
include_once("inc/db.php");
DB::init();

// Registration request
if(isset($_GET['register']) && isset($_GET['c2dmKey']) && strlen($_GET['c2dmKey']) > 0){
	$c2dmKey = $_GET['c2dmKey'];
	$oldPrivateKey = null;
	// If an old key is passed into the request, we want to change it to the new one instead of creating another instance.
	if(isset($_GET['oldPrivateKey']) && strlen($_GET['oldPrivateKey']) > 0)
		$oldPrivateKey = $_GET['oldPrivateKey'];
	
	$newKeys = DB::register($c2dmKey, $oldPrivateKey);
	print $newKeys;
}

// Image request
else if(isset($_GET['image']) && isset($_GET['privateKey']) && strlen($_GET['privateKey'])){
	// Setting the download count and time in the database
	DB::imageDownload($_GET['privateKey']);
	// Redirecting the user to the actual image
	header( "Location: http://www.siineiolekala.net/wallsetter/upload/".DB::filterKey($_GET['privateKey']).".png" ) ;
}


?>