<?

// генерация капчи
session_start();
$digit1 = rand(1, 30);
$digit2 = rand(1, 30);
$_SESSION["rand_number"] = $digit1 + $digit2;
$image = imagecreatetruecolor(200, 40);
$text_color = imagecolorallocate($image, 200, 100, 90);
$bg_color = imagecolorallocate($image, 255, 255, 255);
imagefilledrectangle($image, 0, 0, 200, 40, $bg_color);
imagettftext($image, 30, 0, 10, 35, $text_color, "font.ttf", "$digit1 + $digit2");
imagepng($image);
