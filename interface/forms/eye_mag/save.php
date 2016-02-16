<?php
/*
 * forms/eye_mag/save.php 
 * 
 * This saves the submitted data. 
 *  Forms: new and updates 
 *  User preferences for displaying the form as the user desires.
 *    Each time a form is used, layout choices auto-change preferences.
 *  Retrieves old records so the user can flip through old values within this form,
 *    ideally with the intent that the old data can be carried forward.  
 *    Yeah, gotta write that carry forward stuff yet.  Next week it'll be done?
 *  HTML5 Canvas images the user draws.
 *    For now we have one image per section
 *    I envision a user definable image they can upload to draw on and name such as 
 *    A face image to draw injectable location/dosage for fillers or botulinum toxins. 
 *    Ideally this concept when it comes to fruition will serve as a basis for any specialty image form
 *    to be used.  Upload image, drop widget and save it...  Imagine the dermatologists and neurologists with
 *    a drawable form they made themselves within openEMR.  They'll smile and say it's about time we get to work...
 *    We need to get back to work first and make it happen...
 *
 * Copyright (C) 2014 Raymond Magauran <magauran@MedFetch.com> 
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
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;
ini_set('error_reporting', E_ALL);

$table_name   = "form_eye_mag";
$form_name    = "eye_mag";
$form_folder  = "eye_mag";

include_once("../../globals.php");
include_once("$srcdir/api.inc");
include_once("$srcdir/forms.inc");
include_once("php/".$form_name."_functions.php");
include_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once($srcdir . "/../controllers/C_Document.class.php");
require_once($srcdir . "/documents.php");
//These were added to write the PDF for an encounter. 
//I am not sure what is really needed so I threw them all in...
require_once("$srcdir/patient.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/lists.inc");
require_once("$srcdir/report.inc");
require_once("$srcdir/classes/Document.class.php");
require_once("$srcdir/classes/Note.class.php");
require_once("$srcdir/htmlspecialchars.inc.php");
require_once("$srcdir/html2pdf/html2pdf.class.php");

//we need privileges to be restricted here?

//not sure if this is needed at all
$returnurl    = $GLOBALS['concurrent_layout'] ? 'encounter_top.php' : 'patient_encounter.php';

if (isset($_REQUEST['id']))
{
$id = $_REQUEST['id'];
}
if (!$id) $id = $_REQUEST['pid'];
$pid = $_REQUEST['pid'];
$AJAX_PREFS = $_REQUEST['AJAX_PREFS'];
if ($encounter == "" && !$id && !$AJAX_PREFS && (($_REQUEST['mode'] != "retrieve") or ($_REQUEST['mode'] == "show_PDF"))) {
    echo "Sorry Charlie..."; //should lead to a database of errors for explanation.
    exit;
}
/**  
 * Save/update the preferences  
 */
if ($_REQUEST['AJAX_PREFS']) { 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
                VALUES 
                ('PREFS','VA','Vision',?,'RS','51',?,'1')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_VA']));
 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
                VALUES 
                ('PREFS','W','Current Rx',?,'W','52',?,'2')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_W']));
  
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','MR','Manifest Refraction',?,'MR','53',?,'3')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_MR']));
  
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CR','Cycloplegic Refraction',?,'CR','54',?,'4')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CR']));
  
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CTL','Contact Lens',?,'CTL','55',?,'5')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CTL']));
  
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS', 'VAX', 'Visual Acuities', ?, 'VAX','65', ?,'15')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_VAX']));
   
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ADDITIONAL','Additional Data Points',?,'ADDITIONAL','56',?,'6')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ADDITIONAL']));
  
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CLINICAL','CLINICAL',?,'CLINICAL','57',?,'7')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CLINICAL']));
  
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','IOP','Intraocular Pressure',?,'IOP','67',?,'17')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_IOP']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','EXAM','EXAM',?,'EXAM','58',?,'8')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_EXAM']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CYLINDER','CYL',?,'CYL','59',?,'9')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CYL']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','HPI_VIEW','HPI View',?,'HPI_VIEW','60',?,'10')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_HPI_VIEW']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','EXT_VIEW','External View',?,'EXT_VIEW','66',?,'16')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_EXT_VIEW']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ANTSEG_VIEW','Anterior Segment View',?,'ANTSEG_VIEW','61',?,'11')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ANTSEG_VIEW']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','RETINA_VIEW','Retina View',?,'RETINA_VIEW','62',?,'12')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_RETINA_VIEW']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','NEURO_VIEW','Neuro View',?,'NEURO_VIEW','63',?,'13')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_NEURO_VIEW']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ACT_VIEW','ACT View',?,'ACT_VIEW','64',?,'14')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ACT_VIEW']));
    
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ACT_SHOW','ACT Show',?,'ACT_SHOW','65',?,'15')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ACT_SHOW'])); 

    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','HPI_RIGHT','HPI DRAW',?,'HPI_RIGHT','70',?,'16')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_HPI_RIGHT'])); 

    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','PMH_RIGHT','PMH DRAW',?,'PMH_RIGHT','71',?,'17')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_PMH_RIGHT'])); 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','EXT_RIGHT','EXT DRAW',?,'EXT_RIGHT','72',?,'18')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_EXT_RIGHT'])); 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ANTSEG_RIGHT','ANTSEG DRAW',?,'ANTSEG_RIGHT','73',?,'19')";
    $result = sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ANTSEG_RIGHT'])); 

    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','RETINA_RIGHT','RETINA DRAW',?,'RETINA_RIGHT','74',?,'20')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_RETINA_RIGHT'])); 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','NEURO_RIGHT','NEURO DRAW',?,'NEURO_RIGHT','75',?,'21')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_NEURO_RIGHT'])); 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','IMPPLAN_RIGHT','IMPPLAN DRAW',?,'IMPPLAN_RIGHT','76',?,'22')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_IMPPLAN_RIGHT'])); 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES   
              ('PREFS','PANEL_RIGHT','PMSFH Panel',?,'PANEL_RIGHT','77',?,'23')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_PANEL_RIGHT'])); 
    $query = "REPLACE INTO ".$table_name."_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES   
              ('PREFS','KB_VIEW','KeyBoard View',?,'KB_VIEW','78',?,'24')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_KB'])); 
}

/**
  * ADD ANY NEW PREFERENCES above, and as a hidden field in the body.  I prefer this vs Session items but that would
  * also work here.  No good reason.
  */

/** <!-- End Preferences --> **/

/**  
 * Create, update or retrieve a form and its values  
 */
if (!$pid) $pid = $_SESSION['pid'];
$userauthorized = $_SESSION['userauthorized'];
$encounter      = $_REQUEST['encounter'];
if ($encounter == "") $encounter = date("Ymd");
$form_id        = $_REQUEST['form_id'];
$zone           = $_REQUEST['zone'];

$providerID  =  getProviderIdOfEncounter($encounter);
$providerNAME = getProviderName($providerID);

// The form is submitted to be updated or saved in some way.
// Submission are ongoing and then the final unload of page changes the 
// DOM variable $("#final") to == 1.  As one draws on the HTML5 canvas, each step is saved incrementally allowing
// the user to go back through their history should they make a drawing error or simply want to reverse a
// step.  They are saved client side now.  On finalization, we need to update the _VIEW.png file with the current
// canvases.  

// Is the form LOCKED? when and by whom, and **esigned**  according to openEMR specs...
// Need help here.
// If this is LOCKED by esigning,tell user to move along, nothing to see here... Goto Report?
// if this form/encounter is esigned and locked, then return without touching data.

// We also have the situation where the form is actively being udated in one place but opened in another, ie. NOT esigned.
// We need to have only ONE be able to update the DB.
// Give each instance of a form a uniqueID.  If the form has no owner, update DB with this uniqueID.
// If the DB shows a uniqueID ie. an owner, ask if the new user wishes to take ownership?  
// If yes, any other attempt to save fiedls/form are denied and the return code says you are not the owner...
if ($_REQUEST['unlock'] == '1') { 
  // we are releasing the form, by closing the page or clicking on ACTIVE FORM, so unlock it.
  // if it's locked and they own it ($REQUEST[LOCKEDBY] == LOCKEDBY), they can unlock it
  $query = "SELECT LOCKED,LOCKEDBY,LOCKEDDATE from ".$table_name." WHERE ID=?";
  $lock = sqlQuery($query,array($form_id));
  if (($lock['LOCKED'] >'') && ($_REQUEST['LOCKEDBY'] == $lock['LOCKEDBY']))  { 
    $query = "update ".$table_name." set LOCKED='',LOCKEDBY='' where id=?";
    sqlQuery($query,array($form_id));
  }
  exit;
} elseif ($_REQUEST['acquire_lock']=="1") { 
  //we are taking over the form's active state, others will go read-only
  $query = "UPDATE ".$table_name." set LOCKED='1',LOCKEDBY=? where id=?";
  //Does the new owner must at least know the current LOCKEDBY code to wrangle ownership...
  // If so add to where clause " and LOCKEDBY=$OLD_VALUE".
  $result = sqlQuery($query,array($_REQUEST['uniqueID'],$form_id));
  exit;
} else { 
  $query = "SELECT LOCKED,LOCKEDBY,LOCKEDDATE from ".$table_name." WHERE ID=?";
  $lock = sqlQuery($query,array($form_id));
  if (($lock['LOCKED']) && ($_REQUEST['uniqueID'] != $lock['LOCKEDBY']))  { 
      // We are not the owner or it is not new so it is locked
      // Did the user send a demand to take ownership?
      if ($lock['LOCKEDBY'] != $_REQUEST['ownership']) {
        //tell them they are locked out by another user now
        echo "Code 400";
        // or return a JSON encoded string with current LOCK ID?
        // echo "Sorry Charlie, you get nothing since this is locked...  No save for you!";
        exit;
      } elseif ($lock['LOCKEDBY'] == $_REQUEST['ownership']) { 
        // then they are taking ownership - all others get locked...
        // new LOCKEDBY becomes our uniqueID LOCKEDBY
        $_REQUEST['LOCKED'] = '1';
        $_REQUEST['LOCKEDBY'] = $_REQUEST['uniqueID'];
        //update table
        $query = "update ".$table_name." set LOCKED=?,LOCKEDBY=? where id=?";
        sqlQuery ($query,array('1',$_REQUEST['LOCKEDBY'],$form_id));
        //go on to save what we want...
      }
  } elseif (!$lock['LOCKED']) { // it is not locked yet
    $_REQUEST['LOCKED'] = '1';
    $query = "update ".$table_name." set LOCKED=?,LOCKEDBY=? where id=?";
    sqlQuery ($query,array('1',$_REQUEST['LOCKEDBY'],$form_id));
    //go on to save what we want...
  }
  if (!$_REQUEST['LOCKEDBY'])  $_REQUEST['LOCKEDBY'] = rand();
}
if ($_REQUEST["mode"] == "new")             { 
  $newid = formSubmit($table_name, $_POST, $id, $userauthorized);
  addForm($encounter, $form_name, $newid, $form_folder, $pid, $userauthorized);
} elseif ($_REQUEST["mode"] == "update")    { 
  // The user has write privileges to work with...
  if ($_REQUEST['action']=="store_PDF") {
    /*
    * We want to store/overwrite the current PDF version of this encounter's f
    * Currently this is only called 'beforeunload', ie. when you finish the form
    * In this current paradigm, anytime the form is opened, then closed, the PDF
    * is overwritten.  With esign implemented, the PDF should be locked.  I suppose
    * with esign the form can't even be opened so the only way to get to the PDF
    * is through the DOcuments->Encounters links.
    */
    $query = "select id from categories where name = 'Encounters'";
    $result = sqlStatement($query);
    $ID = sqlFetchArray($result);
    $category_id = $ID['id'];
    $PDF_OUTPUT='1';

    $filename = $pid."_".$encounter.".pdf"; 
    $filepath = $GLOBALS['oer_config']['documents']['repository'] . $pid;
    foreach (glob($filepath.'/'.$filename) as $file) {
      unlink($file);
    }
    $sql = "DELETE from categories_to_documents where document_id IN (SELECT id from documents where documents.url like '%".$filename."')";
    sqlQuery($sql);
    $sql = "DELETE from documents where documents.url like '%".$filename."'";
    sqlQuery($sql);
    // We want to overwrite so only one PDF is stored per form/encounter
    // $pdf = new HTML2PDF('P', 'Letter', 'en', array(5, 5, 5, 5) );  // add a little margin 5cm all around TODO: add to globals 

    /***********/
    $pdf = new HTML2PDF ($GLOBALS['pdf_layout'],
                         $GLOBALS['pdf_size'],
                         $GLOBALS['pdf_language'],
                         array($GLOBALS['pdf_left_margin'],$GLOBALS['pdf_top_margin'],$GLOBALS['pdf_right_margin'],$GLOBALS['pdf_bottom_margin'])
                         ); 
    ob_start();
    ?>
    <link rel="stylesheet" href="<?php echo $webserver_root; ?>/interface/themes/style_pdf.css" type="text/css">
    <div id="report_custom" style="width:100%;">  <!-- large outer DIV -->
      <?php
      report_header($pid);
      include_once($GLOBALS['incdir'] . "/forms/eye_mag/report.php");
      call_user_func($form_name . "_report", $pid, $form_encounter, $N, $form_id);
      if ($printable)
        echo "" . xl('Signature') . ": _______________________________<br />";
      ?>
    </div> <!-- end of report_custom DIV -->

    <?php

    global $web_root, $webserver_root;
    $content = ob_get_clean();
    // Fix a nasty html2pdf bug - it ignores document root!
    $i = 0;
    $wrlen = strlen($web_root);
    $wsrlen = strlen($webserver_root);
    while (true) {
      $i = stripos($content, " src='/", $i + 1);
      if ($i === false) break;
      if (substr($content, $i+6, $wrlen) === $web_root &&
          substr($content, $i+6, $wsrlen) !== $webserver_root)
      {
        $content = substr($content, 0, $i + 6) . $webserver_root . substr($content, $i + 6 + $wrlen);
      }
    }
    $pdf->writeHTML($content, false);
    $temp_filename = '/tmp/'.$filename;
    $content_pdf = $pdf->Output($temp_filename, 'F'); 
    /************/
    $type = "application/pdf"; 
    //$content = substr($content_pdf, strpos($content, ",")+1);
    $size = filesize($temp_filename);
    //echo $size;exit;
    $return = addNewDocument($filename,$type,$temp_filename,0,$size,$_SESSION['authUserID'],$pid,$category_id);    
    $doc_id = $return['doc_id'];
    echo $doc_id;
    $sql = "UPDATE documents set encounter_id=? where id=?"; //link it to this encounter
    sqlQuery($sql,array($encounter,$doc_id));  
  } 
  // Store the IMPPLAN area.  This is separate from the rest of the form
  // It is in a separate table due to its one-to-many relationship with the form_id.
  // If it is decided to use this actual table for the IMP/PLAN of other forms concurrently, 
  // we will need to add encounter and form_type fields to the table.
  // For now it is just part of the Eye Exam form so leave it in this little corner of the world.
  if ($_REQUEST['action']=="store_IMPPLAN") {
    $IMPPLAN = json_decode($_REQUEST['parameter'],true);
    //remove what is there and replace it with this data.
    $query = "DELETE from form_eye_mag_impplan where form_id=? and pid=?";
    sqlQuery($query,array($form_id,$pid));

    for($i = 0; $i < count($IMPPLAN); $i++) {
      $query ="INSERT IGNORE INTO form_eye_mag_impplan (form_id, pid, title, code, codetype, codedesc, codetext, plan, IMPPLAN_order, PMSFH_link) VALUES(?,?,?,?,?,?,?,?,?,?) ";
      $response = sqlQuery($query, array($form_id,$pid,$IMPPLAN[$i]['title'],$IMPPLAN[$i]['code'],$IMPPLAN[$i]['codetype'],$IMPPLAN[$i]['codedesc'],$IMPPLAN[$i]['codetext'],$IMPPLAN[$i]['plan'],$i,$IMPPLAN[$i]['PMSFH_link']));
      //if it is a duplicate then delete this from the array and return the array via json.
      //or rebuild it from mysql
      //echo "$i . response = ".$response."\n";
    }
    //Since we are potentially ignoring duplicates, build json IMPPLAN_items and return it to the user to rebuild IMP/Plan area
    $query ="select * from form_eye_mag_impplan where form_id=? and pid=? ORDER BY IMPPLAN_order";
    $newdata = array();
    $fres = sqlStatement($query,array($form_id,$pid));
    $i=0;
    while ($frow = sqlFetchArray($fres)) {
      $IMPPLAN_items[$i]['form_id'] = $frow['form_id'];
      $IMPPLAN_items[$i]['pid'] = $frow['pid'];
      $IMPPLAN_items[$i]['id'] = $frow['id'];
      $IMPPLAN_items[$i]['title'] = $frow['title'];
      $IMPPLAN_items[$i]['code'] = $frow['code'];
      $IMPPLAN_items[$i]['codetype'] = $frow['codetype'];
      $IMPPLAN_items[$i]['codedesc'] = $frow['codedesc'];
      $IMPPLAN_items[$i]['codetext'] = $frow['codetext'];
      $IMPPLAN_items[$i]['plan'] = $frow['plan'];
      $IMPPLAN_items[$i]['PMSFH_link'] = $frow['PMSFH_link'];
      $IMPPLAN_items[$i]['IMPPLAN_order'] = $frow['IMPPLAN_order'];
      $i++;
    }
    echo json_encode($IMPPLAN_items);
    exit;
  }
 
  /*** START CODE to DEAL WITH PMSFH/ISUUE_TYPES  ****/
  if ($_REQUEST['PMSFH_save'] =='1') { 
    if (!$PMSFH) $PMSFH = build_PMSFH($pid);
    $issue = $_REQUEST['issue'];
    $deletion = $_REQUEST['deletion'];
    $form_save = $_REQUEST['form_save'];
    $pid = $_SESSION['pid'];
    $encounter = $_SESSION['encounter'];
    $form_id = $_REQUEST['form_id'];
    $form_type = $_REQUEST['form_type'];
    $r_PMSFH = $_REQUEST['r_PMSFH'];
    if ($deletion ==1) {
      row_delete("issue_encounter", "list_id = '$issue'");
      row_delete("lists", "id = '$issue'");
      $PMSFH = build_PMSFH($pid);
      send_json_values($PMSFH);
      exit;
    } else {
      if ($form_type=='ROS') { //ROS
        $query="UPDATE form_eye_mag set ROSGENERAL=?,ROSHEENT=?,ROSCV=?,ROSPULM=?,ROSGI=?,ROSGU=?,ROSDERM=?,ROSNEURO=?,ROSPSYCH=?,ROSMUSCULO=?,ROSIMMUNO=?,ROSENDOCRINE=? where id=? and pid=?";
        sqlStatement($query,array($_REQUEST['ROSGENERAL'],$_REQUEST['ROSHEENT'],$_REQUEST['ROSCV'],$_REQUEST['ROSPULM'],$_REQUEST['ROSGI'],$_REQUEST['ROSGU'],$_REQUEST['ROSDERM'],$_REQUEST['ROSNEURO'],$_REQUEST['ROSPSYCH'],$_REQUEST['ROSMUSCULO'],$_REQUEST['ROSIMMUNO'],$_REQUEST['ROSENDOCRINE'],$form_id,$pid));
        $PMSFH = build_PMSFH($pid);
        send_json_values($PMSFH);
        exit;
      } elseif ($form_type=='SOCH') { //SocHx
        $newdata = array();
        $fres = sqlStatement("SELECT * FROM layout_options " .
          "WHERE form_id = 'HIS' AND uor > 0 AND field_id != '' " .
          "ORDER BY group_name, seq");
        while ($frow = sqlFetchArray($fres)) {
          $field_id  = $frow['field_id'];
          $newdata[$field_id] = get_layout_form_value($frow);
        }
        updateHistoryData($pid, $newdata);
        if ($_REQUEST['marital_status'] >'') {  
          // have to match input with list_option for marital to not break openEMR
          $query="select * from list_options where list_id='marital'";
          $fres = sqlStatement($query);
          while ($frow = sqlFetchArray($fres)) { 
            if (($_REQUEST['marital_status'] == $frow['option_id'])||($_REQUEST['marital_status'] == $frow['title'])) {
              $status = $frow['option_id'];
              $query = "UPDATE patient_data set status=? where pid=?";
              sqlStatement($query,array($status,$pid));
            }
          }
        }
        if ($_REQUEST['occupation'] > '') { 
          $query = "UPDATE patient_data set occupation=? where pid=?";
          sqlStatement($query,array($_REQUEST['occupation'],$pid));
        }
        $PMSFH = build_PMSFH($pid);
        send_json_values($PMSFH);
        exit;
      } elseif ($form_type =='FH') { 
        $query = "UPDATE history_data set 
                relatives_cancer=?,
                relatives_diabetes=?,
                relatives_high_blood_pressure=?,
                relatives_heart_problems=?, 
                relatives_stroke=?,
                relatives_epilepsy=?,
                relatives_mental_illness=?,
                relatives_suicide=?,
                usertext11=?,
                usertext12=?,
                usertext13=?,
                usertext14=?,
                usertext15=?,
                usertext16=?,
                usertext17=?,
                usertext18=? where pid=?";
                //echo $_REQUEST['relatives_cancer'],$_REQUEST['relatives_diabetes'],$_REQUEST['relatives_high_blood_pressure'],$_REQUEST['relatives_heart_problems'],$_REQUEST['relatives_stroke'],$_REQUEST['relatives_epilepsy'],$_REQUEST['relatives_mental_illness'],$_REQUEST['relatives_suicide'],$_REQUEST['usertext11'],$_REQUEST['usertext12'],$_REQUEST['usertext13'],$_REQUEST['usertext14'],$_REQUEST['usertext15'],$_REQUEST['usertext16'],$_REQUEST['usertext17'],$_REQUEST['usertext18'],$pid;
        $resFH = sqlStatement($query,array($_REQUEST['relatives_cancer'],$_REQUEST['relatives_diabetes'],$_REQUEST['relatives_high_blood_pressure'],$_REQUEST['relatives_heart_problems'],$_REQUEST['relatives_stroke'],$_REQUEST['relatives_epilepsy'],$_REQUEST['relatives_mental_illness'],$_REQUEST['relatives_suicide'],$_REQUEST['usertext11'],$_REQUEST['usertext12'],$_REQUEST['usertext13'],$_REQUEST['usertext14'],$_REQUEST['usertext15'],$_REQUEST['usertext16'],$_REQUEST['usertext17'],$_REQUEST['usertext18'],$pid));
        $PMSFH = build_PMSFH($pid);
        send_json_values($PMSFH);
        exit;
      } else { 
        if ($_REQUEST['form_title'] =='') return;
        $subtype ='';
        if ($form_type =="POH") {
          $form_type="medical_problem";
          $subtype="eye";
        } elseif ($form_type =="PMH") {
          $form_type="medical_problem";
        } elseif ($form_type =="Allergy") {
          $form_type="allergy";
        } elseif ($form_type =="Surgery") {
          $form_type="surgery";
        } elseif ($form_type =="Medication") {
          $form_type="medication";
        }
        $i = 0;
        $form_begin = fixDate($_REQUEST['form_begin'], '');
        $form_end   = fixDate($_REQUEST['form_end'], '');
        
        /*
         *  When adding an issue, see if the issue is already here.
         *  If so we need to update it.  If not we are adding it.
         *  Check the PMSFH array first by title. 
         *  If not present in PMSFH, check the DB to be sure.
         */
        foreach ($PMSFH[$form_type] as $item) {
          if ($item['title'] == $_REQUEST['form_title']) {
            $issue = $item['issue'];
          }
        }
        if (!$issue) {
          if ($subtype == '') {
            $query = "SELECT id,pid from lists where title=? and type=? and pid=?";
            $issue2 = sqlQuery($query,array($_REQUEST['form_title'],$form_type,$pid));
            $issue = $issue2['id'];
          } else {
            $query = "SELECT id,pid from lists where title=? and type=? and pid=? and subtype=?";
            $issue2 = sqlQuery($query,array($_REQUEST['form_title'],$form_type,$pid,$subtype));
            $issue = $issue2['id'];
          }
        }
        $issue = 0 + $issue;
        /*  Maybe use this for other forms?
        if ($_REQUEST['form_reinjury_id'] =="") $form_reinjury_id="0";
        if ($_REQUEST['form_injury_grade'] =="") $form_injury_grade="0";
        */
        if ($_REQUEST['form_outcome'] =='') $_REQUEST['form_outcome'] ='0';

        if ($issue != '0') { //if this issue already exists we are updating it...
          $query = "UPDATE lists SET " .
            "type = '"        . add_escape_custom($form_type)                  . "', " .
            "title = '"       . add_escape_custom($_REQUEST['form_title'])        . "', " .
            "comments = '"    . add_escape_custom($_REQUEST['form_comments'])     . "', " .
            "begdate = "      . QuotedOrNull($form_begin)   . ", "  .
            "enddate = "      . QuotedOrNull($form_end)     . ", "  .
            "returndate = "   . QuotedOrNull($form_return)  . ", "  .
            "diagnosis = '"   . add_escape_custom($_REQUEST['form_diagnosis'])    . "', " .
            "occurrence = '"  . add_escape_custom($_REQUEST['form_occur'])        . "', " .
            "classification = '" . add_escape_custom($_REQUEST['form_classification']) . "', " .
            "reinjury_id = '" . add_escape_custom($_REQUEST['form_reinjury_id'])  . "', " .
            "referredby = '"  . add_escape_custom($_REQUEST['form_referredby'])   . "', " .
            "injury_grade = '" . add_escape_custom($_REQUEST['form_injury_grade']) . "', " .
            "injury_part = '" . add_escape_custom($form_injury_part)           . "', " .
            "injury_type = '" . add_escape_custom($form_injury_type)           . "', " .
            "outcome = '"     . add_escape_custom($_REQUEST['form_outcome'])      . "', " .
            "destination = '" . add_escape_custom($_REQUEST['form_destination'])   . "', " .
            "reaction ='"     . add_escape_custom($_REQUEST['form_reaction'])     . "', " .
            "erx_uploaded = '0', " .
            "modifydate = NOW(), " .
            "subtype = '"     . $subtype. "' " .
            "WHERE id = '" . add_escape_custom($issue) . "'";
            sqlStatement($query);
            if ($text_type == "medication" && enddate != '') {
              sqlStatement('UPDATE prescriptions SET '
                . 'medication = 0 where patient_id = ? '
                . " and upper(trim(drug)) = ? "
                . ' and medication = 1', array($pid,strtoupper($_REQUEST['form_title'])) );
            }
        } else {          
          $query =  "INSERT INTO lists ( " .
          "date, pid, type, title, activity, comments, ".
          "begdate, enddate, returndate, " .
          "diagnosis, occurrence, classification, referredby, user, " .
          "groupname, outcome, destination,reaction,subtype " .
          ") VALUES ( " .
          "NOW(), ?,?,?,1,?," .
          QuotedOrNull($form_begin).", ".QuotedOrNull($form_end).", ".QuotedOrNull($form_return). ", "  .
          "?,?,?,?,?,".
          "?,?,?,?,?)";
          $issue = sqlInsert($query,array($pid,$form_type,$_REQUEST['form_title'],$_REQUEST['form_comments'],
              $_REQUEST['form_diagnosis'],$_REQUEST['form_occur'],$_REQUEST['form_clasification'],$_REQUEST['form_referredby'],$_SESSION['authUser'],
              $_SESSION['authProvider'],QuotedOrNull($_REQUEST['form_outcome']),$_REQUEST['form_destination'],$_REQUEST['form_reaction'],$subtype));
             
          // For record/reporting purposes, place entry in lists_touch table.
          setListTouch($pid,$form_type);

          // If requested, link the issue to a specified encounter.           
          if ($encounter) {
          $query = "INSERT INTO issue_encounter ( " .
            "pid, list_id, encounter " .
            ") VALUES ( ?,?,? )";
          sqlStatement($query, array($pid,$issue,$encounter,$_REQUEST['form_comments']));
          }

        }
        $irow = '';
        //if it is a medication do we need to do something with dosage fields? 
        //leave all in title field form now.
      }
      $PMSFH = build_PMSFH($pid);
      send_json_values($PMSFH);
      exit;
    }
 }
  /*** END CODE to DEAL WITH PMSFH/ISUUE_TYPES  ****/
  /* Let's save the encounter specific values.
    // Any field that exists in the database could be updated
    // so we need to exclude the important ones...
    // id  date  pid   user  groupname   authorized  activity.  Any other just add them below.
    // Doing it this way means you can add new fields on a web page and in the DB without touching this function.
    // The update feature still works because it only updates columns that are in the table you are working on.  
    // Building an undo feature:  Ctl-Z is curently client side per field.  Global action ctrl-z not implemented.
    // A shadow table could exist and each update request is added there also server side.
    // An UNDO request goes down one.
    // We will need to send a variable to the form with the UNDO table entry info.
    // This table will have an incremental field, pid and new field.  Just save it for now.
    // When done with the chart, or maybe on a repetitive frequency, this UNDO table will be purged
    // Maybe an esign button in the document to do all that openEMR does + this stuff?  We'll see.
    */

  $query = "SHOW COLUMNS from ".$table_name."";
  $result = sqlStatement($query);
  if (!$result) {
    return 'Could not run query: No columns found in your table!  ' . mysql_error();
    exit;
  }
  $fields = array();
  
  if (sqlNumRows($result) > 0) {
    while ($row = sqlFetchArray($result)) {
      //exclude critical columns/fields from update
      if ($row['Field'] == 'id' or 
         $row['Field'] == 'date' or 
         $row['Field'] == 'pid' or 
         $row['Field'] == 'user' or 
         $row['Field'] == 'groupname' or 
         $row['Field'] == 'authorized' or 
         $row['Field'] == 'LOCKED' or 
         $row['Field'] == 'LOCKEDBY' or 
         $row['Field'] == 'activity' or
         $row['Field'] == 'PLAN') 
        continue;
      if (isset($_POST[$row['Field']])) $fields[$row['Field']] = $_POST[$row['Field']];
    }
    // orders are checkboxes created from a user defined list in the PLAN area and stored as item1|item2|item3
    // if there are any, create the $field['PLAN'] value.
    // Remember --  If you uncheck a box, it won't be sent!  
    // So delete all made today by this provider and reload with any Orders sent in this $_POST
    // in addition, we made a special table for orders, and when completed we can mark done?
    // the PLAN2 textarea needs to be incorporated too. 
    $query="select form_encounter.date as encounter_date from form_encounter where form_encounter.encounter =? ";        
    $encounter_data =sqlQuery($query,array($encounter,$pid));
    $dated = new DateTime($encounter_data['encounter_date']);
    $dated = $dated->format('Y/m/d');
    $visit_date = oeFormatShortDate($dated);

    $N = count($_POST['PLAN']);
    $sql_clear = "DELETE from form_eye_mag_orders where ORDER_PID =? and ORDER_PLACED_BYWHOM=? and ORDER_DATE_PLACED=?";
    sqlQuery($sql_clear,array($pid,$providerID,$visit_date));  
    if ($N > '0') {
      for($i=0; $i < $N; $i++)
      {
        $fields['PLAN'] .= $_POST['PLAN'][$i] . "|"; //this makes an entry for form_eyemag: PLAN
        //update Orders
        //id  ORDER_PID   ORDER_DETAILS   ORDER_STATUS  ORDER_PRIORITY  ORDER_DATE_PLACED  ORDER_PLACED_BYWHOM
        $ORDERS_sql = "REPLACE INTO form_eye_mag_orders (ORDER_PID,ORDER_DETAILS,ORDER_STATUS,ORDER_DATE_PLACED,ORDER_PLACED_BYWHOM) VALUES (?,?,?,?,?)";
        $okthen = sqlQuery($ORDERS_sql,array($pid,$_POST['PLAN'][$i],'pending',$visit_date,$providerID));
      }
      $fields['PLAN'] = mb_substr($fields['PLAN'], 0, -1); //get rid of trailing "|"
    }
    echo "plan2 is ".$PLAN2;
    if ($_REQUEST['PLAN2']) {
      $fields['PLAN'] .= $_REQUEST['PLAN2'];
      //there is something in the plan textarea...
      $ORDERS_sql = "REPLACE INTO form_eye_mag_orders (ORDER_PID,ORDER_DETAILS,ORDER_STATUS,ORDER_PRIORITY,ORDER_DATE_PLACED,ORDER_PLACED_BYWHOM) VALUES (?,?,?,?,?,?)";
      $okthen = sqlQuery($ORDERS_sql,array($pid,$_POST['PLAN'][$i],'pending',"PLAN2:$PLAN2",$visit_date,$providerID));
    }

    /** Empty Checkboxes need to be entered manually as they are only submitted via POST when they are checked
      * If NOT checked on the form, they are sent via POST and thus are NOT overridden in the DB, 
      *  so DB won't change unless we define them into the $fields array as "0"...
      */
    if (!$_POST['alert']) $fields['alert'] = '0';
    if (!$_POST['oriented']) $fields['oriented'] = '0';
    if (!$_POST['confused']) $fields['confused'] = '0';
    if (!$_POST['PUPIL_NORMAL']) $fields['PUPIL_NORMAL'] = '0';
    if (!$_POST['MOTILITYNORMAL']) $fields['MOTILITYNORMAL'] = '0';
    if (!$_POST['ACT']) $fields['ACT'] = '0';
    if (!$_POST['DIL_RISKS']) $fields['DIL_RISKS'] = '0';
    if (!$_POST['ATROPINE']) $fields['ATROPINE'] = '0';
    if (!$_POST['CYCLOGYL']) $fields['CYCLOGYL'] = '0';
    if (!$_POST['CYCLOMYDRIL']) $fields['CYCLOMYDRIL'] = '0';
    if (!$_POST['NEO25']) $fields['NEO25'] = '0';
    if (!$_POST['TROPICAMIDE']) $fields['TROPICAMIDE'] = '0';
    if (!$_POST['BALANCED']) $fields['BALANCED'] = '0';
    if (!$_POST['ODVF1']) $fields['ODVF1'] = '0';
    if (!$_POST['ODVF2']) $fields['ODVF2'] = '0';
    if (!$_POST['ODVF3']) $fields['ODVF3'] = '0';
    if (!$_POST['ODVF4']) $fields['ODVF4'] = '0';
    if (!$_POST['OSVF1']) $fields['OSVF1'] = '0';
    if (!$_POST['OSVF2']) $fields['OSVF2'] = '0';
    if (!$_POST['OSVF3']) $fields['OSVF3'] = '0';
    if (!$_POST['OSVF4']) $fields['OSVF4'] = '0';
    if (!$fields['PLAN']) $fields['PLAN'] = '0';
   
    $success = formUpdate($table_name, $fields, $form_id, $_SESSION['userauthorized']);

    return $success;
  }
} elseif ($_REQUEST["mode"] == "retrieve")  {  
    
    if ($_REQUEST['PRIORS_query']) {
      echo display_PRIOR_section($_REQUEST['zone'],$_REQUEST['orig_id'],$_REQUEST['id_to_show'],$pid);
      exit;
    }
} 

/**  
 * Save the canvas drawings  
 */
if ($_REQUEST['canvas']) {
  if (!$pid||!$encounter||!$zone||!$_POST["imgBase64"]) exit;
 
  $side = "OU";
  $base_name = $pid."_".$encounter."_".$side."_".$zone."_VIEW";
  $filename = $base_name.".jpg";
  
  $type = "image/jpeg"; // all our canvases are this type
  $data = $_POST["imgBase64"];
  $data = substr($data, strpos($data, ",")+1);
  $data = base64_decode($data);
  $size = strlen($data);
  
  $query = "select id from categories where name = 'Drawings'";
  $result = sqlStatement($query);
  $ID = sqlFetchArray($result);
  $category_id = $ID['id'];

  // We want to overwrite so only one image is stored per zone per form/encounter
  // I do not believe this function exists in the current library, ie "UpdateDocument" function, so...
  //  we need to delete the previous file from the documents and categories to documents tables and the actual file
  //  There must be a delete_file function in documents class?
  // cannot find it.
  // this will work for harddisk people, not sure about couchDB people:
  $filepath = $GLOBALS['oer_config']['documents']['repository'] . $pid ."/";
  foreach (glob($filepath.'/'.$filename) as $file) {
    unlink($file);
  }

  $sql = "DELETE from categories_to_documents where document_id IN (SELECT id from documents where documents.url like '%".$filename."')";
  sqlQuery($sql);
  $sql ="DELETE from documents where documents.url like '%".$filename."'";
  sqlQuery($sql);
  $return = addNewDocument($filename,$type,$_POST["imgBase64"],0,$size,$_SESSION['authUserID'],$pid,$category_id);
  
  $doc_id = $return['doc_id'];
  $sql = "UPDATE documents set encounter_id=? where id=?"; //link it to this encounter
  sqlQuery($sql,array($encounter,$doc_id));  
  exit;
}

if ($_REQUEST['copy']) {
  copy_forward($_REQUEST['zone'],$_REQUEST['copy_from'],$_SESSION['ID'],$pid);
  return;
}
function QuotedOrNull($fld) {
  if ($fld) return "'".add_escape_custom($fld)."'";
  return "NULL";
}
function debug($local_var) {
    echo "<pre><BR>We are in the debug function.<BR>";
    echo "Passed variable = ". $local_var . " <BR>";
    print_r($local_var);
    exit;
}

/* From original issue.php */

function row_delete($table, $where) {
    $query = "SELECT * FROM $table WHERE $where";
    $tres = sqlStatement($query);
    $count = 0;
    while ($trow = sqlFetchArray($tres)) {
     $logstring = "";
     foreach ($trow as $key => $value) {
      if (! $value || $value == '0000-00-00 00:00:00') continue;
      if ($logstring) $logstring .= " ";
      $logstring .= $key . "='" . addslashes($value) . "'";
    }
    newEvent("delete", $_SESSION['authUser'], $_SESSION['authProvider'], 1, "$table: $logstring");
    ++$count;
    }
    if ($count) {
     $query = "DELETE FROM $table WHERE $where";
     sqlStatement($query);
    }
}
// Given an issue type as a string, compute its index.
// Not sure of the value of this sub given transition to array $PMSFH
// Can I use it to find out which PMSFH item we are looking for?  YES
function issueTypeIndex($tstr) {
  global $ISSUE_TYPES;
  $i = 0;
  foreach ($ISSUE_TYPES as $key => $value) {
    if ($key == $tstr) break;
    ++$i;
  }
  return $i;
}


function merge($filename_x, $filename_y, $filename_result) {
  /**
   *    Three png files (OU,OD,OS) per LOCATION (EXT,ANTSEG,RETINA,NEURO) 
   *    BASE, found in forms/$form_folder/images eg. OU_EXT_BASE.png
   *          BASE is the blank image to start from and can be customized. Currently 432x150px
   *    VIEW, found in /sites/$_SESSION['site_id']."/".$form_folder."/".$pid."/".$encounter
   *    TEMP, intermediate png merge file of new drawings with BASE or previous VIEW
   *          These are saved to be used in an undo feature...
   *    NO LONGER USING but I kept it here because it is cool and I will use it later
   */
  /*  
  This section
  if (file_exists($storage."/OU_".$zone."_VIEW.png")) { //add new drawings to previous for this encounter
      $file_base = $storage."/OU_".$zone."_VIEW.png";
    } else  { //start from the base image
      $file_base = $GLOBALS['webserver_root']."/interface/forms/".$form_folder."/images/OU_".$zone."_BASE.png";
    }
    //merge needs to store to a separate file first, then rename to new VIEW
    $file_temp = $storage."/OU_".$zone."_TEMP.png"; 
    $file_here = $storage."/OU_".$zone."_VIEW.png";
    merge( $file_draw, $file_base, $file_temp);
    rename( $file_temp , $file_here );
   */
  // Get dimensions for specified images
  list($width_x, $height_x) = getimagesize($filename_x);
  list($width_y, $height_y) = getimagesize($filename_y);

  // Create new image with desired dimensions
  $image = imagecreatetruecolor($width_y, $height_y);

  // Load images and then copy to destination image
  $image_x = imagecreatefrompng($filename_x);
  $image_y = imagecreatefrompng($filename_y);

  imagecopy($image, $image_y, 0, 0, 0, 0, $width_x, $height_x);
  imagecopy($image, $image_x, 0, 0, 0, 0, $width_x, $height_x);
 
  // Save the resulting image to disk (as png)
  imagepng($image, $filename_result);

  // Clean up
  imagedestroy($image);
  imagedestroy($image_x);
  imagedestroy($image_y);
}
//finalize($pid,$encounter); //since we are storing images client side, we may not need this...
exit;
?>