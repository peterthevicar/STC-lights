<?php
// Read the header information
$new_disp=json_decode($_POST['json']);
// Get an exclusive lock on json-displays
$fn = 'json-displays.json';
$fp = fopen($fn, "a+") or die ("Unable to open file $fn!");
$success = false;
for ($i=0; $i<3; $i++) { // try 3 times for the lock
	if (flock($fp, LOCK_EX)) {
		$disps = json_decode(file_get_contents($fn), true);
		$new_id = "id".(count($disps)+1);
		$disps[$new_id] = $new_disp;
		echo json_encode($disps);
		//fwrite($json_displays, $_POST['json']);
		//fclose($json_displays);				
		flock($fp, LOCK_UN);
		$success = true;
		break;
	}
	else sleep(1);
}
echo (success? "Yippeeeeee!!": "Oh no! Summat's up!");
//~ TODO: work out how to respond after errors etc, die is not ideal
//~ Complete file writing code
?>
