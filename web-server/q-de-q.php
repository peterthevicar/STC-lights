<?php
// Set up error handler and err function for logging errors
include "s-error-handler.php";
include "s-get-status.php";
//~ err('DEBUG:de-q:5 status='.json_encode($status));
include "s-check-lights-on.php";

// Touch heartbeat file
touch('ts-pulse');

// First handle situations where lights are off so no need to look in queue etc
if (!$lightson) {
	$cur_stat = $status['on'];
	if ($cur_stat == 'TIM') { // Timer is off, wait 30 or until lights are on again
		$add_day = ($until > time()? 0: 60*60*24); # in case 'on' time is tomorrow
		$durn = min(30, $until+$add_day - time());
		//~ err('DEBUG:de-q:19 until='.$until.' time='.time().' durn='.$durn.' add_day='.$add_day);
	}
	else $durn = ($cur_stat == 'OFF'? 30: 1);
	
	//------------------------ Lights are OFF return what sort (standby, reboot etc) in 'stat' field
	echo '{"id":"OFF","durn":'.strval($durn).',"stat":"'.$cur_stat.'"}';
	return;
}

//--------------- Lights are on ---------------
$durn = 5;
$from_q = false;
// Get an exclusive lock on json-q
$fn = 'j-q.json';
$waiting = true; // waiting for lock
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for exclusive access to the file
	$fp = fopen($fn, "c+"); // try to open file but don't truncate
	if ($fp) {
		if (flock($fp, LOCK_EX)) {
			//
			//----------------- EXCLUSIVE LOCK json-q --------------------
			//
			$q = json_decode(file_get_contents($fn), true);
			if ($q == null) { // queue has broken, start again with id1
				err('ERR:de-q:21 Broken queue');
				$q = ['cur_id'=>'id1', 'next_t'=>time(), 'mod'=>false, 'q'=>[]];
			}
			$q_conts = &$q['q']; // reference to the queue contents
			if (count($q_conts) == 0) { // nothing in the queue
				// Use remainder of time (or 5 seconds if we've finished)
				// Need this in case multiple systems are running
				$durn = ($q['next_t'] - time());
				if ($durn < 1) {
					$durn = 5;
					$q['next_t'] = time() + 5;
				}
				$next_id = $q['cur_id'];
			}
			else { // have a queue, two elements per entry: id and duration
				//~ err('DEBUG:de-q:52 q='.json_encode($q));
				$next_id = array_shift($q_conts);
				$durn = array_shift($q_conts);
				// Update the queue header with the new info
				$q['next_t'] = time() + $durn;
				$q['cur_id'] = $next_id;
				$from_q = true;
			}				
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
if ($waiting) trigger_error("ERR:de-q:48 Couldn't open queue", E_USER_ERROR);

// Read in the json-displays file, which may be locked by d-insert.php
$fn = 'j-displays.json';
$lock = ($from_q? LOCK_EX: LOCK_SH); // Only update stats when display first comes in from the queue

$waiting = true;
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for appropriate lock
	$fp = fopen($fn, ($lock==LOCK_EX? 'c+': 'r')); // c+ is open file but don't truncate
	if ($fp) {
		if (flock($fp, $lock)) {
			//
			//-------------- SHARE/EXCLUSIVE LOCKED json-displays -----------
			//
			$content = fread($fp, filesize($fn));
			$disps=json_decode($content, true);
			if ($lock == LOCK_EX) { // Exclusive lock, update stats
				//~ err('DEBUG:de-q:89 next='.$next_id);
				$disps[$next_id]['hd'][4] = time(); // last used date
				$disps[$next_id]['hd'][5]++; // use count
				file_put_contents($fn, json_encode($disps)); // write back modified file
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
if ($waiting) trigger_error("ERR:de-q:84 Couldn't open displays file", E_USER_ERROR);

// Add in the current id and next check-in time
$return = $disps[$next_id];
$return['id'] = $next_id;
$return['durn'] = $durn;
//~ err('DEBUG:de-q:111 status='.file_get_contents('j-status.json'));
$return['brled'] = $status['brled'];
$return['brdmx'] = $status['brdmx'];
$return['brmet'] = $status['brmet'];
// Return the info as a json string
echo json_encode($return);
?>
