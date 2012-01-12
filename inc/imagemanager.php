<?php
class ImageManager{
	
	/**
	 * Open the image file for reading
	 */
	public static function openImage ($file)
	{
		$size = getimagesize($file);

		if($size["mime"] == "image/jpeg" && function_exists("imagecreatefromjpeg"))
		return imagecreatefromjpeg($file);
		else if($size["mime"] == "image/gif" && function_exists("imagecreatefromgif"))
		return imagecreatefromgif($file);
		else if($size["mime"] == "image/png" && function_exists("imagecreatefrompng"))
		return imagecreatefrompng($file);
		return null;
	}
	
	/**
	 * Resize image to specific dimension, cropping as needed
	 */
	public static function resize($imgFile, $width, $height, &$error = null)
	{
		$maxpixels = 30000000;
		$attrs = @getimagesize($imgFile);
		if($attrs == false)
		{
			$error = "Uploaded image is not readable by this page.";
			return null;
		}
		if($attrs[0] * $attrs[1] > $maxpixels)
		{
			$error = "Max pixels allowed is $maxpixels. Your {$attrs[0]} x " .
	               "{$attrs[1]} image has " . $attrs[0] * $attrs[1] . " pixels.";
			return null;
		}
		
		$src = ImageManager::openImage($imgFile);
		if($src == null){
			$error = "Unknown problem trying to open uploaded image.";
			return null;
		}
		
		if($width > 0 && $height > 0){
			$ratio = (($attrs[0] / $attrs[1]) < ($width / $height)) ? $width / $attrs[0] : $height / $attrs[1];
			$x = max(0, round($attrs[0] / 2 - ($width / 2) / $ratio));
			$y = max(0, round($attrs[1] / 2 - ($height / 2) / $ratio));
			//$src = imagecreatefromjpeg($imgFile);
			$resized = imagecreatetruecolor($width, $height);
			$result = imagecopyresampled($resized, $src, 0, 0, $x, $y, $width, $height,round($width / $ratio, 0), round($height / $ratio));
		}
		
		if($result == false)
		{
			$error = "Error trying to resize and crop image.";
			return null;
		}
		else
		{
			return $resized;
		}
	}
	
}