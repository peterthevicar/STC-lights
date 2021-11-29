<?php
include "s-error-handler.php";
if ($_POST == null) err('ERR:put:3 POST[json]='.$_POST['json']);
$json=json_decode(strip_tags($_POST['json']), true);
$j_file = $json['fn'];
unset($json['fn']);
file_put_contents($j_file, json_encode($json));
?>