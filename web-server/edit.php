<!DOCTYPE html>
<?php
//
//Format of json-disp-specs:
//
//  <display specs> ::= <id> <gradient> <segment> <fading> <sparkle> <spot> <meteors>
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


// Read in the information we need from json files
$this_disp = null;
$this_spec = null;
// The display id is passed in as the query string
$disp_id=$_SERVER['QUERY_STRING'];
if ($disp_id == null) $disp_id = "id1"; // in case we don't have a query string
// Read the header information (name, creator etc)
$disps=json_decode(file_get_contents("json-displays.json"), true);
$this_disp=$disps[$disp_id];
// Read the specs for this display (colours, speeds etc)
$disp_specs=json_decode(file_get_contents("json-disp-specs.json"), true);
$this_spec=$disp_specs[$disp_id];

// build a <select> with current value pre-selected
function build_select ($id, $i, $opts) {
	global $this_spec;
	// Retrieve the current value of the requested field
	$cur_val=$this_spec[$id][$i];
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
	global $this_spec;
	// Retrieve the current value of the requested field
	$cur_val=$this_spec[$id][$i];
    // Header with label
    echo "<input id='$id$i' autocomplete='off' type='number' value='$cur_val' min='$min' max='$max' step='$step'>\n";
}
//build a gradient colour selector with the initial colours added
$grad_colours=2;
function build_colours ($id, $i) {
	global $this_spec, $grad_colours;
	// Retrieve the current value of the requested field
	$cur_val=$this_spec[$id][$i];
    // Header with label
    echo "\n<div id='colour_first' style='display:inline-block'>\n";
    echo "  <input id='c1' type='color' autocomplete='off' style='display:inline-block; border-width:2px; border-color:red' value='$cur_val[0]'>\n";
    foreach (array_slice($cur_val,1,-1) as $col) {
		$grad_colours++;
		echo "<input id='c$grad_colours' type='color' autocomplete='off' style='border-width:2px; display:inline-block' value='$col'>\n";
	}
    echo "</div>\n";
    echo "<div style='display:inline-block'>\n";
    $col=$cur_val[$grad_colours-1];
    echo "   <input id='c2' type='color' autocomplete='off' style='display:inline-block; border-width:2px; border-color:red' value='$col'>\n";
    echo "</div>\n";
    echo "<div style='display:block'>\n";
    echo "   <button style='display:inline-block' type='button' onclick='javascript:update_colours(visible+1)'>Insert +</button>\n";
    echo "   <button style='display:inline-block' type='button' onclick='javascript:update_colours(visible-1)'>- Remove</button>\n";
    echo "  </div>\n";
}

//file_put_contents("form.json", json_encode(array('var1'=>'val1', 'var2'=>'val2')));
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
		<?php print('&quot;'.$this_disp[0]."&quot; by &quot;".$this_disp[1].'&quot;'); ?>
		<p>Change anything you like below, then click on the CREATE button.
	</div>
	<button class="collapsible">1 Colours</button>
	<div class="content">
	  <p>Colours for the gradient:
		<?php build_colours("gr",0);?>
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
	// create_new goes through the elements in the original display spec
	// and for each one looks in the DOM for a matching element.
	// If it finds one it puts the new value into new_spec ready for 
	// creating a display with the new spec.
	
	var original_spec = JSON.parse('<?php echo json_encode($this_spec);?>');
	var new_spec = []; // for building up the spec for the created display
	
	function create_new () {
		var changed = false;
		new_spec = {};
		// Go through each section in the original spec
		for (section_id in original_spec) {
			var orig_sect = original_spec[section_id];
			var i, e;
			new_spec[section_id] = [];
			// Now go through each element in this section
			for (i in orig_sect) {
				// We've labeled the elements with section id and index
				e = document.getElementById(section_id + i);
				if (e == null) {
					// nothing there so copy the original
					new_spec[section_id][i] = orig_sect[i];
				}
				else {
					// found a matching element so get its value
					new_spec[section_id][i] = e.value;
					if (e.value != orig_sect[i])
						changed = true;
				}
			}
		}
		alert((changed? "Something changed": "NOTHING changed"));
		alert("Original display spec was\n" + JSON.stringify(original_spec) + "\nNew display spec is\n" + JSON.stringify(new_spec));
	}
</script>	
<script>
	function reset_all () {
		var coll = document.getElementsByTagName("input");
		var i;

		for (i = 0; i < coll.length; i++) {
		  coll[i].value = coll[i].getAttribute("value");
	  }
	}
</script>	
<script>
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
	// Store original number of colours to spot changes
	n_colours0=<?php echo $grad_colours;?>
	// Global counts of how many colour elements we have and how many are currently visible
	created=<?php echo $grad_colours;?>;
	visible=<?php echo $grad_colours;?>;
	function update_colours(n) {
		if (n < 2) return;
		// Make sure there are n colour choosers visible
		for (i=created+1; i<=n; i++) { // create inputs as required
			var e = document.createElement("input");
			e = document.getElementById('colour_first').appendChild(e);
			e.id = 'c'+i;
			e.type = 'color';
			e.style = 'border-width:2px';
			created++;
		}
		for (i=3; i<=n; i++) { // make sure they're visible
			document.getElementById('c'+i).style.display='inline-block'
		}
		for (i=visible; i>n; i--) { // hide others that were visible
			document.getElementById('c'+i).style.display='none'
		}
		visible = n;
	}
//
//
//TODO
//
// Put gradient spec at end of gr or make its own section
// Some way of packing up the colours for create
//
//
</script>

</body>
</html>
