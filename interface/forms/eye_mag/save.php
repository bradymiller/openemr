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


include_once("../../globals.php");
include_once("$srcdir/api.inc");
include_once("$srcdir/forms.inc");
include_once("php/eye_mag_functions.php");
include_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");

//we need privileges to be restricted here?

$table_name   = "form_eye_mag";
$form_name    = "eye_mag";
$form_folder  = "eye_mag";
$returnurl    = $GLOBALS['concurrent_layout'] ? 'encounter_top.php' : 'patient_encounter.php';
//@extract($_SESSION); //working to remomve
//@extract($_REQUEST); //working to remove

$id = $_REQUEST['id'];
$AJAX_PREFS = $_REQUEST['AJAX_PREFS'];
if ($encounter == "" && !$id) {
    return "Sorry Charlie..."; //should lead to a database of errors for explanation.
    exit;
}

/**  
 * Save/update the preferences  
 */
if ($_REQUEST['AJAX_PREFS']) { 
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
                VALUES 
                ('PREFS','VA','Vision',?,'RS','51',?,'1') 
                ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_VA'],$_REQUEST['PREFS_VA)']));
 
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
                VALUES 
                ('PREFS','W','Current Rx',?,'W','52',?,'2')";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_W'],$_REQUEST['PREFS_W)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','MR','Manifest Refraction',?,'MR','53',?,'3') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_MR'],$_REQUEST['PREFS_MR)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CR','Cycloplegic Refraction',?,'CR','54',?,'4') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CR'],$_REQUEST['PREFS_CR)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CTL','Contact Lens',?,'CTL','55',?,'5') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CTL'],$_REQUEST['PREFS_CTL)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ADDITIONAL','Additional Data Points',?,'ADDITIONAL','56',?,'6') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ADDITIONAL'],$_REQUEST['PREFS_ADDITIONAL)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CLINICAL','CLINICAL',?,'CLINICAL','57',?,'7') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CLINICAL'],$_REQUEST['PREFS_CLINICAL)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','IOP','Intraocular Pressure',?,'IOP','67',?,'17') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_IOP'],$_REQUEST['PREFS_IOP)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','EXAM','EXAM',?,'EXAM','58',?,'8') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_EXAM'],$_REQUEST['PREFS_EXAM)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','CYLINDER','CYL',?,'CYL','59',?,'9') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_CYL'],$_REQUEST['PREFS_CYL)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','HPI_VIEW','HPI View',?,'HPI_VIEW','60',?,'10') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_HPI_VIEW'],$_REQUEST['PREFS_HPI_VIEW)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','EXT_VIEW','External View',?,'EXT_VIEW','66',?,'16') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_EXT_VIEW'],$_REQUEST['PREFS_EXT_VIEW)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ANTSEG_VIEW','Anterior Segment View',?,'ANTSEG_VIEW','61',?,'11') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ANTSEG_VIEW'],$_REQUEST['PREFS_ANTSEG_VIEW)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','RETINA_VIEW','Retina View',?,'RETINA_VIEW','62',?,'12') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_RETINA_VIEW'],$_REQUEST['PREFS_RETINA_VIEW)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','NEURO_VIEW','Neuro View',?,'NEURO_VIEW','63',?,'13') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_NEURO_VIEW'],$_REQUEST['PREFS_NEURO_VIEW)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ACT_VIEW','ACT View',?,'ACT_VIEW','64',?,'14') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ACT_VIEW'],$_REQUEST['PREFS_ACT_VIEW)']));
    $query = "REPLACE INTO form_eye_mag_prefs (PEZONE,LOCATION,LOCATION_text,id,selection,ZONE_ORDER,VALUE,ordering) 
              VALUES 
              ('PREFS','ACT_SHOW','ACT Show',?,'ACT_SHOW','65',?,'15') 
              ";
    sqlQuery($query,array($_SESSION['authId'],$_REQUEST['PREFS_ACT_SHOW'],$_REQUEST['PREFS_ACT_SHOW)'])); 
}
/**
  * ADD ANY NEW PREFERENCES above, and as a hidden field in the body.  I prefer this vs Session items but that would
  * also work here.  No good reason.
  */

/** <!-- End Preferences --> **/

/**  
 * Create, update or retrieve a form and its values  
 */
$pid            = $_SESSION['pid'];
$userauthorized = $_SESSION['userauthorized'];
$encounter      = $_REQUEST['encounter'];
if ($encounter == "") $encounter = date("Ymd");
$form_id        = $_REQUEST['form_id'];
$zone           = $_REQUEST['zone'];

if ($_GET["mode"] == "new")             { 
  $newid = formSubmit($table_name, $_POST, $id, $userauthorized);
  addForm($encounter, $form_name, $newid, $form_folder, $pid, $userauthorized);
} elseif ($_GET["mode"] == "update")    { 
  // The form is submitted to be updated.
  // Submission are ongoing and then the final unload of page changes the 
  // DOM variable $("#final") to == 1.  As one draws on the HTML5 canvas, each step is saved incrementally allowing
  // the user to go back through their history should they make a drawing error or simply want to reverse a
  // step.  They are saved client side now.  On finalization, we need to update the _VIEW.png file with the current
  // canvases.  

  // Is the form LOCKED? when and by whom, and esign it according to openEMR specs...
  // Need help here.
  // If this is LOCKED by esigning,tell user to move along, nothing to see here...
  // if this form/encounter? is esigned and locked, then return without touching data.
  
  // Any field that exists in the database could be updated
  // so we need to exclude the important ones...
  // id  date  pid   user  groupname   authorized  activity  .  Any other just add them below.
  // Doing it this way means you can add new fields on a web page and in the DB without touching this function.
  // The update feature still works because it only updates columns that are in the table you are wrking on.    
  $query = "SHOW COLUMNS from form_eye_mag";
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
         $row['Field'] == 'activity') 
        continue;
      if (isset($_POST[$row['Field']])) $fields[$row['Field']] = $_POST[$row['Field']];
    }
    /** checkboxes need to be entered manually as they are only submitted when they are checked
      * if NOT checked they are NOT overridden in the DB, so DB won't change
      *  unless we include them into the $fields array as "0"...
      */
    if (!$_POST['MOTILITYNORMAL']) $fields['MOTILITYNORMAL'] = '0';
    if (!$_POST['ACT']) $fields['ACT'] = '0';
    if (!$_POST['DIL_RISKS']) $fields['DIL_RISKS'] = '0';
    if (!$_POST['ATROPINE']) $fields['ATROPINE'] = '0';
    if (!$_POST['CYCLOGYL']) $fields['CYCLOGYL'] = '0';
    if (!$_POST['CYCLOMYDRIL']) $fields['CYCLOMYDRIL'] = '0';
    if (!$_POST['NEO25']) $fields['NEO25'] = '0';
    if (!$_POST['TROPICAMIDE']) $fields['TROPICAMIDE'] = '0';
    if (!$_POST['BALANCED']) $fields['BALANCED'] = '0';
    if (!$_POST['RX1']) $fields['RX1'] = '0';
    $success = formUpdate($table_name, $fields, $form_id, $_SESSION['userauthorized']);
    return $success;
  }
} elseif ($_GET["mode"] == "retrieve")  { 
    $query = "SELECT * FROM patient_data where pid=?";
    $pat_data =  sqlQuery($query,array($pid));
    @extract($pat_data);

    $query = "SELECT * FROM users where id = ?";
    $prov_data =  sqlQuery($query,array($_SESSION['authUserID']));
    $providerID = $prov_data['fname']." ".$prov_data['lname'];
      //the date in form_eye_mag is the date the form was created 
      //and may not equal the date of the encounter so we must make a special request to get the old data:
    $query = "select form_eye_mag.id as id_to_show from form_eye_mag left 
              join forms on form_eye_mag.id=forms.form_id and form_eye_mag.pid=forms.pid 
              where 
              forms.form_name = ? and 
              forms.id = ? and 
              forms.deleted !='1'  
              ORDER BY forms.date DESC";
    $visit_data =  sqlQuery($query,array($form_folder,$id_to_show));
    $query = "select form_eye_mag.id as id_to_show from form_eye_mag where id=?";
    $visit_data =  sqlQuery($query,array($id_to_show));
    @extract($visit_data);
      //ALL VARIABLES GET EXTRACTED AND ARE READY FOR USE.
      //HERE WE DECIDE WHAT WE WANT TO SHOW = A SEGMENT, A ZONE OR EVEN A VALUE...  
      
    if ($_REQUEST['PRIORS_query']) {
      //$id_to_show = $id;
      include_once("../../forms/".$form_folder."/php/".$form_folder."_functions.php");
      display_PRIOR_section($_REQUEST['zone'],$_REQUEST['orig_id'],$visit_data['id_to_show'],$pid);
      return; 
    }
} 

/**  
 * Save the canvas drawings  
 */

if ($_REQUEST['canvas']) {
  /**
   * Make the directory for this encounter to store the images
   * we are storing the images after the mouse leaves the canvas here:
   * $GLOBALS["OE_SITES_BASE"]."/".$_SESSION['site_id']."/documents/eye_mag/".$pid."/".$encounter
   * which for the "default" practice is going to be here:
   * /openemr/sites/default/documents/$pid/eye_mag/$encounter  
   * Each file also needs to be filed as a Document to retrieve through controller to keep HIPAA happy
   * Documents directory and subdirs are NOT publicly accessible directly (w/o acl checking)
   */
  
  $location = $GLOBALS["OE_SITES_BASE"]."/".$_SESSION['site_id']."/documents/".$pid;
 
  if (!is_dir($location."/".$form_folder."/".$encounter)) {
    if (!is_dir($location)) {
                mkdir($location, 0755, true);
                mkdir($location."/".$form_folder, 0755, true);
                mkdir($location."/".$form_folder."/".$encounter, 0755, true);
    } elseif (!is_dir($location."/".$form_folder)) {
                mkdir($location."/".$form_folder, 0755, true);
                mkdir($location."/".$form_folder."/".$encounter, 0755, true);
    } elseif (!is_dir($location."/".$form_folder."/".$encounter)) {
                mkdir($location."/".$form_folder."/".$encounter, 0755, true);
    } 
  }

  /** 
   *    BASE, found in forms/$form_folder/images eg. OU_EXT_BASE.png
   *          BASE is the blank image to start from and can be customized. 
   *    VIEW, found in /sites/$_SESSION['site_id']."/documents/".$pid."/".$form_folder."/".$encounter
   *    TEMP, intermediate png merge file of new drawings with BASE or previous VIEW
   *          Currently not implementd/used since we merge them client side, but may be later for layers?
   *    side, optional.  To add OD and OS with pre-existing OU.  Will next increase 
   *          to three png files (OU,OD,OS) per LOCATION (HPI,PMH,EXT,ANTSEG,RETINA,NEURO,IMPPLAN) 
   *          Since we only have one drawing so far.  Can extend this to a 3D plot/interpretation (X100Y46Z359) when 
   *          integrating layers with objects, perhaps radiology, OCT or 3D Ultrasound
   *          to pick out images at a specific angle/slice.  For now just use OU.
   */
  $side = "OU";
  $storage = $GLOBALS["OE_SITES_BASE"]."/".$_SESSION['site_id']."/documents/".$pid."/".$form_folder."/".$encounter;  
  $data =$_POST["imgBase64"];
  $data=substr($data, strpos($data, ",")+1);
  $data=base64_decode($data);
  $file_draw = $storage."/OU_".$zone."_VIEW.png";
  file_put_contents($file_draw, $data);


   /** 
    *  We have a file in the right place
    *  We need to tell the documents engine about this file, add it to the documents and doc_to_cat tables.
    *  So we can pullit up later for display.  It is part of the official record.
    */
  $file_here ="file://".$storage."/".$side."_".$zone."_VIEW.png";
  $doc = sqlQuery("Select * from documents where url='".$file_here."'");
  if ($doc['id'] < '1') {
    $doc = sqlQuery("select MAX(id)+1 as id from documents");
    $sql = "REPLACE INTO documents set 
              id=?,
              encounter_id=?,
              type='file_url',size=?,
              date=NOW(),
              mimetype='image/png',
              owner=?,
              foreign_id=?,
              docdate=NOW(),
              path_depth='3',
              url=?";
    $doc_id = sqlQuery($sql,array($doc['id'],$encounter,filesize($file_here),$_SESSION['authUserID'],$pid,$file_here));  

    $category = sqlQuery("select id from categories where name='Drawings'");       
    $sql = "REPLACE INTO categories_to_documents set category_id = ?, document_id = ?";
    sqlQuery($sql,array($category['id'],$doc['id']));  
  }
}

if ($copy) {
  copy_forward($zone,$copy_from,$copy_to,$pid);
}

function debug($local_var) {
    echo "<pre><BR>We are in the debug function.<BR>";
    echo "Passed variable = ". $local_var . " <BR>";
    print_r($local_var);
    exit;
}

function merge($filename_x, $filename_y, $filename_result) {
  /**
   *    Three png files (OU,OD,OS) per LOCATION (EXT,ANTSEG,RETINA,NEURO) 
   *    BASE, found in forms/$form_folder/images eg. OU_EXT_BASE.png
   *          BASE is the blank image to start from and can be customized. Currently 432x150px
   *    VIEW, found in /sites/$_SESSION['site_id']."/".$form_folder."/".$pid."/".$encounter
   *    TEMP, intermediate png merge file of new drawings with BASE or previous VIEW
   *          These are saved to be used in an undo feature...
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
finalize($pid,$encounter); //since we are storing images client side, we may not need this...
exit;
?>