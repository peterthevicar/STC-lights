<?php
//
//Format of json-disp-specs:
//
//  <display specs> ::= <id> <gradient> <segment> <fading> <sparkle> <spot>
//    <gradient> ::= <colour list> <repeats> <blend> <bar on> <bar off>
//  <segment> := <num segments> <motion> <duration> <reverse>
//    <num segments> ::= <int 0-8>
//    <motion> ::= <int LEFT, RIGHT, R2L1, STOP>
//    <duration> ::= <float 0+>
//    <reverse> ::= <int REPEAT, REVERSE>
//  <fading> ::= <duration> <blend> <fade min> <fade max>
//    <blend> ::= <int STEP, SMOOTH>
//    <fade min>, <fade max> ::== <int 0-255>
//  <sparkle> ::== <sparks per thousand> <duration>
//    <sparks per thousand> ::= <int 0-1000>
//  <spot> ::= <size> <colour> <motion> <duration> <reverse>
//    <size> ::= <int 0-32>

// Read in the json-displays file
$disp_specs=json_decode(file_get_contents("json-disp-specs.json"), true);
$disp_id=$_SERVER['QUERY_STRING'];
if ($disp_id == null) $disp_id = "d1";
$this_spec=$disp_specs[$disp_id];
$disps=json_decode(file_get_contents("json-displays.json"), true);
$this_disp=$disps[$disp_id];

// This funtion outputs all the elements for a labelled drop-down (select)
function select_input($select_label, $select_name, array $option) {
    global $disp_specs;
    // Header with label
    echo "<p>$select_label: <select name=\"$select_name\" id=\"$select_name\">\n";
    // Put in all the options
    foreach ($option as $option_value => $option_text) {
        // $sel will be set to 'selected' for the selected option
        $sel=($disp_specs[$select_name]==$option_value)?' selected':'';
        echo "  <option value=\"{$option_value}\"{$sel}>{$option_text}</option>\n";
    }
    // Finish off the select
    echo "</select></p>\n";
}

//file_put_contents("form.json", json_encode(array('var1'=>'val1', 'var2'=>'val2')));
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style type="text/css">
	th {color:blue; text-align:left}
	#curSel, .curSel {color:white; background-color:blue; font-size:100%} /* Selected row in table */
	.header {position: sticky; top: 0; background-color:white} /* will scroll to top of page then stop */
	.footer {position: sticky; bottom: 0; width: 100%} /* stays at foot of page with text scrolling behind */
	button {font-size:100%}
	.hidden {display: none}
	/* 
	@media (min-width: 858px) {
    html {
        font-size: 1em;
    }
    */
</style>
<script>
function doProcess(action) {
	var cursel;
	cursel = document.getElementById("curSel")
	if (cursel) {
		alert((action==1?"DISPLAY":"EDIT")+" "+cursel.children[1].innerText);
	}
}
</script>
</head>
<body>
	<div>
		
You have chosen to edit <?php print($this_disp[0] . " by " . $this_disp[1]); ?>
		
	</div>
	

</body>
</html>
