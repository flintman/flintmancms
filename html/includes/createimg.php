<?php
// Get session ID from query parameter if provided, otherwise start new session
$session_id = $_GET['sid'] ?? null;

if ($session_id && preg_match('/^[a-zA-Z0-9,-]{22,128}$/', $session_id)) {
    session_id($session_id);
}

session_start();

// Check if GD library is available
if (!function_exists('imagecreate')) {
    die('GD library is not installed');
}

header('Content-Type: image/jpeg');
$alphanum = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
$rand = substr(str_shuffle($alphanum), 0, 5);
$_SESSION['vercode'] = $rand;
$height = 25;
$width = 65;
$image_p = imagecreate($width, $height);
$black = imagecolorallocate($image_p, 0, 0, 0);
$white = imagecolorallocate($image_p, 255, 255, 255);
$font_size = 14;
imagestring($image_p, $font_size, 5, 5, $rand, $white);
imagejpeg($image_p, null, 80);
imagedestroy($image_p);
?>
