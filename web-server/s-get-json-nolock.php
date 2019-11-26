<?php
//~ include "s-error-handler.php";
// Try to read the file in $j_file into $j_arr
// If it fails set $j_arr to array()

if (file_exists($j_file)) 
  try { $j_arr = json_decode(file_get_contents($j_file), true); }
  catch (Exception $e) {
	  $j_arr = array();
  }
else $j_arr = array();
?>