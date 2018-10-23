<?php
if ($status['on'] == 'TIM') {
	// Calculate whether it's on or off according to the timer
	$st = strtotime($status['st']);
	$et = strtotime($status['et']);
	if ($st < $et) { //Normal case: start time before off time
		$lightson = (time() >= $st and time() < $et);
	}
	else {// end time before start time means off time is after midnight so logic is reversed
		$lightson = !(time() >= $et and time() < $st);
	}
	$until = ($lightson? $et: $st);
}
else {
	$lightson = ($status['on'] == 'ON');
	$until = 0;
}
//~ err("DEBUG:check:19 lightson=".($lightson?'true':'false'));
?>
