<?php
// Read the query string which is the identifier of the requesting computer
$pcid=(array_key_exists('QUERY_STRING',$_SERVER)? '-'.$_SERVER['QUERY_STRING']: '');
$t = filemtime('ts-pulse'.$pcid); $d = time()-$t; echo ($d); 
?>
