<?php
/**
 * THIS MODULE REPLACES cptcm_codes.php, hcpcs_codes.php AND icd9cm_codes.php.
 * 
 * Copyright (C) This had no previous developer listed
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
 
include_once("../../globals.php");
include_once("../../../custom/code_types.inc.php");
include_once("$srcdir/sql.inc");

//the maximum number of records to pull out with the search:
$M = 30;

//the number of records to display before starting a second column:
$N = 15;

$code_type = $_GET['type'];
?>

<html>
<head>
<?php html_header_show();?>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">

<!-- add jQuery support -->
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.2.2.min.js"></script>

</head>
<body class="body_bottom">
<div id="patient_search_code">

<table border=0 cellspacing=0 cellpadding=0 height=100%>
<tr>

<td valign=top>

<form name="search_form" id="search_form" method="post" action="search_code.php?type=<?php echo attr($code_type) ?>">
<input type="hidden" name="mode" value="search">

<span class="title"><?php echo attr($code_type) ?> <?php echo xlt('Codes'); ?></span><br>

<input type="textbox" id="text" name="text" size=15>

<input type='submit' id="submitbtn" name="submitbtn" value='<?php echo xlt('Search'); ?>'>
<div id="searchspinner" style="display: inline; visibility:hidden;"><img src="<?php echo $GLOBALS['webroot'] ?>/interface/pic/ajax-loader.gif"></div>

</form>

<?php
if (isset($_POST["mode"]) && $_POST["mode"] == "search" && $_POST["text"] == "") {
    echo "<div id='resultsummary' style='background-color:lightgreen;'>";
    echo "Enter search criteria above</div>";
}

if (isset($_POST["mode"]) && $_POST["mode"] == "search" && $_POST["text"] != "") {
    
# This code was added to handle the external Data files
  $procedurecodetype = sqlQuery("SELECT " .
  "ct_proc, ct_key FROM code_types " .
  "WHERE ct_key = ? LIMIT 1", array($code_type));
  $procedure_type = $procedurecodetype['ct_proc'];

# looking to see if the code_type is a procedure  
If($procedure_type == '0') {
  $search = $_POST['text'];
  $filter_key = $code_type;
  $res = main_code_set_search($filter_key,$search,NULL,NULL,false,NULL,false,$fstart,($fend - $fstart),$filter_elements);
}
else
{
  # left this to takecare of the non external data files
  $sql = "SELECT codes.*, prices.pr_price FROM codes " .
    "LEFT OUTER JOIN patient_data ON patient_data.pid = '$pid' " .
    "LEFT OUTER JOIN prices ON prices.pr_id = codes.id AND " .
    "prices.pr_selector = '' AND " .
    "prices.pr_level = patient_data.pricelevel " .
    "WHERE (code_text LIKE '%" . $_POST["text"] . "%' OR " .
    "code LIKE '%" . $_POST["text"] . "%') AND " .
    "code_type = '" . $code_types[$code_type]['id'] . "' " .
    "ORDER BY code ".
    " LIMIT " . ($M + 1).
    "";
    $res = sqlStatement($sql);
}
 
		for($iter=0; $row=sqlFetchArray($res); $iter++)
		{
			$result[$iter] = $row;
		}
        echo "<div id='resultsummary' style='background-color:lightgreen;'>";
        if (count($result) > $M) {
            echo xlt('Showing the first ') .text($M).xlt(' results');
        }
        else if (count($result) == 0) {
            echo xlt('No results found');
        }
        else {
            echo xlt('Showing all ').attr(count($result)).xlt(' results');
        }
        echo "</div>";
?>
<div id="results">
<table><tr class='text'><td valign='top'>
<?php
$count = 0;
$total = 0;

if ($result) {
    foreach ($result as $iter) {
        if ($count == $N) {
            echo "</td><td valign='top'>\n";
            $count = 0;
        }
   
        echo "<div class='oneresult' style='padding: 3px 0px 3px 0px;'>";
        echo "<a target='".xl('Diagnosis')."' href='diagnosis.php?mode=add" .
            "&type="     . attr($code_type) .
            "&code="     . attr($iter{"code"}) .
            "&modifier=" . attr($iter{"modifier"}) .
            "&units="    . attr($iter{"units"}) .
            //"&fee="      . urlencode($iter{"fee"}) .
            "&fee="      . attr($iter['pr_price']) .
            "&text="     . attr($iter{"code_text"}) .
            "' onclick='top.restoreSession()'>";
        echo ucwords("<b>" . strtoupper($iter{"code"}) . "&nbsp;" . $iter['modifier'] .
            "</b>" . " " . strtolower($iter{"code_text"}));
        echo "</a><br>\n";
        echo "</div>";
    
        $count++;
        $total++;

        if ($total == $M) {
            echo "</span><span class=alert>".xl('Some codes were not displayed.')."</span>\n";
            break;
        }
    }
}
?>
</td></tr></table>
</div>
<?php

}
?>

</td>
</tr>
</table>

</div> <!-- end large outer patient_search_code DIV -->
</body>

<script language="javascript">

// jQuery stuff to make the page a little easier to use

$(document).ready(function(){
    $("#text").focus();
    $(".oneresult").mouseover(function() { $(this).toggleClass("highlight"); });
    $(".oneresult").mouseout(function() { $(this).toggleClass("highlight"); });
    //$(".oneresult").click(function() { SelectPatient(this); });
    $("#search_form").submit(function() { SubmitForm(this); });
});

// show the 'searching...' status and submit the form
var SubmitForm = function(eObj) {
    $("#submitbtn").attr("disabled", "true"); 
    $("#submitbtn").css("disabled", "true");
    $("#searchspinner").css("visibility", "visible");
    return top.restoreSession();
}

</script>

</html>
