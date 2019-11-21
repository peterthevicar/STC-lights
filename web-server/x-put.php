<?php
include "s-error-handler.php";
if ($_POST == null) $_POST = ['json' => '{"R":"off","G":"on","B":"on","blink":"3","turn":"3"}'];
//~ err('DEBUG:put:4 POST[json]='.$_POST['json']);
file_put_contents("j-laser.json", strip_tags($_POST['json']));
?>