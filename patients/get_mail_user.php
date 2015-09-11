<?php
/*
 * Find Appointments Popup for Patient Portal (find_appt_popup_user.php)
 *
 * (Adapted from the find appointments written by Rod Roark <rod@sunsetsystems.com>)
 *
 * This program is used to find un-used appointments in the Patient Portal, 
 * allowing the patient to select there own appointment.
 * 
 * Copyright (C) 2015 Terry Hill <terry@lillysystems.com> 
 * 
 * Copyright (C) 2005-2013 Rod Roark <rod@sunsetsystems.com>
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
 * @author Terry Hill <terry@lilysystems.com> 
 * @author Rod Roark <rod@sunsetsystems.com> 
 * @link http://www.open-emr.org 
 *
 * Please help the overall project by sending changes you make to the authors and to the OpenEMR community.
 * 
 */

//continue session
session_start();
//

//SANITIZE ALL ESCAPES
$fake_register_globals=false;

//STOP FAKE REGISTER GLOBALS
$sanitize_all_escapes=true;

//landing page definition -- where to go if something goes wrong
$landingpage = "index.php?site=".$_SESSION['site_id'];
//

// kick out if patient not authenticated
if ( isset($_SESSION['pid']) && isset($_SESSION['patient_portal_onsite']) ) {
  $pid = $_SESSION['pid'];
}
else {
  session_destroy();
  header('Location: '.$landingpage.'&w');
  exit;
}

$ignoreAuth = 1;

 include_once("../interface/globals.php");
 include_once("$srcdir/patient.inc");
 include_once("$srcdir/pnotes.inc");
 include_once("$srcdir/formatting.inc.php");

 $patient_id = $pid;
 error_log(print_r($_REQUEST, true));
 $show= isset($_REQUEST['show']) ? $_REQUEST['show'] : "";
 error_log(print_r($_REQUEST, true));
 ?>
<html>
<head>
<?php html_header_show(); ?>
<title><?php echo xlt('Messaging Center'); ?></title>
<link rel="stylesheet" href='<?php echo $css_header ?>' type='text/css'>

<!-- for the pop up calendar -->
<style type="text/css">@import url(../library/dynarch_calendar.css);</style>
<script type="text/javascript" src="../library/dynarch_calendar.js"></script>
<script type="text/javascript" src="../library/dynarch_calendar_en.js"></script>
<script type="text/javascript" src="../library/dynarch_calendar_setup.js"></script>

<!-- for ajax-y stuff -->
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.2.2.min.js"></script>


<script language="JavaScript">


</script>


<style>
form {
    /* this eliminates the padding normally around a FORM tag */
    padding: 0px;
    margin: 0px;
}
body {
    font-family: sans-serif;
    background-color: #638fd0;
    
    background: -webkit-radial-gradient(circle, white, #638fd0);
    background: -moz-radial-gradient(circle, white, #638fd0);
}
h2 {
    color:#0c2858;
    font-family:Impact;
    font-weight: bold;
    font-size:160%;
    position: absolute;
    top: 10px;
}
gradiant{
  background: #999; /* for non-css3 browsers */
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFF', endColorstr='#D4D4D4'); /* for IE */
  background: -webkit-gradient(linear, left top, left bottom, from(#FFFFFF), to(#D4D4D4)); /* for webkit browsers */
  background: -moz-linear-gradient(top,  #FFFFFF,  #D4D4D4); /* for firefox 3.6+ */
}
#searchCriteria {
    text-align: center;
    width: 100%;
    font-size: 0.8em;
    background-color: #0c2858;
    color:#FFFFFF;
    font-weight: bold;
    padding: 3px;
}
#searchResultsHeader { 
    width: 100%;
    background-color: lightgrey;
}
#searchResultsHeader table { 
    width: 96%;  /* not 100% because the 'searchResults' table has a scrollbar */
    border-collapse: collapse;
}
#searchResultsHeader th {
    font-size: 0.75em;
}
#searchResults {
    width: 100%;
    height: 350px; 
    overflow: auto;
}

.srDate { width: 20%; }
.srTimes { width: 80%; }

#searchResults table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
}
#searchResults td {
    font-size: 0.75em;
    border-bottom: 1px solid gray;
    padding: 1px 5px 1px 5px;
}
.highlight { background-color: #ff9; }
.blue_highlight { background-color: #336699; color: white; }
#am {
    border-bottom: 1px solid lightgrey;
    color: #00c;
}
#pm { color: #c00; }
#pm a { color: #c00; }
</style>

</head>

<body>




<div>
    <table align="center">
                              <tr>
                        <td>
                          <div>
                          <h1 class="heading" align="center">OpenEMR<?php echo xlt(" Message Center"); ?></h1>
                          </div>
                        </td>
                      </tr>
                      
      <tr>
        <td class="gradiant" width="100%" height="100%" align="center" >
 
        </td>
      </tr>
    </table>
    <br>


  

    <table width=100%>
    <tr>
    <td>
    <table border=0 cellpadding=1 cellspacing=0 width=90% align="center" style="border-left: 1px #000000 solid; border-right: 1px #000000 solid; border-top: 1px #000000 solid">
    <form name=MessageList action="messages.php?showall="<?php echo attr($showall)?> "sortby=" <?php echo attr($sortby) ?> "sortorder="<?php echo attr($sortorder) ?> "begin=" <?php echo attr($begin)?>"$activity_string_html" method=post>
    <input type=hidden name=show value=delete>
        <tr height="24" style="background:#638fd0" class="header">
            <td align="center" width="25" style="border-bottom: 1px #000000 solid; border-right: 1px #000000 solid"><input type=checkbox id="checkAll" onclick="selectAll()"></td>
            <td width="20%" style="border-bottom: 1px #000000 solid; border-right: 1px #000000 solid; color: white" class=bold>&nbsp;<b> <?php echo xlt('To') ?> </b> <?php attr($sortlink[0]) ?> </td>
            <td width="20%" style="border-bottom: 1px #000000 solid; border-right: 1px #000000 solid; color: white" class=bold>&nbsp;<b> <?php echo xlt('From') ?> </b> <?php attr($sortlink[1])?> </td>
            <td style="border-bottom: 1px #000000 solid; border-right: 1px #000000 solid; color: white" class=bold>&nbsp;<b>
             <?php echo xlt('Subject') ?> </b> <?php attr($sortlink[2]) ?></td>
            <td width="15%" style="border-bottom: 1px #000000 solid; border-right: 1px #000000 solid; color: white" class=bold>&nbsp;<b> 
             <?php echo xlt('Message') ?> </b> <?php attr($sortlink[3]) ?> </td>
            <td width="15%" style="border-bottom: 1px #000000 solid; border-right: 1px #000000 solid; color: white" class=bold>&nbsp;<b> 
              <?php echo xlt('Date') ?> </b> <?php attr($sortlink[4]) ?></td>
            <td width="15%" style="border-bottom: 1px #000000 solid; color: white" class=bold>&nbsp;<b>
             <?php echo xlt('Status') ?> </b> <?php attr($sortlink[5]) ?></td>
        </tr> 
   <?php
     // Display the Messages table body.
        $count = 0;
         $activity_query = " ";
        $userinfo= '-patient-';
  $sql = "SELECT pnotes.id, pnotes.user, pnotes.body, pnotes.pid, pnotes.title, pnotes.date, pnotes.message_status,
          IF(pnotes.pid = 0 OR pnotes.user != pnotes.pid,users.fname,patient_data.fname) as users_fname,
          IF(pnotes.pid = 0 OR pnotes.user != pnotes.pid,users.lname,patient_data.lname) as users_lname,
          patient_data.fname as patient_data_fname, patient_data.lname as patient_data_lname
          FROM ((pnotes LEFT JOIN users ON pnotes.user = users.username)
          LEFT JOIN patient_data ON pnotes.pid = patient_data.pid) WHERE $activity_query
          pnotes.deleted != '1' AND pnotes.assigned_to LIKE ?";
          
          $result = sqlStatement($sql, array($userinfo));
          
        //$result = getPnotesByUser($active,$show_all,$userinfo,false);
        while ($myrow = sqlFetchArray($result)) {
            $name = $myrow['user'];
            $name = $myrow['users_lname'];
            if ($myrow['users_fname']) {
                $name .= ", " . $myrow['users_fname'];
            }
            $patient = $myrow['pid'];
            if ($patient>0) {
                $patient = $myrow['patient_data_lname'];
                if ($myrow['patient_data_fname']) {
                    $patient .= ", " . $myrow['patient_data_fname'];
                }
            } else {
                $patient = "* Patient must be set manually *";
            }
            $count++;
            echo "
            <tr id=\"row$count\" style=\"background:white\" height=\"24\">
                <td align=\"center\" style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><input type=checkbox id=\"check$count\" name=\"delete_id[]\" value=\"" .
	          attr($myrow['id']) . "\" onclick=\"if(this.checked==true){ selectRow('row$count'); }else{ deselectRow('row$count'); }\"></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
	          attr($patient) . "</td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">
                <a href=\"../interface/main/messages/messages.php?showall=".attr($showall)."&sortby=".attr($sortby)."&sortorder=".attr($sortorder)."&begin=".attr($begin)."&task=edit&noteid=" .
	          attr($myrow['id']) . "&$activity_string_html\" onclick=\"top.restoreSession()\">" .
		      attr($name) . "</a></td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
	          attr($myrow['title']) . "</td><td width=5></td></tr></table></td>
               <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
	          attr(substr($myrow['body'], 1 + strpos($myrow['body'], ")"))) . "</td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
	          attr(oeFormatShortDate(substr($myrow['date'], 0, strpos($myrow['date'], " ")))) . "</td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
	          attr($myrow['message_status']) . "</td><td width=5></td></tr></table></td>
            </tr>";

           // error_log(print_r($myrow, true));
        }
    
        //error_log(print_r($result, true));
        ?>

  </div>
  </body>
  </html>