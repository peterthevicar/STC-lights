<?php
// Read the header information
if ($_POST == null) $next_id = "id1";
else $next_id = $_POST['id'];

// Get an exclusive lock on json-q
$fn = 'json-q.json';
$waiting = true;
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for exclusive access to the file
	$fp = fopen($fn, "c+"); // try to open file but don't truncate
	if ($fp) {
		if (flock($fp, LOCK_EX)) {
			//
			//--------------- EXCLUSIVE LOCKED -----------
			//
			$q = json_decode(file_get_contents($fn), true);
			print_r($q);
			if ($q == null) { // queue has broken, start again with id1
				$q = ['cur_id'=>'id1', 'next_t'=>time(), 'q'=>[]];
			}
			print_r($q);
			// Add this id to the end of the queue
			$queue = &$q['q'];
			$q_end = count($queue);
			// Check if it's already in the queue and add up the queue time
			$q_wait = max($q['next_t'] - time(), 0); // how much left for this one
			$matched = false;
			for ($i=0; $i<$q_end and !$matched; $i+=2) {
				if ($queue[$i] == $next_id) $matched = true;
				else $q_wait += $queue[i+1];
			}
			if (!$matched) { // Add the new id in at the end of the queue
				$queue[$q_end++] = $next_id;
				$queue[$q_end] = 10; // default duration is 10
			}
			echo "\nQueue wait:$q_wait\n";
			echo json_encode($q);
			file_put_contents($fn, json_encode($q));
			flock($fp, LOCK_UN);
			fclose($fp);
			//
			//---------------- UNLOCKED ----------------
			//
			$waiting = false;
		}
		else fclose($fp);
	}
	if ($waiting) sleep(rand(0, 2));
}
if ($waiting) trigger_error("Couldn't open queue", E_USER_ERROR);
//~ TODO
//~ Check for duplicates before inserting into the queue
?>
