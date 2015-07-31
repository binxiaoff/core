<?php
/*
 * @class Phoca Captcha
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 
class PhocaCaptcha
{
	function displayCaptcha($chemin)
	{
		

		header("Expires: Sun, 1 Jan 2000 12:00:00 GMT"); 
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
		header("Cache-Control: no-store, no-cache, must-revalidate"); 
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		$rand_char = PhocaCaptcha::generateRandomChar(6);
		$_SESSION['captcha'] = $rand_char;
		$rand_char_array = array (			$rand_char[0]."          ",
										"  ".$rand_char[1]."        "	,
				   					   "    ".$rand_char[2]."      "	,
									  "      ".$rand_char[3]."    "   ,
									 "        ".$rand_char[4]."  "	,
									"          ".$rand_char[5]);

		$image_name 	= PhocaCaptcha::getRandomImage();
		
		$image 			= @ImageCreateFromJPEG($chemin);
 
		foreach ($rand_char_array as $key => $value)
		{
		 $font_color 	= PhocaCaptcha::getRandomFontColor();
		 $position_x 	= PhocaCaptcha::getRandomPositionX();
		 $position_y 	= PhocaCaptcha::getRandomPositionY();
		 $font_size 	= PhocaCaptcha::getRandomFontSize();
		 
		 
		 ImageString($image, $font_size, $position_x, $position_y, $value, ImageColorAllocate ($image, $font_color[0], $font_color[1], $font_color[2]));
		}
		
		header ('Content-type: image/jpeg');
		
		ImageJPEG($image,NULL,100);
		
		ImageDestroy($image);
		
	}


	function generateRandomChar($string_length)
	{
		
		$digit = array(0,1,2,3,4,5,6,7,8,9);
		$lower = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		$upper = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		
		$char_group = array_merge ($digit, $lower, $upper);
		
		srand ((double) microtime() * 1000000);
		
		$random_string = '';
		
		for($ch=0;$ch<$string_length;$ch++)
		{
			$char_group_start	= 0;
			$char_group_end 	= sizeof($char_group)-1;
			$random_char_group 	= rand($char_group_start, $char_group_end);
			$random_string 	   .= $char_group[$random_char_group];
		}
		return $random_string;
	}

	function getRandomImage()
	{
		$rand 	= mt_rand(1,6);
		$image 	= '0'.$rand.'.jpg';
		return $image;
	}

	function getRandomPositionX()
	{
		$rand 	= mt_rand(2,3);
		return $rand;
	}

	function getRandomPositionY()
	{
		$rand 	= mt_rand(1,4);
		return $rand;
	}

	function getRandomFontSize()
	{
		$rand = mt_rand(4,5);
		return $rand;
	}


	function getRandomFontColor()
	{
		$font_color[0] = 22; 	$font_color[1] = 52; 	$font_color[2] = 98;
		
		return $font_color;
	}

	/*function getRandomFontColor()
	{
		$rand = mt_rand(1,6);
		if ($rand == 1) {$font_color[0] = 0; 	$font_color[1] = 0; 	$font_color[2] = 0;}
		if ($rand == 2) {$font_color[0] = 0; 	$font_color[1] = 0; 	$font_color[2] = 153;}
		if ($rand == 3) {$font_color[0] = 0; 	$font_color[1] = 102; 	$font_color[2] = 0;}
		if ($rand == 4) {$font_color[0] = 102; 	$font_color[1] = 51; 	$font_color[2] = 0;}
		if ($rand == 5) {$font_color[0] = 163; 	$font_color[1] = 0; 	$font_color[2] = 0;}
		if ($rand == 6) {$font_color[0] = 0; 	$font_color[1] = 82; 	$font_color[2] = 163;}
		return $font_color;
	}*/
}
?>