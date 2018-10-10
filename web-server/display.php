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
			$q = json_decode(file_get_contents($fn), true);
			if ($q == null) { // queue has broken, start again with id1
				$q = ['cur_id'=>'id1', 'next_t'=>time(), 'q'=>[]];
			}
			// Add this id to the end of the queue
			$q_end = count($q['q']);
			$q['q'][$q_end++] = $next_id;
			$q['q'][$q_end] = 10; // default duration is 10

			echo json_encode($q);
			fwrite($fp, json_encode($q));
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
//~ Check for duplicates before inserting into the queue
?>
