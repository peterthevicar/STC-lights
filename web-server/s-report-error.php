<?php
include "s-error-handler.php";
$msg=(array_key_exists('QUERY_STRING',$_SERVER)?$_SERVER['QUERY_STRING']:'');
err('ERR:report-error:4 '.html_entity_decode(urldecode($msg)));
?>
