<?php
//
//-------------------------- Error handler -------------------------//
//
// Handle errors ourselves. See https://www.w3schools.com/php/php_error.asp
function err($msg) {
	echo $msg;
	error_log("".date('Y.M.d H:i:s')." $msg\n", 3, "error-log.txt");
}
function customError($error_number, $error_string, $error_file, $error_line, $error_context) {
	err("***Error: [$error_number] $error_string");
	err("File:[$error_file] Line:[$error_line]");
	err(json_encode($error_context));
	err("Ending script");
	err("==========");
	header('HTTP/1.1 500');
	header('Content-Type: application/json; charset=UTF-8');
	die(json_encode(['message' => 'ERROR', 'code' => $error_number]));
	die(1);
}
function customExcept($e) {
	customError($e->getCode(), $e->getMessage(), $e->getFile(), 
		$e->getLine(), $e->getTraceAsString());
}
// Can handle two different levels of error: E_ALL or E_STRICT
set_error_handler("customError", E_ALL);
set_exception_handler("customExcept");
?>
