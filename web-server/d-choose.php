<?php
include "s-nocache.php";
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

// Header format for displays entry
// <header> ::= <0 name> <1 creator> <2 pwd_hash> <3 created> <4 used> <5 uses> <6 version>

//-------------- If called back from CREATE, may have an id to select initially
$init_id = (array_key_exists('QUERY_STRING',$_SERVER)? $_SERVER['QUERY_STRING']: '');
if (substr($init_id, 0, 2) != 'id') $init_d = '';

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- -->
<!-- ---------------------- CSS ------------------- -->
<!-- -->
<style type="text/css">
	@import url('https://fonts.googleapis.com/css?family=Paprika');
	@import url('https://fonts.googleapis.com/css?family=Open+Sans');
	html {
		background-color:white;
		color:rgb(102, 102, 102);
		font-family:'Open Sans';
	}
	h1 {
		font-family: 'Paprika', serif;
		background-image: url('h1-flourish.png');
		background-repeat: no-repeat;
		background-position: bottom left;
		color: rgb(118, 50, 63);
		font-weight: 600;
		text-transform: uppercase;
		min-height: 70px;
		font-size: 34px;
		padding-bottom: 10px !important;
	}
    .home {
        color: rgb(118, 50, 63);
        text-decoration: none;
        border: 2px;
        border-style:solid;
    }
    #countdown {  /* Countdown to chosen display */
		position:fixed; padding:0; margin:0; top:0; left:0;
		width: 100%; height: 100%;
		background-color:black; opacity:0.8;
		display:none;		
    }
    .cdDigits { /* Countdown digits */
		font-size:300%; color:yellow;
	}
	#cdText { /* Text in the countdown div - centred vertically*/
		position:relative; margin: 0 auto;
		top:50%; transform:translateY(-50%);
		font-size:100%; color:white; text-align:center;
	}
	#cdNow {
		font-size:150%; color:yellow;
	}
	th {
		color:rgb(118, 50, 63); text-align:left;
		font-family:'Paprika';
		padding-right:5px;
	}
	td {
		padding-right:5px;
	}
	#curSel, .curSel { /* Selected row in table */
		color:white; background-color:rgb(192, 159, 128);
		font-size:100%;
	}
	.header { /* will scroll to top of page then stop */	
		position: sticky; top: 0;
		background-color:rgb(192, 159, 128);
		color:white;
		padding: 15px 15px 15px 5px;
		border: none;
		text-align: left;
		outline: none;

	}
	.warning { /* will scroll to top of page then stop */
		position: sticky; top: 0; 
		background-color:rgb(118, 50, 63);
		padding:5px;
		color:white;
	}
	.footer { /* stays at foot of page with text scrolling behind */
		position: sticky; bottom: 0; width: 100%;
		margin-top:20px;
	}
	.footer-text {
		background-color:rgb(86, 86, 86);
		color: rgb(150, 150, 150);
		padding: 5px;
		margin: 0 0 0 0;
	}
	.footer a { /* stays at foot of page with text scrolling behind */
		color:white; font-weight: bold;
		text-decoration: none;
	}
	button, #sortselect {font-size:15px}
	.hidden {display: none}
</style>
<!-- -->
<!-- ---------------------- HTML SECTION ------------------------- -->
<!-- -->
</head>
<body>
    
	<h1><a href="index.php" class="home">&nbsp;&lt;&nbsp</a> Display Control</h1>
	<div class="warning" style="display:<?php echo ($lightson?'none':'block'); ?>">
		The lights are switched off at the moment. They should be back
		<?php echo ($until==0? 'soon.': 'at '.date('H:i', $until)); ?>
	</div>
	<div>
		<p>There are lots of different displays to choose from.
		Once you've chosen a display you can either <strong>DISPLAY</strong> it on the 
		church tower or <strong>CREATE</strong> a new display of your own based on the one
		you've chosen. <strong>Enjoy!</strong>
	</div>
	<div>
		<p class="header">
		  Sort:
		  <select id="sortselect" onchange="sortCol(this.value)" autocomplete="off">
			  <option value="2,1,0">Newest first</option>
			  <option value="4,1,0">Most popular</option>
			  <option value="3,1,0" selected="true">Most recently used</option>
			  <option value="0,0,1">Display Name</option>
			  <option value="1,0,1">Creator</option>
		   </select>
		</p>
		<table id="ta" style="border-collapse:collapse">
			<tr>
				<th>Display name</th>
				<th>Creator</th>
				<th>#Views</th>
			</tr>
			<?php foreach ($disps as $disp_id => $disp) {
				// only show user displays (id begins "id")
				if (substr($disp_id,0,2) == "id") {
					$hd = $disp["hd"];
					echo '<tr onclick="selRow(this)"'.($disp_id==$init_id?'id="init_id"':'').'>';
					echo   "<td>",strip_tags($hd[0]),"</td>\n";
					echo   "<td>",strip_tags($hd[1]),"</td>\n";
					echo   "<td class='hidden'>",$hd[3],"</td>\n";
					echo   "<td class='hidden'>",$hd[4],"</td>\n";
					echo   "<td>",$hd[5],"</td>\n";
					echo   "<td class='hidden'>",$disp_id,"</td>";
					echo "</tr>";
				}
			}
			?>
		</table>
	</div>
	<div class="footer">
		<div class="footer-text">
			St.Thomas Church:
			the town church for Lymington offering
			prayer and hospitality in Jesus' name.<br>
			<a href="https://lymingtonchurch.org">Click here for the church web site.</a>
		</div>
	</div>
	<div id="countdown">
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
	newbut.style.margin = '0px 5px 0px 5px';
	newtd.appendChild(newbut);
	newbut = document.createElement("button");
	newbut.innerText = "CREATE/EDIT";
	newbut.onclick = function f() {doProcess(2);};
	newbut.style.margin = '0px 5px 0px 5px';
	newtd.appendChild(newbut);
	newtr.appendChild(newtd);
	thisrow.parentNode.insertBefore(newtr, thisrow.nextSibling);
}
//
//-------------------------- EN-QUEUE the display request -----------
//
var next_id='id1';
//------- countdown window ------
var intervalID;
var countdownEnd;
var countdownDiv = document.getElementById("countdown");
function updateCountdown(q_wait) {	
	if (q_wait > 1) countdownDiv.innerHTML = "<div id=cdText><p>Your display should start in about</p><p class=cdDigits>" + q_wait + "</p><p>seconds</p></div>";
	else countdownDiv.innerHTML = "<div id=cdText><p id=cdNow>Your display should start immediately</p></div>";
}
function tickCountdown() {
	var diff = countdownEnd - Date.now();
	var q_wait = Math.floor(diff / 1000);
	if (q_wait > -1) updateCountdown(q_wait);
	else {
		clearInterval(intervalID);
		countdownDiv.style.display = 'none';
		location.href = 'd-choose.php?'+next_id;
	}
}
function showCountdown(q_wait) {
	countdownDiv.style.display = 'block';
	countdownEnd = Date.now() + 1000*q_wait;
	updateCountdown(q_wait);
	intervalID = setInterval(tickCountdown, 1000);
}
//--------- En-q and process the response
function processResponse() {
	if (this.readyState != 4) return;
	if (this.status == 200) { // success
		var resp = JSON.parse(this.responseText);
		if (resp.err == 0)
			// responseText is the Queue wait returned from the en-q process
			showCountdown(resp.msg);
		else
			// response text is error message
			alert(resp.msg);
	}
	else // error of some sort
		alert("Sorry, unable to contact the server. Please try again later");
}
function doProcess(action) {
	var cursel;
	cursel = document.getElementById("curSel");
	next_id = cursel.lastChild.innerText;
	if (cursel) {
		if (action == 1) { // DISPLAY
			// Send the id to display for queueing
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = processResponse;
			xhr.open("POST", "q-en-q.php", true);
			xhr.setRequestHeader('Content-Type', "application/x-www-form-urlencoded");
			// send the id
			xhr.send('next_id='+next_id);
		}
		else // CREATE/EDIT
			location.href = 'd-create.php?'+next_id;
	}
}
</script>
<script>
//
//---------------------- Initial sort of table ------------------------
//
window.addEventListener("load", 
	function(event){ 
		sortCol('3,1,0');
		e=document.getElementById('init_id');
		if (e != null) selRow(e);
	 }
);	
</script>
</body>
</html>
