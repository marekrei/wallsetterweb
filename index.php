<?php
include_once("inc/config.php");
include_once("inc/db.php");
include_once("inc/imagemanager.php");
include_once("inc/c2dm.php");
DB::init();

$message = null;

// If a file has been submitted
if(isset($_FILES["file"]) && isset($_POST["public_key"])){
	$privateKey = DB::getPrivateKey($_POST['public_key']);
	// If there is a private key corresponding to the public key that was supplied
	if($privateKey != null){
		$image_path = "upload/" . $privateKey .".png";
		// If the file type is appropriate
		if ((($_FILES["file"]["type"] == "image/gif") || ($_FILES["file"]["type"] == "image/png") || ($_FILES["file"]["type"] == "image/jpeg") || ($_FILES["file"]["type"] == "image/pjpeg")) && ($_FILES["file"]["size"] < 3000000)){
			if ($_FILES["file"]["error"] > 0){
				$message =  "Error: " . $_FILES["file"]["error"] . "<br />";
			}
			else{
				// Resize image
				$newimage = ImageManager::resize($_FILES["file"]["tmp_name"], $_CONFIG['default_width'], $_CONFIG['default_height'], $error);
				if($newimage != null){
					// Save the resized image
					imagepng($newimage, $image_path);
					// Update the time in the database
					DB::imageUpdate($privateKey);
					// Send notification to the phone
					C2DM::sendNotification(DB::getC2dmKey($_POST['public_key']));
	
					$message = "Upload successful";
				}
				else
					$message = "Error: Resizer returned null";
			}
		}
		else
			$message = "Invalid file: " . $_FILES["file"]["type"] . " " . $_FILES["file"]["size"];
	}
	else
		$message = "Invalid key";
}
?> 

<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta name="viewport" content="width=device-width" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>WallSetter</title>
</head>
<body>

<div id="container">
<div id="logo"><img src="img/big.png" alt="WallSetter" /></div>
<?php if($message != null) print "<div id=\"message\">".$message."</div>"; ?>
<form action="" method="post" enctype="multipart/form-data">
<div>
<label for="public_key">Key:</label>
<input type="text" name="public_key" id="public_key" />
</div>
<div>
<label for="file">Image:</label>
<input type="file" name="file" id="file" />
</div>
<input type="submit" name="submit" id="submit" value="Submit" />
</form>

<div id="about"><p><strong>About :</strong> WallSetter is a fun little social experiment. Install the app on your phone, make sure you are connected to the internet and it will register itself. Get your public key from the app and share it with a friend. Whoever has it can come to this page and use it to automatically and instantly change your wallpaper. They get to share images with you in a fun way and you get a magically updating wallpaper. </p><p>Easy :) </p><p>The software is distributed free of charge and comes with no guarantees. You use it at your own responsibility. Please send any bugs and feedback to marek.rei@gmail.com. </p> </div>

</div>


</body>
</html>