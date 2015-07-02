<?php
 /*
 * Add Edit Event User for Patient Portal (add_edit_event_user.php)
 *
 * (Adapted from the Add Edit Event written by Rod Roark <rod@sunsetsystems.com>)
 *
 * This program is used to enter provider , comments, and search for un-used  
 * appointments in the Patient Portal, This works to allow the patient to 
 * schedule there own appointment in Patient Portal.
 * 
 * The add edit event screen looks similar to this:
 *
 *------------------------------------------------------------*
 * Category __________________V     Date                      *
 * Date     _____________ [?]       Time     ___:___ __V      *
 * Title    ___________________     duration ____ minutes     *
 * Provider __________________V                               *
 * Comments ________________________________________________  *
 *                                                            *
 *       [Save] [Find Open Appointments] [Cancel]             *
 *------------------------------------------------------------*
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

// continue session
session_start();

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
//

$ignoreAuth = 1;
global $ignoreAuth;

 include_once("../interface/globals.php");
 include_once("$srcdir/patient.inc");
 include_once("$srcdir/forms.inc");

 // Exit if the modify calendar for portal flag is not set
 if (!($GLOBALS['portal_onsite_appt_modify'])) {
   echo htmlspecialchars( xl('You are not authorized to schedule appointments.'),ENT_NOQUOTES);
   exit;
 }

/* Things that might be passed by our opener. */
 $eid           = $_GET['eid'];         // only for existing events
 $date          = $_GET['date'];        // this and below only for new events
 $userid        = $_GET['userid'];
 $default_catid = $_GET['catid'] ? $_GET['catid'] : '5';
 $patientid		= $_GET['patid'];
 //
 if ($date)
  $date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6);
 else
  $date = date("Y-m-d");
 //
 $starttimem = '00';
 if (isset($_GET['starttimem']))
  $starttimem = substr('00' . $_GET['starttimem'], -2);
 //
 if (isset($_GET['starttimeh'])) {
  $starttimeh = $_GET['starttimeh'];
  if (isset($_GET['startampm'])) {
   if ($_GET['startampm'] == '2' && $starttimeh < 12)
    $starttimeh += 12;
  }
 } else {
  $starttimeh = date("G");
 }
 $startampm = '';

 $info_msg = "";

 // If we are saving, then save and close the window.
 //
 if ($_POST['form_action'] == "save") {

    $_POST['form_apptstatus'] = $GLOBALS['portal_default_status'];
  $event_date = fixDate($_POST['form_date']);

  // Compute start and end time strings to be saved.
  if (!$_POST['form_allday']) {
   $tmph = $_POST['form_hour'] + 0;
   $tmpm = $_POST['form_minute'] + 0;
   if ($_POST['form_ampm'] == '2' && $tmph < 12) $tmph += 12;
   $duration = abs($_POST['form_duration']);
  }
  $starttime = "$tmph:$tmpm:00";
  //
  $tmpm += $duration;
  while ($tmpm >= 60) {
   $tmpm -= 60;
   ++$tmph;
  }
  $endtime = "$tmph:$tmpm:00";

  // Useless garbage that we must save.
  $locationspec = 'a:6:{s:14:"event_location";N;s:13:"event_street1";N;' .
   's:13:"event_street2";N;s:10:"event_city";N;s:11:"event_state";N;s:12:"event_postal";N;}';

  // More garbage, but this time 1 character of it is used to save the
  // repeat type.
  if (!$_POST['form_repeat']) {
   $recurrspec = 'a:5:{' .
    's:17:"event_repeat_freq";N;' .
    's:22:"event_repeat_freq_type";s:1:"0";' .
    's:19:"event_repeat_on_num";s:1:"1";' .
    's:19:"event_repeat_on_day";s:1:"0";' .
    's:20:"event_repeat_on_freq";s:1:"1";}';
  }

/* =======================================================
//                                  UPDATE EVENTS
========================================================*/
if (!$eid) {   # if !eid start  

if (!is_array($_POST['form_provider'])) {   

sqlInsert("INSERT INTO openemr_postcalendar_events ( " .
    "pc_catid, pc_aid, pc_pid, pc_title, pc_time, pc_hometext, " .
    "pc_informant, pc_eventDate, pc_endDate, pc_duration, pc_recurrtype, " .
    "pc_recurrspec, pc_startTime, pc_endTime, pc_alldayevent, " .
    "pc_apptstatus, pc_prefcatid, pc_location, pc_eventstatus, pc_sharing, pc_facility " .
    ") VALUES ( " .
    "'" . $_POST['form_category']             . "', " .
    "'" . $_POST['form_provider']             . "', " .
    "'" . $_POST['form_pid']                  . "', " .
    "'" . $_POST['form_title']                . "', " .
    "NOW(), "                                         .
    "'" . $_POST['form_comments']             . "', " .
    "'" . $_SESSION['authUserID']             . "', " .
    "'" . $event_date                         . "', " .
    "'" . fixDate($_POST['form_enddate'])     . "', " .
    "'" . ($duration * 60)                    . "', " .
    "'" . ($_POST['form_repeat'] ? '1' : '0') . "', " .
    "'$recurrspec', "                                 .
    "'$starttime', "                                  .
    "'$endtime', "                                    .
    "'" . $_POST['form_allday']               . "', " .
    "'" . $_POST['form_apptstatus']           . "', " .
    "'" . $_POST['form_prefcat']              . "', " .
    "'$locationspec', "                               .
    "1, " .
    "1," .(int)$_POST['facility']. ")"); // FF stuff
  } // INSERT single
 } // else - insert  EID End

  // Save new DOB if it's there.
  $patient_dob = trim($_POST['form_dob']);
  if ($patient_dob && $_POST['form_pid']) {
   sqlStatement("UPDATE patient_data SET DOB = '$patient_dob' WHERE " .
    "pid = '" . $_POST['form_pid'] . "'");
  }

 }
 
 # not allowing the patient to delete appointments
 else if ($_POST['form_action'] == "delete") {
        // =======================================
        //  multi providers case
        // =======================================
        if ($GLOBALS['select_multi_providers']) {
             // what is multiple key around this $eid?
            $row = sqlQuery("SELECT pc_multiple FROM openemr_postcalendar_events WHERE pc_eid = ?", array($eid));
			if ( $row['pc_multiple'] ) {
				sqlStatement("DELETE FROM openemr_postcalendar_events WHERE pc_multiple = ?", array($row['pc_multiple']));
			} else {
                                sqlStatement("DELETE FROM openemr_postcalendar_events WHERE pc_eid = ?", array($eid));
                        }
        // =======================================
        //  EOS multi providers case
        // =======================================
        } else {
            sqlStatement("DELETE FROM openemr_postcalendar_events WHERE pc_eid = ?", array($eid));
        }
 }

 if ($_POST['form_action'] != "") {
  // Close this window and refresh the calendar display.
  echo "<html>\n<body>\n<script language='JavaScript'>\n";
  if ($info_msg) echo " alert('$info_msg');\n";
  echo " parent.jQuery.fn.fancybox.close();\n";
  echo "</script>\n</body>\n</html>\n";
  exit();
 }
 
 //*********************************
 // If we get this far then we are displaying the form.
 //*********************************

 $repeats = 0; // if the event repeats
 $repeattype = '0';
 $repeatfreq = '0';
 $patienttitle = "";
 $hometext = "";
 $appt_title = "Patient Scheduled in Portal";
 $row = array();

 // If we are editing an existing event, then get its data.
 //if (!$eid) {
  $patientid=$_GET['pid'];
// }

 // If we have a patient ID, get the name and phone numbers to display.
 if ($patientid) {
  $prow = sqlQuery("SELECT lname, fname, phone_home, phone_biz, DOB " .
   "FROM patient_data WHERE pid = ?", array($patientid) );
  $patientname = $prow['lname'] . ", " . $prow['fname'];
  if ($prow['phone_home']) $patienttitle .= " H=" . $prow['phone_home'];
  if ($prow['phone_biz']) $patienttitle  .= " W=" . $prow['phone_biz'];
 }

 // Get the providers list.
 $ures = sqlStatement("SELECT id, username, fname, lname FROM users WHERE " .
  "authorized != 0 AND active = 1 ORDER BY lname, fname");

 //-------------------------------------
 //(CHEMED)
 //Set default facility for a new event based on the given 'userid'
 if ($userid) {
     $pref_facility = sqlFetchArray(sqlStatement("SELECT facility_id, facility FROM users WHERE id = ?", array($userid)));
     $e2f = $pref_facility['facility_id'];
     $e2f_name = $pref_facility['facility'];
 }
 //END of CHEMED -----------------------

 // Get event categories.
 $cres = sqlStatement("SELECT pc_catid, pc_catname, pc_recurrtype, pc_duration, pc_end_all_day " .
  "FROM openemr_postcalendar_categories ORDER BY pc_catname");

 // Fix up the time format for AM/PM.
 $startampm = '1';
 if ($starttimeh >= 12) { // p.m. starts at noon and not 12:01
  $startampm = '2';
  if ($starttimeh > 12) $starttimeh -= 12;
 }

?>
<html>
<head>
<?php html_header_show(); ?>
<title><?php echo $eid ? xlt('Edit') : xlt('Add New') ?> <?php echo xlt('Event');?></title>
<link rel="stylesheet" href='<?php echo $css_header ?>' type='text/css'>

<style>
td { font-size:0.8em; }
</style>

<style type="text/css">@import url(../library/dynarch_calendar.css);</style>
<script type="text/javascript" src="../library/topdialog.js"></script>
<script type="text/javascript" src="../library/dialog.js"></script>
<script type="text/javascript" src="../library/textformat.js"></script>
<script type="text/javascript" src="../library/dynarch_calendar.js"></script>
<script type="text/javascript" src="../library/dynarch_calendar_en.js"></script>
<script type="text/javascript" src="../library/dynarch_calendar_setup.js"></script>

<script language="JavaScript">

 var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

 var durations = new Array();
 // var rectypes  = new Array();
<?php
 // Read the event categories, generate their options list, and get
 // the default event duration from them if this is a new event.
 $catoptions = "";
 $prefcat_options = "    <option value='0'>-- " . xlt("None") . " --</option>\n";
 $thisduration = 0;
 if ($eid) {
  $thisduration = $row['pc_alldayevent'] ? 1440 : round($row['pc_duration'] / 60);
 }
 while ($crow = sqlFetchArray($cres)) {
  $duration = round($crow['pc_duration'] / 60);
  if ($crow['pc_end_all_day']) $duration = 1440;
  echo " durations[" . attr($crow['pc_catid']) . "] = " . attr($duration) . "\n";
  // echo " rectypes[" . $crow['pc_catid'] . "] = " . $crow['pc_recurrtype'] . "\n";
  $catoptions .= "    <option value='" . attr($crow['pc_catid']) . "'";
  if ($eid) {
   if ($crow['pc_catid'] == $row['pc_catid']) $catoptions .= " selected";
  } else {
   if ($crow['pc_catid'] == $default_catid) {
    $catoptions .= " selected";
    $thisduration = $duration;
   }
  }
  $catoptions .= ">" . text(xl_appt_category($crow['pc_catname'])) . "</option>\n";

  // This section is to build the list of preferred categories:
  if ($duration) {
   $prefcat_options .= "    <option value='" . $crow['pc_catid'] . "'";
   if ($eid) {
    if ($crow['pc_catid'] == $row['pc_prefcatid']) $prefcat_options .= " selected";
   }
   $prefcat_options .= ">" . text(xl_appt_category($crow['pc_catname'])) . "</option>\n";
  }

 }
?>

<?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

 // This is for callback by the find-patient popup.
 function setpatient(pid, lname, fname, dob) {
  var f = document.forms[0];
  f.form_patient.value = lname + ', ' + fname;
  f.form_pid.value = pid;
  dobstyle = (dob == '' || dob.substr(5, 10) == '00-00') ? '' : 'none';
  document.getElementById('dob_row').style.display = dobstyle;
 }
 function change_provider(){
  var f = document.forms[0];
  f.form_date.value='';
  f.form_hour.value='';
  f.form_minute.value='';
 }
 // This is for callback by the find-patient popup.
 function unsetpatient() {
  var f = document.forms[0];
  f.form_patient.value = '';
  f.form_pid.value = '';
 }

 // This invokes the find-patient popup.
 function sel_patient() {
  dlgopen('find_patient_popup.php', '_blank', 500, 400);
 }

 // Do whatever is needed when a new event category is selected.
 // For now this means changing the event title and duration.
 function set_display() {
  var f = document.forms[0];
  var s = f.form_category;
  if (s.selectedIndex >= 0) {
   var catid = s.options[s.selectedIndex].value;
   var style_apptstatus = document.getElementById('title_apptstatus').style;
   var style_prefcat = document.getElementById('title_prefcat').style;
   if (catid == '14') { // In Office
    style_apptstatus.display = 'none';
    style_prefcat.display = '';
    f.form_apptstatus.style.display = 'none';
    f.form_prefcat.style.display = '';
   } else {
    style_prefcat.display = 'none';
    style_apptstatus.display = '';
    f.form_prefcat.style.display = 'none';
    f.form_apptstatus.style.display = '';
   }
  }
 }

 // Gray out certain fields according to selection of Category DDL
 function categoryChanged() {
    var value = '5';
   
	document.getElementById("form_patient").disabled=false;
	//document.getElementById("form_apptstatus").disabled=false;
	//document.getElementById("form_prefcat").disabled=false;
 
 }

 // Modify some visual attributes when the all-day or timed-event
 // radio buttons are clicked.
 function set_allday() {
  var f = document.forms[0];
  var color1 = '#777777';
  var color2 = '#777777';
  var disabled2 = true;
  /*if (document.getElementById('rballday1').checked) {
   color1 = '#000000';
  }
  if (document.getElementById('rballday2').checked) {
   color2 = '#000000';
   disabled2 = false;
  }*/
  document.getElementById('tdallday1').style.color = color1;
  document.getElementById('tdallday2').style.color = color2;
  document.getElementById('tdallday3').style.color = color2;
  document.getElementById('tdallday4').style.color = color2;
  document.getElementById('tdallday5').style.color = color2;
  f.form_hour.disabled     = disabled2;
  f.form_minute.disabled   = disabled2;
  f.form_ampm.disabled     = disabled2;
  f.form_duration.disabled = disabled2;
 }

 // This is for callback by the find-available popup.
 function setappt(year,mon,mday,hours,minutes) {
  var f = document.forms[0];
  f.form_date.value = '' + year + '-' +
   ('' + (mon  + 100)).substring(1) + '-' +
   ('' + (mday + 100)).substring(1);
  f.form_ampm.selectedIndex = (hours >= 12) ? 1 : 0;
  f.form_hour.value = (hours > 12) ? hours - 12 : hours;
  f.form_minute.value = ('' + (minutes + 100)).substring(1);
 }
 
    // Invoke the find-available popup.
 function find_available() {
        // (CHEMED) Conditional value selection, because there is no <select> element 
        // when making an appointment for a specific provider
        var s = document.forms[0].form_provider;
        <?php if ($userid != 0) { ?>
            s = document.forms[0].form_provider.value;
        <?php } else {?>
            s = document.forms[0].form_provider.options[s.selectedIndex].value;
        <?php }?>       
        var formDate = document.forms[0].form_date;
        window.open('find_appt_popup_user.php?providerid=' + s +
                '&catid=5' + 
                '&startdate=' + formDate.value, '_blank', "width=550, height=425");
        //END (CHEMED) modifications
 }

 // Check for errors when the form is submitted.
 function validate() {
  var f = document.getElementById('theform');
  if (!f.form_date.value || !f.form_hour.value || !f.form_minute.value) {
   alert('Please click on "Find Open Appointment" to select a time.');
   return false;
  }
  
//  in lunch outofoffice reserved vacation
  f.form_category.value='14';
  if (f.form_patient.value=='Click to select' && (!(
         f.form_category.value=='2' || f.form_category.value=='8' || f.form_category.value=='3' || f.form_category.value=='4' || f.form_category.value=='11'
	 || f.form_category.value=='10'))) {
   alert('Please select a patient.');
   return false;
  } else if (f.form_category.value=='10') {
	unsetpatient();	
  }
  var form_action = document.getElementById('form_action');
  form_action.value="save";
  f.submit();
  return true;
 }

 function deleteEvent() {
    if (confirm("Deleting this event cannot be undone. It cannot be recovered once it is gone. Are you sure you wish to delete this event?")) {
        var f = document.getElementById('theform');
        var form_action = document.getElementById('form_action');
        form_action.value="delete";
        f.submit();
        return true;
    }
    return false;
 }

</script>
 <style type="text/css">
 
body {
    font-family: sans-serif;
    background-color: #638fd0;
    
    background: -webkit-radial-gradient(circle, white, #638fd0);
    background: -moz-radial-gradient(circle, white, #638fd0);
}
 
h1 {
    color:#638fd0;
    font-family:Impact;
    font-weight: bold;
    font-size:200%;
}
td {
    color:#FFFFFF;
    font-family:"arial black";
    font-weight: bold;
}

 </style>
</head>

<body onunload='imclosing()' onload='categoryChanged()'>

<form method='post' name='theform' id='theform' action='add_edit_event_user.php?eid=<?php echo attr($eid) ?>' onsubmit='return top.restoreSession()' />
<input type="hidden" name="form_action" id="form_action" value="">
<center>

<table border='0' width='100%'>

 <tr>
  <td width='1%' nowrap>
   <b><?php echo xlt('Category'); ?>:</b>
  </td>
  <td nowrap>
   <input type="text" id='form_category' name='form_category' value='Office Visit' readonly='readonly' style='width:100%'/>
  </td>

  <td width='1%' nowrap>
  	<b><?php echo xlt('Date'); ?>:</b>
  </td>
  <td colspan='2' nowrap id='tdallday1'>
   <input type='text' size='7' name='form_date' readonly id='form_date' <?php echo $disabled ?>
    value='<?php if (isset($eid)) { echo $eid ? $row['pc_eventDate'] : $date; } ?>'
    onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' 
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b><?php echo xlt('Title'); ?>:</b>
  </td>
  <td nowrap>
   <input type='text' size='5' name='form_title' value='<?php echo attr($appt_title) ?>' readonly='readonly'
    style='width:100%'
    title='<?php echo xla('Event title'); ?>' />
  </td>
  <td nowrap>
  </td>
  <td width='1%' nowrap id='tdallday2'>
   <?php echo xlt('Time'); ?>
  </td>
  <td width='1%' nowrap id='tdallday3'>
   <input type='text' size='1' name='form_hour' value='<?php if(isset($eid)) { echo attr($starttimeh); } ?>'
    title='<?php echo xla('Event start time'); ?>' readonly /> :
   <input type='text' size='1' name='form_minute' value='<?php if(isset($eid)) { echo attr($starttimem); } ?>'
    title='<?php echo xla('Event start time'); ?>' readonly />&nbsp;
   <select name='form_ampm' id='form_ampm' title='<?php echo xla('Note: 12:00 noon is PM, not AM'); ?>' readonly='readonly' >
    <option value='1' ><?php echo xlt('AM'); ?></option>
    <option value='2' <?php if ($startampm == '2') echo " selected" ?>><?php echo xlt('PM'); ?></option>
   </select>
  </td>
 </tr>
 <tr>
  <td nowrap>
   <b><?php echo xlt('Patient'); ?>:</b>
  </td>
  <td nowrap>
   <input type='text' size='10' id='form_patient' name='form_patient' style='width:100%;' value='<?php echo attr($patientname) ?>' title='Patient' readonly />
   <input type='hidden' name='form_pid' value='<?php echo attr($patientid) ?>' />
  </td>
  <td nowrap> &nbsp;
  </td>
  <td nowrap id='tdallday4'><?php echo xlt('duration'); ?>
  </td>
  <td nowrap id='tdallday5'>
   <input type='text' size='1' name='form_duration' value='<?php echo attr($thisduration) ?>' title='<?php echo xla('Event duration in minutes'); ?>' readonly /> 
    <?php echo xlt('minutes'); ?> 
    
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b><?php echo xlt('Provider'); ?>:</b>
  </td>
  <td nowrap>
   <?php

        // present a list of providers to choose from
        // default to the currently logged-in user
    if (!$eid) 
    {
      // default to the currently logged-in user
      $defaultProvider = $_SESSION['authUserID'];
      // or, if we have chosen a provider in the calendar, default to them
      // choose the first one if multiple have been selected
      if (count($_SESSION['pc_username']) >= 1) {
        // get the numeric ID of the first provider in the array
        $pc_username = $_SESSION['pc_username'];
        $firstProvider = sqlFetchArray(sqlStatement("select id from users where username=?", array($pc_username[0]) ));
        $defaultProvider = $firstProvider['id'];
      }
      // if we clicked on a provider's schedule to add the event, use THAT.
      if ($userid) $defaultProvider = $userid;
    }
        echo "<select name='form_provider' onchange='change_provider();' style='width:100%' />";
        while ($urow = sqlFetchArray($ures)) {
            echo "    <option value='" . attr($urow['id']) . "'";
            if ($urow['id'] == $defaultProvider) echo " selected";
            #if (($urow['id'] == $_GET['userid'])||($urow['id']== $userid)) echo " selected"; 
            echo ">" . text($urow['lname']);
            if ($urow['fname']) echo ", " . text($urow['fname']);
            echo "</option>\n";
        }
        echo "</select>";

?>
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b><?php echo xlt('Comments'); ?>:</b>
  </td>
  <td colspan='4' nowrap>
	<input type='text' size='40' name='form_comments' style='width:100%' value='<?php echo attr($hometext); ?>' title='<?php echo xla('Optional information about this event');?>' />
  </td>
 </tr>

</table>

<p>
<input type='button' name='form_save' value='<?php echo xla('Save');?>' onclick="validate()" />
&nbsp;
<input type='button' value='<?php echo xla('Find Open Appointment');?>' onclick='find_available()' />
&nbsp;
<input type='button' value='<?php echo xla('Cancel');?>' onclick='parent.$.fn.fancybox.close()' />
</p>
</center>
</form>

</body>
</html>