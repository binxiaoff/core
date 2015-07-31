<?php
session_start();

$largeur  = 125;
$hauteur  = 28;
$longueur = 8;
$liste = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$code    = '';
$counter = 0;

$image = @imagecreate($largeur, $hauteur) or die('Impossible d\'initializer GD');

$img_create = imagecolorallocate($image,214,214,214);
imagecolortransparent($image,$img_create); // transparent

for( $i=0, $x=0; $i<$longueur; $i++ ) {
   $charactere = substr($liste, rand(0, strlen($liste)-1), 1);
   $x += 10 + mt_rand(0,5);
   imagechar($image, mt_rand(3,5), $x, mt_rand(5,10), $charactere,
   imagecolorallocate($image,183,183,183));
   $code .= strtolower($charactere);
}
   
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);

$_SESSION['securecode'] = $code;
?>