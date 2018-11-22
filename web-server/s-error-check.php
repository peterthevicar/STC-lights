<?php
include "s-nocache.php";
// Set up error handler and err function for logging errors
include "s-error-handler.php";
touch('ts-error-check');
?>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
		.header { /* will scroll to top of page then stop */	
			position: sticky; top: 0;
			background-color:rgb(192, 159, 128);
			color:white;
			padding: 15px 15px 15px 5px;
			border: none;
			text-align: left;
			outline: none;

		}
		</style>
	</head>
	<body>
		<div class="header">
			error-log.txt
		</div>
		<div id="errorlog">
			<?php echo nl2br(file_get_contents('error-log.txt')); ?>
		</div>
	</body>
	<script>
		document.getElementById("errorlog").scrollIntoView(false);
	</script>
</html>
