<?php
//~ include "s-error-handler.php";
// Try to read the j-dmx.json into $dmx
// If it fails set up something reasonable
$dmx_file = 'j-dmx.json';
if (file_exists($dmx_file)) 
  try { $dmx = json_decode(file_get_contents($dmx_file), true); }
  catch (Exception $e) {
	  $dmx = '';
  }
else $dmx = '';
if ($dmx == '' or !array_key_exists("dmx_ts",$dmx)) {
	$dmx = json_decode('{"dmx_ts":"0","R":"off","G":"on","B":"on","blink":"3","turn":"3"}', true);
	err('ERR:get-dmx:13 Missing or malformed j-dmx file, reset to default.');
}
?>