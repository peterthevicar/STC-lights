<?php
// Return in $t the time since the ts-pulse file was updated (touched by q-de-q)
// Read the query string which is the identifier of the requesting computer
$pcid=(array_key_exists('QUERY_STRING',$_SERVER)? $_SERVER['QUERY_STRING']: '');
if ($pcid !== '') $pcid = '-'.$pcid;
// Find modification time for the corresponding ts-pulse file
$t = filemtime('ts-pulse'.$pcid); $d = time()-$t; echo ($d); 
?>
