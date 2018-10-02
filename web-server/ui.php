<?php
//
//Format of json-history:
//
//::= <display details>*
//<display details> ::= <display head> <display params>
//  <display head> ::= <id> <name> <creator> <statistics>
//    <id> ::= <int>
//    <name> ::= <str>
//    <creator> ::= <str>
//    <statistics> ::= <creation date> <last used> <number of uses>
//  <display params> ::= <gradient> <segment> <fading> <sparkle> <spot>
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

// Read in the json-history file
$hist=json_decode(file_get_contents("json-history.json"), true);

// This funtion outputs all the elements for a labelled drop-down (select)
function select_input($select_label, $select_name, array $option) {
    global $hist;
    // Header with label
    echo "<p>$select_label: <select name=\"$select_name\" id=\"$select_name\">\n";
    // Put in all the options
    foreach ($option as $option_value => $option_text) {
        // $sel will be set to 'selected' for the selected option
        $sel=($hist[$select_name]==$option_value)?' selected':'';
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
	td, th {width: 30%}
	#curSel {color:white; background-color:blue; font-size:100%} /* Selected row in table */
	.header {position: sticky; top: 0; background-color:white} /* will scroll to top of page then stop */
	.footer {position: sticky; bottom: 0; width: 100%} /* stays at foot of page with text scrolling behind */
	table {width:100%}
	button {font-size:100%}
	/* 
	@media (min-width: 858px) {
    html {
        font-size: 1em;
    }
    */
</style>
<script>
// https://www.w3schools.com/howto/howto_js_sort_table.asp
function sortTable(tid,n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById(tid);
  switching = true;
  // Set the sorting direction to ascending:
  dir = "asc";
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first, which contains table headers): */
    for (i = 1; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc or desc: */
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch
      and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      // Each time a switch is done, increase this count by 1:
      switchcount ++;
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
function selRow(thisrow) {
	var cursel;
	// un-select current one
	cursel = document.getElementById("curSel")
	if (cursel) {
		cursel.id="";
		cursel.firstChild.removeChild(cursel.firstChild.lastChild);
	}
	// Select new one
	thisrow.id="curSel";
	var newdiv = document.createElement("div");
	newdiv.display = "block";
	var newbut = document.createElement("button");
	newbut.innerText = "DISPLAY";
	newdiv.appendChild(newbut);
	newbut = document.createElement("button");
	newbut.innerText = "EDIT";
	//newbut.style="margin-left:1em";
	newdiv.appendChild(newbut);
	thisrow.firstChild.appendChild(newdiv);
	// For double click to select, this works but needs onclick to be 
	// reset when id is reset above: 
	//thisrow.onclick=function secondClick(){doDisplay();};
}
function doDisplay() {
	var cursel;
	cursel = document.getElementById("curSel")
	if (cursel) {
		alert("0="+cursel.children[0].innerText);
	}
}
</script>
</head>
<body>
	<div>
		Here we go again!
		<?php echo "<br>blob"; ?>
		<?php echo "baah<br>";?>
		<?php echo $vars["un"], $vars["tk"]; ?>
	</div>
	<div>
		<table class="header">
			<tr>
				<!--When a header is clicked, run the sortTable function, with a parameter,
				0 for sorting by names, 1 for sorting by country: -->
				<th onclick="sortTable('ta',0)" style="width:30%">id</th>
				<th onclick="sortTable('ta',1)" style="width:30%">Name</th>
			</tr>
		</table>
		<table id="ta">
			<?php foreach ($hist["dh"] as $entry) {
				echo "<tr  onclick='selRow(this)'><td style='width:30%'>",$entry,"</td><td style='width:30%'>",$entry[1],"</td></tr>";
			}
			?>
		</table>
	</div>
	<div class="footer">
		<button type="button" onclick="doDisplay()">Display</button> 
	</div>

</body>
</html>
