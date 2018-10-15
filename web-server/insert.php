<?php
// Set up error handler and err function for logging errors
include "error-handler.php";

//
//------------------ Code to insert new display spec -----------------//
//
// Read the header information (a json with the spec for the new display)
if ($_POST == null) $_POST = ['json' => '{"hd":["4 colours","Peter","fred",1539552196,0,0],"co":["#0000ff","#00ff00","#ff0000","#000000"],"gr":["1","2","0"],"se":["2","1","2","5","2"],"fa":["0","2","1"],"sk":["0",8.3],"st":["0","#000000","1","1","1"],"fl":["0","#000000","#ffffff","1","3.0"],"me":["0"]}'];

$new_disp = json_decode($_POST['json'], true);
//~ err('DEBUG:insert:12 POST=', json_encode($_POST));
//~ err('DEBUG:insert:13 new=', json_encode($new_disp));
// Hash the plain text password for comparison - very basic security
$new_disp['hd'][2] = substr(hash("md5",$new_disp['hd'][2]),4,8);

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
			$new_id = null;
			// See if it's a duplicate - nothing clever as we're holding a lock, 
			// but foil a simple bot
			foreach ($disps as $id => $spec) {
				//~ err('DEBUG:insert:33:spec='.json_encode($spec['hd']).' new='.json_encode($new_disp['hd']));
				// See if the name is already in the list
				if ($spec['hd'][0] == $new_disp['hd'][0]) {
					// Names are the same, check create date to spot duplicate/automated requests
					if ($spec['hd'][3] == $new_disp['hd'][3]) {
						$duplicate = true;
						break;
					}
					else if ($spec['hd'][1] == $new_disp['hd'][1]
					and $spec['hd'][2] == $new_disp['hd'][2]) {
						// We are modifying an existing display
						$new_id = $id;
					}
					else {
						// Creator name or password don't match
						$duplicate = true;
						break;
					}
				}
			}
			if (!$duplicate) {
				if ($new_id == null) $new_id = "id".(count($disps)+1);
				$disps[$new_id] = $new_disp;
				//~ err("DEBUG:insert:56 disps=".json_encode($disps));
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
	$msg = 'Display "'.$new_disp['hd'][0].'" is already in the list. (Or your password didn\'t match)';
	echo $msg;
	die(2);
	trigger_error($msg, E_USER_ERROR);
}

// Added the new display to the json file, so now let the user know
echo '"'.$new_disp['hd'][0].'" has been added to the list of displays. (Or modified)';
//~ TODO: work out how to respond after errors etc
//~ Complete file writing code
//~ Thorough parameter checking before accepting
//~ Check for very similar or identical displays
?>
