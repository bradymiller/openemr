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

$zone    = $_REQUEST['zone'];

?>
<html>
	<head>
		<style>
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
			}
			blockquote.style2 {
				font: 14px/22px normal helvetica, sans-serif;
				margin-top: 10px;
				margin-bottom: 10px;
				margin-left: 50px;
				padding-left: 15px;
				border-left: 5px solid #ccc;
			} 
			.underline {
				text-decoration: underline;

			}
		</style>
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
    <meta name="description" content="openEMR: Eye Exam Help">
    <meta name="author" content="openEMR: ophthalmology help">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- jQuery library -->
<script src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="<?php echo $GLOBALS['webroot'] ?>/library/js/bootstrap.min.js"></script>  
 
	</head>
	<body>
		<h2><u>Keyboard Shorthand Entry:</u></h2>
		<h4><b>Usage:</b>  location.text(.a)(;)</h4><br />
		<blockquote class="style2"><i>where: <br /></i>
			<b>location</b> is the shorthand for the field.<br/>
			<b>text</b> is the complete or shorthand data to enter into this field.
			<br />
			<b>location</b> and <b>text</b> are separated by a "<b>.</b>" period/fullstop.
			<br />
			The trailing "<b>.a</b>" 
			is optional and will <b>append</b> the <b>text</b> to the data already in the field, instead of replacing it.<br />
			The semi-colon "<b>;</b>" is used to divide entries, allowing multiple field entries simultaneously. <br />
			<small><i>The semi-colon separates entries and cannot be used within a text field.</i></small><br />
		</blockquote>
			<?
		if ($zone == 'ext') {
			?>
			<br />
			<div id="ext" name="ext">
				<hr />
				<h2>External Exam:</h2>
				Click for an <a href="#example_ext">example</a>
				<br />

				<table style="border:0pt solid black;margin:10;">
					<tr style="margin:20;border-bottom:1pt solid black;background-color:#c0C0c0"><th>Clinical Field</th><th>Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
					<tr >
						<td>Default values</td><td>D or d</td>
						<td>d;<br />D;</td>
						<td>All fields with defined default values are <b>erased</b> and filled with default values.<br />Fields without defined values are not affected. </td>
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
				Adding <b>".a"</b> at the end of a section text will append the current text instead of replacing it.
				<br >For example, <b>entering "4xL.+2 meibomitis.a" will <u>append</u> "+2 meibomitis"</b> 
				to each of the eyelid 
				fields, RUL/RLL/LUL/LLL.</i>
				<br><hr />
						<a name="example_ext"></a>
						<h2>External Example:</h2>
				<blockquote class="style2">
					<h3>Keyboard Input:</h3>
					<b>D;bll.+2 meibomitis;rll.frank ect, 7x6mm lid margin bcc lat.a;bul.2mm ptosis;rul.+3 dermato.a</b><br />
					<img src="/openemr/interface/forms/eye_mag/images/sh_ext.png" width="80%" alt="Shorthand Example: Anterior Segment">
					<br />
					<br />
					<div style="float:left;border:1pt solid black;width:45%;padding:0 10;margin:10;">
						<h3>Appearance in openEMR: Eye Exam</h3>
						<img src="/openemr/interface/forms/eye_mag/images/sh_ext_EMR.png" width="95%" alt="Shorthand Example: Anterior Segment">
					</div>
				
					<div style="float:left;border:1pt solid black;width:45%;padding:0 10;margin:10;">
						<h3>Appearance in Reports:</h3>
						<img src="/openemr/interface/forms/eye_mag/images/sh_ext_report.png" width="95%" alt="Shorthand Example: Anterior Segment">
					</div>
				</blockquote>
			</div>
			<?
		} else if ($zone == 'antseg') {
			?>
				<br />
			<div id="antseg" name="antseg">
				<hr />
				<h2>Anterior Segment Exam:</h2>
				Click for an <a href="#example_antseg">example</a>
				<br />

						<table style="border:0pt solid black;margin:10;padding:10;">
							<tr style="margin:20;border-bottom:1pt solid black;background-color:#c0C0c0;padding:10px;">
								<th style="width:1in;">Clinical Field</th><th style="width:1.3in;">Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
							<tr >
								<td>Default values</td><td>D or d</td>
								<td><b style="color:red">d</b>;<br /><b style="color:red">D</b>;</td>
								<td>All fields with defined default values are <b>erased</b> and filled with default values.<br />Fields without defined values are not affected. </td>
						</tr>
							<tr >
								<td>Conjunctiva</td><td>Right = rc<br />Left = lc<br />Both = bc or c</td>
								<td><b style="color:red">rc.</b>+1 inj<br /><b style="color:red">c.</b>med pter</td>
								<td>"+1 injection" (right conj only)<br />"medial pterygium" (both right and left fields are filled)</td>
							</tr>
							<tr>
								<td>Cornea</td><td>Right = rc<br />Left = lc<br />Both = bk or k</td>
								<td><b style="color:red">rk.</b>+3 spk<br /><b style="color:red">k.</b>+2 end gut<b style="color:green">;</b><b style="color:red">rk.</b>+1 str edema<b style="color:green">.a</b></td>
								<td>"+3 SPK" (right cornea only)<br />"+2 endothelial guttatae" (both cornea fields) AND "+1 stromal edema" (appended to Right cornea field)</td>
							</tr>
							<tr>
								<td>Anterior Chamber</td><td>Right = rac<br />Left = lac<br />Both = bac or ac</td>
								<td><b style="color:red">rac.</b>+1 fc<br><b style="color:red">ac.</b>+2 flare</td>
								<td>"+1 flare/cell" (right A/C field only)<br />"+2 flare" (both A/C fields)</td>
							</tr>
							<tr>
								<td>Lens</td><td>Right = rl<br />Left = ll<br />Both = bl or l</td>
								<td><b style="color:red">RL.</b>+2 NS<br /><b style="color:red">ll.</b>+2 NS<b style="color:green">;</b><b style="color:red">l.</b>+3 ant cort spokes.a</td>
								<td>"+2 NS" (right lens only)<br />"+2 NS" (both lens fields) AND "+3 anterior cortical spokes" (appended to both lenses)</td>
							</tr>
							<tr>
								<td>Iris</td><td>Right = ri<br />Left = li<br />Both = bi or i</td>
								<td><b style="color:red">bi.</b>12 0 iridotomy<br /><b style="color:red">ri.</b>+2 TI defects<b style="color:green">.a</b><b style="color:navy">;</b><b style="color:red">li</b>.round</td>
								<td>"12 o'clock iriditomy" (both iris fields)<br />", +2 TI defects" (right iris field AND "round" (left iris field only)</td>
							</tr>
							<tr>
								<td>Gonio</td><td>Right = rg<br />Left = lg<br />Both = bg or g</td>
								<td><b style="color:red">rg.</b>ss 360<br /><b style="color:red">lg.</b>3-5 o angle rec</td>
								<td>SS 360<br />3-5 o'clock angle recession</td>
							</tr>
							<tr>
								<td>Pachymetry</td><td>Right = rp<br />Left = lp<br />Both = bp or p</td>
								<td><b style="color:red">lp.</b>625 um<br /><b style="color:red">p.</b>550 um</td>
								<td>"625 um" (left pachymetry field)<br />"500 um" (both pachymetry fields)</td>
							</tr>
							<tr>
								<td>Schirmer I</td><td>Right = rsch1<br />Left = lsch1<br />Both = bsch1 or sch1</td>
								<td><b style="color:red">rsch1.</b>5mm<br /><b style="color:red">sch1.</b>> 10mm/5 minutes</td>
								<td>"5mm" (right field only)<br />> 10mm/5 minutes" (both fields)</td>
							</tr>
							<tr>
								<td>Schirmer II</td><td>Right = rsch2<br />Left = lsch2<br />Both = bsch2 or sch2</td>
								<td><b style="color:red">rsch2.</b>9 mm<br /><b style="color:red">sch2.</b>> 10mm/5 minutes</td>
								<td>"9 mm" (right field only)<br />> 10mm/5 minutes" (both fields)</td>
							</tr>
							<tr>
								<td>Tear Break-up Time</td><td>Right = RTBUT<br />Left = LTBUT<br />Both = BTBUT or tbut</td>
								<td><b style="color:red">tbut.</b>> 10 seconds<br /><b style="color:red">Rtbut.</b>5 secs<b style="color:green">;</b><b style="color:red">ltbut.</b>9 seconds<b style="color:green">;</b></td>
								<td>"10 seconds" (both fields)<br />"5 seconds" (right) AND "9 seconds" (left)</td>
							</tr>
						</table>
						<br /><small>*<i>case insensitive</i></small><br />
						<small>**<i>The default action is to replace the field with the new text.  
							<br />
						Adding <b>".a"</b> at the end of a section text will append the current text instead of replacing it.
						<br >For example, entering <b>"bk.+2 str scarring.a"</b> will <class="underline bold">append</class> "+2 stromal scarring"</b> 
						to both the right (rc) and left cornea fields (lc).</i></small>
				<br><hr />
				
						<a name="example_antseg"><h2>Anterior Segment Example:</h2></a>
				<blockquote class="style2">

					<h3 class="underline">Keyboard Entry</h3>
					<b>D;bc.+2 inj;bk.med pter;rk.moderate endo gut.a;bac.+1 fc, +1 pig cells</b><br />
					<img src="/openemr/interface/forms/eye_mag/images/sh_antseg.png" alt="Shorthand Example: Anterior Segment">
					<br />
					<br />
					<div style="float:left;border:1pt solid black;width:45%;padding:0 10;margin:10;">
						<h3>Appearance in openEMR: Eye Exam</h3>
						<img src="/openemr/interface/forms/eye_mag/images/sh_antseg_EMR.png" width="95%" alt="Shorthand Example: Anterior Segment">
					</div>
					<div style="float:left;border:1pt solid black;width:45%;padding:0 10;margin:10;">
						<h3>Appearance in Reports:</h3>
						<img src="/openemr/interface/forms/eye_mag/images/sh_antseg_report.png" width="95%" alt="Shorthand Example: Anterior Segment">
					</div>

				</blockquote>
			<br /><hr /><br />
			</div><?
		} else if ($zone == 'retina') {
			?>
			<br />
			<div id="retina" name="retina">
			<hr />
			<h2>Retina Exam:</h2>
			Click for an <a href="#example_retina">example</a>
			<br />

					<table style="border:0pt solid black;margin:10;">
						<tr style="margin:20;border-bottom:1pt solid black;background-color:#c0C0c0">
							<th style="width:1in;">Clinical Field</th><th style="width:1.5in;">Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
						<tr >
							<td>Default values</td><td>D or d</td>
							<td><b style="color:red">d</b>;<br /><b style="color:red">D</b>;</td>
							<td>All fields with defined default values are <b>erased</b> and filled with default values.<br />Fields without defined values are not affected. </td>
						</tr>
						<tr >
							<td>Disc</td>
							<td>Right = rd<br />Left = ld<br />Both = bd or d</td>
							<td><b style="color:red">rd.</b>temp pallor, PPA<br /><b style="color:red">c.</b>NVD at 5 o</td>
							<td>"temporal pallor, PPA" (right disc only)<br />"NVD at 5 o'clock" (both right and left disc fields)</td>
						</tr>
						<tr>
							<td>Cup</td><td>Right = rc<br />Left = lc<br />Both = bc or c</td>
							<td><b style="color:red">rc.</b>0.5 w/ inf notch<br /><b style="color:red">c.</b>temp scalloping, 0.5<b style="color:green">.a</b><b style="color:green">;</b><b style="color:red">rk.</b>+1 str edema<b style="color:green">.a</b></td>
							<td>"+3 SPK" (right cornea only)<br />"temporal scalloping, 0.5" (appended to both cup fields)</td>
						</tr>
						<tr>
							<td>Macula</td><td>Right = rmac<br />Left = lmac<br />Both = bmac or mac</td>
							<td><b style="color:red">rmac.</b>central scar 500um<br><b style="color:red">mac.</b>soft drusen, - heme.a</td>
							<td>"central scar 500um" (right macular field only)<br />"soft drusen, - heme" (appended to both macular fields)</td>
						</tr>
						<tr>
							<td>Vessels</td><td>Right = rv<br />Left = lv<br />Both = bv or v</td>
							<td><b style="color:red">RV.</b>1:2, +2 BDR<br /><b style="color:red">lv.</b>+CSME w/ hard exudate sup to fov (300um)<b style="color:green">;</b><b style="color:red">v.</b>narrow arterioles, 1:2<b style="color:green">.a;</b></td>
							<td>"1:2, +2 BDR" (right vessels only)<br />"+CSME with hard exudate superior to fovea (300um)" (left vessel field only) AND "narrow arterioles, 1:2" (appended to both vessel fields)</td>
						</tr>
						<tr>
							<td>Periphery</td><td>Right = rp<br />Left = lp<br />Both = bp or p</td>
							<td><b style="color:red">rp.</b>12 0 ht, no heme, amenable to bubble<b style="color:green">;</b><br /><b style="color:red">bp.</b>1 clock hour of lattice 2 o<b style="color:green">.a</b><b style="color:navy">;</b><b style="color:red">li</b>.round</td>
							<td>"12 o'clock horseshoe tear, no heme, amenable to bubble" (right periphery field)<br />"1 clock hour of lattice 2 o'clock" (appended to both periphery fields)</td>
						</tr>
						<tr>
							<td>Central Macular Thickness</td><td>Right = rcmt<br />Left = lcmt<br />Both = bcmt or cmt</td>
							<td><b style="color:red">rcmt.</b>254<br /><b style="color:red">cmt.</b>flat</td>
							<td>254 (right CMT only)<br />flat (both CMT fields)</td>
						</tr>
						
					</table>
					<br /><small>*<i>case insensitive</i></small><br />
					<small>**<i>The default action is to replace the field with the new text.  
						<br />
					Adding <b>".a"</b> at the end of a section text will append the current text instead of replacing it.
					<br >For example, <b>entering "4xL.+2 meibomitis.a" will <u>append</u> "+2 meibomitis"</b> 
					to each of the eyelid 
					fields, RUL/RLL/LUL/LLL.</i></small>
			<br><hr />
			<!--
					<a name="example_ext"><b>Example:</b></a>
			<blockquote class="style2">
				<h3>Keyboard Input:</h3>
				<b>D;bll.+2 meibomitis;rll.frank ect, 7x6mm lid margin bcc lat.a;bul.2mm ptosis;rul.+3 dermato.a</b><br />
				<br />
				<h3>Appearance in openEMR: Eye Exam:</h3>
				<img src="/openemr/interface/forms/eye_mag/images/kb_ext_EHR_example.png" alt="Keyboard Example: External">
			<br />
				<h3>Appearance in Reports:</h3>
				<img src="/openemr/interface/forms/eye_mag/images/kb_ext_example.png" alt="Keyboard Example: External">

			</blockquote>
			-->
			</div>
			<?
		} else if ($zone == 'neuro') {
		}
		?>
	</body>
</html>
	<?
exit;



?>
