<?php
// Set up error handler and err function for logging errors
include "s-error-handler.php";
include "s-get-status.php";
//~ err('DEBUG:de-q:5 status='.json_encode($status));
include "s-check-lights-on.php";

// Read the query string which is the identifier of the requesting computer
$pcid=(array_key_exists('QUERY_STRING',$_SERVER)? $_SERVER['QUERY_STRING']: '');
if ($pcid !== '') $pcid = '-'.$pcid;
// Touch heartbeat file for the requesting computer
touch('ts-pulse'.$pcid);

// First handle situations where lights are off so no need to look in queue etc
if (!$lightson) {
	$cur_stat = $status['on'];
	if ($cur_stat == 'TIM') { // Timer is off, wait until lights are on again
		$add_day = ($until > time()? 0: 60*60*24); # in case 'on' time is tomorrow
		$next_t = $until+$add_day;
		//~ err('DEBUG:de-q:19 until='.$until.' time='.time().' next_t='.$next_t.' add_day='.$add_day);
	}
	else $next_t = 0; // Leave it to the RPi to decide when to check back
	
	//------------------------ Lights are OFF return what sort (standby, reboot etc) in 'stat' field
	echo '{"id":"OFF","next_t":'.strval($next_t).',"stat":"'.$cur_stat.'"}';
	return;
}

//--------------- Lights are on ---------------
$next_t = 0;
$new_id = false;
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
				file_put_contents($fn, json_encode($q));
			}
			$q_conts = &$q['q']; // reference to the queue contents
			// How much time left for the current display?
			if ($q['next_t'] - time() < 1) { // time's up, see if anything's waiting in the queue
				if (count($q_conts) > 0) { // have a queue, two elements per entry: id and duration
					//~ err('DEBUG:de-q:52 q='.json_encode($q));
					$new_id = true; // Record that we're switching to a new id
					$next_id = array_shift($q_conts);
					$durn = array_shift($q_conts);
					// Update the queue header with the new info
					$q['next_t'] = time() + $durn;
					$q['cur_id'] = $next_id;
					file_put_contents($fn, json_encode($q));
				}
			}
			$next_id = $q['cur_id'];
			$next_t = $q['next_t'];
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
$lock = ($new_id? LOCK_EX: LOCK_SH); // Only update stats when a new display comes in from the queue

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
$return['next_t'] = $next_t;
//~ err('DEBUG:de-q:111 status='.file_get_contents('j-status.json'));
$return['brled'] = $status['brled'];
$return['brdmx'] = $status['brdmx'];
$return['brmet'] = $status['brmet'];
// Add in the dmx info
$j_file = "j-dmx-fll-Top.json";
include "s-get-json-nolock.php";
$return['dmx']['Top'] = $j_arr;
$j_file = "j-dmx-fll-Clock.json";
include "s-get-json-nolock.php";
$return['dmx']['Clock'] = $j_arr;
$j_file = "j-dmx-fll-Window.json";
include "s-get-json-nolock.php";
$return['dmx']['Window'] = $j_arr;
// Return the info as a json string
echo json_encode($return);
?>
