<?php
// Set up error handler and err function for logging errors
include "s-error-handler.php";

// Check if the system is running
include "s-get-status.php";
include "s-check-lights-on.php";
if (! $lightson)
	trigger_error("ERR:en-q:6 Lights not on until $until", E_USER_ERROR);
	
// Read the header information
if ($_POST == null) $_POST = ["next_id"=>"id1"];
$next_id = $_POST['next_id'];
//~ err('DEBUG:en-q:14 POST='.json_encode($_POST).' next_id='.$next_id);
// Get an exclusive lock on json-q
$fn = 'j-q.json';
$waiting = true;
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for exclusive access to the file
	$fp = fopen($fn, "c+"); // try to open file but don't truncate
	if ($fp) {
		if (flock($fp, LOCK_EX)) {
			//
			//--------------- EXCLUSIVE LOCKED json-q -----------
			//
			$q = json_decode(file_get_contents($fn), true);
			if ($q == null) { // queue has broken, start again with id1
				$q = ['cur_id'=>'id1', 'next_t'=>time(), 'q'=>[]];
			}
			//~ err("DEBUG:en-q:29 q=".json_encode($q));
			// Add this id to the end of the queue
			$q_conts = &$q['q'];
			$q_end = count($q_conts);
			// Check if it's already in the queue and add up the queue time
			$matched = false;
			if ($q['cur_id']==$next_id) { // it's the current one
				$matched=true;
			}
			$q_wait = ($matched? 0: max($q['next_t'] - time(), 0)); // how much left for this one
			for ($i=0; $i<$q_end and !$matched; $i+=2) {
				if ($q_conts[$i] == $next_id) $matched = true;
				else $q_wait += $q_conts[$i+1];
			}
			if (!$matched) { // Add the new id in at the end of the queue
				$q_conts[$q_end++] = $next_id;
				$q_conts[$q_end] = 10; // default duration is 10
			}
			file_put_contents($fn, json_encode($q));
			flock($fp, LOCK_UN);
			fclose($fp);
			//
			//---------------- UNLOCKED ----------------
			//
			echo "$q_wait";
			//~ err("DEBUG:en-q:51 q=".json_encode($q));
			$waiting = false;
		}
		else fclose($fp);
	}
	if ($waiting) sleep(rand(0, 2));
}
if ($waiting) 
	trigger_error("ERR:en-q:54 Couldn't open queue", E_USER_ERROR);

//~ TODO
?>
