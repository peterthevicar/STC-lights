<!DOCTYPE html>
<?php
include "s-nocache.php";
// Format of json-displays
//
//::= <display details>*
//<display details> ::= <id> <header> <colour list> <gradient> <segment> <fading> <sparkle> <spot> <meteors>
//  <id> ::= id<int> [id]
//  <header> ::= <0 name> <1 creator> <2 pwd_hash> <3 created> <4 used> <5 uses> <6 version> [hd]
//    <uses> ::= <int>
//    <name>, <creator> ::= <str>
//    <created>, <used> ::= <timestamp>
//  <colour list> ::= <colour> <colour>* <colour> [co]
//  <gradient> ::= <repeats> <blend> <bar type> [gr]
//	  <bar type> :== <int OFF, DASH, DOT>
//  <segment> := <num segments> <alternate> <motion> <speed> <reverse> [se]
//    <num segments> ::= <int 0-8>
//    <alternate>, <reverse> ::= <int REPEAT, REVERSE>
//    <motion> ::= <int LEFT, RIGHT, R2L1, STOP>
//    <speed> ::= <int VSLOW..VFAST>
//  <fading> ::= <fade speed> <blend> <fade min> [fa]
//    <blend> ::= <int STEP, SMOOTH>
//    <fade min> ::== <int 0-255>
//  <sparkle> ::== <int NONE TOUCH NORMAL LOTS LOTSANDLOTS> [sk]
//  <spot> ::= <size> <colour> <motion> <speed> <reverse> [st]
//    <size> ::= <int 0-32>
//  <floods> ::= <flood spec> <flood spec> <flood spec> [fl]
//    <flood spec> ::=
//      [0,3,6] ::= <0-4> [off, fixed colour, slow auto sequence, medium auto, fast auto]
//      [1,4,7] ::= <0-361> [colour]
//		[2,5,8] ::= <0-3> [no strobe, slow, med, fast]
//  <meteor> ::= <int ON, OFF, AUTO>
		
include "s-error-handler.php";

// Read in the information we need from json file
$this_disp = null;
// The display id is passed in as the query string
$disp_id=(array_key_exists('QUERY_STRING',$_SERVER)?$_SERVER['QUERY_STRING']:'');
if ($disp_id == null) $disp_id = "id1"; // in case we don't have a query string
// Read the header information (name, creator etc)
$disps=json_decode(file_get_contents("j-displays.json"), true);
$this_disp=$disps[$disp_id];

// build a <select> with current value pre-selected
function build_select ($id, $i, $opts) {
	global $this_disp;
	// Retrieve the current value of the requested field
	$cur_val=$this_disp[$id][$i];
    // Header with label
    echo "<select id='$id$i' autocomplete='off'>\n";
    // Put in all the options
    foreach ($opts as $option_value => $option_text) {
        // $sel will be set to 'selected' for the selected option
        $sel=($cur_val==$option_value)?' selected':'';
        echo "  <option value=\"{$option_value}\"{$sel}>{$option_text}</option>\n";
    }
    // Finish off the select
    echo "</select>\n";
}
//build a numeric input with min and max values
function build_number ($id, $i, $min, $max, $step) {
	global $this_disp;
	// Retrieve the current value of the requested field
	$cur_val=$this_disp[$id][$i];
    // Header with label
    echo "<input id='$id$i' autocomplete='off' type='number' value='$cur_val' min='$min' max='$max' step='$step'>\n";
}
// build a colour selector
function build_colour($id, $i) {
	global $this_disp;
	// Retrieve the current colour
	$cur_val=$this_disp[$id][$i];
	echo "<input id='$id$i' class='color-input' type='color' autocomplete='off' style='border-width:2px; display:inline-block' value='$cur_val'>\n";
}
// build a text input box
function build_text($id, $i, $fill) {
	global $this_disp;
	// Retrieve the current content
	$cur_val=$fill? $this_disp[$id][$i]: "";
	echo "<input id='$id$i' type='text' autocomplete='off' style='border-width:2px; display:inline-block' value='$cur_val'>\n";
}
// build a password input box
function build_password($id, $i) {
	echo "<input id='$id$i' type='password' autocomplete='off' style='border-width:2px; display:inline-block' value=''>\n";
}

//build a gradient colour selector with the initial colours added
$last_colour=-1;
function build_colours () {
	global $this_disp, $last_colour;
	// Retrieve the current colour list
	$cur_val=$this_disp["co"];
    // Header with id
    echo "\n<div id='colour_list' style='display:inline-block'>\n";
    $grad_colours = "";
    foreach ($cur_val as $col) {
		$last_colour++;
		echo "<input id='c$last_colour' type='color' autocomplete='off' style='border-width:2px; display:inline-block' value='$col'>\n";
		$grad_colours .= ",$col"; 
	}
    echo "</div>\n";
    echo "<div style='display:block;margin-bottom:16px'>\n";
    echo "   <button style='display:inline-block' type='button' onclick='javascript:update_colours(last_colour_visible+1)'>Insert +</button>\n";
    echo "   <button style='display:inline-block' type='button' onclick='javascript:update_colours(last_colour_visible-1)'>- Remove</button>\n";
    echo "  </div>\n";
/*  Preview of blended gradient - would need to sort add/delete, blend/step etc
    echo "  <div id='grad' style='background-image: linear-gradient(to right" . $grad_colours . ");'>\n";
    echo "    <p>Preview of gradient</p></div>\n";
*/
}


// ----------------------------- END PHP -----------------------------//

?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="styles.css" type="text/css">
</head>
<body>
	<div>
		<h1>Create or Edit Display</h1>
		<p>You have chosen to create a new display based on 
		<?php print(htmlspecialchars('"'.$this_disp["hd"][0].'" by "'.$this_disp["hd"][1].'" (version '.$this_disp["hd"][6].') which has been chosen '.$this_disp['hd'][5].' times.')); ?>
		<p>Change anything you like below, then click on the <strong>CREATE</strong> button to create a new display with a new name, or the <strong>MODIFY</strong> button to change this display (you will need to know the display's password to do this).
	</div>

	<button class="collapsible">Choose your colours</button>
	<div class="content">
		<p>Colours to be used in the display. Note: iPhones don't all give nice colour selectors here. If you see #00FF00 etc, then try colour names such as 'Pink' instead and we'll do what we can. Or get an Android phone!
		<?php build_colours();?>
	</div>
	<button class="collapsible">Blend the colours into a gradient</button>
	<div class="content">
		<p>Blend the colours smoothly from one to another, or go in steps?
			<?php build_select("gr",1,["1"=>"Smooth", "2"=>"Steps"]);?>
		<p>How many times do you want to repeat this gradient in the pattern?
			<?php build_number("gr",0,1,8,1); ?>
		<p>Do you want to have black gaps in the gradient? Dashes means small gaps with lots of colour, Dots means bigger gaps and less colour.
			<?php build_select("gr",2,["0"=>"No gaps", "1"=>"Dashes", "2"=>"Dots"]);?>
	</div>
	
	<button class="collapsible">Get the colours moving</button>
	<div class="content">
		<p>Direction of movement: 
			<?php build_select("se",2,["0"=>"Stop", "2"=>"Left", "1"=>"Right", "3"=>"Two left, one right"]);?>
		<p>Speed of movement: 
			<?php build_select("se",3,["1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]);?>
		<p>When the pattern gets to the end, keep moving in the same direction or reverse and come back: 
			<?php build_select("se",4,["1"=>"Same direction", "2"=>"Reverse"]);?>
	</div>

	<button class="collapsible">Add a moving spot on top</button>
	<div class="content">
		<p>Size of the spot: 
			<?php build_select("st",0,["0"=>"No spot", "1"=>"Tiny", "2"=>"Small", "3"=>"Medium", "4"=>"Large", "5"=>"Huge"]);?>
		<p>Colour of the moving spot: 
			<?php build_colour("st",1); ?>
		<p>Direction of movement: 
			<?php build_select("st",2,["0"=>"Stop", "2"=>"Left", "1"=>"Right"]);?>
		<p>Speed of movement: 
			<?php build_select("st",3,["1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]);?>
		<p>When the spot gets to the end, keep moving in the same direction or reverse and come back: 
			<?php build_select("st",4,["1"=>"Same direction", "2"=>"Reverse"]);?>
	</div>

	<button class="collapsible">Repeat the pattern</button>
	<div class="content">
		<p>Number of times to repeat the pattern along the string of lights. The more repeats you have, the smaller each segment will be. If you choose 0, all the lights will be the same colour, changing together: 
			<?php build_number("se",0,0,16,1); ?>
		<p>All repeats of the pattern should be the same way round, or first one way then the other: 
			<?php build_select("se",1,["1"=>"All the same", "2"=>"Alternate"]);?>
	</div>
	
	<button class="collapsible">Add a bit of sparkle</button>
	<div class="content">
		<p>How much sparkle: 
			<?php build_select("sk",0,["0"=>"No sparkle", "1"=>"Just a touch", "2"=>"Normal", "3"=>"Lots", "4"=>"Lots and lots"]);?>
	</div>
	
	<button class="collapsible">Make the whole display fade up and down</button>
	<div class="content">
		<p>Speed of fading: 
			<?php build_select("fa",0,["0"=>"None", "1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]);?>
		<p>Fading should be smooth, or step from dim to bright:
			<?php build_select("fa",1,["1"=>"Smooth", "2"=>"Step"]);?>
		<p>How much to fade:
			<?php build_select("fa",2,["1"=>"Subtle", "2"=>"Normal", "3"=>"Maximum"]);?>
	</div>
	
	<button class="collapsible">Coloured floodlights</button>
	<div class="content">
		<p>Top floodlights
			<?php build_select("fl",0,["0"=>"OFF", "1"=>"Fixed colour", "2"=>"Slow change", "3"=>"Med change", "4"=>"Fast change"]);?>
		<p>If you chose to have a fixed colour, set the colour here.
			<?php build_colour("fl",1);?>
		<p>Do you want the Top floodlights to flash on and off (strobe)?: 
			<?php build_select("fl",2,["0"=>"No strobing", "1"=>"Very slow", "2"=>"Slow", "3"=>"Med", "4"=>"Fast"]);?>
<!--			
		<p>Clock floodlights
			<?php build_select("fl",3,["0"=>"OFF", "1"=>"Fixed colour", "2"=>"Slow change", "3"=>"Med change", "4"=>"Fast change"]);?>
		<p>If you chose to have a fixed colour, set the colour here.
			<?php build_colour("fl",4);?>
		<p>Do you want the Clock floodlights to flash on and off (strobe)?: 
			<?php build_select("fl",5,["0"=>"No strobing", "1"=>"Very slow", "2"=>"Slow", "3"=>"Med", "4"=>"Fast"]);?>
			
		<p>Window floodlights
			<?php build_select("fl",6,["0"=>"OFF", "1"=>"Fixed colour", "2"=>"Slow change", "3"=>"Med change", "4"=>"Fast change"]);?>
		<p>If you chose to have a fixed colour, set the colour here.
			<?php build_colour("fl",7);?>
			<p>Do you want the Window floodlights to flash on and off (strobe)?: 
			<?php build_select("fl",8,["0"=>"No strobing", "1"=>"Very slow", "2"=>"Slow", "3"=>"Med", "4"=>"Fast"]);?>
-->
	</div>
<!--
	<button class="collapsible">Lasers</button>
	<div class="content">
		<p>Which lasers would you like turned on?
			<?php build_select("la",0,["0"=>"Red off", "255"=>"Red ON"]);?>
			<?php build_select("la",1,["0"=>"Green off", "255"=>"Green ON"]);?>
			<?php build_select("la",2,["0"=>"Blue off", "255"=>"Blue ON"]);?>
        <p>Rotate the lasers?
   			<?php build_select("la",3,["1"=>"Fast left", "3"=>"Medium Left", "4"=>"Slow Left", "5"=>"Stop", "7"=>"Slow Right", "8"=>"Med Right", "10"=>"Fast Right"]);?>
        <p>Flash the lasers?
   			<?php build_select("la",4,["0"=>"Stop", "1"=>"Slow", "2"=>"Med", "3"=>"Fast", "4"=>"Strobe"]);?>
	</div>
-->
	
<!--
	<button class="collapsible">Meteor shower lights</button>
	<div class="content">
		<p>Meteor shower effect (tubes with 'falling' white lights):
			<?php build_select("me",0,["0"=>"No Meteors", "1"=>"Meteors ON"]);?>
	</div>
-->
	
	<button class="collapsible active">Now personalise your creation</button>
	<div class="content" style="display:block">
		<p>Your username (e.g. 'Ms Design'. This will be shown in the list of displays as the creator of this display): <br>
			<?php build_text("hd",1, false);?>
		<p>A unique and descriptive name for your display (e.g. 'Pink and sparkly'): <br>
			<?php build_text("hd",0, true);?>
        <p><strong>*Password</strong> for this display so you can change it later (don't use a precious password, this is not a secure link): <br>
			<?php build_password("hd",2);?>
	</div>

	<div>
		<button class='go-button' type="button" onclick="create_new(2);" style="background-color:orange">MODIFY this display</button>
		<button class='go-button' type="button" onclick="create_new(1);" style="background-color:green">CREATE new display</button>
		<button class='go-button' type="button" onclick="reset_all();" style="background-color:gray">RESET all values</button>
		<button class='go-button' type="button" onclick="location.href = 'd-choose.php?<?php echo $disp_id;?>';" style="background-color:red">CANCEL</button>
	</div>
	<div style='margin-bottom:50px'></div>
	<div class="footer">
		<div class="footer-text">
			Christmas in Bicester 9-11th December 2022
		</div>
	</div>

<script>
	//
	//-------------- onClick for Collapsible sections ----------------//
	//
	var coll = document.getElementsByClassName("collapsible");
	var i;

	for (i = 0; i < coll.length; i++) {
		coll[i].addEventListener("click", function() {
			this.classList.toggle("active");
			var content = this.nextElementSibling;
			if (content.style.display === "block") {
			  content.style.display = "none";
			} else {
			  content.style.display = "block";
			  //~ content.scrollIntoView(false);
			  //~ content.scrollIntoViewIfNeeded(true);
			}
		});
	}
</script>	
<script>
	//
	//--------------------- Expanding colour list --------------------//
	//
	// Store original number of colours to spot changes
	last_colour_0=<?php echo $last_colour;?>
	// Global counts of how many colour elements we have and how many are currently visible
	last_colour_created=<?php echo $last_colour;?>;
	last_colour_visible=<?php echo $last_colour;?>;
	function update_colours(last) {
		if (last < 1) return;
		// Make sure there are last-1 colour choosers visible
		for (i=last_colour_created+1; i<=last; i++) { // create inputs as required
			var e = document.createElement("input");
			e = document.getElementById('colour_list').appendChild(e);
			e.id = 'c'+i;
			e.type = 'color';
			e.style = 'border-width:2px';
			last_colour_created=i;
		}
		for (i=0; i<=last; i++) { // make sure they're visible
			document.getElementById('c'+i).style.display='inline-block'
		}
		for (i=last_colour_visible; i>last; i--) { // hide others that were visible
			document.getElementById('c'+i).style.display='none'
		}
		last_colour_visible = last;
	}
</script>
<script>
	//
	//--------------------- Reset all values to original -------------//
	//
	function reset_all () {
		var coll = document.getElementsByTagName("input");
		var i;

		for (i = 0; i < coll.length; i++) {
		  coll[i].value = coll[i].getAttribute("value");
	  }
	}
</script>	
<script>
	function seconds(){ return Math.floor( Date.now() / 1000 ); }
	//
	//--------------------- Create new display spec -----------------//
	//
	// create_new goes through the elements in the original display spec
	// and for each one looks in the DOM for a matching element.
	// If it finds one it puts the new value into new_disp ready for 
	// creating a display with the new spec.
	
	// Insanely clever function from https://stackoverflow.com/questions/1573053/javascript-function-to-convert-color-names-to-hex-codes
	// Understands red, #ff0000, #f00, rgb(255,0,0) etc and returns #FF0000
	function standardise_colour(str){
		var ctx = document.createElement('canvas').getContext('2d'); 
		ctx.fillStyle = str;
		return ctx.fillStyle;
	}
	var original_disp = JSON.parse('<?php echo json_encode($this_disp);?>');
	
	function create_new ($action) {
		var changed = false;
		var new_disp = {};
		// Go through each section in the original disp
		for (section_id in original_disp) {
			var orig_sect = original_disp[section_id];
			var i, e;
			new_disp[section_id] = [];
			// The header needs to be filled in specially
			if (section_id == 'hd') {
				new_disp['hd'][0] = document.getElementById('hd0').value;
				new_disp['hd'][1] = document.getElementById('hd1').value;
				new_disp['hd'][2] = document.getElementById('hd2').value;
				new_disp['hd'][3] = seconds(); // Creation date
				new_disp['hd'][4] = 0; // Last used
				new_disp['hd'][5] = 0; // Uses
				new_disp['hd'][6] = 1; // Version number
				// Basic sanity checking
				if (new_disp['hd'][2] == '') {
					alert("You must specify a password");
					return;
				}
				if (new_disp['hd'][0] == orig_sect[0]) {
					if ($action == 1) { // Create new display
						alert("You can't use the same name for a new display");
						return;
					}
					else { // Modify existing display
						if (new_disp['hd'][1] != orig_sect[1]) {
							alert("You can't change either name when modifying a display");
							return;
						}
						new_disp['hd'][6] = orig_sect[6]; // Version number
						//~ alert('DEBUG:create:367 orig6='+orig_sect[6]);
					}
				}

			}
			else if (section_id == "co") { // The colour list is variable length
				for (i=0; i<=last_colour_visible; i++) {
					var cv = standardise_colour(document.getElementById("c"+i).value);
					new_disp[section_id][i] = cv;
					if (i >= orig_sect.len || orig_sect[i] != cv)
						changed = true;
				}
			}
			else {
				// Go through each element in this section
				for (i in orig_sect) {
					// We've labeled the elements with section id and index
					id = section_id + i;
					e = document.getElementById(id);
					if (e == null) {
						// nothing there so copy the original
						new_disp[section_id][i] = orig_sect[i];
					}
					else {
						// found a matching element so get its value
						new_disp[section_id][i] = (id=='st1' || id=='fl1' || id=='fl4' || id=='fl7'? standardise_colour(e.value): e.value);
						if (orig_sect[i] != e.value)
							changed = true;
					}
				}
			}
		}
		if (!changed) {
			alert('You haven\'t changed anything!');
		}
		else {
			//~ alert("Original display spec was\n" + JSON.stringify(original_disp) + "\nNew display spec is\n" + JSON.stringify(new_disp));

//------------------------ Send the json to create a new display -------------------
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function () {
				if (this.readyState != 4) return;
				// Do something with the retrieved data ( found in .responseText )
				resp = JSON.parse(this.responseText, true);
				alert (resp.msg);
				if (resp.err == 0) location.href = 'd-choose.php?'+resp.id;
			};
			xhr.open("POST", "d-insert.php", true);
			// can't get application/json to work so have to use form encoding
			xhr.setRequestHeader('Content-Type', "application/x-www-form-urlencoded");
			//xhr.setRequestHeader('Content-type', 'application/json');

			// send the collected data as JSON
			xhr.send('json='+JSON.stringify(new_disp));
		}
	}
</script>	
<script>
//
//
//TODO
//
// Reset colours to original values
// Preview of pattern, done some of this but it's complex, probably remove to a separate file
// Complete the editing collapsibles to cover all the parameters
// Check return from d-insert.php
// Process dots and dashes in "gr" section
// Check for unique name, check for identical values
// Most selects and number inputs: move to generic qualitative statments
//   to interpret in the animator
//
</script>

</body>
</html>
