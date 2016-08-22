<?PHP

include ("frb_functions.inc.php");

$link = db_connect();
$image_id = $_GET["id"];

$png_data = getPublicRadioImage ($link, $image_id);

header ("Content-Type: image/png");
echo $png_data;
