<?php
include "s-error-handler.php";

$jf = 'j-displays.json';
$backdir = 'jd-backups/';

$displays = '';
if (file_exists($jf)) {
	try {
		$jd_conts = file_get_contents($jf);
		$displays = json_decode($jd_conts, true); 
	}
	catch (Exception $e) {
		err('ERR:s-jd-check:9 Problem with decoding j-displays, reverting');
	}
} else err('ERR:s-jd-check:11 j-displays missing, reverting');

// Find the latest backup
$bf = $backdir.scandir($backdir, 1)[0];
print_r($bf);
if (file_exists($bf)) {
	try {
		err('DEBUG:s-jd-check:23 Reading backup '.$bf);
		$bf_conts = file_get_contents($bf);
		}
	catch (Exception $e) {
		err('ERR:s-jd-check:27 Failed to retrieve backup of j-displays');
	}
}
else err ('ERR:s-jd-check:30 No backup of j-displays');

if ($displays == '' or 
!array_key_exists('COU',$displays) or 
!array_key_exists('id1',$displays) or 
!array_key_exists('id2',$displays)) {
	// Something wrong, re-write from the backup
	try {
		file_put_contents($jf, $bf_conts);
	}
	catch (Exception $e) {
		err('ERR:s-jd-check:41 Failed to write from backup');
	}
} else { // all is well, make a backup if anything's changed
	if ($jd_conts != $bf_conts)
		file_put_contents($backdir.date('Ymd-his-').$jf, json_encode($displays));
	else err('DEBUG:s-jd-check:46 No change, no backup made');
}	

?>
