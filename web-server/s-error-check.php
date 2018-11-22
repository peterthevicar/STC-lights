<?php
include "s-nocache.php";
// Set up error handler and err function for logging errors
include "s-error-handler.php";
touch('ts-error-check');
?>
<html>
	<body>
		<div id="errorlog">
			<?php echo nl2br(file_get_contents('error-log.txt')); ?>
		</div>
	</body>
	<script>
		document.getElementById("errorlog").scrollIntoView(false);
	</script>
</html>
