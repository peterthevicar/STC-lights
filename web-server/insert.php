<?php
// Set up error handler and err function for logging errors
include "error-handler.php";

//
//------------------ Code to insert new display spec -----------------//
//
// Read the header information (a json with the spec for the new display)
if ($_POST == null) $new_disp = json_decode(
	['json' => '{"hd":["4 colours","Peter",1539365120,0,0],"co":["#0000ff","#00ff00","#ff0000","#000000"],"gr":["1","2","0"],"se":["2","1","2","5","2"],"fa":["0","2","0"],"sk":["10",8.3],"st":["0","#000000","1","3.5","1"],"fl":["0","#000000","#FFFFFF","1","3.0"],"me":["0"]}']['json'], true);
else $new_disp = json_decode($_POST['json'], true);

// Get an exclusive lock on json-displays
$fn = 'json-displays.json';
$waiting = true;
$duplicate = false; // Assume it's not already in there
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for exclusive access to the file
	$fp = fopen($fn, "c+"); // try to open file but don't truncate
	if ($fp) {
		if (flock($fp, LOCK_EX)) {
			//
			//-------------- EXCLUSIVE LOCKED -----------
			//
			$disps = json_decode(file_get_contents($fn), true);
			// See if it's a duplicate - nothing clever as we're holding a lock, 
			// but foil a simple bot
			foreach ($disps as $id => $spec) {
				if ($spec['hd'][0] == $new_disp['hd'][0]) {
					$duplicate = true;
					break;
				}
			}
			if (!$duplicate) {
				$new_id = "id".(count($disps)+1);
				$disps[$new_id] = $new_disp;
				err("DEBUG:insert.php:".json_encode($disps));
				fwrite($fp, json_encode($disps));
			}
			flock($fp, LOCK_UN);
			fclose($fp);
			//
			//-------------- UNLOCKED -----------
			//
			$waiting = false;
		}
		else fclose($fp);
	}
	if ($waiting) sleep(rand(0, 2));
}
if ($waiting) {
	$msg = "Couldn't open displays database";
	echo $msg;
	die(1);
	trigger_error($msg, E_USER_ERROR);
}
if ($duplicate) {
	$msg = '"'.$new_disp['hd'][0].'" is already in the system, try changing things!';
	echo $msg;
	die(2);
	trigger_error($msg, E_USER_ERROR);
}

// Added the new display to the json file, so now let the user know
echo '"'.$new_disp['hd'][0].'" has been added to the list of displays';
//~ TODO: work out how to respond after errors etc
//~ Complete file writing code
//~ Thorough parameter checking before accepting
//~ Check for very similar or identical displays
?>
