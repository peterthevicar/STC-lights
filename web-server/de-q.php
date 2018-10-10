<?php
// Get an exclusive lock on json-q
$fn = 'json-q.json';
$waiting = true;
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for exclusive access to the file
	$fp = fopen($fn, "c+"); // try to open file but don't truncate
	if ($fp) {
		if (flock($fp, LOCK_EX)) {
			$q = json_decode(file_get_contents($fn), true);
			if ($q == null) { // queue has broken, start again with id1
				$q = ['cur_id'=>'id1', 'next_t'=>time(), 'q'=>[]];
			}
			if (count($q['q']) == 0) { // nothing in the queue
				$q['next_t'] = time() + 5; // check back in 5 seconds
				$changed = false;
			}
			else { // have a queue, two elements per entry: id and duration
				$next_id = array_shift($q['q']);
				$next_t = time() + array_shift($q['q']);
				// Update the queue header with the new info
				$q['next_t'] = $next_t;
				$q['cur_id'] = $next_id;
			}				
			echo json_encode($q);
			file_put_contents($fn, json_encode($q));
			flock($fp, LOCK_UN);
			fclose($fp);
			$waiting = false;
		}
		else fclose($fp);
	}
	if ($waiting) sleep(rand(0, 2));
}
if ($waiting) trigger_error("Couldn't open queue", E_USER_ERROR);

//~ TODO
//~ Check a password in the cheader
?>
