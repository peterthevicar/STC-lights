<?php
// Read the header information
?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
	Here's what I got: 
	<?php 
		$new_disp=json_decode($_POST['json']);
		$fn = 'json-displays.json';
		$fp = fopen($fn, "a+") or die("Unable to open file $fn!");
		for ($i=0; $i<3; $i++) {
			if (flock($fp, LOCK_EX)) {
				$disps = json_decode(file_get_contents($fn), true);
				$new_id = "id".(count($disps)+1);
				$disps[$new_id] = $new_disp;
				echo json_encode($disps);
				//fwrite($json_displays, $_POST['json']);
				//fclose($json_displays);				
				flock($fp, LOCK_UN);
			}
			else sleep(1);
		}
	?>
</body>
</html>
