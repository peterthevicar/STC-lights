<?php
// Set up error handler and err function for logging errors
include "error-handler.php";

// Get an exclusive lock on json-q
$fn = 'json-q.json';
$waiting = true; // waiting for lock
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for exclusive access to the file
	$fp = fopen($fn, "c+"); // try to open file but don't truncate
	if ($fp) {
		if (flock($fp, LOCK_EX)) {
			//
			//----------------- EXCLUSIVE LOCK --------------------
			//
			$q = json_decode(file_get_contents($fn), true);
			if ($q == null) { // queue has broken, start again with id1
				$q = ['cur_id'=>'id1', 'next_t'=>time(), 'q'=>[]];
			}
			$q_conts = &$q['q']; // reference to the queue contents
			if (count($q_conts) == 0) { // nothing in the queue
				$next_t = time() + 5; // check back in 5 seconds
				$q['next_t'] = $next_t;
				$next_id = $q['cur_id'];
				$from_q = false;
			}
			else { // have a queue, two elements per entry: id and duration
				$next_id = array_shift($q_conts);
				$next_t = time() + array_shift($q_conts);
				// Update the queue header with the new info
				$q['next_t'] = $next_t;
				$q['cur_id'] = $next_id;
				$from_q = true;
			}				
			//~ echo "\nDEBUG, Queue contents:\n".json_encode($q)."\n";
			file_put_contents($fn, json_encode($q));
			flock($fp, LOCK_UN);
			fclose($fp);
			//
			//----------------- UNLOCK --------------------
			//
			$waiting = false;
		}
		else fclose($fp);
	}
	if ($waiting) sleep(rand(0, 2));
}
if ($waiting) trigger_error("Couldn't open queue", E_USER_ERROR);

// Read in the json-displays file, which may be locked by insert.php
$fn = 'json-displays.json';
$fp = fopen($fn, 'r');
if($fp != null and flock($fp, LOCK_SH)){ // wait until any write lock is released
	//
	//-------------- SHARE LOCKED -----------
	//
    $content = fread($fp, filesize($fn));
    $disps=json_decode($content, true);
    flock($fp, LOCK_UN);
    fclose($fp);
	//
	//-------------- UNLOCKED -----------
	//
}
// Add in the current id and next check-in time
$return = $disps[$next_id];
$return['id'] = $next_id;
$return['durn'] = $next_t-time();
//~ err('DEBUG:de-q:68 status='.file_get_contents('json-status.json'));
$return['br'] = json_decode(file_get_contents('json-status.json'), true)['br'];
$return['fq'] = ($from_q? '1': '0');
// Return the info as a json string
echo json_encode($return);
//~ TODO
//~ Check a password in the header
?>
