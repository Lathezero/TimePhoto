<?php
session_start();
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 5; $i++) { $code .= $chars[random_int(0, strlen($chars) - 1)]; }
$_SESSION['captcha_code'] = $code;
header('Content-Type: image/png');
$width = 120; $height = 40;
$img = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($img, 255, 255, 255);
$fg = imagecolorallocate($img, 50, 50, 50);
imagefilledrectangle($img, 0, 0, $width, $height, $bg);
for ($i = 0; $i < 50; $i++) { $noise = imagecolorallocate($img, random_int(150,255), random_int(150,255), random_int(150,255)); imagesetpixel($img, random_int(0,$width-1), random_int(0,$height-1), $noise); }
imagestring($img, 5, 22, 12, $code, $fg);
for ($i = 0; $i < 3; $i++) { $line = imagecolorallocate($img, random_int(100,200), random_int(100,200), random_int(100,200)); imageline($img, random_int(0,$width-1), random_int(0,$height-1), random_int(0,$width-1), random_int(0,$height-1), $line); }
imagepng($img);
imagedestroy($img);

