<?php
$req=$_SERVER['QUERY_STRING'];
$status_file = 'sysctl-status.txt';
file_put_contents($status_file, $req);

if ($req == 'off') {
}
else if ($req == 'sta') {
}
else if ($req == 'cou') {
}
else if ($req == 'on') {
}

?>
<html>
	<head>
		<style>
			button {
				display: block;
				width: 200px;
				font-size: 15pt;
				border-radius: 10px;
				padding: 32px;
			}
		</style>
	</head>
	<body>
		<p>System is currently: <?php if (is_file($on_file)) echo 'ON'; else echo 'OFF'; ?>
		<button type=button style="background-color:red" onclick="do_button('off')">OFF</button>
		<button type=button style="background-color:orange" onclick="do_button('sta')">STANDBY</button>
		<button type=button style="background-color:cyan" onclick="do_button('cou')">Countdown</button>
		<button type=button style="background-color:green" onclick="do_button('on')">ON</button>
		
		<script>
			function do_button(id) {
				location.href = 'sysctl.php?'+id; // Call this file again with the button id
			}
		</script>
	</body>
</html>
