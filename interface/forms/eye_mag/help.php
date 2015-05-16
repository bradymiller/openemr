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

$zone    = $_REQUEST['zone'];

?>
<html>
<head>
<style>
table td {
	text-align:left;
	vertical-align: top;
	margin:20;
	border:1pt solid black;
	padding:2 2 2 6;

}
blockquote.style2 {
  font: 14px/22px normal helvetica, sans-serif;
  margin-top: 10px;
  margin-bottom: 10px;
  margin-left: 50px;
  padding-left: 15px;
  border-left: 5px solid #ccc;
} 
</style>
</head>
	<body>
		<h2>Keyboard Shorthand Entry:</h2>
<b>Usage:</b>  location.text(.a)(;)<br />where: <br />
<blockquote class="style2"><b>location</b> is the shorthand for the field into which you are entering the <b>text</b>,
<br />
<b>location</b> and <b>text</b> are separated by a "<b>.</b>" period/fullstop,<br />the trailing "<b>.a</b>" 
is optional and will <b>append</b> the <b>text</b> to the data already in the field.<br />
the "<b>;</b>" is used to divide entries, allowing multiple field entries simultaneously. <br />
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
					<td>All fields are filled with default values </td>
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
			<br /><small>*<i>case insensitive</i></small><br />
			<small>**<i>The default action is to replace the field with the new text.  
				<br />
			Adding <b>".a"</b> at the end of a section text will append the current text instead of replacing it.
			<br >For example, <b>entering "4xL.+2 meibomitis.a" will <u>append</u> "+2 meibomitis"</b> 
			to each of the eyelid 
			fields, RUL/RLL/LUL/LLL.</i></small>
	<br><hr />
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

			<table style="border:0pt solid black;margin:10;">
				<tr style="margin:20;border-bottom:1pt solid black;background-color:#c0C0c0">
					<th style="width:1in;">Clinical Field</th><th style="width:1.3in;">Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
				<tr >
					<td>Default values</td><td>D or d</td>
					<td><b style="color:red">d</b>;<br /><b style="color:red">D</b>;</td>
					<td>All fields are filled with default values </td>
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
					<td>"625 um" (left s<br />medial 2cm SCC</td>
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
			<br >For example, <b>entering "4xL.+2 meibomitis.a" will <u>append</u> "+2 meibomitis"</b> 
			to each of the eyelid 
			fields, RUL/RLL/LUL/LLL.</i></small>
	<br><hr />
	<!--
			<a name="example_antseg"><b>Example:</b></a>
	<blockquote class="style2">
		<h3>Keyboard Input:</h3>
		<b>D;bc.+2 inj;bk.med pter;rk.moderate endo gut;bac.+1 fc, +1 pig cells</b><br />
		<br />
		<h3>Appearance in openEMR: Eye Exam:</h3>
		<img src="/openemr/interface/forms/eye_mag/images/kb_antseg_EHR_example.png" alt="Keyboard Example: External">
	<br />
		<h3>Appearance in Reports:</h3>
		<img src="/openemr/interface/forms/eye_mag/images/kb_antseg_example.png" alt="Keyboard Example: External">

	</blockquote>
-->
</div>
<?
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
					<th style="width:1in;">Clinical Field</th><th style="width:1.3in;">Shorthand* (location)</th><th>Example Keyboard Entry**</th><th>Example Output to Location</th></tr>
				<tr >
					<td>Default values</td><td>D or d</td>
					<td><b style="color:red">d</b>;<br /><b style="color:red">D</b>;</td>
					<td>All fields are filled with default values </td>
				</tr>
				<tr >
					<td>Disc</td>
					<td>Right = rc<br />Left = lc<br />Both = bc or c</td>
					<td><b style="color:red">rc.</b>0.99<br /><b style="color:red">c.</b>PPDR at margins</td>
					<td>"+1 injection" (right conj only)<br />"medial pterygium" (both right and left fields are filled)</td>
				</tr>
				<tr>
					<td>Cup</td><td>Right = rc<br />Left = lc<br />Both = bk or k</td>
					<td><b style="color:red">rk.</b>+3 spk<br /><b style="color:red">k.</b>+2 end gut<b style="color:green">;</b><b style="color:red">rk.</b>+1 str edema<b style="color:green">.a</b></td>
					<td>"+3 SPK" (right cornea only)<br />"+2 endothelial guttatae" (both cornea fields) AND "+1 stromal edema" (appended to Right cornea field)</td>
				</tr>
				<tr>
					<td>Macula</td><td>Right = rac<br />Left = lac<br />Both = bac or ac</td>
					<td><b style="color:red">rac.</b>+1 fc<br><b style="color:red">ac.</b>+2 flare</td>
					<td>"+1 flare/cell" (right A/C field only)<br />"+2 flare" (both A/C fields)</td>
				</tr>
				<tr>
					<td>Vessls</td><td>Right = rl<br />Left = ll<br />Both = bl or l</td>
					<td><b style="color:red">RL.</b>+2 NS<br /><b style="color:red">ll.</b>+2 NS<b style="color:green">;</b><b style="color:red">l.</b>+3 ant cort spokes.a</td>
					<td>"+2 NS" (right lens only)<br />"+2 NS" (both lens fields) AND "+3 anterior cortical spokes" (appended to both lenses)</td>
				</tr>
				<tr>
					<td>Priphery</td><td>Right = ri<br />Left = li<br />Both = bi or i</td>
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
					<td>"625 um" (left s<br />medial 2cm SCC</td>
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
