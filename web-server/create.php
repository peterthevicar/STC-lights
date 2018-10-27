<!DOCTYPE html>
<?php
// Format of json-displays
//
//::= <display details>*
//<display details> ::= <id> <header> <colour list> <gradient> <segment> <fading> <sparkle> <spot> <meteors>
//  <id> ::= id<int>
//  <header> ::= <0 name> <1 creator> <2 pwd_hash> <3 created> <4 used> <5 uses>
//    <uses> ::= <int>
//    <name>, <creator> ::= <str>
//    <created>, <used> ::= <timestamp>
//  <colour list> ::= <colour> <colour>* <colour>
//  <gradient> ::= <repeats> <blend> <bar type>
//	  <bar type> :== <int OFF, DASH, DOT>
//  <segment> := <num segments> <alternate> <motion> <speed> <reverse>
//    <num segments> ::= <int 0-8>
//    <alternate>, <reverse> ::= <int REPEAT, REVERSE>
//    <motion> ::= <int LEFT, RIGHT, R2L1, STOP>
//    <speed> ::= <int VSLOW..VFAST>
//  <fading> ::= <fade speed> <blend> <fade min>
//    <blend> ::= <int STEP, SMOOTH>
//    <fade min> ::== <int 0-255>
//  <sparkle> ::== <sparks per thousand> <speed>
//    <sparks per thousand> ::= <int 0-1000>
//  <spot> ::= <size> <colour> <motion> <speed> <reverse>
//    <size> ::= <int 0-32>
//  <floods> ::= <flood spec> <flood spec>
//    <flood spec> ::= <int OFF, AUTO, IND_SAME, IND_ALT> <flood def>
//      <flood def> ::= <colour> <colour> <blend> <speed>
//  <meteor> ::= <int ON, OFF, AUTO>

include "error-handler.php";

// Read in the information we need from json file
$this_disp = null;
// The display id is passed in as the query string
$disp_id=$_SERVER['QUERY_STRING'];
if ($disp_id == null) $disp_id = "id1"; // in case we don't have a query string
// Read the header information (name, creator etc)
$disps=json_decode(file_get_contents("json-displays.json"), true);
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
	echo "<input id='$id$i' type='color' autocomplete='off' style='border-width:2px; display:inline-block' value='$cur_val'>\n";
}
// build a text input box
function build_text($id, $i) {
	global $this_disp;
	// Retrieve the current content
	$cur_val=$this_disp[$id][$i];
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
    echo "<div style='display:block'>\n";
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
<style type="text/css">
	/*----------------- COLLAPSIBLE ---*/
	/* Style the button that is used to open and close the collapsible content */
	.collapsible {
	  background-color: #eee;
	  color: #444;
	  cursor: pointer;
	  padding: 18px;
	  width: 100%;
	  border: none;
	  text-align: left;
	  outline: none;
	  font-size: 15px;
	}
	.collapsible:after {
		content: '\02795'; /* Unicode character for "plus" sign (+) */
		font-size: 13px;
		color: white;
		float: right;
		margin-left: 5px;
	}

	.active:after {
		content: "\2796"; /* Unicode character for "minus" sign (-) */
	}

	/* Add a background color to the button if it is clicked on (add the .active class with JS), and when you move the mouse over it (hover) */
	.active, .collapsible:hover {
	  background-color: #ccc;
	}

	/* Style the collapsible content. Note: hidden by default */
	.content {
	  padding: 0 18px;
	  display: none;
	  overflow: hidden;
	  background-color: #f1f1f1;
	}
</style>
</head>
<body>
	<div>
		<h2>Create New Display</h2>
		<p>You have chosen to create a new display based on 
		<?php print(htmlspecialchars('"'.$this_disp["hd"][0].'" by "'.$this_disp["hd"][1].'" which has been chosen '.$this_disp['hd'][5].' times.')); ?>
		<p>Change anything you like below, then click on the CREATE button.
	</div>

	<button class="collapsible">Choose your colours</button>
	<div class="content">
	  <p>Colours to be used in the display:
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
		<p>Number of times to repeat the pattern along the string of lights. The more repeats you have, the smaller each segment will be: 
			<?php build_number("se",0,1,16,1); ?>
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
	
	<button class="collapsible">Coloured flood lights</button>
	<div class="content">
		<p>The two flood lights on the cupola can automatically pick up the colours from the rest of the display or can be set independently. If they are independent of the rest of the display the two sides can either be the same as each other, or alternate from side to side.
			<?php build_select("fl",0,["0"=>"No flood lights", "1"=>"Automatic", "2"=>"Independent, both the same", "3"=>"Independent, alternating"]);?>
		<p>For independent flood lights, set two colours for them to switch between.
			<?php build_colour("fl",1);?><?php build_colour("fl",2);?>
		<p>Move between the colours smoothly or in a single step:
			<?php build_select("fl",3,["1"=>"Smooth", "2"=>"Step"]);?>
		<p>Speed of colour change: 
			<?php build_select("fl",4,["0"=>"None", "1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]);?>
	</div>
	
	<button class="collapsible">Meteor shower lights</button>
	<div class="content">
		<p>Meteor shower effect (tubes with 'falling' white lights):
			<?php build_select("me",0,["0"=>"No Meteors", "1"=>"Meteors ON"]);?>
	</div>
	
	<button class="collapsible">Now name your creation</button>
	<div class="content" style="display:block">
		<p>A unique and descriptive name for your display: 
			<?php build_text("hd",0);?>
		<p>Your name (this will be shown in the list of displays as the creator of this display): 
			<?php build_text("hd",1);?>
		<p>A password to prevent other people changing your display: 
			<?php build_password("hd",2);?>
	</div>

	
	<button type="reset" onclick="reset_all();">RESET ALL values</button>
	<button type="button" onclick="create_new(1);">CREATE new display</button>
	<button type="button" onclick="create_new(2);">MODIFY this display</button>

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
			if (section_id == "hd") {
				new_disp['hd'][0] = document.getElementById('hd0').value;
				new_disp['hd'][1] = document.getElementById('hd1').value;
				new_disp['hd'][2] = document.getElementById('hd2').value;
				new_disp['hd'][3] = seconds(); // Creation date
				new_disp['hd'][4] = 0; // Last used
				new_disp['hd'][5] = 0; // Uses
				// Basic sanity checking
				if (new_disp['hd'][2] == '') {
					alert("You must specify a password");
					return;
				}
				if (new_disp['hd'][0] == orig_sect[0]) {
					if ($action == 1) {
						alert("You can't use the same name for a new display");
						return;
					}
					else if (new_disp['hd'][1] != orig_sect[1]) {
						alert("You can't change either name when modifying a display");
						return;
					}
				}

			}
			else if (section_id == "co") { // The colour list is variable length
				for (i=0; i<=last_colour_visible; i++) {
					var cv = document.getElementById("c"+i).value;
					new_disp[section_id][i] = cv;
					if (orig_sect[i] != cv)
						changed = true;
				}
			}
			else {
				// Go through each element in this section
				for (i in orig_sect) {
					// We've labeled the elements with section id and index
					e = document.getElementById(section_id + i);
					if (e == null) {
						// nothing there so copy the original
						new_disp[section_id][i] = orig_sect[i];
					}
					else {
						// found a matching element so get its value
						new_disp[section_id][i] = e.value;
						if (orig_sect[i] != e.value)
							changed = true;
					}
				}
			}
		}
		//~ alert((changed? "Something changed": "NOTHING changed"));
		//~ alert("Original display spec was\n" + JSON.stringify(original_disp) + "\nNew display spec is\n" + JSON.stringify(new_disp));

		// Send the json to create a new display
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function () {
			if (this.readyState != 4) return;
			// Do something with the retrieved data ( found in .responseText )
			alert(this.responseText);
			location.href = 'choose.php?new';
		};
		xhr.open("POST", "insert.php", true);
		// can't get application/json to work so have to use form encoding
		xhr.setRequestHeader('Content-Type', "application/x-www-form-urlencoded");
		//xhr.setRequestHeader('Content-type', 'application/json');

		// send the collected data as JSON
		xhr.send('json='+JSON.stringify(new_disp));
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
// Check return from insert.php
// Process dots and dashes in "gr" section
// Check for unique name, check for identical values
// Most selects and number inputs: move to generic qualitative statments
//   to interpret in the animator
//
</script>

</body>
</html>
