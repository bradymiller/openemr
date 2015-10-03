<?php
/** 
 * forms/eye_mag/view.php 
 * 
 * Central view for the eye_mag form.  Here is where all new data is entered
 * New forms are created via new.php and then this script is displayed.
 * Edit requsts come here too...
 * 
 * Copyright (C) 2015 Raymond Magauran <magauran@MedFetch.com> 
 * 
 * LICENSE: This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 3 
 * of the License, or (at your option) any later version. 
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the 
 * GNU General Public License for more details. 
 * You should have received a copy of the GNU General Public License 
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;. 
 * 
 * @package OpenEMR 
 * @author Ray Magauran <magauran@MedFetch.com> 
 * @link http://www.open-emr.org 
 *   
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

include_once("/openemr/globals.php");
$GLOBALS['webroot'] = "/openemr";
$form_name = "eye_mag";
$form_folder = "eye_mag";
$Form_Name = "Eye Exam"; 

/** There are two wys to use this:
 *	1.  As a stand alone Snellen chart.
 *			Here all files are local, no internet connection needed.
 *	2.  Embedded with an openEMR installation
 *			Via network, pull the pages?  or if part of an openEMR install, just piong the API?
 *			Yes.  All is local.
 *	3.  How about a hosted version where we can store the info for each installation and update them remotely
 * 			as needed?
 */
//create a list with options
//start snellen with default chart type;
//start at a preferred line/acuity
//link to a room number in patient flow board
//starting letter height for the given room
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">

	      <!-- jQuery library -->
    <script src="<?php echo $GLOBALS['webroot']; ?>/library/js/jquery.min.js"></script>
	  <style>
@font-face {
   font-family: Sloan;
   src: url(fonts/LiberationSans-Regular.ttf);
}

.body {
   font-family: Sloan;
   font-size: 3em;
   height:98vh;
}
.body2 {
	background-color:black;
}
.snellenX {
	color:#fff;
	text-align:center;
	vertical-align:middle;
	width:100%;
	padding: auto 10px;
	white-space: nowrap;
}
.snellen2 {
	color:#fff;
	text-align:center;
	vertical-align:middle;
	width:100%;
	height:200px;
	margin: 25% auto 25%;
    padding: 10px;
    background-color: #fff;
    color:black;
    font-family: Sloan;
}
.lines {
	width:100%;
	text-align:center;
	line-height: 10em;
}
.line_400 {
	line-height:30em;
	margin: 3% 3%;
}
.line_200 {
	line-height:30em;
	margin: 3% 3%;
}
.nodisplay {
	display:none;
}

#DivParent {
    position: relative;
    text-align:center;
}
#DivWhichNeedToBeVerticallyAligned {
    position: absolute;
	top: 30%;
	margin: 3em 6em;
	width: 80%;
	height: 40%;
}

.DivHelper {
    
}

</style>
<script>
var zone="1";
var line= new Array("10","15","20_1","20_2","20_3","20_4","25","30","40","50","60","80","100","200","400");
var item=6;
var left_right = new Array("Snellen","Sloan","Pedi","illit_E","W4D","Amblyopia_isolation","Graphics");

//var curSize = 3; //get this from a list_option for the room
$( window ).height();
$("#liner").change(function() {
	$(".lines").addClass('nodisplay');
	$("#line_"+line[item]).removeClass('nodisplay');
})

$(document).keydown(function(e) {
	//alert(e.which);
	switch(e.which) {
		//left_right switches between types of vision charts
        case 37: // left
        alert ("left");
        break;

        case 39: // right
        break;

        //need a way to add red/green to screen
        //need worth four dot
        //need pedi chart - pictures.  Consider icon.  ? FontAwesome?
        //Sloan chart
        //illiterate E
        //random letter chart
        case 38: // up
        item = item+1;
        if (item > line.length) item =0;
        $(".snellen2").addClass('nodisplay');
		$('.line_'+line[item]).toggleClass('nodisplay');
		$('.line_'+line[item]).position({
		    of: $(window)
		});
        break;

        case 40: // down
        item = item-1;
        if (item < 0) item =line.length;
        $(".snellen2").addClass('nodisplay');
		$('.line_'+line[item]).toggleClass('nodisplay');
		break;

		case 61:
		//increase font size
		curSize = parseInt($('#body').css('font-size'));
		if (curSize <60) {
			curSize = curSize+1;
		}
		$('#body').css('font-size',curSize);
		break;

		case 107: //plus sign number pad
		//increase font size
		curSize = parseInt($('#body').css('font-size'));
		if (curSize <60) {
			curSize = curSize+1;
		}
		$('#body').css('font-size',curSize);
		break;

		case 173:
		//decrease font size
		curSize = parseInt($('#body').css('font-size'));
		
		if (curSize >1) {
			curSize = curSize-1;
		}
		$('#body').css('font-size',curSize);
		break;

		case 109: //minus sign on number pad
		//decrease font size
		curSize = parseInt($('#body').css('font-size'));
		
		if (curSize >1) {
			curSize = curSize-1;
		}
		$('#body').css('font-size',curSize);
		break;

        default: return; // exit this handler for other keys
    }
    e.preventDefault(); // prevent the default action (scroll / move caret)
});

</script>
</head>

<body id="body" class="body2">
<div id="DivParent">
    <div id="DivWhichNeedToBeVerticallyAligned">
    	
				<table class="snellen2 lines line_400">
					<tr style="font-size:30.0em;">
						<td>E</td>
					</tr>
				</table>

				<table class="snellen2 lines line_200">
					<tr style="font-size:20.0em;">
						<td>S</td><td>L</td>
					</tr>
				</table>
				<table class="snellen2 lines line_100">
					<tr style="font-size:10.0em;">
						<td>O</td><td>P</td><td>L</td><td>B</td>
					</tr>
				</table>

				<table class="snellen2 lines line_80">
					<tr style="font-size:8.0em;">
						<td>C</td><td>A</td><td>V</td><td>8</td>
					</tr>
				</table>
			
				<table class="snellen2 lines line_70">
					<tr style="font-size:7.0em;">
						<td>D</td><td>F</td><td>G</td><td>7</td>
					</tr>
				</table>
				<table class="snellen2 lines line_60">
					<tr style="font-size:5.0em;">
						<td>D</td><td>A</td><td>O</td><td>6</td>
					</tr>
				</table>
				<table class="snellen2 lines line_50">
					<tr style="font-size:4.0em;">
						<td>E</td><td>G</td><td>N</td><td>U</td><td>5</td>
					</tr>
						</table>
				<table class="snellen2 lines line_40">
					<tr style="font-size:3em;">
						<td>F</td><td>Z</td><td>B</td><td>D</td><td>4</td>
					</tr>
				</table>
				<table class="snellen2 lines line_30">
					<tr  style="font-size:1.8em;">
						<td>O</td><td>F</td><td>L</td><td>C</td><td>3</td>
					</tr>
				</table>
				<table class="snellen2 lines line_25">
					<tr style="font-size:1.0em;">
						<td>A</td><td>P</td><td>S</td><td>O</td><td>2</td><TD>5</TD>
					</tr>
				</table>
					<table class="snellen2  lines line_20_1">
					<tr style="font-size:1.0em;">
						<td>
				E</td><td>V</td><td>O</td><td>T</td><td>Z</td><td>2</td>	
				</tr>
				</table>
				</tr>
				</table>
					<table class="snellen2 lines line_20_2">
					<tr style="font-size:1.0em;">

				<td>T</td><td>E</td><td>G</td><td>A</td><td>D</td><td>M</td>	
				</tr>
				</table>
					<table class="snellen2 lines line_20_3">
						<tr style="font-size:1.0em;">
				<td>H</td><td>X</td><td>F</td><td>Z</td><td>H</td><td>G</td>	
				</tr>
				</table>
					<table class="snellen2 lines line_20_4">
						<tr style="font-size:1.0em;">

				<td>T</td><td>Z</td><td>V</td><td>E</td><td>C</td><td>L</td>	
				</tr>
				</table>
					<table class="snellen2 lines line_15">
						<tr style="font-size:0.75em;">

				<td>O</td><td>H</td><td>P</td><td>N</td><td>T</td><td>Z</td>	
				</tr>
				</table>
					<table class="snellen2 lines line_10">
						<tr style="font-size:0.5em;">

				<td>E</td><td>V</td><td>O</td><td>T</td><td></td><td>S</td><td>H</td>	
				</tr>
				</table>
		
	</div>

	<div class="sloan nodisplay">
	</div>

	<div class="pedi nodisplay">
		</div>

	<div class="illit_E nodisplay">
	</div>

	<div class="graphics nodisplay">
	</div>

    </div>
</div>

</body>
</html>