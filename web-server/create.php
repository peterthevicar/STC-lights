<!DOCTYPE html>
<?php
// Format of json-displays
//
//::= <display details>*
//<display details> ::= <id> <header> <colour list> <gradient> <segment> <fading> <sparkle> <spot> <meteors>
//  <id> ::= id<int>
//  <header> ::= <name> <creator> <created> <used> <uses>
//    <uses> ::= <int>
//    <name>, <creator> ::= <str>
//    <created>, <used> ::= <timestamp>
//  <colour list> ::= <colour> <colour>* <colour>
//  <gradient> ::= <colour list> <repeats> <blend> <bar on> <bar off>
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
//  <floods> ::= <flood spec> <flood spec>
//    <flood spec> ::= <int AUTO, MANUAL> <flood def>
//      <flood def> ::= <colour> <colour> <blend> <duration>
//  <meteor> ::= <int ON, OFF, AUTO>


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
		<?php print('&quot;'.$this_disp["hd"][0]."&quot; by &quot;".$this_disp["hd"][1].'&quot;'); ?>
		<p>Change anything you like below, then click on the CREATE button.
	</div>
	<button class="collapsible">1 Colours</button>
	<div class="content" style="display:block">
	  <p>Colours for the gradient:
		<?php build_colours();?>
	</div>
	
	<button class="collapsible">2 Repeats</button>
	<div class="content">
		<p>Number of times to repeat the pattern (one big one or more smaller ones): 
			<?php build_number("se",0,1,16,1); ?>
	</div>
	
	<button class="collapsible">3 Movement</button>
	<div class="content">
		<p>Direction of movement: 
			<?php build_select("se",1,["0"=>"Stop", "1"=>"Left", "2"=>"Right", "3"=>"Two left, one right"]);?>
		<p>Time to get to the end (seconds): 
			<?php build_number("se",2,0.5,20,0.5);?>
		<p>What to do when the pattern gets to the end: 
			<?php build_select("se",3,["1"=>"Reverse and come back", "2"=>"Keep going round in the same direction"]);?>
	</div>
	<button type="reset" onclick="reset_all();">RESET ALL values</button>
	<button type="button" onclick="create_new();">CREATE new display</button>

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
	//
	//--------------------- Create new display spec -----------------//
	//
	// create_new goes through the elements in the original display spec
	// and for each one looks in the DOM for a matching element.
	// If it finds one it puts the new value into new_disp ready for 
	// creating a display with the new spec.
	
	var original_disp = JSON.parse('<?php echo json_encode($this_disp);?>');
	
	function create_new () {
		var changed = false;
		var new_disp = {};
		// Go through each section in the original disp
		for (section_id in original_disp) {
			var orig_sect = original_disp[section_id];
			var i, e;
			new_disp[section_id] = [];
			// First a special way of handling the colour list as it's variable length
			if (section_id == "co") {
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
		//alert((changed? "Something changed": "NOTHING changed"));
		//alert("Original display spec was\n" + JSON.stringify(original_disp) + "\nNew display spec is\n" + JSON.stringify(new_disp));

		// Send the json to create a new display
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function () {
			if (this.readyState != 4 || this.status != 200) return;
			// Do something with the retrieved data ( found in .responseText )
			alert(this.responseText);
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
// Reset colours to original vlaues
// Preview of pattern, done some of this but it's complex
</script>

</body>
</html>
