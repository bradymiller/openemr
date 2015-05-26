<?php

/** 
 * forms/eye_mag/view.php 
 * 
 * Central view for the eye_mag form.  Here is where all new data is entered
 * New forms are created via new.php and then this script is displayed.
 * Edit requsts come here too...
 * 
 * Copyright (C) 2010-14 Raymond Magauran <magauran@MedFetch.com> 
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
include_once("../../globals.php");
include_once("$srcdir/acl.inc");
include_once("$srcdir/lists.inc");
include_once("$srcdir/api.inc");
include_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");

$showit    = $_REQUEST['zone'];
if ($showit=='') $showit="general";
if ($showit=='ext') $showit="external";
?>
<html>
	<head>
		
	<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/pure-min.css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/bootstrap-3-2-0.min.css">
    <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/style.css" type="text/css">    
    <link rel=stylesheet href="<?php echo $GLOBALS['css_header']; ?>" type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/font-awesome-4.2.0/css/font-awesome.css">
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Eye Exam Help">
    <meta name="author" content="openEMR: ophthalmology help">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- jQuery library -->
	<script src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.min.js"></script>

	<!-- Latest compiled JavaScript -->
	<script src="<?php echo $GLOBALS['webroot'] ?>/library/js/bootstrap.min.js"></script>  
	  <script>
	 $(function() {
	$("[id^='accordion_']" ).accordion({
	heightStyle: "content",
	collapsible: true,
	header: "h3",
	active: 0
	});
});
	$(document).ready(function() {
		$("[name^='accordion_']").hide();
		$("#accordion_<?php echo $showit; ?>_group").show()
		$("#<?php echo $showit; ?>_button").css("color","red");
		$("[id$='_button']").click(function() {
			var zone = this.id.match(/(.*)_button/)[1];
			$("[id$='_button']").css("color","black");
			$("#"+zone+"_button").css("color","red");
			$("[id$='_group']").hide();
			$("[id^='accordion_"+zone+"_group']").show();
			var showit = zone+'_0';
			
		});
		
		$("[id^='accordion_']").click(function() {
			var active_panel = $(this).accordion( "option", "active" );
			$("[id^='accordion_']").accordion({	
				active: active_panel
			});
		})
	});
	</script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<link rel="stylesheet" href="/resources/demos/style.css">

	<style>
	 	body {
		 	font: 12px/18px FontAwesome, normal helvetica, sans-serif;
			font-family: FontAwesome,Arial,sans-serif; 	
		 }
		 .nodisplay {
		 	display:none;
		 }
			table th {
				text-align:center;
				vertical-align: middle;
				margin:20;
				border:1pt solid black;
				padding:5 ;
			}
			table td {
				text-align:left;
				vertical-align: top;
				margin:20;
				border:1pt solid black;
				padding:5;
				font-size:0.7em;
			}
			blockquote.style2 {
				margin-top: 0px;
				margin-bottom: 10px;
				margin-left: 20px;
				margin-right:20px;
				padding: 10px;
				border:none;
				width:98%;
				font-size:1em;
				display:inline-block;
			} 
			.style3 {
				margin:20;
				border-bottom:1pt solid black;
				background-color:#c0C0c0;
				text-align: left;
			}
			.underline {
				text-decoration: underline;
			}
			.kb_entry {
				font-weight:600;
				width:85%;
				min-height:0.5in;
				text-align:center;
				margin:2 5 20 5;
				border:1pt solid #129FEA;
				background-color:#ff9;
				xpadding:20;
				vertical-align: middle;
				top:50%;
			}
			.output_EMR {
				clear:both;float:left;border:1pt solid black;width:50%;padding:0 10;margin:5;
				height: 340;
			}
			.output_reports {
				float:left;border:1pt solid black;width:45%;padding:0 10;margin:5;
				height: 340;
			}
			.ui-state-active {
				background: #97C4FE;

			}
			.field {
				color:red;
				font-weight:600;
			}
			.bold {
				font-weight:600;
			}
	</style>
	</head>
	<body style="font-size:1.2em;padding:25;">
		<div style="position:absolute;
		top:0in;
		left:0in;
		width:100%;
		height:30px;
		background-color:#C9DBF2;
		color:black;
		font-family: FontAwesome;
		font-weight:400;
		font-size:1.1em;
		padding:5 10 5 10;">
<img class="little_image left" height="18" src="/openemr/sites/default/images/login_logo.gif"></img>  OpenEMR: Eye Exam <span class="bold">Keyboard Entry Help</span>
		</div>
<br />
		<button id="general_button">Introduction</button>
		<button id="external_button">External</button>
		<button id="antseg_button">Anterior Segment</button>
		<button id="retina_button">Retina</button>
		<button id="neuro_button">Neuro</button>
		<div id="container" name="container_group" style="margin:10;text-align:left;">
			
			<div id="accordion_general_group" name="accordion_group" class="ui-accordion" style="text-align:left;margin:10;padding:20;">
				<h3 class="ui-accordion-header external">Introduction</h3>
				<div id="general" style="text-align:left;">
					<blockquote class="style2">
						You trained.  You learned how to perform an eye exam, to help patients.  <br />
						You developed your own style and documented all your findings.<br />
						You used a pen and paper. <br />
						You were efficient and provided high quality care to thousands of patients.<br />
						Other eyedocs could decipher your hieroglyphics; regulators and compliance people could not.<br />
						In the name of quality, they fixed us. 
						They gave us EHRs.  
						<br />
						EHRs that speak at a fourth grade level.<br />
						They disconnected our brains from our charts.  They slowed us down.<br />
						We pine for our pen and paper, and the speed our brains are capable of working at.<br />
						We suffer the agony of slow throughput to "check the box",<br />
						or we incur the added costs of hiring scribes.<br/>
						<hr />
						What we are proposing with openEMR: Eye Exam is not radical.  It's reasonable.<br />
						We have to give up our pens.  OK.  But we don't have to succumb entirely.<br />
						<h2 class="bold">What if you could type your shorthand?</h2>
						In addition to the point-click-type option, the Pull-forward from old records option, 
						the Fill Defaults feature and the draw programs, 
						<b>we added the Eye Shorthand technique, merging our lingua franca oculī with the keyboard</b>.<br />
						You can customize it to match <b>your brain</b>, but you may not need to.<br />
						It should already be very familiar. It is based on the shorthand we all learned in training.<br /><hr />
						You know what <b>RUL: 2mm ptosis</b> means. <br />
						You could click on the field for the right upper lid and type in 2mm ptosis.  <br />
						Then click on the next field, maybe even tab to it, and type in the next field.<br />
						What about <b>Right C/D 0.6Vx0.4H with inferior notch.  Left C/D 0.5</b>?<br />
						Find the fields to click. Two mouse clicks at least. Lots of hand movement going in.  <br />
						<br />
						Does this make any sense to you:<br />

						<div style="text-align:center;font-weight:600;margin:20;background-color:yellow;padding:30 auto;width:75%;border:1pt black solid">rcup.0.6Vx0.4H w/ inf notch;lcup.0.5</span><br />
						</div>
						It is the same thing in shorthand.  You can even shorten rcup to rc, lcup to c, or just "c" for both (if the data to enter is the same), and go even faster.<br />
						<b>Field.text;field.text;Field.text;field.text</b><br />
						It is that simple and doesn't the <b>text</b> part look familiar?<br />
						Even slow typers will find the Eye Exam Shorthand can speed up EHR documentation time.<br />
						It is easy to learn.<br /> 
						Type the abbreviation for a clinical field, a period/fullstop, followed by your finding, in your shorthand.<br />
						If our shorthand doesn't include your shorthand terms, your shorthand will be displayed.<br />
						Ask us and over time, we'll include your shorthand into this form so together we can build a better EHR for everyone.

						That's Open Source and <b>it's why we do what we do, the way we do it.</b><br />
						<hr />
						This tutorial centers around four lines of typing which 
						document normal findings <b>and more than 40 different clinical issues</b>. 
							<br />
							That's a lot to document and one mighty complicated patient!<br />
							Many more issues than we would see on a routine day with routine patients, but it could happen...  <br />
						Documenting this many findings would take a little bit of time on paper, and a lifetime in an EHR. <br />
						With the openEMR Shorthand, the average typist can do it <b>in less than a minute.</b>
						<hr />
						Go ahead and paste the lines below into a test patient's chart.  Fiddle with them. <br />
						Read the accompanying help pages if you like.  We tried to make them useful but they look so overwhelming.<br />
						If you are already an eyedoc, they won't be. 
						It will all make perfect sense in short order. <br />
						It's time to plug the EHR into your brain, and work at the speed of your brain.<br />
						Finally.  
						<hr />
						</blockquote>
			<h4 class="bold">External: </h4>
					<textarea class="kb_entry">D;bll.+2 meibomitis;rll.frank ect, 7x6mm lid margin bcc lat.a;bul.2mm ptosis;rul.+3 dermato.a
					</textarea>
					<br /><h4 class="bold">Anterior Segment:</h4>
					<textarea class="kb_entry">D;bc.+2 inj;bk.med pter;rk.mod endo gut.a;bac.+1 fc, +1 pig cells
					</textarea>
					<br />
					<h4 class="bold">Retina:</h4>
					<textarea class="kb_entry">D;bd.+2 bowtie pallor;rc.0.6Vx0.4H w/ inf notch;lc.0.5;rmac.+2 BDR, +CSME;lmac.flat, tr BDR;v.+PPDR, ++venous beading;rp.ht 1 o,no vh;
					</textarea>
					<h4 class="bold">Strabismus:</h4>
					<textarea class="kb_entry">scDist;5.8ix 1rht;4.10ix;6.6ix;2.15xt;8.5ix;ccDist;4.5ix;5.ortho;6.ortho
					</textarea>
				</div>				

				<h3 class="ui-accordion-header external">Shorthand Keyboard Entry Structure</h3>
				<div id="general" style="text-align:left;">
					<h4><b>Usage:</b>  location.text(.a)(;)</h4>
					<blockquote class="style2"><i>where: <br /></i>
						<b>location</b> is the shorthand term for the clinical field.<br/>
						<b>text</b> is the complete or shorthand data to enter into this <b>location</b>.
						<br />
						<b>location</b> and <b>text</b> are separated by a "<b>.</b>" period/fullstop.
						<br />
						The trailing "<b>.a</b>" 
						is optional and will <b>append</b> the <b>text</b> to the data already in the field, instead of replacing it.<br />
						The semi-colon "<b>;</b>" is used to divide entries, allowing multiple field entries simultaneously. <br />
						<small><i>The semi-colon separates entries and cannot be used within a text field.</i></small><br />
						After pressing <b>Enter/Return</b>, the data is submitted.  <br />
						Pressing <b>TAB</b> will jump to the next clinical area's Keboard/Shorthand entry field.<br />
					</blockquote>
				</div>
			</div>	
				
			<div id="accordion_external_group" name="accordion_group" class="ui-accordion" style="text-align:left;margin:10;padding:20;">
				<div name="external" class="ui-accordion external">
					<h3 name="external_group" id="external_0">External: Shorthand Walk Through</h3>
					<div name="external_group" class="external" style="text-align:left;margin:0;padding:0;">
						<a name="example_ext"></a>
						<blockquote class="style2">
							<h4 class="underline">Keyboard Entry</h4>
							<textarea class="kb_entry">D;bll.+2 meibomitis;rll.frank ect, 7x6mm lid margin bcc lat.a;bul.2mm ptosis;rul.+3 dermato.a
							</textarea>
							<img src="/openemr/interface/forms/eye_mag/images/sh_ext.png" style="width: 90%;" alt="Shorthand Example: Anterior Segment">
							<br />
						</blockquote>
					</div>
					<h3>External: Example Output</h3>
					<div id="external_output" style="text-align:left;margin:0;padding:0;">
						<blockquote class="style2">
							Input:<br /><br />
							<b>D;bll.+2 meibomitis;rll.frank ect, 7x6mm lid margin bcc lat.a;bul.2mm ptosis;rul.+3 dermato.a</b>
							<br />
							<br />						 
							Output:
							<br /><br />
							<div class="output_EMR" >
								<h4>Eye Exam</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_ext_EMR.png" width="95%" alt="Shorthand Example: openEMR">
							</div>
							<div class="output_reports">
								<h4>Reports</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_ext_report.png" width="95%" alt="Shorthand Example: Reports">
							</div>
						</blockquote>
					</div>
					<h3>External: Location Codes and Abbreviations</h3>
					<div id="external_codes" style="clear:both; border:0pt solid black;text-align:left;">
						<a name="output_external"></a>
						<blockquote class="style2">
							<table style="border:0pt solid black;margin:10;">
								<tr class="style3"><th>Clinical Field</th><th>Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
								<tr >
									<td>Default values</td><td>D or d</td>
									<td>d;<br />D;</td>
									<td>All fields with defined default values are <b>erased</b> and filled with default values.<br />Fields without defined default values are not affected. </td>
								</tr>
								<tr >
									<td>Right Brow</td><td>rb or RB</td>
									<td>rb.1cm lat ptosis<br />rb.med 2cm SCC</td>
									<td>1cm lateral ptosis<br />medial 2cm SCC</td>
								</tr>
								<tr>
									<td>Left Brow</td><td>rb or RB</td>
									<td>rb.loss of lat brow follicles<br />lb.no rhytids from VIIth nerve palsy</td>
									<td>loss of lateral brow follicles<br />no rhytids from VIIth nerve palsy</td>
								</tr>
								<tr>
									<td>Both Brows/Forehead</td><td>fh or FH<br />bb or BB</td>
									<td>fh.+3 fh rhytids<br>BB.+3 glab rhytids</td>
									<td>+3 forehead rhytids<br />+3 glabellar rhytids</td>
								</tr>
								<tr>
									<td>Right Upper Lid</td><td>rul or RUL</td>
									<td>RUL.1cm lat ptosis<br />rul.med 2cm SCC</td>
									<td>1cm lateral ptosis<br />medial 2cm SCC</td>
								</tr>
								<tr>
									<td>Left Upper Lid</td><td>lul or LUL</td>
									<td>LUL.1cm lat ptosis<br />lul.med 2cm SCC</td>
									<td>1cm lateral ptosis<br />medial 2cm SCC</td>
								</tr>
								<tr>
									<td>Right Lower Lid</td><td>rll or RLL</td>
									<td>rll.1cm lat ptosis<br />rll.med 2cm SCC</td>
									<td>1cm lateral ptosis<br />medial 2cm SCC</td>
								</tr>
								<tr>
									<td>Left Lower Lid</td><td>lll or LLL</td>
									<td>lll.0.5cm lat ptosis<br />LLL.med 2cm SCC</td>
									<td>1cm lateral ptosis<br />medial 2cm SCC</td>
								</tr>
								<tr>
									<td>Right Medial Canthus</td><td>rmc or RMC</td>
									<td>rmc.1cm bcc<br />RMC.healed dcr scar</td>
									<td>1cm BCC<br />healed DCR scar</td>
								</tr>
								<tr>
									<td>Left Medial Canthus</td><td>lmc or LMC</td>
									<td>lmc.acute dacryo, tender w/ purulent drainage<br />lmc.1.2cm x 8mm mass</td>
									<td>acute dacryo, tender with purulent drainage<br />1.2cm x 8mm mass</td>
								</tr>
								<tr>
									<td>Right Adnexa</td><td>rad or RAD</td>
									<td>rad.1.8x2.0cm bcc lat<br />RAD.healed DCR scar</td>
									<td>1cm BCC<br />healed DCR scar</td>
								</tr>
								<tr>
									<td>Left Adnexa</td><td>lad or LAD</td>
									<td>lad.1cm lacr cyst protruding under lid<br />LAD.1.2cm x 8mm mass</td>
									<td>1cm lacrimal cyst protruding under lid<br />1.2cm x 8mm mass</td>
								</tr>
							</table>
							<br />*<i>case insensitive</i><br />
							**<i>The default action is to replace the field with the new text.  
							<br />
							Adding <b>".a"</b> at the end of a <b>text</b> section will append the current text instead of replacing it.
							<br >For example, <b>entering "4xL.+2 meibomitis.a" will <u>append</u> "+2 meibomitis"</b> 
							to each of the eyelid fields, RUL/RLL/LUL/LLL.</i>
						
							<hr />
							<a name="abbrev_external"></a>
							<h2 class="underline">External Shorthand Abbreviations:</h2>

							The following terms will be expanded from their shorthand to full expression in the EMR fields:
						
							<table style="border:1pt solid black;margin:10;width:85%;">
									<tr class="style3"><th>Enter this:</th><th>Get this:</th></tr>
									<tr><td>inf</td><td>inferior</td></tr>
									<tr><td>sup</td><td>superior</td></tr>
									<tr><td>nas</td><td>nasal</td></tr>
									<tr><td>temp</td><td>temporal</td></tr>
									<tr><td>med</td><td>medial</td></tr>
									<tr><td>lat</td><td>lateral</td></tr>
									<tr><td>dermato</td><td>dematochalasis</td></tr>
									<tr><td>w/</td><td>with</td></tr>
									<tr><td>lac</td><td>laceration</td></tr>
									<tr><td>lacr</td><td>lacrimal</td></tr>
									<tr><td>dcr</td><td>DCR</td></tr>
									<tr><td>bcc</td><td>BCC</td></tr>
									<tr><td>scc</td><td>SCC</td></tr>
									<tr><td>sebca</td><td>sebaceous cell</td></tr>
									<tr><td>tr</td><td>trace</td></tr>
		                    </table>
						</blockquote>
					</div>
				</div>
			</div>
			
			<div id="accordion_antseg_group" name="accordion_group" class="ui-accordion" style="text-align:left;margin:10;padding:20;">
				<div name="antseg">
					<h3 class="antseg" id="antseg_0" name="antseg_group">Anterior Segment: Shorthand Walk Through</h3>
					<div id="antseg_input" class="ANTSEG" style="text-align:left;margin:0;padding:0;">
							<a name="example_antseg"></a>
							
							<blockquote class="style2">
								<h4 class="underline">Keyboard Entry</h4>
								<textarea class="kb_entry">D;bc.+2 inj;bk.med pter;rk.moderate endo gut.a;bac.+1 fc, +1 pig cells
								</textarea>
								<img src="/openemr/interface/forms/eye_mag/images/sh_antseg.png" alt="Shorthand Example: Anterior Segment">
								<br />
							</blockquote>
					</div>
					<h3>Anterior Segment: Example Output</h3>
					<div id="external_output" style="text-align:left;margin:0;padding:20;">
						<blockquote class="style2">
							Input:<br /><br />
							<b>D;bc.+2 inj;bk.med pter;rk.moderate endo gut.a;bac.+1 fc, +1 pig cells</b><br />
							<br />						 
							Output:
							<br /><br />
							<div class="output_EMR">
								<h4>Eye Exam</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_antseg_EMR.png" width="95%" alt="Shorthand Example: openEMR">
							</div>
							<div class="output_reports">
								<h4>Reports</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_antseg_report.png" width="95%" alt="Shorthand Example: Reports">
							</div>	
						</blockquote>
					</div>
					<h3>Anterior Segment: Location Codes and Abbreviations</h3>
					<div id="antseg_codes" style="clear:both; border:0pt solid black;text-align:left;">
						<a name="output_antseg"></a>
						<blockquote class="style2">
							<table style="border:0pt solid black;margin:10;padding:10;">
								<tr style="margin:20;border-bottom:1pt solid black;background-color:#c0C0c0;padding:10px;">
									<th style="width:1in;">Clinical Field</th><th style="width:1.3in;">Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
								<tr >
									<td>Default values</td><td>D or d</td>
									<td><span class="field">d</span>;<br /><span class="field">D</span>;</td>
									<td>All fields with defined default values are <b>erased</b> and filled with default values.<br />Fields without defined default values are not affected. </td>
								</tr>
								<tr >
									<td>Conjunctiva</td><td>Right = rc<br />Left = lc<br />Both = bc or c</td>
									<td><span class="field">rc.</span>+1 inj<br /><span class="field">c.</span>med pter</td>
									<td>"+1 injection" (right conj only)<br />"medial pterygium" (both right and left fields are filled)</td>
								</tr>
								<tr>
									<td>Cornea</td><td>Right = rc<br />Left = lc<br />Both = bk or k</td>
									<td><span class="field">rk.</span>+3 spk<br /><span class="field">k.</span>+2 end gut<b style="color:green">;</b><span class="field">rk.</span>+1 str edema<b style="color:green">.a</b></td>
									<td>"+3 SPK" (right cornea only)<br />"+2 endothelial guttatae" (both cornea fields) AND "+1 stromal edema" (appended to Right cornea field)</td>
								</tr>
								<tr>
									<td>Anterior Chamber</td><td>Right = rac<br />Left = lac<br />Both = bac or ac</td>
									<td><span class="field">rac.</span>+1 fc<br><span class="field">ac.</span>+2 flare</td>
									<td>"+1 flare/cell" (right A/C field only)<br />"+2 flare" (both A/C fields)</td>
								</tr>
								<tr>
									<td>Lens</td><td>Right = rl<br />Left = ll<br />Both = bl or l</td>
									<td><span class="field">RL.</span>+2 NS<br /><span class="field">ll.</span>+2 NS<b style="color:green">;</b><span class="field">l.</span>+3 ant cort spokes.a</td>
									<td>"+2 NS" (right lens only)<br />"+2 NS" (both lens fields) AND "+3 anterior cortical spokes" (appended to both lenses)</td>
								</tr>
								<tr>
									<td>Iris</td><td>Right = ri<br />Left = li<br />Both = bi or i</td>
									<td><b style="color:red">bi.</b>12 0 iridotomy<br /><span class="field">ri.</span>+2 TI defects<b style="color:green">.a</b><b style="color:navy">;</b><span class="field">li</span>.round</td>
									<td>"12 o'clock iriditomy" (both iris fields)<br />", +2 TI defects" (right iris field AND "round" (left iris field only)</td>
								</tr>
								<tr>
									<td>Gonio</td><td>Right = rg<br />Left = lg<br />Both = bg or g</td>
									<td><span class="field">rg.</span>ss 360<br /><span class="field">lg.</span>3-5 o angle rec</td>
									<td>SS 360<br />3-5 o'clock angle recession</td>
								</tr>
								<tr>
									<td>Pachymetry</td><td>Right = rp<br />Left = lp<br />Both = bp or p</td>
									<td><span class="field">lp.</span>625 um<br /><span class="field">p.</span>550 um</td>
									<td>"625 um" (left pachymetry field)<br />"500 um" (both pachymetry fields)</td>
								</tr>
								<tr>
									<td>Schirmer I</td><td>Right = rsch1<br />Left = lsch1<br />Both = bsch1 or sch1</td>
									<td><span class="field">rsch1.</span>5mm<br /><span class="field">sch1.</span>> 10mm/5 minutes</td>
									<td>"5mm" (right field only)<br />> 10mm/5 minutes" (both fields)</td>
								</tr>
								<tr>
									<td>Schirmer II</td><td>Right = rsch2<br />Left = lsch2<br />Both = bsch2 or sch2</td>
									<td><span class="field">rsch2.</span>9 mm<br /><span class="field">sch2.</span>> 10mm/5 minutes</td>
									<td>"9 mm" (right field only)<br />> 10mm/5 minutes" (both fields)</td>
								</tr>
								<tr>
									<td>Tear Break-up Time</td><td>Right = RTBUT<br />Left = LTBUT<br />Both = BTBUT or tbut</td>
									<td><b style="color:red">tbut.</b>> 10 seconds<br /><b style="color:red">Rtbut.</b>5 secs<b style="color:green">;</b><b style="color:red">ltbut.</b>9 seconds<b style="color:green">;</b></td>
									<td>"10 seconds" (both fields)<br />"5 seconds" (right) AND "9 seconds" (left)</td>
								</tr>
							</table>
							<br />*<i>case insensitive</i><br />
							**<i>The default action is to replace the field with the new text.  
							<br />
							Adding <b>".a"</b> at the end of a <b>text</b> section will append the current text instead of replacing it.
							<br >For example, entering <b>"bk.+2 str scarring.a"</b> will <class="underline bold">append</class> "+2 stromal scarring"</b> 
							to both the right (rc) and left cornea fields (lc).</i></small>
							<br>
						
							<br />
							<a name="abbrev_antseg"></a>
							<h2 class="underline">External Shorthand Abbreviations:</h2>

							The following terms will be expanded from their shorthand to full expression in the EMR fields:
							<table style="border:1pt solid black;margin:10;width:85%;">
									<tr class="style3"><th>Enter this:</th><th>Get this:</th></tr>
									<tr><td>inf</td><td>inferior</td></tr>
									<tr><td>sup</td><td>superior</td></tr>
									<tr><td>nas</td><td>nasal</td></tr>
									<tr><td>temp</td><td>temporal</td></tr>
									<tr><td>med</td><td>medial</td></tr>
									<tr><td>lat</td><td>lateral</td></tr>
									<tr><td>dermato</td><td>dematochalasis</td></tr>
									<tr><td>w/</td><td>with</td></tr>
									<tr><td>lac</td><td>laceration</td></tr>
									<tr><td>lacr</td><td>lacrimal</td></tr>
									<tr><td>dcr</td><td>DCR</td></tr>
									<tr><td>bcc</td><td>BCC</td></tr>
									<tr><td>scc</td><td>SCC</td></tr>
									<tr><td>sebca</td><td>sebaceous cell</td></tr>
									<tr><td>tr</td><td>trace</td></tr>
			                </table>
						</blockquote>
					</div>
				</div>
			</div>
				
			<div id="accordion_retina_group" name="accordion_group" class="ui-accordion" style="text-align:left;margin:10;padding:20;">
				<div name="retina">
					<h3 class="retina">Retina: Shorthand Walk Through</h3>
					<div id="retina_input" class="RETINA" style="text-align:left;margin:0;padding:0;">
						<blockquote class="style2">
							<h4 class="underline">Keyboard Entry</h4>
							<textarea class="kb_entry">D;bd.+2 bowtie pallor;rc.0.6Vx0.4H w/ inf notch;lc.0.5;rmac.+2 BDR, +CSME;lmac.flat, tr BDR;v.+PPDR, ++venous beading;rp.ht 1 o,no vh;
							</textarea>
							<img src="/openemr/interface/forms/eye_mag/images/sh_retina.png" alt="Shorthand Example: Anterior Segment">
							<br />
						</blockquote>
					</div>
					<h3>Retina: Example Output</h3>
					<div id="retina_output" style="text-align:left;margin:0;padding:20;">
						<blockquote class="style2">
							Input:<br /><br />
							<b>D;bd.+2 bowtie pallor;rc.0.6Vx0.4H w/ inf notch;lc.0.5;rmac.+2 BDR, +CSME;lmac.flat, tr BDR;v.+PPDR, ++venous beading;rp.ht 1 o,no vh;
							</b><br />
							<br />						 
							Output:
							<br /><br />
							<div class="output_EMR">
								<h4>Eye Exam</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_retina_EMR.png" width="95%" alt="Shorthand Example: openEMR">
							</div>
							<div class="output_reports">
								<h4>Reports</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_retina_report.png" width="95%" alt="Shorthand Example: Reports">
							</div>	
						</blockquote>
					</div>
					<h3>Retina: Location Codes and Abbreviations</h3>
					<div id="retina_codes" style="clear:both; border:0pt solid black;text-align:left;">
						<blockquote class="style2">
							<table style="border:1pt solid black;margin:10;width:85%;">
									<tr style="margin:20;border-bottom:1pt solid black;background-color:#c0C0c0">
										<th style="width:1in;">Clinical Field</th><th style="width:1.5in;">Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
									<tr >
										<td>Default values</td><td>D or d</td>
										<td><span class="field">d</span>;<br /><span class="field">D</span>;</td>
										<td>All fields with defined default values are <b>erased</b> and filled with default values.<br />Fields without defined default values are not affected. </td>
									</tr>
									<tr >
										<td>Disc</td>
										<td>Right = rd<br />Left = ld<br />Both = bd or d</td>
										<td><span class="field">rd.</span>temp pallor, PPA<br /><span class="field">c.</span>NVD at 5 o</td>
										<td>"temporal pallor, PPA" (right disc only)<br />"NVD at 5 o'clock" (both right and left disc fields)</td>
									</tr>
									<tr>
										<td>Cup</td><td>Right = rc<br />Left = lc<br />Both = bc or c</td>
										<td><span class="field">rc.</span>0.5 w/ inf notch<br /><span class="field">c.</span>temp scalloping, 0.5<b style="color:green">.a</b><b style="color:green">;</b><span class="field">rk.</span>+1 str edema<b style="color:green">.a</b></td>
										<td>"+3 SPK" (right cornea only)<br />"temporal scalloping, 0.5" (appended to both cup fields)</td>
									</tr>
									<tr>
										<td>Macula</td><td>Right = rmac<br />Left = lmac<br />Both = bmac or mac</td>
										<td><span class="field">rmac.</span>central scar 500um<br><span class="field">mac.</span>soft drusen, - heme.a</td>
										<td>"central scar 500um" (right macular field only)<br />"soft drusen, - heme" (appended to both macular fields)</td>
									</tr>
									<tr>
										<td>Vessels</td><td>Right = rv<br />Left = lv<br />Both = bv or v</td>
										<td><span class="field">RV.</span>1:2, +2 BDR<br /><span class="field">lv.</span>+CSME w/ hard exudate sup to fov (300um)<b style="color:green">;</b><span class="field">v.</span>narrow arterioles, 1:2<b style="color:green">.a;</b></td>
										<td>"1:2, +2 BDR" (right vessels only)<br />"+CSME with hard exudate superior to fovea (300um)" (left vessel field only) AND "narrow arterioles, 1:2" (appended to both vessel fields)</td>
									</tr>
									<tr>
										<td>Periphery</td><td>Right = rp<br />Left = lp<br />Both = bp or p</td>
										<td><span class="field">rp.</span>12 0 ht, no heme, amenable to bubble<b style="color:green">;</b><br /><b style="color:red">bp.</b>1 clock hour of lattice 2 o<b style="color:green">.a</b><b style="color:navy">;</b><span class="field">li</span>.round</td>
										<td>"12 o'clock horseshoe tear, no heme, amenable to bubble" (right periphery field)<br />"1 clock hour of lattice 2 o'clock" (appended to both periphery fields)</td>
									</tr>
									<tr>
										<td>Central Macular Thickness</td><td>Right = rcmt<br />Left = lcmt<br />Both = bcmt or cmt</td>
										<td><span class="field">rcmt.</span>254<br /><span class="field">cmt.</span>flat</td>
										<td>254 (right CMT only)<br />flat (both CMT fields)</td>
									</tr>
							</table>
							<br />*<i>case insensitive</i><br />
							**<i>The default action is to replace the field with the new text.  
							<br />
							Adding <b>".a"</b> at the end of a <b>text</b> section will append the current text instead of replacing it.
							<br >For example, entering <b>"bk.+2 str scarring.a"</b> will <class="underline bold">append</class> "+2 stromal scarring"</b> 
							to both the right (rc) and left cornea fields (lc).</i></small>
							<br>
							
							<br />
							<a name="abbrev_retina"></a>
							<h2 class="underline">Retina Shorthand Abbreviations:</h2>
							
							The following terms will be expanded from their shorthand to full expression in the EMR fields:
					
							<table style="border:1pt solid black;margin:10;width:85%;">
								<tr class="style3"><th>Enter this:</th><th>Get this:</th></tr>
								<tr><td>inf</td><td>inferior</td></tr>
	                                                        <tr><td>sup</td><td>superior</td></tr>
	                                                        <tr><td>nas</td><td>nasal</td></tr>
	                                                        <tr><td>temp</td><td>temporal</td></tr>
	                                                        <tr><td>med</td><td>medial</td></tr>
	                                                        <tr><td>lat</td><td>lateral</td></tr>
	                                                        <tr><td>csme</td><td>CSME</td></tr>
	                                                        <tr><td>w/</td><td>with</td></tr>
	                                                        <tr><td>bdr</td><td>BDR</td></tr>
	                                                        <tr><td>ppdr</td><td>PPDR</td></tr>
	                                                        <tr><td>ht</td><td>horsheshoe tear</td></tr>
	                                                        <tr><td>ab</td><td>air bubble</td></tr>
	                                                        <tr><td>c3f8</td><td>C3F8</td></tr>
	                                                        <tr><td>ma</td><td>macroaneurysm</td></tr>
	                                                        <tr><td>tr</td><td>trace</td></tr>
	                                                        <tr><td>mias</td><td>microaneurysm</td></tr>
	                                                        <tr><td>ped</td><td>PED</td></tr>
	                                                        <tr><td>1 o</td><td> 1 o'clock</td></tr>
	                                                        <tr><td>2 o</td><td>2 o'clock</td></tr>
	                                                        <tr><td>3 o</td><td> 3 o'clock</td></tr>
	                                                        <tr><td>4 o</td><td> 4 o'clock</td></tr>
	                                                        <tr><td>5 o</td><td> 5 o'clock</td></tr>
	                                                        <tr><td>6 o</td><td> 6 o'clock</td></tr>
	                                                        <tr><td>7 o</td><td> 7 o'clock</td></tr>
	                                                        <tr><td>8 o</td><td> 8 o'clock</td></tr>
	                                                        <tr><td>9 o</td><td> 9 o'clock</td></tr>
	                                                        <tr><td>10 o</td><td> 10 o'clock</td></tr>
	                                                        <tr><td>11 o</td><td> 11 o'clock</td></tr>
	                                                        <tr><td>12 o</td><td> 12 o'clock</td></tr>
	                                                        <tr><td>mac</td><td>macula</td></tr>
	                                                        <tr><td>fov</td><td>fovea</td></tr>
	                                                        <tr><td>vh</td><td>vitreous hemorrhage</td></tr>
	                        </table>
						</blockquote>
					</div>
				</div>
			</div>

			<div id="accordion_neuro_group" name="accordion_group" class="ui-accordion" style="text-align:left;margin:10;padding:20;">
				<div name="neuro">
					<h3 class="neuro">Neuro: Shorthand Walk Through</h3>
					<div id="neuro_input" class="neuro" style="text-align:left;margin:0;padding:0;">
						<blockquote class="style2">
							<h4 class="underline">Keyboard Entry</h4>
							<textarea class="kb_entry">scDist;5.8ix 1rht;4.10ix;6.6ix;2.15xt;8.5ix;ccDist;4.5ix;5.ortho;6.ortho
							</textarea>
							<img src="/openemr/interface/forms/eye_mag/images/sh_neuro.png" alt="Shorthand Example: Anterior Segment">
							<br />
						</blockquote>
					</div>
					<h3>Neuro: Example Output</h3>
					<div id="neuro_output" style="text-align:left;margin:0;padding:20;">
						<a name="output_neuro"></a>
						<blockquote class="style2">
							Input:<br /><br />
							<b>scDist;5.8ix 1rht;4.10ix;6.6ix;2.15xt;8.5ix;ccDist;4.5ix;5.ortho;6.ortho;</b><br />
							<br />						 
							Output:
							<br /><br />
							<div class="output_EMR">
								<h4>Eye Exam</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_neuro_EMR1.png"  style="height: 200;width:45%;margin:12 0 0 20;padding-left:10" alt="Shorthand Example: openEMR">
								<img src="/openemr/interface/forms/eye_mag/images/sh_neuro_EMR2.png"  style="float:right;height: 200;width:45%;margin:10 0 0 20;padding-left:10" alt="Shorthand Example: openEMR">
							</div>
							<div class="output_reports">
								<h4>Reports</h4>
								<img src="/openemr/interface/forms/eye_mag/images/sh_neuro_report.png" width="95%" alt="Shorthand Example: Reports">
							</div>	
						</blockquote>
					</div>
					<h3>Neuro: Location Codes and Abbreviations</h3>
					<div id="neuro_codes" style="clear:both; border:0pt solid black;text-align:left;">
						<blockquote class="style2">
							<table style="border:1pt solid black;margin:10;width:85%;">
									<tr style="margin:20;border-bottom:1pt solid black;background-color:#c0C0c0">
										<th style="width:1in;">Clinical Field</th><th style="width:1.5in;">Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
									<tr >
										<td>Default values</td><td>D or d</td>
										<td><span class="field">d</span>;<br /><span class="field">D</span>;</td>
										<td>All fields with defined default values are <b>erased</b> and filled with default values.<br />Fields without defined default values are not affected. </td>
									</tr>
									<tr>
										<td>Without correction at Distance</td><td>scDist</td>
										<td><b style="color:red;">scdist</b><b style="color:green;">;</a></td>
										<td>scDIST is selected for ensuing values.</td>
									</tr>
									<tr>
										<td>With correction at Distance</td><td>scDist</td>
										<td><b style="color:red;">ccdist</b><b style="color:green;">;</a></td>
										<td>ccDIST is selected for ensuing values.</td>
									</tr><tr>
										<td>Without correction at Near</td><td>scNear</td>
										<td><b style="color:red;">scdist</b><b style="color:green;">;</a></td>
										<td>scDIST is selected for ensuing values.</td>
									</tr>
									<tr>
										<td>With correction at Near</td><td>scNear</td>
										<td><b style="color:red;">scdist</b><b style="color:green;">;</a></td>
										<td>scDIST is selected for ensuing values.</td>
									</tr>
							</table>
							<br />*<i>case insensitive</i><br />
							**<i>The default action is to replace the field with the new text.  
							<br />
							Adding <b>".a"</b> at the end of a <b>text</b> section will append the current text instead of replacing it.
							<br >For example, entering <b>"bk.+2 str scarring.a"</b> will <class="underline bold">append</class> "+2 stromal scarring"</b> 
							to both the right (rc) and left cornea fields (lc).</i></small>
							<br>
							
							<br />
							<a name="abbrev_neuro"></a>
							<h2 class="underline">Neuro Shorthand Abbreviations:</h2>
							
							The following terms will be expanded from their shorthand to full expression in the EMR fields:
					
							<table style="border:1pt solid black;margin:10;width:85%;">
								<tr class="style3"><th>Strabismus</th><th>Enter this:</th><th>Get this:</th></tr>
	                            <tr><td>Exophoria</td><td>x</td><td>X</td></tr>
                                <tr><td>Intermittent Esotropia</td><td>ie or e(t)</td><td>E(T)</td></tr>
                                <tr><td>Esoptropia</td><td>et</td><td>ET</td></tr>
                                <tr><td>Esophoria</td><td>e</td><td>E</td></tr>
                                <tr><td>Intermittent Exotropia</td><td>ix or x(t)</td><td>X(T)</td></tr>
                                <tr><td>Exoptropia</td><td>xt</td><td>XT</td></tr>
                                <tr><td>Hyperphoria</td><td>h</td><td>H</td></tr>
                                <tr><td>Intermittent Hypertropia</td><td>H(T)</td><td>H(T)</td></tr>
                                <tr><td>Hypertropia</td><td>rht<br />lht</td><td>RHT<br />LHT</td></tr>
                                <tr><td>Hypotropia</td><td>hyt</td><td>HyT</td></tr>
                                
	                        </table>
						</blockquote>
					</div>
				</div>
			</div>

		</div>
	</body>
</html>
	<?
exit;



?>
