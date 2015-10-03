<?php

/** 
 * forms/eye_mag/report.php 
 * 
 * Central report form for the eye_mag form.  Here is where all new data for display
 * is created.  New reports are created via new.php and then this script is displayed.
 * Edit are performed in view.php.  Nothing is editable here, but it is scrollable 
 * across time...
 * 
 * Copyright (C) 2016 Raymond Magauran <magauran@MedFetch.com> 
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
 *   * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *  The HTML5 Sketch plugin stuff:
 *    Copyright (C) 2011 by Michael Bleigh and Intridea, Inc.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software 
 *  and associated documentation files (the "Software"), to deal in the Software without restriction, 
 *  including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,  
 *  and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,  
 *  subject to the following conditions:
 *   
 *  The above copyright notice and this permission notice shall be included in all copies or substantial  
 *  portions of the Software.
 *   * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

include_once("../../globals.php");
include_once("$srcdir/acl.inc");
include_once("$srcdir/api.inc");
include_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/classes/Document.class.php");
require_once("$srcdir/classes/Note.class.php");
include_once("$srcdir/lists.inc");
require_once("$srcdir/forms.inc");
require_once("$srcdir/patient.inc");

$form_name = "eye_mag";
$form_folder = "eye_mag";

include_once("../../forms/".$form_folder."/php/".$form_folder."_functions.php");

$choice = $_REQUEST['choice'];

if ($_REQUEST['ptid']) $pid = $_REQUEST['ptid'];
if ($_REQUEST['encid']) $encounter=$_REQUEST['encid'];
if ($_REQUEST['formid']) $form_id = $_REQUEST['formid'];
if ($_REQUEST['formname']) $form_name=$_REQUEST['formname'];
if (!$id) $id=$form_id;
// Get users preferences, for this user 
// (and if not the default where a fresh install begins from, or someone else's) 
$query  = "SELECT * FROM form_eye_mag_prefs where PEZONE='PREFS' AND id=? ORDER BY ZONE_ORDER,ordering";
$result = sqlStatement($query,array($_SESSION['authUserID']));
while ($prefs= sqlFetchArray($result))   {    
  $LOCATION = $prefs['LOCATION'];
  $$LOCATION = text($prefs['VALUE']);
}

function eye_mag_report($pid, $encounter, $cols, $id, $formname='eye_mag') {
  global $form_folder;
  global $form_name;

  /** openEMR note:  eye_mag Index is id, 
    * linked to encounter in form_encounter 
    * whose encounter is linked to id in forms.
  */ 

  $query="select form_encounter.date as encounter_date,form_eye_mag.* 
  from form_eye_mag ,forms,form_encounter 
  where 
  form_encounter.encounter =? and 
  form_encounter.encounter = forms.encounter and 
  form_eye_mag.id=forms.form_id and
  forms.pid =form_eye_mag.pid and 
  form_eye_mag.pid=? ";        
  $objQuery =sqlQuery($query,array($encounter,$pid));
  @extract($objQuery);
  
  $dated = new DateTime($encounter_date);
  $dated = $dated->format('Y/m/d');
  $visit_date = oeFormatShortDate($dated);

 
  /*
   * Patient/Client -> Visits -> Visit History, on mouse over this is called with variable "choice".
   * The variable $choice will tell us what to display.
   * @param string $choice options NULL,TEXT,DRAW,NARRATIVE, only really using narrative for now
   * @param string $encounter = encounter number
   * @param string $pid value = patient id
   * @return string => returns the HTML of the report selected
   */    
  if ($_REQUEST['choice']) {  //this shows up on the encounter screen.
    $side="OU";
    $zone = array("HPI","PMH","VISION","NEURO","EXT","ANTSEG","RETINA","IMPPLAN");
      //  for ($i = 0; $i < count($zone); ++$i) {
      //  show only 2 for now in the encounter page
    ($choice =='drawing') ? ($count = count($zone)) : ($count ='2');
    for ($i = 0; $i < $count; ++$i) {
      $file_location = $GLOBALS["OE_SITES_BASE"]."/".$_SESSION['site_id']."/documents/".$pid."/".$form_folder."/".$encounter."/".$side."_".$zone[$i]."_VIEW.png";
      $sql = "SELECT * from documents where url='file://".$file_location."'";
      $doc = sqlQuery($sql);
      if (file_exists($file_location) && ($doc['id'] > '0')) {
        $filetoshow = $GLOBALS['web_root']."/controller.php?document&retrieve&patient_id=$pid&document_id=$doc[id]&as_file=false";
        ?><div style='position:relative;float:left;width:100px;height:75px;'>
        <img src='<?php echo $filetoshow; ?>' width=100 heght=75>
        </div> <?
      } else {
             // $filetoshow = "../../forms/".$form_folder."/images/".$side."_".$zone[$i]."_BASE.png?".rand();
      } 
      ?>

      <?php
    }
  } else if ($choice == "drawing") {
    ?>
    <div class="borderShadow">
      <?php display_draw_section ("VISION",$encounter,$pid); ?>
    </div>
    <div class="borderShadow">

      <?php display_draw_section ("NEURO",$encounter,$pid); ?>
    </div>
    <div class="borderShadow">

      <?php display_draw_section ("EXT",$encounter,$pid); ?>
    </div>
    <div class="borderShadow">

      <?php display_draw_section ("ANTSEG",$encounter,$pid); ?>
    </div>
    <div class="borderShadow">

      <?php display_draw_section ("RETINA",$encounter,$pid); ?>
    </div>
    <div class="borderShadow">

      <?php display_draw_section ("IMPPLAN",$encounter,$pid); ?>
    </div>
    <?php 
  } else if ($choice !="narrative") {
    narrative($pid, $encounter, $cols, $id);
    echo "</html>";
    return;
  }
}
function left_overs() {
  /*
  * Keep: this could be co-opted to export an XML/HL7 type of document
  */
  $count = 0;
  $data = formFetch($table_name, $id);

  if ($data) {
    foreach($data as $key => $value) {
      $$key=$value;
    }
  }
}

/*
 *  This is the core report, including Practice logo, patient ID/header.
 *  It relies on the presence of the PMSFH,IMPPLAN arrays.
 *  Rest of fields are pulled from the DB.
 */
function narrative($pid, $encounter, $cols, $form_id) {
  global $form_folder;
  global $PDF_OUTPUT;
  global $OE_SITE_DIR;
  global $formres;
  global $dateres;
  global $encounter;   
  
  $query="select form_encounter.date as encounter_date,form_encounter.*, form_eye_mag.* from form_eye_mag ,forms,form_encounter 
  where 
  form_encounter.encounter =? and 
  form_encounter.encounter = forms.encounter and 
  form_eye_mag.id=forms.form_id and
  forms.deleted != '1' and 
  form_eye_mag.pid=? ";        

  $encounter_data =sqlQuery($query,array($encounter,$pid));
  @extract($encounter_data);
  $providerID  =  getProviderIdOfEncounter($encounter);
  $providerNAME = getProviderName($providerID);
  ?>
  <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/font-awesome-4.2.0/css/font-awesome.css">
  <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/report.css" type="text/css">  
  <link rel="stylesheet" href="<?php echo $GLOBALS['css_header']; ?>" type="text/css">
  <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
  <style>
    h2 {
      font-size:1.3em;
    }
    .title {
      font-size:1em;
      position:absolute;
      right:10px;
      top:30px;
      font-size: 1em;
      position: absolute;
    }

    .refraction_panel {
      display:none;
      width:95%;
      height:auto;
    }

    .refraction_report {
      float: left;
      border: 1.00pt solid #000000; 
      padding: 0.1in; 
      margin: 4px; 
      webkit-box-flex: 0;
      -moz-box-flex: 0;
      box-flex: 0;
      display:inline-block;
      font-size: 1.0em;

    }
    .refraction td {
      text-align:center;
      font-size:0.9em;
      width:0.35in;
      vertical-align: text-middle;
      text-decoration: none;
      padding: 3px;
    }
    .refraction_report th {
      font-size: 1.0em;
      text-align: left;
      padding: 5 0 2 0;
      color: black; 
    }
    .refraction_report ul {
      list-style-type: none;
      margin: 0;
      padding: 0;
      overflow: hidden;
    }
    .refraction li {
      float: left;
    }

    input[type=text] {
      padding: 0.2em 0.4em;
      display: inline-block;
      text-align:center;
      width:60px;
      font-size:0.7em;
    }
    .refraction_report  b{
      text-decoration:bold;
    }
    .report_text {
      padding:2 10 2 10;
      width:150px;
      display: -webkit-flex;
    -webkit-flex-wrap: wrap;
    flex-wrap: wrap;
    }
    .right {
      -webkit-align-content: right;
    align-content: right;
      text-align:right;
    }
    .left {
      -webkit-align-content: left;
      align-content: left;
      text-align:left;
    }
    .label {
      color:black;
      border-radius:none;
    }

    .middle {
      text-align: center;
      font-weight: bold;
    }
    .body2 {
      font-size:12px;
      margin:0;
      padding:0;
    }
    .text {
      font-family: Arial;
    }
    .right {
      text-align:right;

    }
    #report_custom .encounter_form {
      padding-bottom:0px;
      margin-bottom:0px;
    }
    .refraction_tables {
      font-size:0.9em;
    }
    .refraction_tables td {
      text-align: center;
      padding-right:5px;
    }
    .borderShadow {
      min-width:700px;
      width:95%;
    }
    .center {
      text-align:center;
    }
    <?php if ($PDF_OUTPUT) { ?>
    .mot {
      text-align: center;
      width:3mm;
      height:3mm;
    }
    <?php } else { ?>
    .mot {
      text-align: center;
      width:5mm;
      height:5mm;
    }

      <?php }  ?>
    .panel_title {
      font-weight:bold;
    }
    .show_report {
      padding-left: 14px;
      padding-right:4px;
      text-align:left
    }
    .body2 {
      padding-left:105px;
    }
    .ACT {
      width:58px;
      height:58px;
    }
    .report_vitals {
      vertical-align:top;
      text-align:center;
      float:left;
      padding-right:2px;
      padding-left:2px;
      border-right:1pt black solid;
      margin-bottom:20px;
      font-size:0.9em;
      min-height:1in;
    }
    .report_exam_group {
      width:650px; text-align:center;
    }
    .report_section {
      margin:0 auto;padding:5px;text-align:center;
    }
  </style>
  <body class="body2">
    <?php 
  
    if ($PDF_OUTPUT) {
      $titleres = getPatientData($pid, "fname,lname,providerID,DATE_FORMAT(DOB,'%m/%d/%Y') as DOB_TS");
      if ($_SESSION['pc_facility']) {
      $sql = "select * from facility where id=" . $_SESSION['pc_facility'];
      } else {
      $sql = "SELECT * FROM facility ORDER BY billing_location DESC LIMIT 1";
      }
      /******************************************************************/
      $db = $GLOBALS['adodb']['db'];
      $results = $db->Execute($sql);
      $facility = array();
      if (!$results->EOF) {
      $facility = $results->fields;
      }
      // Setup Headers and Footers for html2PDF only Download
      // in HTML view it's just one line at the top of page 1
      //echo '<page footer="page">'; //use body tag instead; this <page></page>takes way too long
      echo '<page_header style="text-align:right;margin:-5mm;font-size:10px;"> ' . xlt("PATIENT") . ': ' . text($titleres['lname']) . ', ' . text($titleres['fname']) . ' - ' . $titleres['DOB_TS'] . '</page_header>    ';
      echo '<page_footer style="text-align:right;margin:5mm;font-size:10px;">' . xlt('Generated on') . ' ' . oeFormatShortDate() . ' - ' . text($facility['name']) . ' Phone:' . text($facility['phone']) . ' Fax:' . text($facility['fax']) . '</page_footer>';
      //  echo '</page>';

 

      // Use logo if it exists as 'practice_logo.gif' in the site dir
      // old code used the global custom dir which is no longer a valid
      $practice_logo = "$OE_SITE_DIR/images/practice_logo.gif";
      if (file_exists($practice_logo)) {
        echo "<img src='$practice_logo' align='left' style='width:100px;margin:10px;'><br />\n";
      } 
      ?><b><?php echo $facility['name'] ?></b><br />
      <?php echo $facility['street'] ?><br />
      <?php echo $facility['city'] ?>, <?php echo $facility['state'] ?> <?php echo $facility['postal_code'] ?><br clear='all' />
      <?php echo $facility['phone'] ?>

      <span class='title' style="top:0px;"><a href="javascript:window.close();"><?php echo $titleres['fname'] . " " . $titleres['lname']; ?></a><br />
        <span class='text'><?php echo xlt('Generated on'); ?>: <?php echo oeFormatShortDate(); ?></span>
        <br />
        <span class='text'><?php echo xlt('Visit Date'); ?>: <?php echo oeFormatSDFT(strtotime($dateres["date"])); ?></span>
        <br />
        <span class='text'><?php echo xlt('Provider') . ': ' . text(getProviderName(getProviderIdOfEncounter($encounter))).'<br />'; ?></span>
      </span>
      <?php  
    } ?>
    <br />
    <br /> 
    <br />
    <br />
    <table>
      <tr> 
        <td style="text-align:left;padding:1px;vertical-align:top;max-width:500px;">
          <table style="padding:5px;width:510px;">
            <tr>
              <td colspan="1" style="text-align: justify;text-justify: inter-word;width:500px;">
                <b><?php echo xlt('Chief Complaint'); ?>:</b> &nbsp;<?php echo text($CC1); ?>
                <br /><br />
                <b><?php echo xlt('HPI'); ?>:</b>   
                &nbsp;<?php echo $HPI1; ?>
                <br />
                <div style="padding-left:20px;">
                  <?php 
                  if ($TIMING1) {
                    echo "<i>".xlt('Timing'); ?>:</i>  &nbsp;<?php echo text($TIMING1)."<br />"; 
                  }
                  if ($CONTEXT1) {
                    echo "<i>".xlt('Context'); ?>:</i> &nbsp;<?php echo text($CONTEXT1)."<br />"; 
                  }
                  if ($SEVERITY1) {
                    echo "<i>".xlt('Severity'); ?>:</i> &nbsp;<?php echo text($SEVERITY1)."<br />"; 
                  }
                  if ($MODIFY1) {
                    echo "<i>".xlt('Modifying'); ?>:</i> &nbsp;<?php echo text($MODIFY1)."<br />";
                  }
                  if ($ASSOCIATED1) {
                    echo "<i>".xlt('Associated'); ?>:</i> &nbsp;<?php echo text($ASSOCIATED1)."<br />"; 
                  }
                  if ($LOCATION1) {
                    echo "<i>".xlt('Location'); ?>:</i> &nbsp;<?php echo text($LOCATION1)."<br />";
                  }
                  if ($QUALITY1) {
                    echo "<i>".xlt('Quality'); ?>:</i> &nbsp;<?php echo text($QUALITY1)."<br />";
                  }
                  if ($DURATION1) {
                    echo "<i>".xlt('Duration'); ?>:</i> &nbsp;<?php echo text($DURATION1)."<br />";
                  }
                  ?>

                  <?php 
                  if ($CC2) {
                    echo "
                    ";
                    echo "<b>".xlt('Chief Complaint 2'); ?>:</b> &nbsp;<?php echo text($CC2); ?>
                    <br />
                    <b><?php echo xlt('HPI'); ?>:</b>   
                    &nbsp;<?php echo text($HPI2); ?>
                    <br />

                    <div style="padding-left:10px;">
                      <?php 
                      if ($TIMING2) {
                        echo "<i>".xlt('Timing'); ?>:</i>  &nbsp;<?php echo text($TIMING2)."<br />"; 
                      }
                      if ($CONTEXT2) {
                        echo "<i>".xlt('Context'); ?>:</i> &nbsp;<?php echo text($CONTEXT2)."<br />"; 
                      }
                      if ($SEVERITY2) {
                        echo "<i>".xlt('Severity'); ?>:</i> &nbsp;<?php echo text($SEVERITY2)."<br />"; 
                      }
                      if ($MODIFY2) {
                        echo "<i>".xlt('Modifying'); ?>:</i> &nbsp;<?php echo text($MODIFY2)."<br />";
                      }
                      if ($ASSOCIATED2) {
                        echo "<i>".xlt('Associated'); ?>:</i> &nbsp;<?php echo text($ASSOCIATED2)."<br />"; 
                      }
                      if ($LOCATION2) {
                        echo "<i>".xlt('Location'); ?>:</i> &nbsp;<?php echo text($LOCATION2)."<br />";
                      }
                      if ($QUALITY2) {
                        echo "<i>".xlt('Quality'); ?>:</i> &nbsp;<?php echo text($QUALITY2)."<br />";
                      }
                      if ($DURATION2) {
                        echo "<i>".xlt('Duration'); ?>:</i> &nbsp;<?php echo text($DURATION2)."<br />";
                      }
                      ?>
                    </div>
                    <?
                  }
                  if ($CC3) {
                    ?>


                    <?php echo "<b>".xlt('Chief Complaint 3'); ?>:</b> &nbsp;<?php echo text($CC3); ?>
                    <br />
                    <?php echo xlt('HPI'); ?>&nbsp; <?php echo text($HPI3); ?>
                    <br />
                    <div style="padding-left:10px;">
                      <?php 
                      if ($TIMING3) {
                        echo "<i>".xlt('Timing'); ?>:</i>  &nbsp;<?php echo text($TIMING3)."<br />"; 
                      }
                      if ($CONTEXT3) {
                        echo "<i>".xlt('Context'); ?>:</i> &nbsp;<?php echo text($CONTEXT3)."<br />"; 
                      }
                      if ($SEVERITY3) {
                        echo "<i>".xlt('Severity'); ?>:</i> &nbsp;<?php echo text($SEVERITY3)."<br />"; 
                      }
                      if ($MODIFY3) {
                        echo "<i>".xlt('Modifying'); ?>:</i> &nbsp;<?php echo text($MODIFY3)."<br />";
                      }
                      if ($ASSOCIATED3) {
                        echo "<i>".xlt('Associated'); ?>:</i> &nbsp;<?php echo text($ASSOCIATED3)."<br />"; 
                      }
                      if ($LOCATION3) {
                        echo "<i>".xlt('Location'); ?>:</i> &nbsp;<?php echo text($LOCATION3)."<br />";
                      }
                      if ($QUALITY3) {
                        echo "<i>".xlt('Quality'); ?>:</i> &nbsp;<?php echo text($QUALITY3)."<br />";
                      }
                      if ($DURATION3) {
                        echo "<i>".xlt('Duration'); ?>:</i> &nbsp;<?php echo text($DURATION3)."<br />";
                      }
                      ?>
                    </div>

                    <?php 
                  } 
                  ?>
               
                  <?php 
                if ($CHRONIC1) { ?>
                  <b><?php echo xlt('Chronic or Inactive Problems'); ?>:</b> <br />
                  &nbsp;<?php echo text($CHRONIC1)."<br />"; 
                  if ($CHRONIC2) echo "&nbsp;".$CHRONIC2."<br />";
                  if ($CHRONIC3) echo "&nbsp;".$CHRONIC3."<br />";
                } ?> 
                </div>
              </td>
            </tr>
          </table> 
        </td>
        <td style="width:220px;padding:1px;vertical-align:top;">
          <?php 
            //get patient photo
            //no idea if this works for couchDB people
            $sql = "SELECT * FROM documents,categories,categories_to_documents WHERE 
                  documents.id=categories_to_documents.document_id and 
                  categories.id=categories_to_documents.category_id and 
                  categories.name='Patient Photograph' and 
                  documents.foreign_id=?";
            $doc = sqlQuery($sql,array($pid));
            $document_id =$doc['document_id'];
          if (is_numeric($document_id)) {
          
            $d = new Document($document_id);
            $fname = basename($d->get_url());
            $couch_docid = $d->get_couch_docid();
            $couch_revid = $d->get_couch_revid();
            $url_file = $d->get_url_filepath();
            if($couch_docid && $couch_revid){
              $url_file = $d->get_couch_url($pid,$encounter);
            }
            // Collect filename and path
            $from_all = explode("/",$url_file);
            $from_filename = array_pop($from_all);
            $from_pathname_array = array();
            for ($i=0;$i<$d->get_path_depth();$i++) {
              $from_pathname_array[] = array_pop($from_all);
            }
            $from_pathname_array = array_reverse($from_pathname_array);
            $from_pathname = implode("/",$from_pathname_array);
            if($couch_docid && $couch_revid) {
              $from_file = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $from_filename;
            }
            else {
              $from_file = $GLOBALS["fileroot"] . "/sites/" . $_SESSION['site_id'] .
              '/documents/' . $from_pathname . '/' . $from_filename;
            } 
            if ($PDF_OUTPUT) {
              echo "<img src='". $from_file."' style='width:220px;'>";
            } else {
              $filetoshow = "/openemr/controller.php?document&retrieve&patient_id=$pid&document_id=".$doc['document_id']."&as_file=false&blahblah=".rand();
              echo "<img src='".$filetoshow."' style='width:220px;'>";
            }
          }
             ?>
        </td>
      </tr>
    </table>
     <br />
    <hr style="width:50%;text-align:center;" />
    <table style="margin:1 auto;">
      <tr>
        <td style="width:730px;text-align:center;margin:1 auto;">        
        <?php 
        $PMSFH = build_PMSFH($pid);
        show_PMSFH_report($PMSFH);

         $query = "select * from form_eye_mag_wearing where PID=? and FORM_ID=? and ENCOUNTER=? ORDER BY RX_NUMBER";
                $wear = sqlStatement($query,array($pid,$form_id,$encounter));
                while ($wearing = sqlFetchArray($wear))   {
                  $count_rx++;
                  ${"display_W_$count_rx"} = '';
                  ${"ODSPH_$count_rx"} = $wearing['ODSPH'];
                  ${"ODCYL_$count_rx"} = $wearing['ODCYL'];
                  ${"ODAXIS_$count_rx"} = $wearing['ODAXIS'];
                  ${"OSSPH_$count_rx"} = $wearing['OSSPH'];
                  ${"OSCYL_$count_rx"} = $wearing['OSCYL'];
                  ${"OSAXIS_$count_rx"} = $wearing['OSAXIS'];
                  ${"ODMIDADD_$count_rx"} = $wearing['ODMIDADD'];
                  ${"OSMIDADD_$count_rx"} = $wearing['OSMIDADD'];
                  ${"ODADD_$count_rx"} = $wearing['ODADD'];
                  ${"OSADD_$count_rx"} = $wearing['OSADD'];
                  ${"ODVA_$count_rx"} = $wearing['ODVA'];
                  ${"OSVA_$count_rx"} = $wearing['OSVA'];
                  ${"ODNEARVA_$count_rx"} = $wearing['ODNEARVA'];
                  ${"OSNEARVA_$count_rx"} = $wearing['OSNEARVA'];
                  ${"ODPRISM_$count_rx"} = $wearing['ODPRISM'];
                  ${"OSPRISM_$count_rx"} = $wearing['OSPRISM'];
                  ${"COMMENTS_$count_rx"} = $wearing['COMMENTS'];
                  ${"W_$count_rx"} = '1';
                  ${"RX_TYPE_$count_rx"} = $wearing['RX_TYPE'];
                } 
        ?>
        
        </td>
      </tr>
    </table>
    <br />
    <hr style="width:50%;text-align:center;" />
    <table>
            <tr>
              <!-- Start of the Vision box -->   
              <td class="report_vitals">
                <b class="underline"><?php echo xlt('Visual Acuities'); ?></b>   
                <table id="Additional_VA" cellspacing="2" style="text-align:center;font-size:1.0em;">
                  <tr style="font-weight:bold;">
                    <td style="text-align:center;"></td>
                    <td style="width:50px;text-align:center;text-decoration:underline;"><?php echo xlt('OD'); ?></td>
                    <td style="width:50px;text-align:center;text-decoration:underline;"><?php echo xlt('OS'); ?></td>
                  </tr>
                  <?php if ($SCODVA||$SCOSVA) { ?>
                  <tr>
                    <td><?php echo xlt('sc{{without correction}}'); ?></td>
                    <td><?php echo attr($SCODVA); ?></td>
                    <td><?php echo attr($SCOSVA); ?></td>
                  </tr>
                  <?php } if ($ODVA_1||$OSVA_1) { ?>
                  <tr>
                    <td><?php echo xlt('cc{{with correction}}'); ?></td>
                    <td><?php echo attr($ODVA_1); ?></td>
                    <td><?php echo attr($OSVA_1); ?></td>
                  </tr>
                  <?php } if ($MRODVA||$MROSVA) { ?>
                  <tr>
                    <td><?php echo xlt('MR{{Manifest Refraction}}'); ?></td>
                    <td><?php echo attr($MRODVA); ?></td>
                    <td><?php echo attr($MROSVA); ?></td>
                  </tr>
                  <?php } if ($PHODVA||$PHOSVA) { ?>
                  <tr>
                    <td><?php echo xlt('PH{{Pinhole Vision}}'); ?></td>
                    <td><?php echo attr($PHODVA); ?></td>
                    <td><?php echo attr($PHOSVA); ?></td>
                  </tr>
                  <?php } if ($CTLODVA||$CTLOSVA) { ?>
                  <tr>
                    <td><?php echo xlt('CTL{{Contact Lens Vision}}'); ?></td>
                    <td><?php echo attr($CTLODVA); ?></td>
                    <td><?php echo attr($CTLOSVA); ?></td>
                  </tr>
                  <?
                } ?>
                </table>
              </td>
              <!-- START OF THE PRESSURE BOX -->
              <td class="report_vitals">    
                <b class="underline"><?php echo xlt('Intraocular Pressures'); ?></b>
                <table cellspacing="2" style="margin:2;text-align:center;font-size:1.0em;"> 
                  <tr style="font-weight:bold;">
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;text-decoration:underline;"><?php echo xlt('OD'); ?></td>
                    <td style="text-align:center;text-decoration:underline;"><?php echo xlt('OS'); ?></td>
                  </tr>
                  <?php
                  if ($ODIOPAP||$OSIOPAP) echo "<tr><td style='text-align:right;padding-right:10px;'>".xlt('App{{Applanation abbreviation}}').":</td><td style='text-align:center;'>".text($ODIOPAP)."</td><td style='width:75px;text-align:center;'>".text($OSIOPAP)."</td></tr>";
                  if ($ODIOPTPN||$OSIOPTPN) echo "<tr><td style='text-align:right;padding-right:10px;'>".xlt('Tpn{{Tonopen abbreviation}}').":</td><td style='text-align:center;'>".text($ODIOPTPN)."</td><td style='width:75px;text-align:center;'>".text($OSIOPTPN)."</td></tr>";
                  if ($ODIOPFTN||$OSIOPFTN) echo "<tr><td style='text-align:right;padding-right:10px;'>".xlt('FTN{{Finger Tension abbreviation}}').":</td><td style='text-align:center;'>".text($ODIOPFTN)."</td><td style='width:75px;text-align:center;'>".text($OSIOPFTN)."</td></tr>";
                  ?>
                  <tr>
                    <td colspan="3" style="padding:5px;text-align:center;">
                      @ <?php echo text($IOPTIME); ?>
                    </td>
                  </tr>
                </table>
              </td>
              <!-- START OF THE AMSLER BOX -->
              <?php if ($AMSLEROD||$AMSLEROS) { ?>
              <td class="report_vitals <?php if (!$ODAMSLER && !$OSAMSLER) echo "display"; ?>">    
                <b class="underline"><?php echo xlt('Amsler'); ?></b>      
                <?php 
                if (!$AMSLEROD) $AMSLEROD= "0";
                if (!$AMSLEROS) $AMSLEROS= "0";
                ?> 
                <table style="font-size:1.0em;">
                  <tr style="font-weight:bold;">
                    <td style="text-align:center;text-decoration:underline;"><?php echo xlt('OD'); ?></td>
                    <td></td>
                    <td style="text-align:center;text-decoration:underline;"><?php echo xlt('OS'); ?></td>
                  </tr>

                  <tr>
                    <td style="text-align:center;">
                      <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROD); ?>.jpg" id="AmslerOD" style="margin:0.05in;height:0.5in;width:0.6in;" />
                      <br />
                      <small><?php echo text($AMSLEROD); ?>/5</small>
                    </td>
                    <td></td>
                    <td style="text-align:center;">
                      <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROS); ?>.jpg" id="AmslerOS" style="margin:0.05in;height:0.5in;width:0.6in;" />
                      <br /><small><?php echo text($AMSLEROS); ?>/5</small>
                    </td>
                  </tr>
                </table>
              </td>
              <?php } ?>
              <!-- START OF THE FIELDS BOX -->
              <td class="report_vitals">   
                <b class="underline"><?php echo xlt('Fields'); ?></b>
                <?php 
                // if the VF zone is checked, display it
                // if ODVF1 = 1 (true boolean) the value="0" checked="true"
                for ($z=1; $z <5; $z++) {
                  $ODzone = "ODVF".$z;
                  if ($$ODzone =='1') {
                    $ODVF[$z] = '<i class="fa fa-square fa-5"></i>';
                     if ($PDF_OUTPUT) $ODVF[$z] = 'X';
                    $bad++;
                  } else {
                    $ODVF[$z] = '<i class="fa fa-square-o fa-5"></i>';
                    if ($PDF_OUTPUT) $ODVF[$z] = 'O';

                  }
                  $OSzone = "OSVF".$z;
                  if ($$OSzone =="1") {
                    $OSVF[$z] = '<i class="fa fa-square fa-5"></i>';
                    if ($PDF_OUTPUT) $OSVF[$z] = 'X';
                    $bad++;
                  } else {
                    $OSVF[$z] = '<i class="fa fa-square-o fa-5"></i>';
                    if ($PDF_OUTPUT) $OSVF[$z] = 'O';

                  }
                }
                ?>
                <?php 
                if ($bad < '1' ) { 
                  echo "<br /><br />&nbsp;Full to CF OU&nbsp;<br /><br /><br />";
                } else {
                  ?>
                  <table style="font-size:1.0em;text-align:center;">        
                    <tr style="font-weight:bold;">    
                            <td style="width:0.5in;text-align:center;text-decoration:underline;" colspan="2"><b><?php echo xlt('OD'); ?></b>
                              <br />
                              <br />
                            </td>
                            <td style="width:0.1in;"> </td>
                            <td style="width:0.5in;text-align:center;text-decoration:underline;" colspan="2"><b><?php echo xlt('OS'); ?></b>
                              <br />
                              <br />
                            </td>
                    </tr> 
                    <tr>    
                      <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:center;">
                        <?php echo $ODVF['1']; ?>
                      </td>
                      <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:center;">
                        <?php echo $ODVF['2']; ?>
                      </td>
                      <td></td>
                      <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:center;">
                        <?php echo $OSVF['1']; ?>
                      </td>
                      <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:center;">
                        <?php echo $OSVF['2']; ?>
                      </td>
                    </tr>       
                    <tr>    
                      <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:center;">
                        <?php echo $ODVF['3']; ?>
                      </td>
                      <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:center;">
                        <?php echo $ODVF['4']; ?>
                      </td>
                      <td></td>
                      <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:center;">
                        <?php echo $OSVF['3']; ?>
                      </td>
                      <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:center;">
                        <?php echo $OSVF['4']; ?>
                      </td>                    
                    </tr>
                  </table>
                  <?php 
                } ?>
              </td>  
              <!-- START OF THE MOTILITY BOX -->
              <td class="report_vitals" style="border-right:0pt;">
                <b class="underline"><?php echo xlt('Motility'); ?></b>
                <?php 
                if ($MOTILITYNORMAL =='on') { 
                  echo "<br /><br />&nbsp;".xlt('D&V Full OU{{Ductions and Versions full OU}}') ."&nbsp;<br /><br />";
                } else { 
                  if ($PDF_OUTPUT) {
                    $background = "url(".$GLOBALS["fileroot"] . "/interface/forms/".$form_folder."/images/eom.jpg)";
                  } else {
                    $background = "url(../../forms/".$form_folder."/images/eom.bmp)";
                  }
                  $zone = array("MOTILITY_RRSO","MOTILITY_RS","MOTILITY_RLSO","MOTILITY_RR","MOTILITY_R0","MOTILITY_RL","MOTILITY_RRIO","MOTILITY_RI","MOTILITY_RLIO","MOTILITY_LRSO","MOTILITY_LS","MOTILITY_LLSO","MOTILITY_LR","MOTILITY_L0","MOTILITY_LL","MOTILITY_LRIO","MOTILITY_LI","MOTILITY_LLIO");
                  for ($i = 0; $i < count($zone); ++$i) {
                    ($$zone[$i] >= '1') ? ($$zone[$i] = "-".$$zone[$i]) : ($$zone[$i] = '');
                  }
                  ?>
                  <table style="font-size:1.0em;">
                    <tr style="font-weight:bold;">
                    <td style="text-align:center;text-decoration:underline;"><?php echo xlt('OD'); ?></td>
                    <td style="text-align:center;text-decoration:underline;"><?php echo xlt('OS'); ?></td>
                  </tr>
                    <tr>
                      <td style="font-weight:600;">
                        <table style="background: <?php echo $background; ?> no-repeat center center;filter: progid:DXImageTransform.Microsoft.Alpha(opacity=50); -moz-opacity: 0.5; -webkit-opacity: 0.5; opacity:0.5;padding-bottom:5px;">
                          <tr>
                            <td class="mot"><?php echo $MOTILITY_RRSO; ?></td>
                            <td class="mot"><?php echo $MOTILITY_RS; ?></td>
                            <td class="mot"><?php echo $MOTILITY_RLSO; ?></td>
                          </tr>
                          <tr>
                            <td class="mot"><?php echo $MOTILITY_RR; ?></td>
                            <td class="mot"><?php echo $MOTILITY_R0; ?></td>
                            <td class="mot"><?php echo $MOTILITY_RL; ?></td>
                          </tr>
                          <tr>
                            <td class="mot"><?php echo $MOTILITY_RRIO; ?></td>
                            <td class="mot"><?php echo $MOTILITY_RI; ?></td>
                            <td class="mot"><?php echo $MOTILITY_RLIO; ?></td>
                          </tr>
                        </table>
                      </td>
                      <td style="text-align:center;font-weight:600;padding-left:20px;">
                        <table style="background: <?php echo $background; ?> no-repeat center center;filter: progid:DXImageTransform.Microsoft.Alpha(opacity=50)              -moz-opacity: 0.5;              -webkit-opacity: 0.5;              opacity:0.5;              padding:1px;">
                          <tr>
                            <td class="mot"><?php echo $MOTILITY_LRSO; ?></td>
                            <td class="mot"><?php echo $MOTILITY_LS; ?></td>
                            <td class="mot"><?php echo $MOTILITY_LLSO; ?></td>
                          </tr>
                          <tr>
                            <td class="mot"><?php echo $MOTILITY_LR; ?></td>
                            <td class="mot"><?php echo $MOTILITY_L0; ?></td>
                            <td class="mot"><?php echo $MOTILITY_LL; ?></td>
                          </tr>
                          <tr>
                            <td class="mot"><?php echo $MOTILITY_LLIO; ?></td>
                            <td class="mot"><?php echo $MOTILITY_LI; ?></td>
                            <td class="mot"><?php echo $MOTILITY_LLIO; ?></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                  <?php
                } ?>
              </td>
            </tr>
    </table>
    <hr style="width:50%;text-align:center;" />
    <table>
      <tr>
        <td>
          <?php  
          if ($PUPIL_NORMAL =='1') { ?>
            <b class="underline"><?php echo xlt('Pupils'); ?></b>&nbsp;&nbsp;
            <?php echo xlt('Round and Reactive')."<br />"; 
          } 
          if ($ODPUPILSIZE1||$OSPUPILSIZE1) { ?>
            <table cellspacing="2" style="margin:2;text-align:center;font-size:1.0em;">
              <tr>
                <!-- start of the Pupils box -->
                <td>
                <b class="underline"><?php echo xlt('Pupils'); ?></b> 
                <table style="font-size: 0.7em;text-align:top;"> 
                      <tr>    
                        <th style="width:0.4in;"> &nbsp;
                        </th>
                        <th style="width:1.0in;padding: 0.1;"><?php echo xlt('size'); ?> (<?php echo xlt('mm{{size in millimeters}}'); ?>)
                        </th>
                        <th style="width:0.4in;padding: 0.1;"><?php echo xlt('react{{reactivity}}'); ?> 
                        </th>
                        <th style="width:0.4in;padding: 0.1;"><?php echo xlt('APD{{Afferent Pupillary Defect}}'); ?>
                        </th>
                      </tr>
                      <tr>    
                        <td><b><?php echo xlt('OD'); ?></b>
                        </td>
                        <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:center;">
                          <?php echo text($ODPUPILSIZE1); ?>
                          --&gt;
                          <?php echo text($ODPUPILSIZE2); ?>
                        </td>
                        <td style="text-align:center;border-left:1pt solid black;border-right:1pt solid black;border-bottom:1pt solid black;">
                          <?php echo text($ODPUPILREACTIVITY); ?>
                        </td>
                        <td style="text-align:center;border-bottom:1pt solid black;">
                          <?php echo text($ODAPD); ?>
                        </td>
                      </tr>
                      <tr>    
                        <td><b><?php echo xlt('OS'); ?></b>
                        </td>
                        <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:center;">
                          <?php echo text($OSPUPILSIZE1); ?>
                          --&gt;
                          <?php echo text($OSPUPILSIZE2); ?>
                        </td>
                        <td style="text-align:center;border-left:1pt solid black;border-right:1pt solid black;border-top:1pt solid black;">
                          <?php echo text($OSPUPILREACTIVITY); ?>
                        </td>
                        <td style="text-align:center;border-top:1pt solid black;">
                          <?php echo text($OSAPD); ?>
                        </td>
                      </tr>
                </table>
                <?php if ($DIMODPUPILSIZE1||$DIMOSPUPILSIZE1||$PUPIL_COMMENTS) { ?>
                <!-- start of slide down pupils_panel --> 
                <?php (($DIMODPUPILSIZE1) || ($DIMOSPUPILSIZE1)) ? ($display_DP='display') : ($display_DP='nodisplay'); ?>
                </td>
                <td style="padding-left:20px;"> <b class="underline"><?php echo xlt('Pupils') ?>: <?php echo xlt('Dim'); ?></b>
                  <table style="font-size: 0.7em;text-align:top;"> 
                      <tr >    
                        <th></th>
                        <th style="width:0.9in;padding: 0.1;text-align:center;" ><?php echo xlt('size'); ?> (<?php echo xlt('mm'); ?>)
                        </th>
                        <td rowspan="4" style="padding-left:3px;text-align:bottom;">

                          <?php echo text($PUPIL_COMMENTS); ?>
                        </td>
                      </tr>
                      <tr>    
                        <td><b><?php echo xlt('OD'); ?></b>
                        </td>
                        <td style="text-align:bottom;border-bottom:1pt solid black;padding-left:0.1in;">
                          <?php echo text($DIMODPUPILSIZE1); ?>
                          --&gt;
                          <?php echo text($DIMODPUPILSIZE2); ?>
                        </td>

                      </tr>
                      <tr>    
                        <td ><b><?php echo xlt('OS'); ?></b>
                        </td>
                        <td style="border-top:1pt solid black;padding-left:0.1in;">
                          <?php echo text($DIMOSPUPILSIZE1); ?>
                          --&gt;
                          <?php echo text($DIMOSPUPILSIZE2); ?>
                        </td>
                      </tr>
                  </table>
                  <!-- end of slide down pupils_panel --> 
                  <!-- end of the Pupils box -->
                  <?php } ?>
                </td>
              </tr>
            </table>
            <?php 
          } ?>
        </td>
      </tr>
    </table>
  
    <!-- start of the refraction boxes -->
    <table class="refraction_tables">
            <?php 
            if ($count_rx||$ARODSPH||$AROSSPH||$MRODSPH||$MROSSPH||$CRODSPH||$CROSSPH||$CTLODSPH||$CTLOSSPH) { ?>
                <tr>
                  <td colspan="9" style="text-align:left;text-decoration:underline;font-weight:bold;"><?php echo xlt('Refractive States'); ?>
                  </td>
                </tr>
                <tr style="text-align:center;padding:5px;text-decoration:underline;">
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Type'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Eye'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Sph{{Sphere}}'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Axis'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Prism'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Acuity'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Mid{{Middle Distance Add}}'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('ADD{{Near Add}}'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('Acuity'); ?></td>
                </tr>
                <?php 
                  $count_rx++;
                  for ($i=1; $i < $count_rx; $i++) {
                    if (${"RX_TYPE_$i"} =="0") {
                      $RX_TYPE = xlt('Single Vision'); 
                    } else if (${"RX_TYPE_$i"} =="1") {
                      $RX_TYPE = xlt('Bifocals'); 
                    } else if (${"RX_TYPE_$i"} =="2") {
                      $RX_TYPE = xlt('Trifocals'); 
                    } else if (${"RX_TYPE_$i"} =="3") {
                      $RX_TYPE = xlt('Progressive'); 
                    }  
                    /*
                        Note html2pdf does not like the last field of a table to be blank.  
                        If it is it will squish the lines together.
                        Work around: if the field is blank, then replace it with a "-" else echo it.
                        aka echo (text($field))?:"-");
                    */
                    ?>
                    <tr>
                      <td style="font-weight:600;font-size:0.7em;text-align:right;"><?php echo xlt('Current RX')." #".$i.": "; echo $RX_TYPE; ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('OD{{right eye}}'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODSPH_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODCYL_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODAXIS_$i"})?:"-"); ?></td>   
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODPRISM_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODVA_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODMIDADD_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODADD_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"ODNEARVA_$i"})?:"-"); ?></td>
                    </tr>
                    <tr>
                      <td style="font-weight:600;font-size:0.7em;text-align:right;"></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('OS{{left eye}}'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSSPH_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSCYL_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSAXIS_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSPRISM_$i"})?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSVA_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSMIDADD_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSADD_$i"})?:"-"); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text(${"OSNEARVA_$i"})?:"-"); ?></td>
                    </tr>
                    <?php 
                    if (${"COMMENTS_$i"}) {
                      ?>
                      <tr>
                        <td></td>
                        <td colspan="2"><?php echo xlt('Comments'); ?>:</td>
                        <td colspan="7"><?php echo text(${"COMMENTS_$i"}); ?></td>
                      </tr>
                      <?php 
                    } 
                  } 

                  if ($ARODSPH||$AROSSPH) { ?>
                    <tr style="border-bottom:1pt solid black;">
                      <td style="font-weight:600;font-size:0.7em;text-align:right;"><?php echo xlt('Autorefraction'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('OD{{right eye}}'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARODSPH)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARODCYL)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARODAXIS)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARODPRISM)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARODVA)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;">-</td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARODADD)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARNEARODVA)?:"-"); ?></td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td style="font-weight:400;font-size:1.0em;text-align:right;"><?php echo xlt('OS{{left eye}}'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($AROSSPH)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($AROSCYL)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($AROSAXIS)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($AROSPRISM)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($AROSVA)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;">-</td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($AROSADD)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($ARNEAROSVA)?:"-"); ?></td>
                    </tr>
                    <?php 
                    if (${"COMMENTS_$i"}) {
                      ?>
                      <tr>
                        <td></td><td></td>
                        <td>Comments:</td>
                        <td colspan="7"><?php echo text(${"COMMENTS_$i"}); ?></td>
                      </tr>
                      <?php 
                    } 
                  }
                  if ($MRODSPH||$MROSSPH) { ?>
                      <tr>
                        <td style="font-weight:600;font-size:0.7em;text-align:right;"><?php echo xlt('Manifest (Dry) Refraction'); ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('OD{{right eye}}'); ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRODSPH)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRODCYL)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRODAXIS)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRODPRISM)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRODVA)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;">-</td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRODADD)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRNEARODVA)?:"-"); ?></td>
                      </tr>
                      <tr></tr>
                      <tr>
                        <td></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:right;"><?php echo xlt('OS{{left eye}}'); ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MROSSPH)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MROSCYL)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MROSAXIS)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MROSPRISM)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MROSVA)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;">-</td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MROSADD)?:"-");  ?></td>
                        <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($MRNEAROSVA)?:"-"); ?></td>
                      </tr>
                      <?php 
                  }
                  if ($CRODSPH||$CROSSPH) { ?>
                    <tr>
                      <td style="font-weight:600;font-size:0.8em;text-align:right;"><?php echo xlt('Cycloplegic (Wet) Refraction'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('OD{{right eye}}'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRODSPH)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRODCYL)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRODAXIS)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRODPRISM)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRODVA)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;">-</td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRODADD)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRNEARODVA)?:"-"); ?></td>
                    </tr>
                    <tr>
                      <td></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:right;"><?php echo xlt('OS{{left eye}}'); ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CROSSPH)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CROSCYL)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CROSAXIS)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CROSPRISM)?:"-");  ?>&nbsp;</td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CROSVA)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;">-</td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CROSADD)?:"-");  ?></td>
                      <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CRNEAROSVA)?:"-"); ?></td>
                    </tr>
                    <?php 
                  }
                  if ($CTLODSPH||$CTLOSSPH) { ?>
                <tr style="text-align:center;padding:5px;text-decoration:underline;">
                  <td></td>
                  <td><?php echo xlt('Eye'); ?></td>
                  <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                  <td><?php echo xlt('Cyl{{Cyinder}}'); ?></td>
                  <td><?php echo xlt('Axis'); ?></td>
                  <td><?php echo xlt('BC{{Base Curve}}'); ?></td>
                  <td><?php echo xlt('Diam{{Diameter}}'); ?></td>
                  <td><?php echo xlt('ADD'); ?></td>
                  <td><?php echo xlt('Acuity'); ?></td>
                </tr>
                <tr>
                  <td style="font-weight:600;font-size:0.8em;text-align:right;"><?php echo xlt('Contact Lens'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('OD{{right eye}}'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLODSPH)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLODCYL)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLODAXIS)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLODBC)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLODDIAM)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLODADD)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLODVA)?:"-"); ?></td>
                </tr>
                <tr style="font-size:0.6em;">
                  <td></td>
                  <td></td>
                  <td colspan="3" style="font-weight:400;font-size:1.0em;text-align:left;"><?php echo xlt('Brand'); ?>:<?php echo (text($CTLBRANDOD)?:"-");  ?></td>
                  <td colspan="3" style="font-weight:400;font-size:1.0em;text-align:left;"><?php echo xlt('by{{made by/manufacturer}}'); ?> <?php echo (text($CTLMANUFACTUREROD)?:"-");  ?></td>
                  <td colspan="3" style="font-weight:400;font-size:1.0em;text-align:left;"><?php echo xlt('via{{shipped by/supplier}}'); ?> <?php echo (text($CTLSUPPLIEROD)?:"-");  ?></td>
                  
                </tr>
                <tr>
                  <td></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo xlt('OS{{left eye}}'); ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLOSSPH)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLOSCYL)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLOSAXIS)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLOSBC)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLOSDIAM)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo (text($CTLOSADD)?:"-");  ?></td>
                  <td style="font-weight:400;font-size:1.0em;text-align:center;"><?php echo ($CTLOSVA?:"-"); ?></td>
                </tr>
                <tr style="font-size:0.6em;">
                  <td></td>
                  <td></td>
                  <td colspan="3" style="font-weight:400;font-size:1.0em;text-align:left;"><?php echo xlt('Brand'); ?>: <?php echo (text($CTLBRANDOS)?:"-");  ?></td>
                  <td colspan="3" style="font-weight:400;font-size:1.0em;text-align:left;"><?php echo xlt('by{{made by/manufacturer}}'); ?> <?php echo (text($CTLMANUFACTUREROS)?:"-");  ?></td>
                  <td colspan="3" style="font-weight:400;font-size:1.0em;text-align:left;"><?php echo xlt('via{{shipped by/supplier}}'); ?> <?php echo (text($CTLSUPPLIEROS)?:"-");  ?></td>
                </tr>
              
                <?php 
                  }
            } ?>
    </table>
    <?php

    if ($GLAREODVA||$CONTRASTODVA||$ODK1||$ODK2||$LIODVA||$PAMODBA) { ?>
      <table>
        <tr>
          <td id="LayerVision_ADDITIONAL" class="refraction <?php echo $display_Add; ?>" style="padding:10px;font-size:0.9em;">
          <table id="Additional" style="padding:5;font-size:1.0em;">
            <tr><td colspan="9" style="text-align:left;text-decoration:underline;font-weight:bold;"><?php echo xlt('Additional Data Points'); ?></td></tr>
            <tr><td></td>
              <td><?php echo xlt('PH{{Pinhole}}'); ?></td>
              <td><?php echo xlt('PAM{{Potential Acuity Meter}}'); ?></td>
              <td><?php echo xlt('LI{{Laser Interferometry}}'); ?></td>
              <td><?php echo xlt('BAT{{Brightness Acuity Testing}}'); ?></td>
              <td><?php echo xlt('K1{{Keratometry 1}}'); ?></td>
              <td><?php echo xlt('K2{{Keratometry 2}}'); ?></td>
              <td><?php echo xlt('Axis'); ?></td>
            </tr>
            <tr><td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
              <td><?php echo attr($PHODVA); ?></td>
              <td><?php echo attr($PAMODVA); ?></td>
              <td><?php echo attr($LIODVA); ?></td>
              <td><?php echo attr($GLAREODVA); ?></td>
              <td><?php echo attr($ODK1); ?></td>
              <td><?php echo attr($ODK2); ?></td>
              <td><?php echo attr($ODK2AXIS); ?></td>
            </tr>
            <tr>
              <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
              <td><?php echo attr($PHOSVA); ?></td>
              <td><?php echo attr($PAMOSVA); ?></td>
              <td><?php echo attr($LIOSVA); ?></td>
              <td><?php echo attr($GLAREOSVA); ?></td>
              <td><?php echo attr($OSK1); ?></td>
              <td><?php echo attr($OSK2); ?></td>
              <td><?php echo attr($OSK2AXIS); ?></td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
              <td></td>
              <td><?php echo xlt('AxLength{{axial Length}}'); ?></td>
              <td><?php echo xlt('ACD{{anterior chamber depth}}'); ?></td>
              <td><?php echo xlt('PD{{pupillary distance}}'); ?></td>
              <td><?php echo xlt('LT{{lens thickness}}'); ?></td>
              <td><?php echo xlt('W2W{{white-to-white}}'); ?></td>
              <td><?php echo xlt('ECL{{equivalent contact lens power at the corneal level}}'); ?></td>
              <!-- <td><?php echo xlt('pend'); ?></td> -->
            </tr>
            <tr><td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
              <td><?php echo attr($ODAXIALLENGTH); ?></td>
              <td><?php echo attr($ODACD); ?></td>
              <td><?php echo attr($ODPDMeasured); ?></td>
              <td><?php echo attr($ODLT); ?></td>
              <td><?php echo attr($ODW2W); ?></td>
              <td><?php echo attr($ODECL); ?></td>
              <!-- <td><input type=text id="pend" name="pend"  value="<?php echo attr($pend); ?>"></td> -->
            </tr>
            <tr>
              <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
              <td><?php echo attr($OSAXIALLENGTH); ?></td>
              <td><?php echo attr($OSACD); ?></td>
              <td><?php echo attr($OSPDMeasured); ?></td>
              <td><?php echo attr($OSLT); ?></td>
              <td><?php echo attr($OSW2W); ?></td>
              <td><?php echo attr($OSECL); ?></td>
              <!--  <td><input type=text id="pend" name="pend" value="<?php echo attr($pend); ?>"></td> -->
            </tr>
          </table>
          </td>
        </tr> 
      </table>
      <?php 
    } ?>
        
    <!-- end of the refraction boxes --> 

    <!-- start of external exam -->
    <div class="report_exam_group">
      <table class="borderShadow">
        <tr>
          <td style="text-align:left;vertical-align:top;"> 
            <b class="underline"><?php echo xlt('External Exam'); ?>:</b>
            <table class="report_section">
              <tr>
                <td class="bold" style="text-align:right;padding-right:10px;text-decoration:underline;max-width:200px;"><?php echo xlt('Right'); ?></td>
                <td style="width:100px;"></td>
                <td class="bold" style="text-align:left;padding-left:10px;text-decoration:underline;max-width:200px;"><?php echo xlt('Left'); ?></td>
              </tr>
              <tr>
                <td class="report_text right"><?php echo text($RBROW); ?></td>
                <td class="middle"><?php echo xlt('Brow'); ?></td>
                <td class="report_text left"><?php echo text($LBROW); ?></td>
              </tr> 
              <tr>
                <td class="report_text right ">
                  <?php echo text($RUL); ?>
                </td>
                <td class="middle"><?php echo xlt('Upper Lids'); ?></td>
                <td class="report_text left"><?php echo text($LUL); ?></td>
              </tr> 
              <tr>
                <td class="report_text right "><?php echo text($RLL); ?></td>
                <td class="middle"><?php echo xlt('Lower Lids'); ?></td>
                <td class="report_text left"><?php echo text($LLL); ?></td>
              </tr>
              <tr>
                <td class="report_text right "><?php echo text($RMCT); ?></td>
                <td class="middle"><?php echo xlt('Medial Canthi'); ?></td>
                <td class="report_text left"><?php echo text($LMCT); ?></td>
              </tr>
              <?php 
              if ($RADNEXA || $LADNEXA) { 
                ?> 
                <tr>
                  <td class="report_text right"><?php echo text($RADNEXA); ?></td>
                  <td class="middle"><?php echo xlt('Adnexa'); ?></td>
                  <td class="report_text left"><?php echo text($LADNEXA); ?></td>
                </tr>
                <?php  
              } 
              if ($EXT_COMMENTS) { ?>
              <tr>
                <td colspan="3">

                  <b><?php echo xlt('Comments'); ?>:</b>
                  <span style="height:3.0em;">
                    <?php echo text($EXT_COMMENTS); ?>
                  </span>
                </td>
              </tr>
              <?php } ?>
            </table>                
          </td>
          <td style="text-align:center;padding:1px;vertical-align:middle;"> 
              <?php 
              display_draw_image ("EXT",$encounter,$pid); 
              ?>           
          </td>
        </tr>
      </table>
      <!-- end of external exam -->
      <!-- start of Anterior Segment exam -->
      <table class="borderShadow">
        <tr> 
          <td style="text-align:left;padding:1px;vertical-align:top;">
            <b class="underline"><?php echo xlt('Anterior Segment'); ?>:</b>
            <table class="report_section">
                <tr>
                  <td class="bold" style="text-align:right;padding-right:10px;text-decoration:underline;max-width:200px;"><?php echo xlt('Right'); ?></td>
                  <td style="width:100px;"></td>
                  <td class="bold" style="text-align:left;padding-left:10px;text-decoration:underline;max-width:200px;"><?php echo xlt('Left'); ?></td>
                </tr>
                <tr>
                  <td class="report_text right"><?php echo text($ODCONJ); ?></td>
                  <td class="middle"><?php echo xlt('Conj'); ?></td>
                  <td class="report_text left"><?php echo text($OSCONJ); ?></td>
                </tr> 
                <tr>
                  <td class="report_text right "><?php echo text($ODCORNEA); ?></td>
                  <td  class="middle"><?php echo xlt('Cornea'); ?></td>
                  <td class="report_text left"><?php echo text($OSCORNEA); ?></td>
                </tr> 
                <tr>
                  <td class="report_text right "><?php echo text($ODAC); ?></td>
                  <td class="middle"><?php echo xlt('A/C'); ?></td>
                  <td class="report_text left"><?php echo text($OSAC); ?></td>
                </tr>
                <tr>
                  <td class="report_text right "><?php echo text($ODLENS); ?></td>
                  <td class="middle"><?php echo xlt('Lens'); ?></td>
                  <td class="report_text left"><?php echo text($OSLENS); ?></td>
                </tr>
                <tr>
                  <td class="report_text right"><?php echo text($ODIRIS); ?></td>
                  <td class="middle"><?php echo xlt('Iris'); ?></td>
                  <td class="report_text left"><?php echo text($OSIRIS); ?></td>
                </tr>
                <?php if ($ODGONIO||$OSDGONIO) { ?>
                <tr>
                  <td class="report_text right" style="width:100px;"><?php echo text($ODGONIO); ?></td>
                  <td class="middle"><?php echo xlt('Gonioscopy'); ?></td>
                  <td class="report_text left" style="width:100px;"><?php echo text($OSGONIO); ?></td>
                </tr> 
                <?php } if ($ODKTHICKNESS||$OSKTHICKNESS) { ?>
                <tr>
                  <td class="report_text right"><?php echo text($ODKTHICKNESS); ?></td>
                  <td class="middle" title="<?php echo xla('Pachymetry'); ?>"><?php echo xlt('Pachymetry'); ?></td>
                  <td  class="report_text left"><?php echo text($OSKTHICKNESS); ?></td>
                </tr>
                <?php } if ($ODSCHIRMER1||$OSSCHIRMER1) { ?>
                <tr>
                  <td class="report_text right"><?php echo text($ODSCHIRMER1); ?></td>
                  <td class="middle" title="<?php echo xla('Schirmers I (w/o anesthesia)'); ?>"><?php echo xlt('Schirmers I'); ?></td>
                  <td class="report_text left"><?php echo text($OSSCHIRMER1); ?></td>
                </tr>
                <?php } if ($ODSCHIRMER2||$OSSCHIRMER2) { ?>
                <tr>
                  <td class="report_text right"><?php echo text($ODSCHIRMER2); ?></td>
                  <td class="middle" title="<?php echo xla('Schirmers II (w/ anesthesia)'); ?>"><?php echo xlt('Schirmers II'); ?></td>
                  <td class="report_text left"><?php echo text($OSSCHIRMER2); ?></td>
                </tr>
                <?php } if ($ODTBUT||$OSTBUT) { ?>
                <tr>
                  <td class="report_text right"><?php echo text($ODTBUT); ?></td>
                  <td class="middle" title="<?php echo xla('Tear Break Up Time'); ?>"><?php echo xlt('TBUT'); ?></td>
                  <td class="report_text left"><?php echo text($OSTBUT); ?></td>
                </tr>
                <?php } 
                if ($ANTSEG_COMMENTS) { ?>
                <tr>
                  <td colspan="2">
                    <b><?php echo xlt('Comments'); ?>:</b>
                    <span style="height:3.0em;">
                      <?php echo text($ANTSEG_COMMENTS); ?>
                    </span>
                  </td>
                </tr>
                <?php } ?>
            </table>
          </td>
          <td style="text-align:center;padding:1px;vertical-align:middle;">
              <?php 
              display_draw_image ("ANTSEG",$encounter,$pid); 
              ?>   
          </td>
        </tr>
      </table>
      <!-- end of Anterior Segment exam -->
      <!-- start of Other exam -->
      <?php
      if ($RLF || $LLF || $RMRD || $LMRD || $RVFISSURE || $LVFISSURE || 
        $RCAROTID || $LCAROTID || $RTEMPART || $LTEMPART || $RCNV || $LCNV || 
        $RCNVII || $LCNVII || $HERTELBASE || $ODCOLOR || $OSCOLOR || $ODREDDESAT || 
        $OSREDDESAT ||$ODCOINS || $OSCOINS || $ODNPA || $OSNPA || $NPA || $NPC || $STEREOPSIS ||
        $DACCDIST || $DACCNEAR || $CACCDIST || $CACCNEAR || $VERTFUSAMPS) {
        ?>
        <table class="borderShadow">
        <tr>
          <td style="text-align:left;vertical-align:top;padding:1px;">
            <b><u><?php echo xlt('Additional Findings'); ?>:</u></b>  
            <?php if ($ACT =='on' and $MOTILITYNORMAL == 'on') { ?>
              <span id="ACTNORMAL_CHECK" name="ACTNORMAL_CHECK">
              <?php echo xlt('Orthophoric'); ?>
              </span>
            <?php } ?>
            <table class="report_section">
              <tr>
                <td class="bold" style="text-align:right;padding-right:10px;text-decoration:underline;max-width:200px;"><?php echo xlt('Right'); ?></td>
                <td style="width:100px;"></td>
                <td class="bold" style="text-align:left;padding-left:10px;text-decoration:underline;max-width:200px;"><?php echo xlt('Left'); ?></td>
              </tr>
                <?php 
                if ($RLF || $LLF) { ?> 
                  <tr>
                    <td class="report_text right" style=""><?php echo text($RLF); ?></td>
                    <td class="middle" style="width:100px;"><?php echo xlt('Levator Function'); ?></td>
                    <td class="report_text left" style=""><?php echo text($LLF); ?></td>
                  </tr> 
                  <?php  
                } 
                if ($RMRD || $LMRD) { ?> 
                  <tr>
                    <td class="report_text right"><?php echo text($RMRD); ?></td>
                    <td class="middle" title="<?php echo xla('Marginal Reflex Distance'); ?>"><?php echo xlt('MRD{{marginal reflex distance}}'); ?></td>
                    <td  class="report_text left"><?php echo text($LMRD); ?></td>
                  </tr>
                  <?php  
                } 
                if ($RVFISSURE || $LVFISSURE) { ?> 
                  <tr>
                    <td class="report_text right"><?php echo attr($RVFISSURE); ?></td>
                    <td class="middle" title="<?php echo xla('Vertical Fissure: central height between lid margins'); ?>"><?php echo xlt('Vert Fissure{{vertical fissure}}'); ?></td>
                    <td class="report_text left"><?php echo text($LVFISSURE); ?></td>
                  </tr>
                  <?php  
                } 
                if ($RCAROTID || $LCAROTID) { ?> 
                  <tr>
                    <td class="report_text right"><?php echo text($RCAROTID); ?></td>
                    <td class="middle" title="<?php echo xla('Any carotid bruits appreciated?'); ?>"><?php echo xlt('Carotid{{carotid arteries}}'); ?></td>
                    <td class="report_text left"><?php echo text($LCAROTID); ?></td>
                  </tr>
                  <?php  
                } 
                if ($RTEMPART || $LTEMPART) { ?> 
                  <tr>
                    <td class="report_text right"><?php echo text($RTEMPART); ?></td>
                    <td class="middle" title="<?php echo xla('Temporal Arteries'); ?>"><?php echo xlt('Temp. Art.{{temporal arteries}}'); ?></td>
                    <td class="report_text left"><?php echo text($LTEMPART); ?></td>
                  </tr>
                  <?php  
                } 
                if ($RCNV || $LCNV) { ?> 
                  <tr>
                  <td class="report_text right"><?php echo text($RCNV); ?></td>
                  <td class="middle" title="<?php echo xla('Cranial Nerve 5: Trigeminal Nerve'); ?>"><?php echo xlt('CN V{{cranial nerve five}}'); ?></td>
                  <td class="report_text left"><?php echo text($LCNV); ?></td>
                  </tr>
                  <?php  
                } 
                if ($RCNVII || $LCNVII) { ?> 
                  <tr>
                  <td class="report_text right"><?php echo text($RCNVII); ?></td>
                  <td class="middle" title="<?php echo xla('Cranial Nerve 7: Facial Nerve'); ?>"><?php echo xlt('CN VII{{cranial nerve seven}}'); ?></td>
                  <td class="report_text left"><?php echo text($LCNVII); ?></td>
                  </tr>
                  <?php  
                } 
                if ($HERTELBASE) { ?> 
                  <tr>
                  <td colspan="3" style="text-align:center;padding-top:15px;">
                    <b style="font-weight:bold;padding-bottom:5px;">
                      <?php echo xlt('Hertel Exophthalmometry'); ?>
                    </b>
                    <br />
                    <?php 
                    if ($HERTELBASE) { ?>

                    <b style="border:1pt solid black;width:30px;text-align:center;padding:0 5;">
                      <?php echo text($ODHERTEL); ?>
                    </b>
                    <b class="fa fa-minus">--</b> 
                    <b style="border:1pt solid black;width:40px;text-align:center;padding:0 5;">
                      <?php echo text($HERTELBASE); ?>
                    </b>
                    <b class="fa fa-minus">--</b> 
                    <b style="border:1pt solid black;width:30px;text-align:center;padding:0 5;">
                      <?php echo text($OSHERTEL); ?>
                    </b>
                    <?php 
                  } ?>
                  </td>
                  </tr>
                  <?php  
                }  

                if ($ODCOLOR || $OSCOLOR || $ODREDDESAT || 
                    $OSREDDESAT ||$ODCOINS || $OSCOINS || 
                    $ODNPA || $OSNPA || $NPA || $NPC || $STEREOPSIS) { ?>
                      <!-- start of NEURO exam -->
                      <tr>
                  <td class="report_text right"><?php echo text($ODCOLOR); ?></td>
                  <td class="middle"><?php echo xlt('Color Vision'); ?></td>
                  <td class="report_text left"><?php echo text($OSCOLOR); ?></td>
                      </tr>
                    <?php if ($ODREDDESAT or $OSREDDESAT) { ?> 
                    <tr>
                      <td class="report_text right"><?php echo text($ODREDDESAT); ?></td>
                      <td class="middle"><span title="<?php xla('Variation in red color discrimination between the eyes (eg. OD=100, OS=75)'); ?>"><?php echo xlt('Red Desaturation'); ?></span></td>
                      <td class="report_text left"><?php echo text($OSREDDESAT); ?></td>
                    </tr><?php 
                } 
                if ($ODCOINS or $OSCOINS) { ?> 
                  <tr>
                    <td class="report_text right"><?php echo text($ODCOINS); ?></td>
                    <td class="middle"><span title="<?php echo xla('Variation in white (muscle) light brightness discrimination between the eyes (eg. OD=$1.00, OS=$0.75)'); ?>"><?php echo xlt('Coins'); ?></span></td>
                    <td class="report_text left"><?php echo text($OSCOINS); ?></td>
                  </tr>  
                  <?php 
                } 
                if ($ODNPA or $OSNPA) { ?> 
                  <tr>
                  <td class="report_text right"><?php echo text($ODNPA); ?></td>
                  <td class="middle"><span title="<?php echo xla('Near Point of Accomodation'); ?>"><?php echo xlt('NPA{{near point of accomodation}}'); ?></span></td>
                  <td class="report_text left"><?php echo text($OSNPA); ?></td>
                  </tr>            
                <?php } 
                if ($ODNPC or $OSNPC) { ?> 
                  <tr>
                              <td class="right" style="font-weight:600;"><?php echo xlt('NPC{{near point of convergence}}'); ?>:&nbsp;</td>
                              <td class="center" colspan="2" ><?php echo attr($NPC); ?></td>
                  </tr>
                <?php } 
                if ($DACCDIST or $DACCNEAR or $CACCDIST or $CACCNEAR or $VERTFUSAMPS) { ?> 
                  <tr style="text-decoration:underline;">
                    <td></td>
                    <td  class="middle"><?php echo xlt('Distance'); ?> </td>
                    <td class="middle"> <?php echo xlt('Near'); ?></td>
                  </tr>
                <?php } 
                if ($DACCDIST or $DACCNEAR) { ?> 
                  <tr>
                    <td class="right" style="font-weight:600;"><?php echo xlt('Divergence Amps'); ?>: </td>
                    <td class="center"><?php echo attr($DACCDIST); ?></td>
                    <td class="center"><?php echo attr($DACCNEAR); ?></td>
                  </tr>
                <?php } 
                 if ($CACCDIST or $CACCNEAR) { ?> 
                            <tr>
                              <td class="right" style="font-weight:600;"><?php echo xlt('Convergence Amps'); ?>: </td>
                              <td class="center"><?php echo attr($CACCDIST); ?></td>
                              <td class="center"><?php echo attr($CACCNEAR); ?></td>
                            </tr>
                <?php } 
                 if ($VERTFUSAMPS) { ?> 
                            <tr>
                                <td class="right" style="font-weight:600;">
                                  <?php echo xlt('Vert Fusional Amps'); ?>:
                                </td>
                                <td colspan="2" class="center">
                                  <?php echo attr($VERTFUSAMPS); ?>
                                  <br />
                                </td>
                            </tr>
                <?php } 
                 if ($STEREOPSIS) { ?> 
                            <tr>
                              <td class="right" style="font-weight:600;"><?php echo xlt('Stereopsis'); ?>:&nbsp;</td>
                              <td  class="center" colspan="2"><?php echo attr($STEREOPSIS); ?></td>
                            </tr>
                <?php } 
              }  ?> 
            </table> 
          </td>
          <td style="text-align:center;padding:1px;vertical-align:middle;"> 
            <?php 
            display_draw_image ("NEURO",$encounter,$pid); 
            ?>           
          </td>
        </tr>
        </table>
        <?php  
      }  ?>
      <!-- end of Other exam -->    
      <!-- start of the Retina exam -->
      <?php 
      if ($ODDISC||$OSDISC||$ODCUP||$ODMACULA||$ODVESSELS||$ODPERIPH) {
        ?>
        <table class="borderShadow">
          <tr>
            <td  style="text-align:left;padding:1px;vertical-align:top;">        
                  <b><u><?php echo xlt('Retina'); ?>:</u></b>
                  <table class="report_section">
                    <tr>
                      <td class="bold" style="text-align:right;text-decoration:underline;max-width:200px;"><?php echo xlt('Right'); ?></td>
                      <td style="width:100px;"></td>
                      <td class="bold" style="text-align:left;text-decoration:underline;max-width:200px;"><?php echo xlt('Left'); ?></td>
                    </tr>
                    <tr>
                      <td class="report_text right"><?php echo text($ODDISC); ?></td>
                      <td class="middle"><?php echo xlt('Disc'); ?></td>
                      <td class="report_text left"><?php echo text($OSDISC); ?></td>
                    </tr> 
                    <tr>
                      <td class="report_text right"><?php echo text($ODCUP); ?></td>
                      <td class="middle"><?php echo xlt('Cup'); ?></td>
                      <td class="report_text left"><?php echo text($OSCUP); ?></td>
                    </tr> 
                    <tr>
                      <td class="report_text right"><?php echo text($ODMACULA); ?></td>
                      <td class="middle"><?php echo xlt('Macula'); ?></td>
                      <td class="report_text left"><?php echo text($OSMACULA); ?></td>
                    </tr>
                    <tr>
                      <td class="report_text right"><?php echo text($ODVESSELS); ?></td>
                      <td class="middle"><?php echo xlt('Vessels'); ?></td>
                      <td class="report_text left"><?php echo text($OSVESSELS); ?></td>
                    </tr>
                    <?php  if ($ODPERIPH||$OSPERIPH) { ?>
                    <tr>
                      <td class="report_text right"><?php echo text($ODPERIPH); ?></td>
                      <td class="middle"><?php echo xlt('Periph{{periphery}}'); ?></td>
                      <td class="report_text left"><?php echo text($OSPERIPH); ?></td>
                    </tr>
                    <?php } if ($ODCMT||$OSCMT) { ?>
                    <tr>
                      <td class="report_text right">&nbsp;<?php echo text($ODCMT); ?></td>
                      <td class="middle"><?php echo xlt('Central Macular Thickness'); ?> </td>
                      <td class="report_text left" >&nbsp;<?php echo text($OSCMT); ?></td>
                    </tr>
                    <?php } ?>  
                    <?php if ($RETINA_COMMENTS) { ?>
                    <tr>
                      <td colspan="2" class="report_text left">
                        
                        <b><?php echo xlt('Comments'); ?>:</b>
                        <span style="height:3.0em;">
                          <?php echo text($RETINA_COMMENTS); ?>
                        </span>
                        
                      </td>
                    </tr> 
                    <?php } ?>
                  </table>
            </td>
            <td style="text-align:center;padding:1px;vertical-align:middle;"> 
                <?php 
                display_draw_image ("RETINA",$encounter,$pid); 
                ?>           
            </td>
          </tr>
        </table>
      
              <?php 
      } ?>
      <!-- end of Retina exam -->
    </div>
    <?php 
    if ($ACT !='on') { ?>
                <table style="text-align:center;font-size:0.7em;">
                  <tr>    
                    <td colspan=3 style="">
                          <table> 
                            <tr style="text-align:left;height:16px;vertical-align:middle;width:880px;">
                              <td>
                                <span id="ACTTRIGGER" name="ACTTRIGGER" style="text-decoration:underline;padding-left:2px;">
                                  <?php echo xlt('Alternate Cover Test'); ?>:
                                </span>
                              </td>
                            </tr>
                            <tr>
                              <?php 
                              if ($ACT5SCDIST) { ?>
                                <td style="text-align:center;"> <!-- scDIST -->
                                  <table cellpadding="0" 
                                  style="position:relative;text-align:center;font-size:1.2em;margin: 7 5 10 5;">
                                    <tr>
                                      <td id="ACT_tab_SCDIST" name="ACT_tab_SCDIST" class="ACT_deselected"> <?php echo xlt('sc Distance{{without correction distance}}'); ?> </td>
                                    </tr>
                                    <tr>
                                      <td colspan="4" style="text-align:center;font-size:0.9em;">
                                          <table> 
                                            <tr> 
                                              <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>   
                                              <td class="ACT"><?php echo report_ACT($ACT1SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT2SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT3SCDIST); ?></td>
                                              <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td> 
                                            </tr>
                                            <tr>    
                                              <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                              <td class="ACT"><?php echo report_ACT($ACT4SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT5SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT6SCDIST); ?></td>
                                              <td><i class="fa fa-share rotate-right"></i></td> 
                                            </tr> 
                                            <tr> 
                                              <td class="ACT"><?php echo report_ACT($ACT10SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT7SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT8SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT9SCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT11SCDIST); ?></td>
                                            </tr>
                                          </table>
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                                <?php 
                              } 
                              if ($ACT5CCDIST) { 
                                ?>
                                <td style="text-align:center;"> <!-- ccDIST -->
                                  <table cellpadding="0" style="position:relative;text-align:center;font-size:1.5em;margin: 7 5 10 5;border-collapse: separate;">
                                    <tr>
                                      <td class="ACT_deselected"> <?php echo xlt('cc Distance{{with correction at distance}}'); ?> </td>
                                    </tr>
                                    <tr>
                                      <td colspan="4" style="text-align:center;font-size:0.8em;">
                                          <table> 
                                            <tr> 
                                              <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>   
                                              <td class="ACT"><?php echo report_ACT($ACT1CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT2CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT3CCDIST); ?></td>
                                              <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td> 
                                            </tr>
                                            <tr>    
                                              <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                              <td class="ACT"><?php echo report_ACT($ACT4CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT5CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT6CCDIST); ?></td>
                                              <td><i class="fa fa-share rotate-right"></i></td> 
                                            </tr> 
                                            <tr> 
                                              <td class="ACT"><?php echo report_ACT($ACT10CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT7CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT8CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT9CCDIST); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT11CCDIST); ?>
                                              </td>
                                            </tr>
                                          </table>
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                            </tr>
                            <tr>
                                <?php 
                              } 
                              if ($ACT5SCNEAR) { 
                                ?>
                             
                              <td style="text-align:center;"> <!-- scNEAR -->
                                  <table cellpadding="0" style="position:relative;text-align:center;font-size:1.5em;margin: 7 5 10 5;border-collapse: separate;">
                                    <tr>
                                      <td class="ACT_deselected"> <?php echo xlt('sc Near{{without correction near'); ?> </td>
                                    </tr>
                                    <tr>
                                      <td colspan="4" style="text-align:center;font-size:0.8em;">
                                          <table> 
                                            <tr> 
                                              <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>    
                                              <td class="ACT"><?php echo report_ACT($ACT1SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT2SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT3SCNEAR); ?></td>
                                              <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td> 
                                            </tr>
                                            <tr>    
                                              <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                              <td class="ACT"><?php echo report_ACT($ACT4SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT5SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT6SCNEAR); ?></td>
                                              <td><i class="fa fa-share rotate-right"></i></td> 
                                            </tr> 
                                            <tr> 
                                              <td class="ACT"><?php echo report_ACT($ACT10SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT7SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT8SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT9SCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT11SCNEAR); ?>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                    </tr>
                                  </table>
                              </td>
                                <?php 
                              } 
                              if ($ACT5CCNEAR) { 
                                ?>
                             
                              <td style="text-align:center;"> <!-- ccNEAR -->
                                  <table cellpadding="0" style="position:relative;text-align:center;font-size:1.5em;margin: 7 5 10 5;border-collapse: separate;">
                                    <tr>
                                      <td class="ACT_deselected"> <?php echo xlt('cc Near{{with correction at Near}}'); ?> </td>
                                    </tr>
                                    <tr>
                                      <td colspan="4" style="text-align:center;font-size:1.0em;">
                                          <table> 
                                            <tr> 
                                              <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>    
                                              <td class="ACT"><?php echo report_ACT($ACT1CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT2CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT3CCNEAR); ?></td>
                                              <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td>
                                            </tr>
                                            <tr>    
                                              <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                              <td class="ACT"><?php echo report_ACT($ACT4CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT5CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT6CCNEAR); ?></td>
                                              <td><i class="fa fa-share rotate-right"></i></td> 
                                            </tr> 
                                            <tr> 
                                              <td class="ACT"><?php echo report_ACT($ACT10CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT7CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT8CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT9CCNEAR); ?></td>
                                              <td class="ACT"><?php echo report_ACT($ACT11CCNEAR); ?></td>
                                            </tr>
                                          </table>
                                      </td>
                                    </tr>
                                  </table>
                                
                              </td>
                              <?php } ?>
                            </tr>
                          </table>
                      <?php if ($NEURO_COMMENTS) { ?>
                      <table>
                        <tr>
                          <td colspan="2">
                            <b><?php echo xlt('Comments'); ?>:</b><br />
                            <span style="height:3.0em;">
                              <?php echo report_ACT($NEURO_COMMENTS); ?>
                            </span>
                            <br /><br />
                          </td>
                        </tr>
                      </table>
                      <?php } ?>
                    </td>
                  </tr>
                </table>
                <?php 
    } ?>
    <p></p>
    <!-- start of IMPPLAN exam -->
    <table>
      <tr>
        <td style="text-align:left;padding:1px;vertical-align:top;width:400px;">
          <b><u><?php echo xlt('Impression/Plan'); ?>:</u></b>
          <table style="">
            <tr>
              <td style="padding:5px;text-align: left;width:400px;text-align:justify;">
                <?php        
                /*  
                 *  Retrieve and Display the IMPPLAN_items for the Impression/Plan zone.
                 */
                  $query = "select * from form_".$form_folder."_impplan where form_id=? and pid=? order by IMPPLAN_order ASC";
                  $result =  sqlStatement($query,array($form_id,$pid));
                  $i='0';
                  $order   = array("\r\n", "\n", "\r","\v","\f","\x85","\u2028","\u2029");
                  $replace = "<br />";
                 // echo '<ol>';
                  while ($ip_list = sqlFetchArray($result))   {
                    $newdata =  array (
                      'form_id'       => $ip_list['form_id'],
                      'pid'           => $ip_list['pid'],
                      'title'         => $ip_list['title'],
                      'code'          => $ip_list['code'],
                      'codetype'      => $ip_list['codetype'],
                      'codetext'      => $ip_list['codetext'],
                      'plan'          => str_replace($order, $replace, $ip_list['plan']),
                      'IMPPLAN_order' => $ip_list['IMPPLAN_order']
                      );
                    $IMPPLAN_items[$i] =$newdata;
                    $i++;
                  }
                  //for ($i=0; $i < count($IMPPLAN_item); $i++) {
                  foreach ($IMPPLAN_items as $item) {
                    echo ($item['IMPPLAN_order'] +1).'. <b>'.text($item['title']).'</b><br />';
                    echo  '<div style="padding-left:15px;">';
                    $pattern = '/Code/';
                    if (preg_match($pattern,$item['code']))  $item['code'] = '';
                    if ($item['codetext'] > '')  {
                      echo $item['codetext']."<br />";
                    } else {
                      if ($item['code'] > '') {
                         if ($item['codetype'] > '') {
                          $item['code'] =  $item['codetype'].": ".$item['code'];
                        }
                        echo $item['code']."<br />";
                      }
                    }
                    echo  $item['plan']."</div><br />";
                  }
                  ?>
              </td>
            </tr>
          </table>
        </td>
        <td style="text-align:left;vertical-align:bottom;padding:5 5 3 5;">
          <?php 
            display_draw_image ("IMPPLAN",$encounter,$pid); 
          ?>
          <br />
          <?php 
          if ($PLAN && $PLAN != '0') { ?>
            <b>Orders/Next Visit:</b>
            <br />
            <div style="padding-left:15px;padding-bottom:10px;">
              <?php 
              $PLAN_items = explode('|',$PLAN);
              foreach ($PLAN_items as $item) {
                echo  $item."<br />";
              }  
              if ($PLAN2) {
                echo $PLAN2."<br />";
              }
              ?>
            </div>
            <?php 
          } ?>

          <div style="width:220px;position:relative;left:0.in;padding-left:5 2;">
            <?php 
            if ($PDF_OUTPUT) {
              //display a stored optional electronic sig for this providerID, ie the patient's Doc not the tech
                  $from_file = $GLOBALS["webserver_root"] ."/interface/forms/".$form_folder."/images/sign_".$providerID.".jpg";
                  if (file_exists($from_file)) {
                    echo "<img style='width:50mm; bottom:1px;' src='$from_file'><br />
                    <i style='font-size:9px;'>".xlt('electronically signed on')." ".oeFormatShortDate()."</i>";
                  }
                  ?><br />
              <span style="border-top:1pt solid black;padding-left:50px;"><?php echo text($providerNAME); ?></span>
              <?php
            } else {
              $signature = $GLOBALS["webserver_root"]."/interface/forms/".$form_folder."/images/sign_".$providerID.".jpg";
              if (file_exists($signature)) {
                  echo "<img src='".$GLOBALS['web_root']."/interface/forms/".$form_folder."/images/sign_".$providerID.".jpg'  style='width:30mm; height:6mm;bottom:1px;' height='10' />"; 
              }
            } 
            ?> 
          </div>
        </td>
      </tr>  
    </table>
  </body>
  <?php
  return; 
}
function display_draw_image($zone,$encounter,$pid){
  global $form_folder;
  global $web_root;
  global $PDF_OUTPUT;
  $side = "OU";
  $base_name = $pid."_".$encounter."_".$side."_".$zone."_VIEW";
  $filename = $base_name.".jpg";
  $sql = "SELECT * from documents where documents.url like '%".$filename."'";
  $doc = sqlQuery($sql);
  $document_id =$doc['id'];

  if ($doc['id'] > '1') {
    if (!is_numeric($document_id)) continue;
    $d = new Document($document_id);
    $fname = basename($d->get_url());

    $couch_docid = $d->get_couch_docid();
    $couch_revid = $d->get_couch_revid();
    $extension = substr($fname, strrpos($fname,"."));
    $notes = Note::notes_factory($d->get_id());
    if (!empty($notes)) echo "<table>";
    foreach ($notes as $note) {
      echo '<tr>';
      echo '<td>' . xlt('Note') . ' #' . $note->get_id() . '</td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td>' . xlt('Date') . ': ' . oeFormatShortDate($note->get_date()) . '</td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td>'.$note->get_note().'<br /><br /></td>';
      echo '</tr>';
    }
    if (!empty($notes)) echo "</table>";

    $url_file = $d->get_url_filepath();
    if($couch_docid && $couch_revid){
      $url_file = $d->get_couch_url($pid,$encounter);
    }
    // Collect filename and path
    $from_all = explode("/",$url_file);
    $from_filename = array_pop($from_all);
    $from_pathname_array = array();
    for ($i=0;$i<$d->get_path_depth();$i++) {
      $from_pathname_array[] = array_pop($from_all);
    }
    $from_pathname_array = array_reverse($from_pathname_array);
    $from_pathname = implode("/",$from_pathname_array);

    if($couch_docid && $couch_revid) {
      $from_file = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $from_filename;
      $to_file = substr($from_file, 0, strrpos($from_file, '.')) . '_converted.jpg';
    }
    else {
      $from_file = $GLOBALS["fileroot"] . "/sites/" . $_SESSION['site_id'] .
      '/documents/' . $from_pathname . '/' . $from_filename;
      $to_file = substr($from_file, 0, strrpos($from_file, '.')) . '_converted.jpg';
    }

    //               if ($extension == ".png" || $extension == ".jpg" || $extension == ".jpeg" || $extension == ".gif") {
    if ($PDF_OUTPUT) {
        echo "<img src='". $from_file."' style='width:220px;height:120px;'>";
    } else {
      $filetoshow = "/openemr/controller.php?document&retrieve&patient_id=$pid&document_id=".$doc['id']."&as_file=false&blahblah=".rand();
      echo "<img src='".$filetoshow."' style='width:220px;height:120px;'>";
    }
  }  
  //else show base_image
   else {
   
    $filetoshow = "../../forms/".$form_folder."/images/".$side."_".$zone."_BASE.jpg"; 
    if ($PDF_OUTPUT) $filetoshow = $GLOBALS["webserver_root"] ."/interface/forms/".$form_folder."/images/".$side."_".$zone."_BASE.jpg"; 
    // uncomment to show base image, no touch up by user.
    // echo "<img src='". $filetoshow."' style='width:220px;height:120px;'>";
  }
   
 return;

}

/**
 *  This is the original base report function.  Keep it for others to use
 *  when testing output for their forms.  We are not using it.
 */
function full_report( $pid, $encounter, $cols='2', $id) {

  return;

  /** CHANGE THIS - name of the database table we are reporting on **/
  $table_name = "form_eye_mag";

  $count = 0;
  $data = formFetch($table_name, $id);

  if ($data) {

    print "<table><tr>";

    foreach($data as $key => $value) {
      if ($key == "id" || $key == "pid" || $key == "user" || 
        $key == "groupname" || $key == "authorized" || 
        $key == "activity" || $key == "date" || 
        $value == "" || $value == "0000-00-00 00:00:00" || 
        $value == "n") 
      {
      // skip certain fields and blank data
        continue;
      }

      $key=ucwords(str_replace("_"," ",$key));
      print("<tr>\n");  
      print("<tr>\n");  
      print "<td><span class=bold>".xlt($key)." </span><span class=text>".text($value)."</span></td>";
      $count++;
      if ($count == $cols) {
        $count = 0;
        print "</tr><tr>\n";
      }
    }
  }
  print "</tr></table>";
}

function report_ACT($term) {
  $term = nl2br(htmlspecialchars($term,ENT_NOQUOTES));
  return $term."&nbsp;";
}
?> 
