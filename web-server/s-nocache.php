<?php
// From https://stackoverflow.com/questions/49547/how-to-control-web-page-caching-across-all-browsers
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
?>
