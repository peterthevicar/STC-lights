<?php
// Get the current status
// set up $status with all the status stuff,
// set up $lightson and $until with current timer settings
$status_file = 'j-status.json';
if (file_exists($status_file)) 
  try { $status = json_decode(file_get_contents($status_file), true); }
  catch (Exception $e) {
	  err('Problem with decoding j-status, resetting');
	  $status = '';
  }
else $status = '';
if ($status == '' or 
!array_key_exists('st',$status) or 
!array_key_exists('et',$status) or 
!array_key_exists('br',$status)) {
	$status = json_decode('{"on":"ON", "st":"16:00", "et":"01:00", "br":"128"}', true);
}
?>
