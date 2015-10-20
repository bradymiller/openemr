<?php
/**
 * 
 * Copyright (C) 2015 Terry Hill <terry@lillysystems.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @author  Terry Hill <terry@lillysystems.com>
 * @link    http://www.open-emr.org
 */

$sanitize_all_escapes=true;
$fake_register_globals=false;

require_once("../../globals.php");
require_once("$srcdir/billing.inc");
require_once("$srcdir/formdata.inc.php");

$targparm = $GLOBALS['concurrent_layout'] ? "" : "target='Main'";

if (isset($mode)) {
	if ($mode == "add") {
		addBilling($encounter, $type, $code, strip_escape_custom($text),$pid, $userauthorized,$_SESSION['authUserID']);
	}
	elseif ($mode == "delete") {
		deleteBilling($id);
	}
	elseif ($mode == "clear") {
		clearBilling($id);
	}
}
?>
<html>
<head>
<?php html_header_show();?>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
</head>

<body class="body_top">

<?php if ($GLOBALS['concurrent_layout']) { ?>
<a href="encounter_bottom.php" onclick="top.restoreSession()">
<?php } else { ?>
<a href="patient_encounter.php" target="Main" onclick="top.restoreSession()">
<?php } ?>

<span class=title><?php echo xlt('Billing'); ?></span>
<font class=more><?php echo attr($tback);?></font></a>

<table border=0 cellpadding=3 cellspacing=0>

<?php
if ($result = getBillingByEncounter($pid,$encounter,"*") ) {
	$billing_html = array();
	foreach ($result as $iter) {
		if ($iter["code_type"] == "ICD9" || $iter["code_type"] == "ICD10") {
			$html = "<tr>";
			$html .= "<td valign=\"middle\"></td>" .
				"<td><div><a $targparm class='small' href='diagnosis_full.php' onclick='top.restoreSession()'><b>" .
				attr($iter{"code"}) . "</b> " . attr($iter{"code_text"}) .
				"</a></div></td>\n";
			$billing_html[$iter["code_type"]] .= $html;
			$counter++;
		}
		elseif ($iter["code_type"] == "COPAY") {
			$billing_html[$iter["code_type"]] .= "<tr><td></td>" .
				"<td><a $targparm class='small' href='diagnosis_full.php' onclick='top.restoreSession()'><b>" .
				attr($iter{"code"})."</b> " . attr($iter{"code_text"}) .
				"</a></td>\n";
		}
		else {
			$billing_html[$iter["code_type"]] .= "<tr><td></td>" .
				"<td><a $targparm class='small' href='diagnosis_full.php' onclick='top.restoreSession()'><b>" .
				attr($iter{"code"}) . "</b> " . attr($iter{"code_text"}) .
				"</a><span class=\"small\">";
			$js = split(":",$iter['justify']);
			$counter = 0;
			foreach ($js as $j) {
				if(!empty($j)) {
					if ($counter == 0) {
						$billing_html[$iter["code_type"]] .= " (<b>$j</b>)";
					}
					else {
						$billing_html[$iter["code_type"]] .= " ($j)";
					}
					$counter++;
				}
			}

			$billing_html[$iter["code_type"]] .= "</span></td>";
			$billing_html[$iter["code_type"]] .= "<td>" .
				"<a class=\"link_submit\" href='diagnosis_full.php?mode=clear&id=" .
				attr($iter{"id"}) . "' class='link' onclick='top.restoreSession()'>[" . xl('Clear Justification') .
				"]</a></td>";
		}

		$billing_html[$iter["code_type"]] .= "<td>" .
			"<a class=\"link_submit\" href='diagnosis_full.php?mode=delete&id=" .
			attr($iter{"id"}) . "' class='link' onclick='top.restoreSession()'>[Delete]</a></td>";
		$billing_html[$iter["code_type"]] .= "</tr>\n";
	}

	foreach ($billing_html as $key => $val) {
		print "<tr><td>$key</td><td><table>$val</table><td></tr><tr><td height=\"5\"></td></tr>\n";
	}
}

?>
</table>

</body>
</html>
