<?php
//
//-------------------------- Error handler -------------------------//
//
// Handle errors ourselves. See https://www.w3schools.com/php/php_error.asp
function customError($error_number, $error_string, $error_file, $error_line, $error_context) {
  echo "\n\n***Error: [$error_number] $error_string\n";
  echo date("Y-m-d H:i:s")."\nFile: [$error_file]\n".
	"    Line: [$error_line]\n".
	var_export($error_context, true)."\n==========\n",
	3, "error_log.txt";
  echo "Ending Script";
  die(1);
}
function customExcept($e) {
  $error_number = $e->getCode();
  $error_string = $e->getMessage();
  $error_file = $e->getFile();
  $error_line = $e->getLine();
  echo "\n\n***Exception: [$error_number] $error_string\n";
  echo date("Y-m-d H:i:s")."\nFile: [$error_file]\n".
	"    Line: [$error_line]\n".
	$e->getTraceAsString()."\n==========\n",
	3, "error_log.txt";
  echo "Ending Script";
  die(1);
}
// Can handle two different levels of error: E_ALL or E_STRICT
set_error_handler("customError", E_STRICT);
set_exception_handler("customExcept");
// To use this on purpose, use 
// trigger_error($message, $level);
// $level must be E_USER_WARNING, E_USER_ERROR, or E_USER_WARNING
?>

<?php
//
//------------------ Code to insert new display spec -----------------//
//
// Read the header information (a json with the spec for the new display)
if ($_POST == null) $new_disp = json_decode(
	['json' => '{"hd":["Newest","Peter",11244,1243124,10],"co":["#0000ff","#00ff00","#ff0000"],"gr":[1,2,0,0],"se":["2","2","5","2"],"fa":[5.2,2,0,255],"sk":[10,8.3],"st":[4,16777215,1,3.5,1]}']['json'], true);
else $new_disp = json_decode($_POST['json'], true);

// Get an exclusive lock on json-displays
$fn = 'json-displays.json';
$waiting = true;
for ($i=1; $waiting and $i<=3; $i++) { // try 3 times for exclusive access to the file
	$fp = fopen($fn, "c+"); // try to open file but don't truncate
	if ($fp) {
		if (flock($fp, LOCK_EX)) {
			$disps = json_decode(file_get_contents($fn), true);
			$new_id = "id".(count($disps)+1);
			$disps[$new_id] = $new_disp;
			echo json_encode($disps);
			fwrite($fp, json_encode($disps));
			fclose($fp);
			flock($fp, LOCK_UN);
			$waiting = false;
		}
		else fclose($fp);
	}
	if ($waiting) sleep(rand(0, 2));
}
if ($waiting) trigger_error("Couldn't open displays database", E_USER_ERROR);

// Added the new display to the json file, so now let the user know
echo "\n\nYippeeeeee!!\n\n";
//~ TODO: work out how to respond after errors etc, die is not ideal
//~ Complete file writing code
//~ Thorough parameter checking before accepting
//~ Check for very similar or identical displays
?>
