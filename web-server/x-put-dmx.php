<?php
include "s-error-handler.php";
if ($_POST == null) $_POST = ['json' => '{"dmx_ts":1,"R":"off","G":"on","B":"on","blink":"3","turn":"3"}'];
// Add time stamp so we know when the last change was made
$_POST['json'] = preg_replace("/[0-9]+/", time(), $_POST['json'], 1);
err('DEBUG:put:6 POST[json]='.$_POST['json']);
file_put_contents("j-dmx.json", strip_tags($_POST['json']));
?>