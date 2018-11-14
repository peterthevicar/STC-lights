<?php
include "s-error-handler.php";
//~ err('DEBUG:choose:3 query='.(array_key_exists('QUERY_STRING',$_SERVER)? $_SERVER['QUERY_STRING']:'null'));
// Read in the json-displays file, which may be locked by d-insert.php
$fn = 'j-displays.json';
$fp = fopen($fn, 'r');
if($fp != null and flock($fp, LOCK_SH)){ // wait until any write lock is released
	//
	//-------------- json-displays SHARE LOCKED -----------
	//
    $content = fread($fp, filesize($fn));
    $disps=json_decode($content, true);
    flock($fp, LOCK_UN);
    fclose($fp);
	//
	//-------------- UNLOCKED -----------
	//
}
// read in the status file to see if the lights are on at the moment (fills in $status)
include "s-get-status.php";
include "s-check-lights-on.php";

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style type="text/css">
	th {color:blue; text-align:left}
	#curSel, .curSel {color:white; background-color:blue; font-size:100%} /* Selected row in table */
	.header {position: sticky; top: 0; background-color:white;} /* will scroll to top of page then stop */	
	.warning {position: sticky; top: 0; background-color:red;} /* will scroll to top of page then stop */
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
<!-- -->
<!-- ---------------------- HTML SECTION ------------------------- -->
<!-- -->
</head>
<body>
	<div class="warning" style="display:<?php echo ($lightson?'none':'block'); ?>">
		The lights are switched off at the moment. They should be back
		<?php echo ($until==0? 'soon.': 'at '.date('H:i', $until)); ?>
	</div>
	<div>
		<p>Welcome to the St.Thomas Christmas Lights controller.
		There are lots of different displays to choose from. 
		<p>Once you've chosen a display you can either DISPLAY it on the 
		church tower or CREATE a new display of your own based on the one
		you've chosen. 
		<p>Enjoy!
	</div>
	<div>
		<p class="header">
		  Sort:
		  <select id=sortselect onchange="sortCol(this.value)" autocomplete="off">
			  <option value="0,0,1">Display Name</option>
			  <option value="1,0,1">Creator</option>
			  <option value="2,1,0">Newest first</option>
			  <option value="3,1,0">Most recently used</option>
			  <option value="4,1,0">Most popular</option>
		   </select>
		</p>
		<table id="ta">
			<tr>
				<th>Display name</th>
				<th>Created by</th>
			</tr>
			<?php foreach ($disps as $disp_id => $disp) {
				// only show user displays (id begins "id")
				if (substr($disp_id,0,2) == "id") {
					$hd = $disp["hd"];
					echo "<tr onclick='selRow(this)'>";
					echo   "<td>",$hd[0],"</td>\n";
					echo   "<td>",$hd[1],"</td>\n";
					echo   "<td class='hidden'>",$hd[3],"</td>\n";
					echo   "<td class='hidden'>",$hd[4],"</td>\n";
					echo   "<td class='hidden'>",$hd[5],"</td>\n";
					echo   "<td class='hidden'>",$disp_id,"</td>";
					echo "</tr>";
				}
			}
			?>
		</table>
	</div>
	<div class="footer">
		St.Thomas Church: the town church for Lymington offering
		prayer and hospitality in Jesus' name.
	</div>
<script>
//=========================== JAVASCRIPT ===============================
//
//---------------- Apallingly inefficient table sort --------------------
//
// https://www.w3schools.com/howto/howto_js_sort_table.asp
function sortTable(tid,n,n_headers,type,dir) {
  var table, rows, switching, i, x, y, shouldSwitch, switchcount = 0;
  table = document.getElementById(tid);
  switching = true;
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first n_headers, which contains table headers): */
    for (i = n_headers; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc=1 or desc=0: */
      if (dir == 1) {
		var xv = (type==0?x.innerHTML.toLowerCase():Number(x.innerHTML));
		var yv = (type==0?y.innerHTML.toLowerCase():Number(y.innerHTML));
        if (xv > yv) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      } else if (dir == 0) {
		var xv = (type==0?x.innerHTML.toLowerCase():Number(x.innerHTML));
		var yv = (type==0?y.innerHTML.toLowerCase():Number(y.innerHTML));
        if (xv < yv) {
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
    }
  }
}
function sortCol(params) {
	var paramv = params.split(",");
	unselRow();
	sortTable('ta',paramv[0],1,paramv[1],paramv[2]);
}
//
//------------------- Selection of row in table ---------------
//
function unselRow() {
	var cursel;
	cursel = document.getElementById("curSel")
	if (cursel) {
		cursel.id="";
		cursel.parentNode.removeChild(cursel.nextSibling);
	}
}
function selRow(thisrow) {
	var cursel;
	// un-select previously selected one
	unselRow();
	// Select this one
	thisrow.id="curSel";
	// Add in a new row below with the display/create buttons
	var newtr = document.createElement("tr");
	newtr.classList.add("curSel");
	var newtd = document.createElement("td");
	newtd.colSpan = 6;
	var newbut = document.createElement("button");
	newbut.innerText = "DISPLAY";
	newbut.onclick = function f() {doProcess(1);};
	newtd.appendChild(newbut);
	newbut = document.createElement("button");
	newbut.innerText = "CREATE/EDIT";
	newbut.onclick = function f() {doProcess(2);};
	newtd.appendChild(newbut);
	newtr.appendChild(newtd);
	thisrow.parentNode.insertBefore(newtr, thisrow.nextSibling);
}
//
//-------------------------- EN-QUEUE the display request -----------
//
function processResponse() {
	if (this.readyState != 4) return;
	if (this.status == 200) { // success
		// responseText is the Queue wait returned from the en-q process
		var q_wait = parseInt(this.responseText, 10);
		if (q_wait > 0) {
			alert("Thank you, your chosen display has been queued and should start in about " +
				q_wait + " seconds.");
		}
		else alert("Thank you, your chosen display should start immediately.");
	}
	else // error of some sort
		alert("Sorry, unable to contact the server. Please try again later");
}
function doProcess(action) {
	var cursel;
	cursel = document.getElementById("curSel");
	if (cursel) {
		if (action == 1) { // DISPLAY
			// Send the id to display for queueing
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = processResponse;
			xhr.open("POST", "q-en-q.php", true);
			xhr.setRequestHeader('Content-Type', "application/x-www-form-urlencoded");
			// send the id
			xhr.send('next_id='+cursel.lastChild.innerText);
		}
		else // CREATE/EDIT
			location.href = 'd-create.php?'+cursel.lastChild.innerText;
	}
}
</script>
<script>
//
//---------------------- Initial sort of table ------------------------
//
	// params to sortCol are: field, type(0=str, 1=num), dir(0=desc, 1=asc)
	// fields are: 0:Display Name; 1:Creator; 2:Creation; 3: Used date; 4:Used count
	document.getElementById('sortselect').selectedIndex = <?php echo ((array_key_exists('QUERY_STRING',$_SERVER) and ($_SERVER['QUERY_STRING'] == 'new'))? '2': '4'); ?>;
</script>
</body>
</html>
