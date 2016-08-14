<?php
/** 
 * forms/eye_mag/view.php 
 * 
 * Central view for the eye_mag form.  Here is where all new data is entered
 * New forms are created via new.php and then this script is displayed.
 * Edit requests come here too...
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
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

require_once("../../globals.php");
include_once("$srcdir/acl.inc");
include_once("$srcdir/lists.inc");
include_once("$srcdir/api.inc");
//include_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/forms.inc");
require_once("$srcdir/patient.inc");

$form_name = "eye_mag";
$form_folder = "eye_mag";
$Form_Name = "Eye Exam"; 

include_once("../../forms/".$form_folder."/php/".$form_folder."_functions.php");
//check to see if this is the first time this is run
//if so, create the categories and lists we need to run the eye_form
$form_id    = $_REQUEST['id']; 
$action     = $_REQUEST['action'];
$finalize   = $_REQUEST['finalize'];
$id         = $_REQUEST['id'];
$display    = $_REQUEST['display'];
$pid        = $_REQUEST['pid'];
if ($pid =='') $pid = $_SESSION['pid'];
$refresh    = $_REQUEST['refresh'];
if ($_REQUEST['url']) {
  redirector($_REQUEST['url']);
  exit;
}
// Get user preferences, for this user
$query  = "SELECT * FROM form_eye_mag_prefs where PEZONE='PREFS' AND (id=?) ORDER BY id,ZONE_ORDER,ordering";
$result = sqlStatement($query,array($_SESSION['authId'])); 
while ($prefs= sqlFetchArray($result))   {    
    $LOCATION = $prefs['LOCATION'];
    $$LOCATION = text($prefs['GOVALUE']);
}

$query = "SELECT * FROM patient_data where pid=?";
$pat_data =  sqlQuery($query,array($pid));

$query="select form_encounter.date as encounter_date,form_encounter.*, form_eye_mag.* from form_eye_mag, forms,form_encounter 
                    where 
                    form_encounter.encounter =? and 
                    form_encounter.encounter = forms.encounter and 
                    form_eye_mag.id=forms.form_id and
                    forms.deleted != '1'  and 
                    forms.formdir='eye_mag' and 
                    form_eye_mag.pid=? ";        
$encounter_data =sqlQuery($query,array($encounter,$pid));
@extract($encounter_data); 
//Do we have to have it?  
//We can iterate through every value and perform openEMR escape-specfific functions?
//We can rewrite the code to rename variables eg $encounter_data['RUL'] instead of $RUL?
//Isn't this what extract does?
//And the goal is to redefine each variable, so overwriting them is actually desirable.
//Given others forms may be based off this and we have no idea what those fields will be named, 
//should we make a decision here to create an openEMR extract like function?  
//Would it would have to test for "protected variables" by name?

$providerID   = findProvider($pid,$encounter);
$providerNAME = getProviderName($providerID);
$query        = "SELECT * FROM users where id = ?";
$prov_data    =  sqlQuery($query,array($providerID));

// build $PMSFH array
global $priors;
global $earlier;
$PMSFH = build_PMSFH($pid); 

/*
  Two windows anywhere with the same chart open is not compatible with the autosave feature.
  Data integrity problems will arise.  
  We use a random number generated for each instance - each time the form is opened - == uniqueID.
  If:   the form is LOCKED 
        and the LOCKEDBY variable != uniqueID
        and less than one hour has passed since it was locked
  then: a pop-up signals READ-ONLY mode.
  This user can take control if they wish.  If they confirm yes, take control,
  LOCKEDBY is changed to their uniqueID,  
  Any other instance of the form cannot save data, and if they try, 
  they will receive a popup saying hey buddy, you lost ownership, entering READ-ONLY mode.
  "Do you want to take control" is offered, should they wish to regain write priviledges.
  If they stay in READ-ONLY mode, the fields are locked and submit_form is not allowed...  
  In READ-ONLY mode, the form is refreshed via ajax every 15 seconds with changed fields' css
  background-color attribute set to purple.
  Once the active user with write priviledges closes their instance of the form, the form_id is unlocked.
  READ-ONLY users stay read only if they do nothing.
 */
  
$warning = 'nodisplay';
$uniqueID = mt_rand();
$warning_text ='READ-ONLY mode.';

if (!$LOCKED||!$LOCKEDBY) { //no one else has write privs.
  $LOCKEDBY= $uniqueID;
  $LOCKED='1';  
} else {
    //warning.  This form is locked by another user.
    $warning = ""; //remove nodisplay class
    $take_ownership = $uniqueID;
}
//drop TIME from encounter_date (which is in DATETIME format)
//since OpenEMR assumes input is yyyy-mm-dd
//we could do this by changing the MYSQL query in the first place too.  Which is better?
$dated = new DateTime($encounter_data['encounter_date']);
$dated = $dated->format('Y-m-d');
$visit_date = oeFormatShortDate($dated);

if (!$form_id && !$encounter) { echo text($encounter)."-".text($form_id).xlt('No encounter...'); exit;} 
//ideally this would point to an error databased by problem #, cause it'd be a problem.

if ($refresh and $refresh != 'fullscreen') {
  if ($refresh == "PMSFH")  { 
    echo display_PRIOR_section($refresh,$id,$id,$pid); 
  } else if ($refresh == "PMSFH_panel") {
    echo show_PMSFH_panel($PMSFH);
  } else if ($refresh == "page") {
    echo send_json_values($PMSFH);
  } else if ($refresh == "GFS") {
    echo display_GlaucomaFlowSheet($pid);
  }
  exit;
}

?><!DOCTYPE html>
<html>
  <head>
    <title> <?php echo xlt('Chart'); ?>: <?php echo text($pat_data['fname'])." ".text($pat_data['lname'])." ".text($visit_date); ?></title>
    <script src="<?php echo $GLOBALS['assets_static_relative'] ?>/jquery-min-1-11-1/index.js"></script>
    <script src="<?php echo $GLOBALS['assets_static_relative'] ?>/bootstrap-3-3-4/dist/js/bootstrap.min.js"></script>  
    <script src="<?php echo $GLOBALS['assets_static_relative'] ?>/qtip2-2-2-1/jquery.qtip.min.js"></script>
    <script language="JavaScript">    
    <?php require_once("$srcdir/restoreSession.php"); 
    ?>
    function dopclick(id) {
      <?php if ($thisauth != 'write'): ?>
      dlgopen('../../patient_file/summary/a_issue.php?issue=0&thistype=' + id, '_blank', 550, 400);
      <?php else: ?>
      alert("<?php echo xls('You are not authorized to add/edit issues'); ?>");
      <?php endif; ?>
    }
    function doscript(type,id,encounter,rx_number) {
      dlgopen('../../forms/eye_mag/SpectacleRx.php?REFTYPE=' + type + '&id='+id+'&encounter='+ encounter+'&form_id=<?php echo attr(addslashes($form_id)); ?>&rx_number='+rx_number, '_blank', 660, 590);
    }
     
    function dispensed(pid) {
      dlgopen('../../forms/eye_mag/SpectacleRx.php?dispensed=1&pid='+pid, '_blank', 560, 590);
    }
    function refractions(pid) {
      dlgopen('../../forms/eye_mag/SpectacleRx.php?dispensed=1&pid='+pid, '_blank', 560, 590);
    }
    // This invokes the find-code popup.
    function sel_diagnosis(target,term) {
      if (target =='') target = "0";
      IMP_target = target;
      <?php

      if($irow['type'] == 'PMH') //or POH
      {
        ?>
      dlgopen('../../patient_file/encounter/find_code_popup.php?codetype=<?php echo attr(collect_codetypes("medical_problem","csv")) ?>&search_term='+escape(term), '_blank', 600, 400);
        <?php
      } else{
        ?>
      dlgopen('../../patient_file/encounter/find_code_popup.php?codetype=<?php echo attr(collect_codetypes("diagnosis","csv")) ?>&search_term='+escape(term), '_blank', 600, 400);
        <?php
      }
      ?>
    }
    var obj =[];
    <?php 
      //also add in any obj.Clinical data if the form was already opened
      $codes_found = start_your_engines($encounter_data);
      if ($codes_found) { ?>
      obj.Clinical = [<?php echo json_encode($codes_found[0]); ?>];
      <?php }  ?>

    </script>
    
    <!-- Add Font stuff for the look and feel.  -->
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'] ?>/jquery-ui-1-11-4/themes/excite-bike/jquery-ui.css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'] ?>/pure-0-5-0/pure-min.css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'] ?>/bootstrap-3-3-4/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'] ?>/qtip2-2-2-1/jquery.qtip.min.css" />
    <link rel="stylesheet" href="<?php echo $GLOBALS['css_header']; ?>" type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'] ?>/font-awesome-4-6-3/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/style.css" type="text/css"> 

    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OpenEMR: Eye Exam">
    <meta name="author" content="OpenEMR: Ophthalmology">
    <meta name="viewport" content="width=device-width, initial-scale=1">
      
    <script language="JavaScript">
      function openNewForm(sel) {
          top.restoreSession();
          <?php 
          if ($GLOBALS['concurrent_layout']) { ?>
            FormNameValueArray = sel.split('formname=');
            if(FormNameValueArray[1] == 'newpatient' || (!parent.Forms))
            {
              parent.location.href = sel
            }
            else
            {
              parent.Forms.location.href = sel;
            }
            <?php } 
          else { ?>
              top.frames['Main'].location.href = sel;
              <?php } ?>
      }
    </script>
      <style type="text/css">
          .TEXT_class {
              margin: auto 5;
              min-height: 2.5in;
              text-align: left;
          }
      </style>
  </head>
  <body class="bgcolor2" background="<?php echo $GLOBALS['backpic']?>" style="padding:0;margin:0;" topmargin=0 rightmargin=0 leftmargin=0 bottommargin=0 marginwidth=0 marginheight=0>
    <?php
      $input_echo = menu_overhaul_top($pid,$encounter);
    ?><br /><br />
   
    <div id="page-wrapper" data-role="page" style="margin: 0px 0px 0px 0px;text-align:center;">
      <div id="Layer2" name="Layer2" class="nodisplay">
      </div>
      <div id="Layer3" name="Layer3" class="container-fluid" style="text-align:center;padding:0px 30px;">
        <?php   

        $output_priors = priors_select("ALL",$id,$id,$pid); 

        if ($output_priors != '') {
          // get any orders from the last visit for this visit
          // $priors[earlier]['PLAN'] contains the orders from last visit
          // explode '|' and display as needed
        }
        menu_overhaul_left($pid,$encounter); 
        ?>
     

        <!-- start form -->
        <form method="post" action="<?php echo $rootdir;?>/forms/<?php echo $form_folder; ?>/save.php?mode=update" id="eye_mag" class="eye_mag pure-form" name="eye_mag">
          <div id="Layer1" name="Layer1" class="display">
            <div id="warning" name="warning" class="alert alert-warning <?php echo $warning; ?>" style="padding-top:10px;margin: 0 auto;width:50%;">
              <span type="button" class="close" data-dismiss="alert">&times;</span>
              <h4><?php echo xlt('Warning'); ?>!
              <?php echo text($warning_text); ?></h4>
            </div>

            <!-- start form_container for the main body of the form -->
            <div class="body_top text-center row" id="form_container" name="form_container">
              <input type="hidden" name="menustate" id="menustate" value="start">
              <input type="hidden" name="form_folder" id="form_folder" value="<?php echo attr($form_folder); ?>">
              <input type="hidden" name="form_id" id="form_id" value="<?php echo attr($form_id); ?>">
              <input type="hidden" name="pid" id="pid" value="<?php echo attr($pid); ?>">
              <input type="hidden" name="encounter" id="encounter" value="<?php echo attr($encounter); ?>">
              <input type="hidden" name="visit_date" id="visit_date" value="<?php echo attr($encounter_date); ?>">
              <input type="hidden" name="PREFS_VA" id="PREFS_VA" value="<?php echo attr($VA); ?>">
              <input type="hidden" name="PREFS_W" id="PREFS_W" value="<?php echo attr($W); ?>">
              <input type="hidden" name="PREFS_MR" id="PREFS_MR" value="<?php echo attr($MR); ?>">
              <input type="hidden" name="PREFS_CR" id="PREFS_CR" value="<?php echo attr($CR); ?>">
              <input type="hidden" name="PREFS_CTL" id="PREFS_CTL" value="<?php echo attr($CTL); ?>">
              <input type="hidden" name="PREFS_VAX" id="PREFS_VAX" value="<?php echo attr($VAX); ?>">
              <input type="hidden" name="PREFS_ADDITIONAL" id="PREFS_ADDITIONAL" value="<?php echo attr($ADDITIONAL); ?>">
              <input type="hidden" name="PREFS_CLINICAL" id="PREFS_CLINICAL" value="<?php echo attr($CLINICAL); ?>">
              <input type="hidden" name="PREFS_IOP" id="PREFS_IOP" value="<?php echo attr($IOP); ?>">
              <input type="hidden" name="PREFS_EXAM" id="PREFS_EXAM" value="<?php echo attr($EXAM); ?>">
              <input type="hidden" name="PREFS_CYL" id="PREFS_CYL" value="<?php echo attr($CYLINDER); ?>">
              <input type="hidden" name="PREFS_HPI_VIEW" id="PREFS_HPI_VIEW" value="<?php echo attr($HPI_VIEW); ?>">
              <input type="hidden" name="PREFS_EXT_VIEW" id="PREFS_EXT_VIEW" value="<?php echo attr($EXT_VIEW); ?>">
              <input type="hidden" name="PREFS_ANTSEG_VIEW" id="PREFS_ANTSEG_VIEW" value="<?php echo attr($ANTSEG_VIEW); ?>">
              <input type="hidden" name="PREFS_RETINA_VIEW" id="PREFS_RETINA_VIEW" value="<?php echo attr($RETINA_VIEW); ?>">
              <input type="hidden" name="PREFS_NEURO_VIEW" id="PREFS_NEURO_VIEW" value="<?php echo attr($NEURO_VIEW); ?>">
              <input type="hidden" name="PREFS_ACT_VIEW" id="PREFS_ACT_VIEW" value="<?php echo attr($ACT_VIEW); ?>">
              <input type="hidden" name="PREFS_PMH_RIGHT" id="PREFS_PMH_RIGHT" value="<?php echo attr($PMH_RIGHT); ?>">
              <input type="hidden" name="PREFS_HPI_RIGHT" id="PREFS_HPI_RIGHT" value="<?php echo attr($HPI_RIGHT); ?>">
              <input type="hidden" name="PREFS_EXT_RIGHT" id="PREFS_EXT_RIGHT" value="<?php echo attr($EXT_RIGHT); ?>">
              <input type="hidden" name="PREFS_ANTSEG_RIGHT" id="PREFS_ANTSEG_RIGHT" value="<?php echo attr($ANTSEG_RIGHT); ?>">
              <input type="hidden" name="PREFS_RETINA_RIGHT" id="PREFS_RETINA_RIGHT" value="<?php echo attr($RETINA_RIGHT); ?>">
              <input type="hidden" name="PREFS_NEURO_RIGHT" id="PREFS_NEURO_RIGHT" value="<?php echo attr($NEURO_RIGHT); ?>">
              <input type="hidden" name="PREFS_IMPPLAN_RIGHT" id="PREFS_IMPPLAN_RIGHT" value="<?php echo attr($IMPPLAN_RIGHT); ?>">
              <input type="hidden" name="PREFS_PANEL_RIGHT" id="PREFS_PANEL_RIGHT" value="<?php echo attr($PANEL_RIGHT); ?>">
              <input type="hidden" name="PREFS_KB" id="PREFS_KB" value="<?php echo attr($KB_VIEW); ?>">
              <input type="hidden" name="PREFS_TOOLTIPS" id="PREFS_TOOLTIPS" value="<?php echo attr($TOOLTIPS); ?>">
              <input type="hidden" name="ownership" id="ownership" value="<?php echo attr($ownership); ?>">
              <input type="hidden" name="PREFS_ACT_SHOW"  id="PREFS_ACT_SHOW" value="<?php echo attr($ACT_SHOW); ?>">
              <input type="hidden" name="COPY_SECTION"  id="COPY_SECTION" value="">
              <input type="hidden" name="UNDO_ID"  id="UNDO_ID" value="<?php echo attr($UNDO_ID); ?>">
              <input type="hidden" name="LOCKEDBY" id="LOCKEDBY" value="<?php echo attr($LOCKEDBY); ?>">
              <input type="hidden" name="LOCKEDDATE" id="LOCKEDDATE" value="<?php echo attr($LOCKEDDATE); ?>">
              <input type="hidden" name="LOCKED"  id="LOCKED" value="<?php echo attr($LOCKED); ?>">
              <input type="hidden" name="uniqueID" id="uniqueID" value="<?php echo attr($uniqueID); ?>">
              <input type="hidden" name="chart_status" id="chart_status" value="on">
              <input type="hidden" name="finalize"  id="finalize" value="0">
            
              <!-- start first div -->
              <div id="first" name="first" class="text_clinical" style="display:inline-block;width:90%;">
                <!-- start    HPI spinner -->
                <div class="loading" id="HPI_sections_loading" name="HPI_sections_loading"><i class="fa fa-spinner fa-spin"></i>
                </div> 
                <!-- end      HPI spinner -->
                <?php ($CLINICAL =='1') ? ($display_Add = "size100") : ($display_Add = "size50"); ?>
                <?php ($CLINICAL =='0') ? ($display_Visibility = "display") : ($display_Visibility = "nodisplay"); ?>
                <!-- start    HPI_PMH row -->
                <div id="HPIPMH_sections" style="display:inline-block;margin: 10 auto;text-align:center;" class="nodisplay" >
                  <!-- start    HPI_section -->
                  <div id="HPI_1" name="HPI_1" class="<?php echo attr($display_Add); ?>">
                    <span class="anchor" id="HPI_anchor"></span>
                    
                    <!-- start  HPI Left -->
                    <div id="HPI_left" name="HPI_left" class="exam_section_left borderShadow">
                      <div id="HPI_left_text" class="TEXT_class" style="height: 315px;text-align:left;" >
                        <span class="closeButton fa fa-paint-brush" title="<?php echo xla('Open/Close the HPI Canvas'); ?>" id="BUTTON_DRAW_HPI" name="BUTTON_DRAW_HPI"></span>
                        <i class="closeButton_2 fa fa-database" title="<?php echo xla('Open/Close the detailed HPI panel'); ?>" id="BUTTON_QP_HPI" name="BUTTON_QP_HPI"></i>  
                        <i class="closeButton_3 fa fa-user-md fa-sm fa-2" name="Shorthand_kb" title="<?php echo xla("Open/Close the Shorthand Window and display Shorthand Codes"); ?>"></i>

                        <b><?php echo xlt('HPI'); ?>:</b> <i class="fa fa-help"></i><br />
                          <div id="tabs_wrapper" >
                            <div id="tabs_container" style="z-index:3;margin-top:5px;">
                              <ul id="tabs">
                                <li id="tab1_CC" class="active" ><a class="fa fa-check" href="#tab1"> <?php echo xlt('CC{{Chief Complaint}}'); ?> 1</a></li>
                                <li id="tab2_CC"><a <?php if ($CC2 >'') echo 'class="fa fa-check"'; ?> href="#tab2"><?php echo xlt('CC{{Chief Complaint}}'); ?> 2</a></li>
                                <li id="tab3_CC"><a <?php if ($CC3 >'') echo 'class="fa fa-check"'; ?> href="#tab3"><?php echo xlt('CC{{Chief Complaint}}'); ?> 3</a></li>
                              </ul>
                            </div>
                            <div id="tabs_content_container" style="z-index:1;height:303px;" class="borderShadow">
                              <div id="tab1_CC_text" class="tab_content">
                                <table border="0" width="100%" cellspacing="0" cellpadding="0" style="min-height: 2.0in;text-align:left;">
                                  <tr>
                                    <td class="" style="padding:10px;" colspan="2">
                                      <div class="kb kb_left">CC</div><b><span title="<?php echo xla('In the patient\'s words'); ?>"><?php echo xlt('Chief Complaint'); ?> 1:
                                      </span>  </b>
                                      <br />
                                      <textarea name="CC1" id="CC1" class="HPI_text" tabindex="10"><?php echo text($CC1); ?></textarea>
                                    </td>
                                  </tr> 
                                  <tr>
                                    <td class="" style="vertical-align:top;padding:10px;">
                                      <span title="<?php echo xla('History of Present Illness: A detailed HPI may be completed by using either four or more HPI elements OR the status of three chronic or inactive problems.'); ?>" style="height:1in;font-weight:600;vertical-align:text-top;"><?php echo xlt('HPI'); ?>:
                                        </span><div class="kb kb_left">HPI</div>
                                      <br />
                                      <textarea name="HPI1" id="HPI1" class="HPI_text" tabindex="21" style="min-height:1.4in;max-height:2.0in;width:2.1in;"><?php echo text($HPI1); ?></textarea>
                                      <br />
                                    </td>
                                    <td style="vertical-align:top;padding:10px;"><span title="<?php echo xla('Chronic/Inactive Problems:')."&nbsp\n".xla('document 3 and their status to reach the detailed HPI level')."&nbsp\n"; 
                                    echo "PMH items flagged as Chronic with a comment regarding status will automatically appear here.";?>" style="height:1in;font-weight:600;vertical-align:text-top;"><?php echo xlt('Chronic Problems') ?>:</span>
                                      <span class="kb_off"><br /></span><div class="kb kb_right">CHRONIC1</div>
                                      <textarea name="CHRONIC1" id="CHRONIC1" class="HPI_text chronic_HPI" tabindex="22" style="max-height:2.0in;width:1.8in;margin-bottom:9px;"><?php echo text($CHRONIC1); ?></textarea>
                                      <span class="kb_off"><br /></span><div class="kb kb_right">CHRONIC2</div><textarea name="CHRONIC2" id="CHRONIC2" class="HPI_text chronic_HPI" tabindex="23" style="max-height:2.0in;width:1.8in;margin-bottom:9px;"><?php echo text($CHRONIC2); ?></textarea>
                                      <span class="kb_off"><br /></span><div class="kb kb_right">CHRONIC3</div><textarea name="CHRONIC3" id="CHRONIC3" class="HPI_text chronic_HPI" tabindex="24" style="max-height:2.0in;width:1.8in;"><?php echo text($CHRONIC3); ?></textarea>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td colspan="2" style="text-align:center;">
                                      <i id="CODE_HIGH_0" name="CODE_HIGH" class="CODE_HIGH fa fa-check nodisplay" value="1"></i>
                                      <span style="font-size:0.8em;margin-top:10px;text-align:center;width:85%;font-weight:400;color:#876F6F;">
                                        <span class="detailed_HPI" name=""><?php echo xlt('Detailed HPI') ?>:</span>
                                        <span class="detail_4_elements" name=""><?php echo xlt('> 3 HPI elements'); ?></span> <?php echo xlt('OR{{as in AND/OR, ie. not an abbreviation}}'); ?> 
                                        <span class="chronic_3_elements"><?php echo xlt('the status of three chronic/inactive problems'); ?></span>
                                      </span>
                                    </td>
                                  </tr>
                                </table>
                              </div>
                                   
                              <div id="tab2_CC_text" class="tab_content">
                                  <table border="0" width="100%" cellspacing="0" cellpadding="0" 
                                      style="min-height: 2.0in;text-align:left;">
                                    <tr>
                                      <td class="" style="vertical-align:top;padding:10px;" colspan="2">
                                        <b><span title="<?php echo xla('In the patient\'s words'); ?>"><?php echo xlt('Chief Complaint'); ?> 2:
                                        </span>  </b>
                                        <br />
                                        <textarea name="CC2" id="CC2" class="HPI_text" tabindex="10" style="height:56px;"><?php echo text($CC2); ?></textarea>
                                      </td>
                                    </tr> 
                                    <tr>
                                      <td class="" style="vertical-align:top;padding:10px;">
                                        <span title="<?php echo xla('History of Present Illness: A detailed HPI may be completed by using either four or more HPI elements OR the status of three chronic or inactive problems.'); ?>" style="height:1in;font-weight:600;vertical-align:text-top;"><?php echo xlt('HPI'); ?>:
                                        </span>
                                        <br />
                                        <textarea name="HPI2" id="HPI2" class="HPI_text" tabindex="21" style="min-height:1.5in;max-height:2.0in;width:4.1in;"><?php echo text($HPI2); ?></textarea>
                                        <br />
                                      </td>
                                    </tr> 
                                  </table>
                              </div>
                              <div id="tab3_CC_text" class="tab_content">
                                  <table border="0" width="100%" cellspacing="0" cellpadding="0" 
                                      style="min-height: 2.0in;text-align:left;">
                                    <tr>
                                      <td style="vertical-align:top;padding:10px;" colspan="2">
                                        <b><span title="<?php echo xla('In the patient\'s words'); ?>"><?php echo xlt('Chief Complaint'); ?> 3:
                                        </span>  </b>
                                        <br />
                                        <textarea name="CC3" id="CC3" class="HPI_text" tabindex="10" style="height:65px;"><?php echo text($CC3); ?></textarea>
                                      </td>
                                    </tr> 
                                    <tr>
                                      <td class="" style="vertical-align:top;padding:10px;">
                                        <span title="<?php echo xla('History of Present Illness: A detailed HPI may be completed by using either four or more HPI elements OR the status of three chronic or inactive problems.'); ?>" style="height:1in;font-weight:600;vertical-align:text-top;"><?php echo xlt('HPI'); ?>:
                                        </span>
                                        <br />
                                        <textarea name="HPI3" id="HPI3" class="HPI_text" tabindex="21" style="min-height:1.5in;max-height:2.0in;width:4.1in;"><?php echo text($HPI3); ?></textarea>
                                        <br />
                                      </td>
                                    </tr> 
                                  </table>
                              </div>
                            </div>
                          </div>
                         
                        <?php ($HPI_VIEW !=2) ? ($display_HPI_view = "wide_textarea") : ($display_HPI_view= "narrow_textarea");?>                                 
                        <?php ($display_HPI_view == "wide_textarea") ? ($marker ="fa-minus-square-o") : ($marker ="fa-plus-square-o");?>
                      </div>
                    </div>
                    <!-- end    HPI Left -->

                    <!-- start  HPI Right -->
                    <div id="HPI_right" name="HPI_right" class="exam_section_right borderShadow">
                      <?php display_draw_section ("HPI",$encounter,$pid); ?>
                      <!-- start    QP_HPI_Build -->
                      <div id="QP_HPI" name="QP_HPI" class="QP_class" style="text-align:left;">
                        <div id="HPI_text_list" name="HPI_text_list">
                          <span class="closeButton fa fa-close pull-right z100" id="BUTTON_TEXTD_HPI" name="BUTTON_TEXTD_HPI" value="1"></span>
                      
                      
                          <b><?php echo xlt('HPI Elements'); ?>:</b> <br />
                          <div id="tabs_wrapper" >
                            <div id="tabs_container" style="margin-top:5px;">
                              <ul id="tabs">
                                <li id="tab1_HPI_tab" class="active" ><a type="button" <?php if ($CC1 >'') echo 'class="fa fa-check" '; ?> href="#tab1"> <?php echo xlt('HPI'); ?> 1</a></li>
                                <li id="tab2_HPI_tab" ><a <?php if ($CC2 >'') echo 'class="fa fa-check"'; ?> href="#tab2"><?php echo xlt('HPI'); ?> 2</a></li>
                                <li id="tab3_HPI_tab" ><a <?php if ($CC3 >'') echo 'class="fa fa-check"'; ?> href="#tab3"><?php echo xlt('HPI'); ?> 3</a></li>
                              </ul>
                            </div>
                            <div id="tabs_content_container" style="z-index:1;" class="borderShadow">
                              <div id="tab1_HPI_text" class="tab_content" style="min-height: 2.0in;text-align:left;font-size: 0.9em;">
                                <table>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Timing'); ?>:</b></td>
                                    <td>
                                      <textarea name="TIMING1" id="TIMING1" class="count_HPI" tabindex="30" style="width:250px;"><?php echo text($TIMING1); ?></textarea>
                                    </td>
                                  </td><td><i><?php echo xlt('When and how often?'); ?></i><br /></td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Context'); ?>:</b></td>
                                    <td>
                                      <textarea name="CONTEXT1" id="CONTEXT1" class="count_HPI" tabindex="31"  style="width:250px;"><?php echo text($CONTEXT1); ?></textarea>
                                        <br />
                                    </td>
                                    <td>
                                      <i><?php echo xlt('Does it occur in certain situations?'); ?></i><br />
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Severity'); ?>:</b></td>
                                    <td>
                                      <textarea name="SEVERITY1" id="SEVERITY1" class="count_HPI" tabindex="32" style="width:250px;"><?php echo text($SEVERITY1); ?></textarea>
                                      </td>
                                      <td><i><?php echo xlt('How bad is it? 0-10, mild, mod, severe?'); ?></i>
                                      </td>
                                  </tr>
                                  <tr>
                                    <td  class="right"><b><?php echo xlt('Modifying'); ?>:</b></td>
                                    <td>
                                      <textarea name="MODIFY1" id="MODIFY1" class="count_HPI" tabindex="33"  style="width:250px;"><?php echo text($MODIFY1); ?></textarea>
                                        </td>
                                        <td><i ><?php echo xlt('Does anything make it better? Worse?'); ?></i>
                                        </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Associated'); ?>:</b></td>
                                    <td>
                                      <textarea name="ASSOCIATED1" id="ASSOCIATED1" class="count_HPI" tabindex="34" style="width:250px;"><?php echo text($ASSOCIATED1); ?></textarea>
                                      </td>
                                      <td><i><?php echo xlt('Anything else occur at the same time?'); ?></i>
                                      </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Location'); ?>:</b></td>
                                    <td>
                                      <textarea name="LOCATION1" id="LOCATION1" class="count_HPI" tabindex="35" style="width:250px;"><?php echo text($LOCATION1); ?></textarea>                        
                                    </td>
                                    <td><i><?php echo xlt('Where on your body does it occur?'); ?></i>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Quality'); ?>:</b></td>
                                    <td>
                                      <textarea name="QUALITY1" id="QUALITY1" class="count_HPI" tabindex="36" style="width:250px;"><?php echo text($QUALITY1); ?></textarea>
                                          
                                    </td>
                                    <td>
                                      <i><?php echo xlt('eg. aching, burning, radiating pain'); ?></i>
                                    </td>
                                  </tr> 
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Duration'); ?>:</b></td>
                                    <td><textarea name="DURATION1" id="DURATION1" class="count_HPI" tabindex="37" style="width:250px;"><?php echo text($DURATION1); ?></textarea>
                                    </td>
                                    <td>
                                      <i><?php echo xlt('How long does it last?'); ?></i>
                                    </td>
                                  </tr>
                                </table>
                                <center>
                                 <i id="CODE_HIGH_1" name="CODE_HIGH" class="CODE_HIGH fa fa-check nodisplay" value="1"></i>
                                  <span style="font-size:0.7em;margin-top:4px;text-align:center;width:85%;font-weight:400;color:#C0C0C0;">
                                    <span class="detailed_HPI"><?php echo xlt('Detailed HPI') ?>:</span>
                                    <span class="detail_4_elements"><?php echo xlt('> 3 HPI elements'); ?></span> <?php echo xlt('OR{{as in AND/OR, ie. not an abbreviation}}'); ?> 
                                    <span class="chronic_3_elements"><?php echo xlt('the status of three chronic/inactive problems'); ?></span>
                                  </span>
                                </center>
                               
                              </div>
                              <div id="tab2_HPI_text" class="tab_content" style="min-height: 2.0in;text-align:left;font-size: 0.9em;">  
                                <table>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Timing'); ?>:</b></td>
                                    <td>
                                      <textarea name="TIMING2" id="TIMING2" tabindex="30" style="width:250px;"><?php echo text($TIMING2); ?></textarea>
                                    </td>
                                  </td><td><i><?php echo xlt('When and how often?'); ?></i><br /></td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Context'); ?>:</b></td>
                                    <td>
                                      <textarea name="CONTEXT2" id="CONTEXT2" tabindex="31"  style="width:250px;"><?php echo text($CONTEXT2); ?></textarea>
                                        <br />
                                    </td>
                                    <td>
                                      <i><?php echo xlt('Does it occur in certain situations?'); ?></i><br />
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Severity'); ?>:</b></td>
                                    <td>
                                      <textarea name="SEVERITY2" id="SEVERITY2" tabindex="32" style="width:250px;"><?php echo text($SEVERITY2); ?></textarea>
                                      </td>
                                      <td><i><?php echo xlt('How bad is it? 0-10, mild, mod, severe?'); ?></i>
                                      </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Modifying'); ?>:</b></td>
                                    <td>
                                      <textarea name="MODIFY2" id="MODIFY2" tabindex="33"  style="width:250px;"><?php echo text($MODIFY2); ?></textarea>
                                        </td>
                                        <td><i ><?php echo xlt('Does anything make it better? Worse?'); ?></i>
                                        </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Associated'); ?>:</b></td>
                                    <td>
                                      <textarea name="ASSOCIATED2" id="ASSOCIATED2" tabindex="34" style="width:250px;"><?php echo text($ASSOCIATED2); ?></textarea>
                                      </td>
                                      <td><i><?php echo xlt('Anything else occur at the same time?'); ?></i>
                                      </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Location'); ?>:</b></td>
                                    <td>
                                      <textarea name="LOCATION2" id="LOCATION2" tabindex="35" style="width:250px;"><?php echo text($LOCATION2); ?></textarea>                        
                                    </td>
                                    <td><i><?php echo xlt('Where on your body does it occur?'); ?></i>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Quality'); ?>:</b></td>
                                    <td>
                                      <textarea name="QUALITY2" id="QUALITY2" tabindex="36" style="width:250px;"><?php echo text($QUALITY2); ?></textarea>
                                          
                                    </td><td>
                                    <i><?php echo xlt('eg. aching, burning, radiating pain'); ?></i>
                                    </td>
                                  </tr> 
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Duration'); ?>:</b></td>
                                    <td><textarea name="DURATION2" id="DURATION2" tabindex="37" style="width:250px;"><?php echo text($DURATION2); ?></textarea>
                                    </td>
                                    <td>
                                      <i><?php echo xlt('How long does it last?'); ?></i>
                                    </td>
                                  </tr>
                                </table>
                                <center>
                                  <i id="CODE_HIGH_2" name="CODE_HIGH" class="CODE_HIGH fa fa-check nodisplay" value="1"></i>
                                  <span style="font-size:0.7em;margin-top:4px;text-align:center;width:85%;font-weight:400;color:#C0C0C0;">
                                    <span class="detailed_HPI"><?php echo xlt('Detailed HPI') ?>:</span>
                                    <span class="detail_4_elements"><?php echo xlt('> 3 HPI elements'); ?></span> <?php echo xlt('OR{{as in AND/OR, ie. not an abbreviation}}'); ?> 
                                    <span class="chronic_3_elements"><?php echo xlt('the status of three chronic/inactive problems'); ?></span>
                                  </span>
                                </center>
                              </div>
                              <div id="tab3_HPI_text" class="tab_content" style="min-height: 2.0in;text-align:left;font-size: 0.9em;">         
                                <table>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Timing'); ?>:</b></td>
                                    <td>
                                      <textarea name="TIMING3" id="TIMING3" tabindex="30" style="width:250px;"><?php echo text($TIMING3); ?></textarea>
                                    </td>
                                    </td><td><i><?php echo xlt('When and how often?'); ?></i><br /></td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Context'); ?>:</b></td>
                                    <td>
                                      <textarea name="CONTEXT3" id="CONTEXT3" tabindex="31"  style="width:250px;"><?php echo text($CONTEXT3); ?></textarea>
                                        <br />
                                    </td>
                                    <td>
                                      <i><?php echo xlt('Does it occur in certain situations?'); ?></i><br />
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Severity'); ?>:</b></td>
                                    <td>
                                      <textarea name="SEVERITY3" id="SEVERITY3" tabindex="32" style="width:250px;"><?php echo text($SEVERITY3); ?></textarea>
                                      </td>
                                      <td><i><?php echo xlt('How bad is it? 0-10, mild, mod, severe?'); ?></i>
                                      </td>
                                  </tr>
                                  <tr>
                                    <td  class="right"><b><?php echo xlt('Modifying'); ?>:</b></td>
                                    <td>
                                      <textarea name="MODIFY3" id="MODIFY3" tabindex="33"  style="width:250px;"><?php echo text($MODIFY3); ?></textarea>
                                        </td>
                                        <td><i ><?php echo xlt('Does anything make it better? Worse?'); ?></i>
                                        </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Associated'); ?>:</b></td>
                                    <td>
                                      <textarea name="ASSOCIATED3" id="ASSOCIATED3" tabindex="34" style="width:250px;"><?php echo text($ASSOCIATED3); ?></textarea>
                                      </td>
                                      <td><i><?php echo xlt('Anything else occur at the same time?'); ?></i>
                                      </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Location'); ?>:</b></td>
                                    <td>
                                      <textarea name="LOCATION3" id="LOCATION3" tabindex="35" style="width:250px;"><?php echo text($LOCATION3); ?></textarea>                        
                                    </td>
                                    <td><i><?php echo xlt('Where on your body does it occur?'); ?></i>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Quality'); ?>:</b></td>
                                    <td>
                                      <textarea name="QUALITY3" id="QUALITY3" tabindex="36" style="width:250px;"><?php echo text($QUALITY3); ?></textarea>
                                          
                                    </td><td>
                                      <i><?php echo xlt('eg. aching, burning, radiating pain'); ?></i>
                                    </td>
                                  </tr> 
                                  <tr>
                                    <td class="right"><b><?php echo xlt('Duration'); ?>:</b></td>
                                    <td><textarea name="DURATION3" id="DURATION3" tabindex="37" style="width:250px;"><?php echo text($DURATION3); ?></textarea>
                                    </td>
                                    <td>
                                      <i><?php echo xlt('How long does it last?'); ?></i>
                                    </td>
                                  </tr>
                                </table>
                                <center>
                                  <i id="CODE_HIGH_3" name="CODE_HIGH" class="CODE_HIGH fa fa-check nodisplay" value="1"></i>
                                  <span style="font-size:0.7em;margin-top:4px;text-align:center;width:85%;font-weight:400;color:#C0C0C0;">
                                    <span class="detailed_HPI"><?php echo xlt('Detailed HPI') ?>:</span>
                                    <span class="detail_4_elements"><?php echo xlt('> 3 HPI elements'); ?></span> <?php echo xlt('OR{{as in AND/OR, ie. not an abbreviation}}'); ?> 
                                    <span class="chronic_3_elements"><?php echo xlt('the status of three chronic/inactive problems'); ?></span>
                                  </span>
                                </center>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>  
                      <!-- end      QP_HPI -->
                    </div>
                    <!-- end    HPI Right -->
                  </div>
                  <!-- end      HPI_section -->
                  <!-- start    PMH_section -->
                  <div id="PMH_1" name="PMH_1" class="<?php echo attr($display_Add); ?> clear_both">
                    <span class="anchor" id="PMH_anchor"></span>
                    <!-- start  PMH Left -->
                    <div id="PMH_left" name="PMH_left" class="exam_section_left borderShadow">
                      <div id="PMH_left_text" style="height: 2.5in;text-align:left;" class="TEXT_class">
                        <b class="left"><?php echo xlt('PMSFH{{Abbreviation for Past medical Surgical Family and Social History}}'); ?>:</b> <i class="fa fa-help"></i><br />
                        <span class="closeButton_2 fa fa-paint-brush" title="<?php echo xla('Open/Close the PMH draw panel'); ?>" id="BUTTON_DRAW_PMH" name="BUTTON_DRAW_PMH"></span>
                        <i class="closeButton_3 fa fa-database" title="<?php echo xla('Open/Close the PMSFH summary panel'); ?>" id="BUTTON_QP_PMH" name="BUTTON_QP_PMH"></i>
                        <i class="closeButton_4 fa fa-user-md fa-sm fa-2" name="Shorthand_kb" title="<?php echo xla("Open/Close the Shorthand Window and display Shorthand Codes"); ?>"></i>
                        <a class="closeButton fa fa-list" title="<?php echo xla('Toggle the right-sided PMSFH panel'); ?>" id="right-panel-link" name="right-panel-link" href="#right-panel"></a>

                        <?php ($PMH_VIEW !=2) ? ($display_PMH_view = "wide_textarea") : ($display_PMH_view= "narrow_textarea");?>                                 
                        <?php ($display_PMH_view == "wide_textarea") ? ($marker ="fa-minus-square-o") : ($marker ="fa-plus-square-o");?>
                        <div id="PMSFH_sections" name="PMSFH_sections">
                          <div id="Enter_PMH" name="Enter_PMH" class="PMH_class" style="text-align:left;vertical-align:middle; min-height: 2.3in;font-size:0.7em;">
                            <center>
                              <iframe id="iframe" name="iframe" 
                                src="../../forms/eye_mag/a_issue.php?uniqueID=<?php echo $uniqueID; ?>&form_type=POH&pid=<?php echo $pid; ?>&encounter=<?php echo $encounter; ?>&form_id=<?php echo $form_id; ?>" 
                                width="445" height="340" scrolling = "yes" frameBorder = "0" >
                              </iframe>
                            </center>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- end    PMH Left -->
                    <!-- start  PMH Right -->
                    <div id="PMH_right" name="PMH_right" class="exam_section_right borderShadow">
                      <a class="nodisplay left_PMSFH_tab" id="right-panel-link" href="#right-panel">
                        <img src="<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/images/PMSFHx.png">
                      </a>  
                      <span class="fa fa-close pull-right closeButton" id="BUTTON_TEXTD_PMH" name="BUTTON_TEXTD_PMH" value="1"></span>
                      <?php display_draw_section("PMH",$encounter,$pid); ?>
                      <div id="QP_PMH" name="QP_PMH" class="QP_class" style="text-align:left;float:left;display: inline-block;">
                        <?php echo display_PRIOR_section("PMSFH",$id,$id,$pid); ?>

                      </div>
                    </div>
                    <!-- end    PMH Right -->
                  </div>  
                  <!-- end      PMH_section -->
                </div>
                <!-- end    HPI_PMH row -->
              </div>
              <!-- end first div -->

              <div id="clinical_anchor" name="clinical_anchor"></div>
              <br />
              
              <!-- start of the CLINICAL BOX -->
               <?php 
                $display_W_1 = "nodisplay";
                $display_W_2 = "nodisplay";
                $display_W_3 = "nodisplay";
                $display_W_4 = "nodisplay";
                $RX_count='1';

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
                  ${"W_$count_rx"} = '1';
                  ${"RX_TYPE_$count_rx"} = $wearing['RX_TYPE'];
                } 
              ?>
              <div class="loading row" id="LayerTechnical_sections_loading" name="LayerTechnical_sections_loading"><i class="fa fa-spinner fa-spin"></i>
              </div> 
              <div class="clear_both row" style="display: inline-block;margin:10px auto;" id="LayerTechnical_sections_1" name="LayerTechnical_sections" >
                 
                <!-- start of the Mood BOX -->
                <div id="LayerMood" class="vitals" style="width: 156px; min-height: 1.05in;border: 1.00pt solid #000000;text-align:left;padding-left:10px;float:left;">
                  <div id="Lyr2.9" class="top_left">
                    <th class="text_clinical" nowrap><b id="MS_tab"><?php echo xlt('Mental Status'); ?>:</b></th>
                  </div>    
                  <br />
                  <input type="checkbox" name="alert" id="alert" <?php if ($alert) echo "checked='checked'"; ?> value="1"> 
                  <label for="alert" class="input-helper input-helper--checkbox"><?php echo xlt('Alert'); ?></label><br />
                  <input type="checkbox" name="oriented" id="oriented" <?php if ($oriented) echo "checked='checked'"; ?> value="1">
                  <label for="oriented" class="input-helper input-helper--checkbox"><?php echo xlt('Oriented TPP{{oriented to person and place}}'); ?></label><br />
                  <input type="checkbox" name="confused" id="confused" <?php if ($confused) echo "checked='checked'"; ?> value="1">
                  <label for="confused" class="input-helper input-helper--checkbox"><?php echo xlt('Mood/Affect Nml{{Mood and affect normal}}'); ?></label><br />
                   
                </div>
                <!-- end of the Mood BOX -->
                
                <!-- start of the VISION BOX -->                  
                <div id="LayerVision" class="vitals" style="width: 2.2in; min-height: 1.05in;padding: 0.02in; border: 1.00pt solid #000000;float:left;">
                  <div id="Lyr3.0" class="top_left ">
                    <th class="text_clinical"><b id="vision_tab" title="Show/hide the refraction panels"><?php echo xlt('Vision'); ?>:</b></th>
                  </div>
                       <?php 
                                              //if the prefs show a field, ie visible, the highlight the zone.
                       if ($W == '1') $button_W = "buttonRefraction_selected";
                       if ($MR == '1') $button_MR = "buttonRefraction_selected";
                       if ($CR == '1') $button_AR = "buttonRefraction_selected";
                       if ($CTL == '1') $button_CTL = "buttonRefraction_selected";
                       if ($ADDITIONAL == '1') $button_ADDITIONAL = "buttonRefraction_selected";
                       if ($VAX == '1') $button_VAX = "buttonRefraction_selected";
                       ?>
                  <div class="top_right">
                          <span id="tabs" style="font-size:0.5em;">  
                              <ul>
                                  <li id="LayerVision_W_lightswitch" class="<?php echo attr($button_W); ?>" value="Current" title="<?php echo xla("Display the patient's current glasses"); ?>"><?php echo xlt('W{{Current Rx - wearing}}'); ?></li> | 
                                  <li id="LayerVision_MR_lightswitch" class="<?php echo attr($button_MR); ?>" value="Auto" title="<?php echo xla("Display the Manifest Refraction panel"); ?>"><?php echo xlt('MR{{Manifest Refraction}}'); ?></li> | 
                                  <li id="LayerVision_CR_lightswitch" class="<?php echo attr($button_AR); ?>" value="Cyclo" title="<?php echo xla("Display the Autorefraction Panel"); ?>"><?php echo xlt('AR{{AutoRefraction}}'); ?></li> | 
                                  <li id="LayerVision_CTL_lightswitch" class="<?php echo attr($button_CTL); ?>" value="Contact Lens" title="<?php echo xla("Display the Contact Lens Panel"); ?>"><?php echo xlt('CTL{{Contact Lens}}'); ?></li> | 
                                  <li id="LayerVision_ADDITIONAL_lightswitch" class="<?php echo attr($button_ADDITIONAL); ?>" value="Additional" title="<?php echo xla("Display Additional measurements (Ks, IOL cals, etc)"); ?>"><?php echo xlt('Add.{{Additional Measurements}}'); ?></li> | 
                                  <li id="LayerVision_VAX_lightswitch" class="<?php echo attr($button_VAX); ?>" value="Visual Acuities" title="<?php echo xla("Summary of Acuities for this patient"); ?>"><?php echo xlt('Va{{Visual Acuities}}'); ?></li>                           
                              </ul>
                          </span>
                  </div>    

                  <div id="Lyr3.1" style="position: absolute; top: 0.23in; left: 0.09in;width: 0.4in;height: 0.3in; border: none; padding: 0in; " dir="LTR">
                    <font style="font-face:'San Serif'; font-size:3.5em;"><?php echo xlt('V{{One letter abbrevation for Vision}}'); ?></font>
                    <font style="font-face:arial; font-size:0.9em;"></font>
                  </div>
                  <div id="Visions_A" name="Visions_A" class="" style="position: absolute; top: 0.35in; text-align:right;right:0.1in; height: 0.72in;  padding: 0in;" >
                      <b>OD </b>
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="40" id="SCODVA" name="SCODVA" value="<?php echo attr($SCODVA); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="42" id="ODVA_1_copy" name="ODVA_1_copy" value="<?php echo attr($ODVA_1); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="44" id="PHODVA_copy" name="PHODVA_copy" value="<?php echo attr($PHODVA); ?>">
                      <br />                            
                      <b>OS </b>
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="41" id="SCOSVA" name="SCOSVA" value="<?php echo attr($SCOSVA); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="43" id="OSVA_1_copy" name="OSVA_1_copy" value="<?php echo attr($OSVA_1); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="45" id="PHOSVA_copy" name="PHOSVA_copy" value="<?php echo attr($PHOSVA); ?>">
                      <br />
                      <span id="more_visions_1" name="more_visions_1" style="position: absolute;top:0.44in;left:-0.37in;font-size: 0.9em;padding-right:4px;"><b><?php echo xlt('Acuity'); ?></b> </span>
                      <span style="position: absolute;top:0.48in;left:0.35in;font-size: 0.8em;"><b><?php echo xlt('SC{{without correction}}'); ?></b></span>
                      <span style="position: absolute;top:0.48in;left:0.80in;font-size: 0.8em;"><b><?php echo xlt('CC{{with correction}}'); ?></b></span>
                      <span style="position: absolute;top:0.48in;left:1.25in;font-size: 0.8em;"><b><?php echo xlt('PH{{pinhole acuity}}'); ?></b></span><br /><br /><br />
                  </div>
                  <div id="Visions_B" name="Visions_B" class="nodisplay" style="position: absolute; top: 0.35in; text-align:right;right:0.1in; height: 0.72in;  padding: 0in;" >
                      <b><?php echo xlt('OD'); ?> </b>
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="46" Xsize="6" id="ARODVA_copy" name="ARODVA_copy" value="<?php echo attr($ARODVA); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="48" Xsize="6" id="MRODVA_copy" name="MRODVA_copy" value="<?php echo attr($MRODVA); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="50" Xsize="6" id="CRODVA_copy" name="CRODVA_copy" value="<?php echo attr($CRODVA); ?>">
                      <br />                            
                      <b><?php echo xlt('OS'); ?> </b>
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="47" Xsize="6" id="AROSVA_copy" name="AROSVA_copy" value="<?php echo attr($AROSVA); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="49" Xsize="6" id="MROSVA_copy" name="MROSVA_copy" value="<?php echo attr($MROSVA); ?>">
                      <input type="TEXT" style="left: 0.5in; width: 0.35in; height: 0.19in; " tabindex="51" Xsize="6" id="CROSVA_copy" name="CROSVA_copy" value="<?php echo attr($CROSVA); ?>">
                      <br />
                      <span id="more_visions_2" name="more_visions_2" style="position: absolute;top:0.44in;left:-0.37in;font-size: 0.9em;padding-right:4px;"><b><?php echo xlt('Acuity'); ?></b> </span>
                      <span style="position: absolute;top:0.48in;left:0.35in;font-size: 0.8em;"><b><?php echo xlt('AR{{Autorefraction Acuity}}'); ?></b></span>
                      <span style="position: absolute;top:0.48in;left:0.80in;font-size: 0.8em;"><b><?php echo xlt('MR{{Manifest refraction}}'); ?></b></span>
                      <span style="position: absolute;top:0.48in;left:1.25in;font-size: 0.8em;"><b><?php echo xlt('CR{{Cycloplegic refraction}}'); ?></b></span>
                  </div>       
                </div>
                <!-- end of the VISION BOX -->

                <!-- START OF THE PRESSURE BOX -->
                <div id="LayerTension" class="vitals" style="width: 1.9in; height: 1.05in;padding: 0.02in; border: 1.00pt solid #000000;float:left;">
                      
                      <span title="Display the Glaucoma Flow Sheet" id="LayerVision_IOP_lightswitch" name="LayerVision_IOP_lightswitch" class="closeButton fa  fa-line-chart" id="IOP_Graph" name="IOP_Graph" style="padding-left:0px;"></span>
                      <!-- -->
                      <div id="Lyr4.0" style="position:absolute; left:0.05in; width: 1.4in; top:0.0in; padding: 0in; ">
                          <span class="top_left">
                              <b id="tension_tab"><?php echo xlt('Tension'); ?>:</b> 
                              <div style="position:absolute;background-color:#ffffff;text-align:left;width:70px; top:0.7in;font-size:0.9em;left:0.02in;">
                                  <?php  
                                  if (($IOPTIME == '00:00:00')||(!$IOPTIME)) {
                                      $IOPTIME =  date('G:i A'); 
                                  }
                                    $show_IOPTIME = date('g:i A',strtotime($IOPTIME));
                                 ?>
                                  <input type="text" name="IOPTIME" id="IOPTIME" tabindex="-1" style="background-color:#ffffff;font-size:0.8em;border:none;" value="<?php echo attr($show_IOPTIME); ?>">

                              </div>    
                          </span>
                      </div>
                      <div id="Lyr4.1" style="position: absolute; top: 0.23in; left: 0.09in; width: 0.37in;height: 0.45in; border: none; padding: 0in;">
                          <font style="font-face:arial; font-size:3.5em;"><?php echo xlt('T{{one letter abbreviation for Tension/Pressure}}'); ?></font>
                          <font style="font-face:arial; font-size: 0.9em;"></font>
                      </div>
                      <div id="Lyr4.2" style="position: absolute; top: 0.35in; text-align:right;right:0.1in; height: 0.72in;  padding: 0in; border: 1pt black;">
                          <b><?php echo xlt('OD{{right eye}}'); ?></b>
                          <input type="text" style="left: 0.5in; width: 0.23in; height: 0.18in; " tabindex="52" name="ODIOPAP" id="ODIOPAP" value="<?php echo attr($ODIOPAP); ?>">
                          <input type="text" style="left: 0.5in; width: 0.23in; height: 0.18in; " tabindex="54" name="ODIOPTPN" id="ODIOPTPN" value="<?php echo attr($ODIOPTPN); ?>">
                          <input type="text" style="left: 0.5in; width: 0.23in; height: 0.18in; " name="ODIOPFTN" id="ODIOPFTN" value="<?php echo attr($ODIOPFTN); ?>">
                          <br />
                          <b><?php echo xlt('OS{{left eye}}'); ?> </b>
                          <input type="text" style="left: 0.5in; width: 0.23in; height: 0.18in; " tabindex="53" name="OSIOPAP" id="OSIOPAP" value="<?php echo attr($OSIOPAP); ?>">
                          <input type="text" style="left: 0.5in; width: 0.23in; height: 0.18in; " tabindex="55" name="OSIOPTPN" id="OSIOPTPN" value="<?php echo attr($OSIOPTPN); ?>">
                          <input type="text" style="left: 0.5in; width: 0.23in; height: 0.18in; " name="OSIOPFTN" id="OSIOPFTN" value="<?php echo attr($OSIOPFTN); ?>">
                          <br /><br />
                          <span style="position: absolute;top:0.48in;left:0.32in;font-size: 0.8em;"><b><?php echo xlt('AP{{applanation}}'); ?></b></span>
                          <span style="position: absolute;top:0.48in;left:0.64in;font-size: 0.8em;"><b><?php echo xlt('TP{{tonopen}}'); ?></b></span>
                          <span style="position: absolute;top:0.48in;left:0.96in;font-size: 0.8em;"><b><?php echo xlt('FT{{finger tension}}'); ?></b></span>
                      </div>
                </div>
                <!-- END OF THE PRESSURE BOX -->  
              
                <!-- start of the Amsler box -->
                <div id="LayerAmsler" class="vitals" style="width: 1.5in; height: 1.05in;padding: 0.02in; border: 1.00pt solid #000000;">
                    <div id="Lyr5.0" style="width: 1.4in; top:0in; padding: 0in;">
                        <span class="top_left">
                            <b><?php echo xlt('Amsler'); ?>:</b>
                        </span>
                    </div>
                    <?php 
                        if (!$AMSLEROD) $AMSLEROD= "0";
                        if (!$AMSLEROS) $AMSLEROS= "0";
                        if ($AMSLEROD || $AMSLEROS) {
                            $checked = 'value="0"'; 
                        } else {
                            $checked = 'value="1" checked';
                        }
                        
                    ?>
                    <input type="hidden" id="AMSLEROD" name="AMSLEROD" value='<?php echo attr($AMSLEROD); ?>'>
                    <input type="hidden" id="AMSLEROS" name="AMSLEROS" value='<?php echo attr($AMSLEROS); ?>'>
                    
                    <div style="position:absolute;text-align:right; top:0.03in;font-size:0.8em;right:0.1in;">
                        <label for="Amsler-Normal" class="input-helper input-helper--checkbox"><?php echo xlt('Normal'); ?></label>
                        <input id="Amsler-Normal" type="checkbox" <?php echo attr($checked); ?> tabindex="56">
                    </div>     
                    <div id="Lyr5.1" style="position: absolute; top: 0.2in; left: 0.12in; display:inline-block;border: none; padding: 0.0in;">
                        <table cellpadding=0 cellspacing=0 style="padding:0px;margin:auto;width:90%;align:auto;font-size:0.8em;text-align:center;">
                            <tr>
                                <td colspan=3 style="text-align:center;"><b><?php echo xlt('OD{{right eye}}'); ?></b>
                                </td>
                                <td></td>
                                <td colspan=3 style="text-align:center;"><b><?php echo xlt('OS{{left eye}}'); ?></b>
                                </td>
                            </tr>

                            <tr>
                                <td colspan=3>
                                    <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROD); ?>.jpg" id="AmslerOD" style="margin:0.05in;height:0.45in;width:0.5in;" /></td>
                                <td></td>
                                <td colspan=3>
                                    <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROS); ?>.jpg" id="AmslerOS" style="margin:0.05in;height:0.45in;width:0.5in;" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan=3 style="text-align:center;">
                                    <div class="AmslerValueOD" style="font-size:0.8em;text-decoration:italics;">
                                        <span id="AmslerODvalue"><?php echo text($AMSLEROD); ?></span>/5
                                    </div>
                                </td>
                                <td></td>
                                <td colspan=3 style="text-align:center;">
                                    <div class="AmslerValueOS" style="font-size:0.8em;text-decoration:italics;">
                                        <span id="AmslerOSvalue"><?php echo text($AMSLEROS); ?></span>/5
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- end of the Amsler box -->

                <!-- start of the Fields box -->
                <div id="LayerFields" class="vitals" style="width: 1.42in;height:1.05in;padding: 0.02in; border: 1.00pt solid #000000;">
                    <div  id="Lyr6.0" style="position:absolute;  left:0.05in; width: 1.4in; top:0.0in; padding: 2px; " dir="LTR">
                        <span class="top_left">
                            <b id="fields"><?php echo xlt('Fields{{visual fields}}'); ?>:</b>
                                   
                        </span>
                    </div> 
                        <?php 
                            // if the VF zone is checked, display it
                            // if ODVF1 = 1 (true boolean) the value="0" checked="true"
                            $bad='';
                            for ($z=1; $z <5; $z++) {
                                $ODzone = "ODVF".$z;
                                if ($$ODzone =='1') {
                                    $ODVF[$z] = 'checked value=1';
                                    $bad++;
                                } else {
                                    $ODVF[$z] = 'value=0';
                                }
                                $OSzone = "OSVF".$z;
                                if ($$OSzone =="1") {
                                    $OSVF[$z] = 'checked value=1';
                                    $bad++;
                                } else {
                                    $OSVF[$z] = 'value=0';
                                }
                            }
                            if (!$bad)  $VFFTCF = "checked";
                        ?>
                    <div style="position:relative;text-align:right; top:0.03in;font-size:0.8em;right:0.1in;">
                                <label for="FieldsNormal" class="input-helper input-helper--checkbox"><?php echo xlt('FTCF{{Full to count fingers}}'); ?></label>
                                <input id="FieldsNormal" type="checkbox" value="1" <?php echo attr($VFFTCF); ?>>
                    </div>   
                    <div id="Lyr5.1" style="position: relative; top: 0.08in; left: 0.0in; border: none; background: white">
                        <table cellpadding='1' cellspacing="1" style="font-size: 0.8em;margin:auto;"> 
                            <tr>    
                                <td style="width:0.6in;text-align:center;" colspan="2"><b><?php echo xlt('OD{{right eye}}'); ?></b><br /></td>

                                <td style="width:0.05in;"> </td>
                                <td style="width:0.6in;text-align:center;" colspan="2"><b><?php echo xlt('OS{{left eye}}'); ?></b></td>
                            </tr> 
                            <tr>    
                                <td style="border-right:1pt solid black;border-bottom:1pt solid black;">
                                    <input name="ODVF1" id="ODVF1" type="checkbox" <?php echo attr($ODVF['1'])?> class="hidden"> 
                                    <label for="ODVF1" class="input-helper input-helper--checkbox boxed"></label>
                                </td>
                                <td style="border-left:1pt solid black;border-bottom:1pt solid black;">
                                    <input name="ODVF2" id="ODVF2" type="checkbox" <?php echo attr($ODVF['2'])?> class="hidden"> 
                                    <label for="ODVF2" class="input-helper input-helper--checkbox boxed"></label>
                                </td>
                                <td></td>
                                <td style="border-right:1pt solid black;border-bottom:1pt solid black;">
                                    <input name="OSVF1" id="OSVF1" type="checkbox" <?php echo attr($OSVF['1']); ?> class="hidden" >
                                    <label for="OSVF1" class="input-helper input-helper--checkbox boxed"></label>
                                </td>
                                <td style="border-left:1pt solid black;border-bottom:1pt solid black;">
                                    <input name="OSVF2" id="OSVF2" type="checkbox" <?php echo attr($OSVF['2']); ?> class="hidden">                                                         
                                    <label for="OSVF2" class="input-helper input-helper--checkbox boxed"> </label>
                                </td>
                            </tr>       
                            <tr>    
                                <td style="border-right:1pt solid black;border-top:1pt solid black;">
                                    <input name="ODVF3" id="ODVF3" type="checkbox"  class="hidden" <?php echo attr($ODVF['3']); ?>> 
                                    <label for="ODVF3" class="input-helper input-helper--checkbox boxed"></label>
                                </td>
                                <td style="border-left:1pt solid black;border-top:1pt solid black;">
                                    <input  name="ODVF4" id="ODVF4" type="checkbox"  class="hidden" <?php echo attr($ODVF['4']); ?>>
                                    <label for="ODVF4" class="input-helper input-helper--checkbox boxed"></label>  
                                </td>
                                <td></td>
                                <td style="border-right:1pt solid black;border-top:1pt solid black;">
                                    <input name="OSVF3" id="OSVF3" type="checkbox"  class="hidden" <?php echo attr($OSVF['3']); ?>>
                                    <label for="OSVF3" class="input-helper input-helper--checkbox boxed"></label>
                                </td>
                                <td style="border-left:1pt solid black;border-top:1pt solid black;">
                                    <input name="OSVF4" id="OSVF4" type="checkbox"  class="hidden" <?php echo attr($OSVF['4']); ?>>
                                    <label for="OSVF4" class="input-helper input-helper--checkbox boxed"></label>
                                </td>                    
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- end of the Fields box -->

                <!-- start of the Pupils box -->
                <div id="LayerPupils" class="vitals" style="width: 192px;height: 1.05in; padding: 0.02in; border: 1.00pt solid #000000; ">  
                  <span class="top_left"><b id="pupils"><?php echo xlt('Pupils'); ?>:</b> </span>
                  <div style="position:absolute;text-align:right; top:0.03in;font-size:0.8em;right:0.1in;">
                              <label for="PUPIL_NORMAL" class="input-helper input-helper--checkbox"><?php echo xlt('Normal'); ?></label>
                              <input id="PUPIL_NORMAL" name="PUPIL_NORMAL" type="checkbox"  <?php if ($PUPIL_NORMAL =='1') echo 'checked="checked" value="1"'; ?>>
                  </div>
                  <div id="Lyr7.0" style="position: absolute; top: 0.3in; left: 0.15in; border: none;">
                    <table cellpadding=2 cellspacing=1 style="font-size: 0.8em;"> 
                      <tr>    
                          <th style="width:0.2in;"> &nbsp;
                          </th>
                          <th style="width:0.7in;padding: 0.1;"><?php echo xlt('size'); ?> (<?php echo xlt('mm{{millimeters}}'); ?>)
                          </th>
                          <th style="width:0.2in;padding: 0.1;"><?php echo xlt('react{{reactivity}}'); ?> 
                          </th>
                          <th style="width:0.2in;padding: 0.1;"><?php echo xlt('APD{{afferent pupillary defect}}'); ?>
                          </th>
                      </tr>
                      <tr>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?></b>
                          </td>
                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;" nowrap>
                              <input type="text" id ="ODPUPILSIZE1" name="ODPUPILSIZE1" style="width:0.25in;height:0.2in;" value="<?php echo attr($ODPUPILSIZE1); ?>">
                              <font>&#8594;</font>
                              <input type="text" id ="ODPUPILSIZE2" size="1" name="ODPUPILSIZE2" style="width:0.25in;height:0.2in;" value="<?php echo attr($ODPUPILSIZE2); ?>">
                          </td>
                          <td style="border-left:1pt solid black;border-right:1pt solid black;border-bottom:1pt solid black;">
                              <input type="text" style="margin: 0 2 0 2;width:0.3in;height:0.2in;" name='ODPUPILREACTIVITY' id='ODPUPILREACTIVITY' value='<?php echo attr($ODPUPILREACTIVITY); ?>'>
                          </td>
                          <td style="border-bottom:1pt solid black;">
                              <input type="text" style="margin: 0 2 0 2;width:0.20in;height:0.2in;" name="ODAPD" id='ODAPD' value='<?php echo attr($ODAPD); ?>'>
                          </td>
                      </tr>
                      <tr>    
                          <td><b><?php echo xlt('OS{{left eye}}'); ?></b>
                          </td>
                          <td style="border-right:1pt solid black;border-top:1pt solid black;">
                              <input type="text" size="1" name="OSPUPILSIZE1" id="OSPUPILSIZE1" style="width:0.25in;height:0.2in;" value="<?php echo attr($OSPUPILSIZE1); ?>">
                              <font>&#8594;</font>
                              <input type="text" size="1" name="OSPUPILSIZE2" id="OSPUPILSIZE2" style="width:0.25in;height:0.2in;" value="<?php echo attr($OSPUPILSIZE2); ?>">
                          </td>
                          <td style="border-left:1pt solid black;border-right:1pt solid black;border-top:1pt solid black;">
                              <input type=text style="margin: 0 2 0 2;width:0.3in;height:0.2in;" name='OSPUPILREACTIVITY' id='OSPUPILREACTIVITY' value="<?php echo attr($OSPUPILREACTIVITY); ?>">
                          </td>
                          <td style="border-top:1pt solid black;">
                              <input type="text" style="margin: 0 2 0 2;width:0.20in;height:0.2in;" name="OSAPD" id="OSAPD" value='<?php echo attr($OSAPD); ?>'>
                          </td>
                      </tr>
                    </table>
                  </div>  
                </div>
                <!-- end of the Pupils box -->
              
                <br />
                <!-- end of the CLINICAL BOX -->  
                <!-- start of slide down pupils_panel --> 
                <?php ($DIMODPUPILSIZE != '') ? ($display_dim_pupils_panel = "display") : ($display_dim_pupils_panel = "nodisplay"); ?>
                <div id="dim_pupils_panel" name="dim_pupils_panel" class="vitals <?php echo attr($display_dim_pupils_panel); ?>" 
                  style="z-index:99;text-align:center;height: 1.05in; width:2.3in;padding: 0.02in; border: 1.00pt solid #000000;float: right;">                     
                  <span class="top_left"><b id="pupils_DIM" style="width:100px;"><?php echo xlt('Pupils') ?>: <?php echo xlt('Dim'); ?></b> </span>
                  <div id="Lyr7.1" style="position: absolute; top: 0.3in; left: 0.1in; border: none;">
                    <table cellpadding="0" style="font-size: 0.9em;"> 
                      <tr>    
                          <th></th>
                          <th style="width:0.7in;padding: 0;"><?php echo xlt('size'); ?> (<?php echo xlt('mm{{millimeters}}'); ?>)
                          </th>
                      </tr>
                      <tr>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?></b>
                          </td>
                          <td style="border-bottom:1pt solid black;" nowrap>
                              <input type="text" size=1 id ="DIMODPUPILSIZE1" name="DIMODPUPILSIZE1" style="width:0.25in;height:0.2in;" value='<?php echo attr($DIMODPUPILSIZE1); ?>'>
                              <font style="font-size:1.0em;">&#8594;</font>
                              <input type="text" id ="DIMODPUPILSIZE2" size=1 name="DIMODPUPILSIZE2" style="width:0.25in;height:0.2in;" value='<?php echo attr($DIMODPUPILSIZE2); ?>'>
                          </td>
                      </tr>
                      <tr>    
                          <td ><b><?php echo xlt('OS{{left eye}}'); ?></b>
                          </td>
                          <td style="border-top:1pt solid black;">
                              <input type="text" size=1 name="DIMOSPUPILSIZE1" id="DIMOSPUPILSIZE1" style="width:0.25in;height:0.2in;" value="<?php echo attr($DIMOSPUPILSIZE1); ?>">
                              <font style="font-size:1.0em;">&#8594;</font>
                              <input type='text' size=1 name='DIMOSPUPILSIZE2' id='DIMOSPUPILSIZE2' style="width:0.25in;height:0.2in;" value='<?php echo attr($DIMOSPUPILSIZE2); ?>'>
                          </td>
                      </tr>
                    </table>
                  </div>   
                  <div style="position:absolute;  top: 0.2in; left: 1.1in; border: none;padding: auto;">
                      <b><?php echo xlt('Comments'); ?>:</b><br />
                      <textarea style="height:0.60in;width:95px;font-size:0.8em;" id="PUPIL_COMMENTS" name="PUPIL_COMMENTS"><?php echo text($PUPIL_COMMENTS); ?></textarea>
                  </div>
                </div> 
                <!-- end of slide down pupils_panel -->
              </div>
            
             <?php ($IOP ==1) ? ($display_IOP = "") : ($display_IOP = "nodisplay"); ?>
                  <div id="LayerVision_IOP" class="borderShadow <?php echo $display_IOP; ?>" style="display:inline-block;text-align:left;width:85%;">
                    <?php echo display_GlaucomaFlowSheet($pid); ?>
                  </div>
              <!-- start of the refraction box -->
              <span class="anchor" id="REFRACTION_anchor"></span>
              <div class="loading" id="EXAM_sections_loading" name="REFRACTION_sections_loading"><i class="fa fa-spinner fa-spin"></i></div> 
              <div id="REFRACTION_sections" name="REFRACTION_sections" class="row nodisplay clear_both" style="position:relative;display:inline-block;margin:5px auto;max-width:90%;margin:0px 100px;">
                <div id="LayerVision2" class="section" style="display:inline-block;text-align:center;width:100%;" >
                  <!-- start IOP chart section -->
                  <!-- end IOP chart section -->
                  <?php ($W ==1) ? ($display_W = "") : ($display_W = "nodisplay"); ?>
                  <div id="LayerVision_W" class="<?php echo $display_W; ?>" style="display:inline-block;">
                    <input type="hidden" id="W_1" name="W_1" value="1">
                    <div id="LayerVision_W_1" class="refraction current_W borderShadow">
                      <i class="closeButton fa fa-close" id="Close_W_1" name="Close_W_1" title="<?php echo xla('Close All Current Rx Panels and make this a Preference to stay closed'); ?>"></i>
                      <i onclick="top.restoreSession();  doscript('W','<?php echo attr($pid); ?>','<?php echo attr($encounter); ?>','1'); return false;" 
                       title="<?php echo xla("Dispense this Rx"); ?>" class="closeButton2 fa fa-print"></i>
                      <i onclick="top.restoreSession();  dispensed('<?php echo attr($pid); ?>');return false;" 
                         title="<?php echo xla("List of previously dispensed Spectacle and Contact Lens Rxs"); ?>" class="closeButton3 fa fa-list-ul"></i>
                      <table id="wearing_1" >
                        <tr>
                          <th colspan="9"><?php echo xlt('Current Glasses'); ?>:  
                            <i id="Add_Glasses" name="Add_Glasses" class="button btn" style="font-size:0.7em;margin-left:30px;background:#C9DBF2;"><?php echo xlt('Additonal Rx{{Additional glasses}}'); ?></i> 
                          </th>
                        </tr>
                        <tr style="font-weight:400;">
                          <td ></td>
                          <td></td>
                          <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                          <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                          <td><?php echo xlt('Axis'); ?></td>
                          <td><?php echo xlt('Prism'); ?></td>
                          <td><?php echo xlt('Acuity'); ?></td>
                          <td rowspan="7" class="right" style="padding:10 0 10 0;font-size:0.8em;width:100px;">
                            <b style="font-weight:600;text-decoration:underline;"><?php echo xlt('Rx Type{{Type of glasses prescription}}'); ?></b><br />
                            <label for="Single_1" class="input-helper input-helper--checkbox"><?php echo xlt('Single'); ?></label>
                            <input type="radio" value="0" id="Single_1" name="RX_TYPE_1" <?php if ($RX_TYPE_1 == '0') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Bifocal_1" class="input-helper input-helper--checkbox"><?php echo xlt('Bifocal'); ?></label>
                            <input type="radio" value="1" id="Bifocal_1" name="RX_TYPE_1" <?php if ($RX_TYPE_1 == '1') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Trifocal_1" class="input-helper input-helper--checkbox"><?php echo xlt('Trifocal'); ?></label>
                            <input type="radio" value="2" id="Trifocal_1" name="RX_TYPE_1" <?php if ($RX_TYPE_1 == '2') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Progressive_1" class="input-helper input-helper--checkbox"><?php echo xlt('Prog.'); ?></label>
                            <input type="radio" value="3" id="Progressive_1" name="RX_TYPE_1" <?php if ($RX_TYPE_1 == '3') echo 'checked="checked"'; ?> /></span><br />
                          </td>
                        </tr>
                        <tr>
                          <td rowspan="2"><?php echo xlt('Dist{{distance}}'); ?></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <td><input type="text" class="sphere" id="ODSPH_1" name="ODSPH_1"  value="<?php echo attr($ODSPH_1); ?>" tabindex="100"></td>
                          <td><input type=text class="cylinder" id="ODCYL_1" name="ODCYL_1"  value="<?php echo attr($ODCYL_1); ?>" tabindex="101"></td>
                          <td><input type=text class="axis" id="ODAXIS_1" name="ODAXIS_1" value="<?php echo attr($ODAXIS_1); ?>" tabindex="102"></td>
                          <td><input type=text class="prism" id="ODPRISM_1" name="ODPRISM_1" value="<?php echo attr($ODPRISM_1); ?>"></td>
                          <td><input type=text class="acuity" id="ODVA_1" name="ODVA_1" value="<?php echo attr($ODVA_1); ?>" tabindex="108"></td>
                        </tr>
                        <tr>
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td><input type=text class="sphere" id="OSSPH_1" name="OSSPH_1" value="<?php echo attr($OSSPH_1); ?>" tabindex="103"></td>
                          <td><input type=text class="cylinder" id="OSCYL_1" name="OSCYL_1" value="<?php echo attr($OSCYL_1); ?>" tabindex="104"></td>
                          <td><input type=text class="axis" id="OSAXIS_1" name="OSAXIS_1" value="<?php echo attr($OSAXIS_1); ?>" tabindex="105"></td>
                          <td><input type=text class="prism" id="OSPRISM_1" name="OSPRISM_1" value="<?php echo attr($OSPRISM_1); ?>"></td>
                          <td><input type=text class="acuity" id="OSVA_1" name="OSVA_1" value="<?php echo attr($OSVA_1); ?>" tabindex="109"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td rowspan=2><span style="text-decoration:none;"><?php echo xlt('Mid{{middle Rx strength}}'); ?>/<br /><?php echo xlt('Near'); ?></span></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <?php echo '<input type="hidden" name="RXStart" id="RXStart" value="'.$RX1.'">'; ?>
                          <td class="WMid"><input type=text class="presbyopia" id="ODMIDADD_1" name="ODMIDADD_1" value="<?php echo attr($WODADD1); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="ODADD_1" name="ODADD_1" value="<?php echo attr($ODADD_1); ?>" tabindex="106"></td>
                          <td></td>
                          <td></td>
                          <td><input class="jaeger" type=text id="ODNEARVA_1" name="ODNEARVA_1" value="<?php echo attr($ODNEARVA_1); ?>" tabindex="110"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td class="WMid"><input type=text class="presbyopia" id="OSMIDADD_1" name="OSMIDADD_1" value="<?php echo attr($OSMIDADD_1); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="OSADD_1" name="OSADD_1" value="<?php echo attr($OSADD_1); ?>" tabindex="107"></td>
                          <td></td>
                          <td></td>
                          <td><input class="jaeger" type=text id="OSNEARVA_1" name="OSNEARVA_1" value="<?php echo attr($OSNEARVA_1); ?>" tabindex="110"></td>
                        </tr>
                        <tr style="top:3.5in;">
                          <td colspan="2" style="text-align:right;vertical-align:top;top:0px;"><b><?php echo xlt('Comments'); ?>:</b>
                          </td>
                          <td colspan="4" class="up" style="text-align:left;vertical-align:middle;top:0px;"></td>
                        </tr>
                        <tr>
                          <td colspan="8">
                            <textarea style="width:100%;height:3.0em;" id="COMMENTS_1" name="COMMENTS_1"><?php echo text($WCOMMENTS); ?></textarea>     
                          </td>
                          <td colspan="2"> 
                          </td>
                        </tr>
                      </table>
                    </div>
                    <input type="hidden" id="W_2" name="W_2" value="<?php echo text($W_2); ?>">
                    <div id="LayerVision_W_2" class="refraction current_W borderShadow <?php echo attr($display_W_2); ?>">
                      <i class="closeButton fa fa-close" id="Close_W_2" name="Close_W_2" title="<?php echo xla('Close this panel and delete this Rx'); ?>"></i>
                      <i onclick="top.restoreSession();  doscript('W','<?php echo attr($pid); ?>','<?php echo attr($encounter); ?>','2'); return false;" 
                       title="<?php echo xla("Dispense Rx"); ?>" class="closeButton2 fa fa-print"></i>
                      <i onclick="top.restoreSession();  dispensed('<?php echo attr($pid); ?>');return false;" 
                         title="<?php echo xla("List of previously dispensed Spectacle and Contact Lens Rxs"); ?>" class="closeButton3 fa fa-list-ul"></i>
                      <table id="wearing_2" >
                        <tr>
                          <th colspan="9"><?php echo xlt('Current Glasses'); ?>: #2
                          </th>
                        </tr>
                        <tr style="font-weight:400;">
                          <td ></td>
                          <td></td>
                          <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                          <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                          <td><?php echo xlt('Axis'); ?></td>
                          <td><?php echo xlt('Prism'); ?></td>
                          <td><?php echo xlt('Acuity'); ?></td>
                          <td rowspan="7" class="right" style="padding:10 0 10 0;font-size:0.8em;width:100px;">
                            <b style="font-weight:600;text-decoration:underline;"><?php echo xlt('Rx Type{{Type of glasses prescription}}'); ?></b><br />
                            <label for="Single_2" class="input-helper input-helper--checkbox"><?php echo xlt('Single'); ?></label>
                            <input type="radio" value="0" id="Single_2" name="RX_TYPE_2" <?php if ($RX_TYPE_2 == '0') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Bifocal_2" class="input-helper input-helper--checkbox"><?php echo xlt('Bifocal'); ?></label>
                            <input type="radio" value="1" id="Bifocal_2" name="RX_TYPE_2" <?php if ($RX_TYPE_2 == '1') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Trifocal_2" class="input-helper input-helper--checkbox"><?php echo xlt('Trifocal'); ?></label>
                            <input type="radio" value="2" id="Trifocal_2" name="RX_TYPE_2" <?php if ($RX_TYPE_2 == '2') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Progressive_2" class="input-helper input-helper--checkbox"><?php echo xlt('Prog.'); ?></label>
                            <input type="radio" value="3" id="Progressive_2" name="RX_TYPE_2" <?php if ($RX_TYPE_2 == '3') echo 'checked="checked"'; ?> /></span><br />
                          </td>
                        </tr>
                        <tr>
                          <td rowspan="2"><?php echo xlt('Dist'); ?></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <td><input type="text" class="sphere" id="ODSPH_2" name="ODSPH_2"  value="<?php echo attr($ODSPH_2); ?>" tabindex="110"></td>
                          <td><input type="text" class="cylinder" id="ODCYL_2" name="ODCYL_2"  value="<?php echo attr($ODCYL_2); ?>" tabindex="111"></td>
                          <td><input type="text" class="axis" id="ODAXIS_2" name="ODAXIS_2" value="<?php echo attr($ODAXIS_2); ?>" tabindex="112"></td>
                          <td><input type="text" class="prism" id="ODPRISM_2" name="ODPRISM_2" value="<?php echo attr($ODPRISM_2); ?>"></td>
                          <td><input type="text" class="acuity" id="ODVA_2" name="ODVA_2" value="<?php echo attr($ODVA_2); ?>" tabindex="118"></td>
                        </tr>
                        <tr>
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td><input type=text class="sphere" id="OSSPH_2" name="OSSPH_2" value="<?php echo attr($OSSPH_2); ?>" tabindex="113"></td>
                          <td><input type=text class="cylinder" id="OSCYL_2" name="OSCYL_2" value="<?php echo attr($OSCYL_2); ?>" tabindex="114"></td>
                          <td><input type=text class="axis" id="OSAXIS_2" name="OSAXIS_2" value="<?php echo attr($OSAXIS_2); ?>" tabindex="115"></td>
                          <td><input type=text class="prism" id="OSPRISM_2" name="OSPRISM_2" value="<?php echo attr($OSPRISM_2); ?>"></td>
                          <td><input type=text class="acuity" id="OSVA_2" name="OSVA_2" value="<?php echo attr($OSVA_2); ?>" tabindex="119"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td rowspan=2><span style="text-decoration:none;"><?php echo xlt('Mid{{middle Rx strength}}'); ?>/<br /><?php echo xlt('Near'); ?></span></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <?php echo '<input type="hidden" name="RXStart_2" id="RXStart_2" value="'.$RX_TYPE_2.'">'; ?>
                          <td><input type=text class="presbyopia" id="ODMIDADD_2" name="ODMIDADD_2" value="<?php echo attr($ODMIDADD_2); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="ODADD_2" name="ODADD_2" value="<?php echo attr($ODADD_2); ?>" tabindex="116"></td>
                          <td></td>
                          <td></td>
                          <td><input type="text" class="jaeger" id="ODVANEAR_2" name="ODVANEAR_2" value="<?php echo attr($ODNEARVA_2); ?>" tabindex="120"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td class="WMid"><input type=text class="presbyopia" id="OSMIDADD_2" name="OSMIDADD_2" value="<?php echo attr($OSMIDADD_2); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="OSADD_2" name="OSADD_2" value="<?php echo attr($OSADD_2); ?>" tabindex="117"></td>
                          <td></td>
                          <td></td>
                          <td><input class="jaeger" type="text" id="OSNEARVA_2" name="OSNEARVA_2" value="<?php echo attr($OSNEARVA_2); ?>" tabindex="121"></td>
                        </tr>
                        <tr style="top:3.5in;">
                          <td colspan="2" style="text-align:right;vertical-align:top;top:0px;"><b><?php echo xlt('Comments'); ?>:</b>
                          </td>
                          <td colspan="4" class="up" style="text-align:left;vertical-align:middle;top:0px;"></td></tr>
                          <tr><td colspan="8">
                            <textarea style="width:100%;height:3.0em;" id="COMMENTS_2" name="COMMENTS_2"><?php echo text($COMMENTS_2); ?></textarea>     
                          </td>
                          <td colspan="2"> 
                          </td>
                        </tr>
                      </table>
                    </div>
                    <input type="hidden" id="W_3" name="W_3" value="<?php echo text($W_3); ?>">
                    <div id="LayerVision_W_3" class="refraction current_W borderShadow <?php echo attr($display_W_3); ?>">
                      <i class="closeButton fa fa-close" id="Close_W_3" name="Close_W_3" title="<?php echo xla('Close this panel and delete this Rx'); ?>"></i>
                      <i onclick="top.restoreSession();  doscript('W','<?php echo attr($pid); ?>','<?php echo attr($encounter); ?>','3'); return false;" 
                       title="<?php echo xla("Dispense Rx"); ?>" class="closeButton2 fa fa-print"></i>
                      <i onclick="top.restoreSession();  dispensed('<?php echo attr($pid); ?>');return false;" 
                         title="<?php echo xla("List of previously dispensed Spectacle and Contact Lens Rxs"); ?>" class="closeButton3 fa fa-list-ul"></i>
                      <table id="wearing_3" >
                        <tr>
                          <th colspan="9"><?php echo xlt('Current Glasses'); ?>: #3
                          </th>
                        </tr>
                        <tr style="font-weight:400;">
                          <td ></td>
                          <td></td>
                          <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                          <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                          <td><?php echo xlt('Axis'); ?></td>
                          <td><?php echo xlt('Prism'); ?></td>
                          <td><?php echo xlt('Acuity'); ?></td>
                          <td rowspan="7" class="right" style="padding:10 0 10 0;font-size:0.8em;width:100px;">
                            <b style="font-weight:600;text-decoration:underline;"><?php echo xlt('Rx Type{{Type of glasses prescription}}'); ?></b><br />
                            <label for="Single_3" class="input-helper input-helper--checkbox"><?php echo xlt('Single'); ?></label>
                            <input type="radio" value="0" id="Single_3" name="RX_TYPE_3" <?php if ($RX_TYPE_3 == '0') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Bifocal_3" class="input-helper input-helper--checkbox"><?php echo xlt('Bifocal'); ?></label>
                            <input type="radio" value="1" id="Bifocal_3" name="RX_TYPE_3" <?php if ($RX_TYPE_3 == '1') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Trifocal_3" class="input-helper input-helper--checkbox"><?php echo xlt('Trifocal'); ?></label>
                            <input type="radio" value="2" id="Trifocal_3" name="RX_TYPE_3" <?php if ($RX_TYPE_3 == '2') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Progressive_3" class="input-helper input-helper--checkbox"><?php echo xlt('Prog.'); ?></label>
                            <input type="radio" value="3" id="Progressive_3" name="RX_TYPE_3" <?php if ($RX_TYPE_3 == '3') echo 'checked="checked"'; ?> /></span><br />
                          </td>
                        </tr>
                        <tr>
                          <td rowspan="2"><?php echo xlt('Dist{{distance}}'); ?></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <td><input type=text class="sphere" id="ODSPH_3" name="ODSPH_3"  value="<?php echo attr($ODSPH_3); ?>" tabindex="130"></td>
                          <td><input type=text class="cylinder" id="ODCYL_3" name="ODCYL_3"  value="<?php echo attr($ODCYL_3); ?>" tabindex="131"></td>
                          <td><input type=text class="axis" id="ODAXIS_3" name="ODAXIS_3" value="<?php echo attr($ODAXIS_3); ?>" tabindex="132"></td>
                          <td><input type=text class="prism" id="ODPRISM_3" name="ODPRISM_3" value="<?php echo attr($ODPRISM_3); ?>"></td>
                          <td><input type=text class="acuity" id="ODVA_3" name="ODVA_3" value="<?php echo attr($ODVA_3); ?>" tabindex="138"></td>
                        </tr>
                        <tr>
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td><input type=text class="sphere" id="OSSPH_3" name="OSSPH_3" value="<?php echo attr($OSSPH_3); ?>" tabindex="133"></td>
                          <td><input type=text class="cylinder" id="OSCYL_3" name="OSCYL_3" value="<?php echo attr($OSCYL_3); ?>" tabindex="134"></td>
                          <td><input type=text class="axis" id="OSAXIS_3" name="OSAXIS_3" value="<?php echo attr($OSAXIS_3); ?>" tabindex="135"></td>
                          <td><input type=text class="prism" id="OSPRISM_3" name="OSPRISM_3" value="<?php echo attr($OSPRISM_3); ?>"></td>
                          <td><input type=text class="acuity" id="OSVA_3" name="OSVA_3" value="<?php echo attr($OSVA_3); ?>" tabindex="139"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td rowspan=2><span style="text-decoration:none;"><?php echo xlt('Mid{{middle Rx strength}}'); ?>/<br /><?php echo xlt('Near'); ?></span></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <?php echo '<input type="hidden" name="RXStart_3" id="RXStart_3" value="'.$RX_TYPE_3.'">'; ?>
                          <td><input type=text class="presbyopia" id="ODMIDADD_3" name="ODMIDADD_3" value="<?php echo attr($ODMIDADD_3); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="ODADD_3" name="ODADD_3" value="<?php echo attr($ODADD_3); ?>" tabindex="136"></td>
                          <td></td>
                          <td></td>
                          <td><input class="jaeger" type=text id="NEARODVA_3" name="NEARODVA_3" value="<?php echo attr($NEARODVA_3); ?>" tabindex="180"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td class="WMid"><input type=text class="presbyopia" id="OSMIDADD_3" name="OSMIDADD_3" value="<?php echo attr($OSMIDADD_3); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="OSADD_3" name="OSADD_3" value="<?php echo attr($OSADD_3); ?>" tabindex="137"></td>
                          <td></td>
                          <td></td>
                          <td><input class="jaeger" type=text id="NEAROSVA_3" name="NEAROSVA_3" value="<?php echo attr($NEAROSVA_3); ?>" tabindex="181"></td>
                        </tr>
                        <tr style="top:3.5in;">
                          <td colspan="2" style="text-align:right;vertical-align:top;top:0px;"><b><?php echo xlt('Comments'); ?>:</b>
                          </td>
                          <td colspan="4" class="up" style="text-align:left;vertical-align:middle;top:0px;"></td></tr>
                          <tr><td colspan="8">
                            <textarea style="width:100%;height:3.0em;" id="COMMENTS_3" name="COMMENTS_3"><?php echo text($COMMENTS_3); ?></textarea>     
                          </td>
                          <td colspan="2"> 
                          </td>
                        </tr>
                      </table>
                    </div>
                    <input type="hidden" id="W_4" name="W_4" value="<?php echo text($W_4); ?>">
                    <div id="LayerVision_W_4" class="refraction current_W borderShadow <?php echo attr($display_W_4); ?>">
                      <i class="closeButton fa fa-close" id="Close_W_4" name="Close_W_4" title="<?php echo xla('Close this panel and delete this Rx'); ?>"></i>
                      <i onclick="top.restoreSession();  doscript('W','<?php echo attr($pid); ?>','<?php echo attr($encounter); ?>','4'); return false;" 
                       title="<?php echo xla("Dispense Rx"); ?>" class="closeButton2 fa fa-print"></i>
                      <i onclick="top.restoreSession();  dispensed('<?php echo attr($pid); ?>');return false;" 
                         title="<?php echo xla("List of previously dispensed Spectacle and Contact Lens Rxs"); ?>" class="closeButton3 fa fa-list-ul"></i>
                      <table id="wearing_4" >
                        <tr>
                          <th colspan="9"><?php echo xlt('Current Glasses'); ?>: #4
                          </th>
                        </tr>
                        <tr style="font-weight:400;">
                          <td ></td>
                          <td></td>
                          <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                          <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                          <td><?php echo xlt('Axis'); ?></td>
                          <td><?php echo xlt('Prism'); ?></td>
                          <td><?php echo xlt('Acuity'); ?></td>
                          <td rowspan="7" class="right" style="padding:10 0 10 0;font-size:0.8em;width:100px;">
                            <b style="font-weight:600;text-decoration:underline;"><?php echo xlt('Rx Type{{Type of glasses prescription}}'); ?></b><br />
                            <label for="Single_4" class="input-helper input-helper--checkbox"><?php echo xlt('Single'); ?></label>
                            <input type="radio" value="0" id="Single_4" name="RX_TYPE_4" <?php if ($RX_TYPE_4 == '0') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Bifocal_4" class="input-helper input-helper--checkbox"><?php echo xlt('Bifocal'); ?></label>
                            <input type="radio" value="1" id="Bifocal_4" name="RX_TYPE_4" <?php if ($RX_TYPE_4 == '1') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Trifocal_4" class="input-helper input-helper--checkbox"><?php echo xlt('Trifocal'); ?></label>
                            <input type="radio" value="2" id="Trifocal_4" name="RX_TYPE_4" <?php if ($RX_TYPE_4 == '2') echo 'checked="checked"'; ?> /></span><br /><br />
                            <label for="Progressive_4" class="input-helper input-helper--checkbox"><?php echo xlt('Prog.'); ?></label>
                            <input type="radio" value="3" id="Progressive_4" name="RX_TYPE_4" <?php if ($RX_TYPE_4 == '3') echo 'checked="checked"'; ?> /></span><br />
                          </td>
                        </tr>
                        <tr>
                          <td rowspan="2"><?php echo xlt('Dist{{diatance}}'); ?></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <td><input type=text class="sphere" id="ODSPH_4" name="ODSPH_4"  value="<?php echo attr($ODSPH_4); ?>" tabindex="150"></td>
                          <td><input type=text class="cylinder" id="ODCYL_4" name="ODCYL_4"  value="<?php echo attr($ODCYL_4); ?>" tabindex="151"></td>
                          <td><input type=text class="axis" id="ODAXIS_4" name="ODAXIS_4" value="<?php echo attr($ODAXIS_4); ?>" tabindex="152"></td>
                          <td><input type=text class="prism" id="ODPRISM_4" name="ODPRISM_4" value="<?php echo attr($ODPRISM_4); ?>"></td>
                          <td><input type=text class="acuity" id="ODVA_4" name="ODVA_4" value="<?php echo attr($ODVA_4); ?>" tabindex="158"></td>
                        </tr>
                        <tr>
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td><input type=text class="sphere" id="OSSPH_4" name="OSSPH_4" value="<?php echo attr($OSSPH_4); ?>" tabindex="153"></td>
                          <td><input type=text class="cylinder" id="OSCYL_4" name="OSCYL_4" value="<?php echo attr($OSCYL_4); ?>" tabindex="154"></td>
                          <td><input type=text class="axis" id="OSAXIS_4" name="OSAXIS_4" value="<?php echo attr($OSAXIS_4); ?>" tabindex="155"></td>
                          <td><input type=text class="prism" id="OSPRISM_4" name="OSPRISM_4" value="<?php echo attr($OSPRISM_4); ?>"></td>
                          <td><input type=text class="acuity" id="OSVA_4" name="OSVA_4" value="<?php echo attr($OSVA_4); ?>" tabindex="159"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td rowspan=2><span style="text-decoration:none;"><?php echo xlt('Mid{{middle Rx strength}}'); ?>/<br /><?php echo xlt('Near'); ?></span></td>    
                          <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                          <?php echo '<input type="hidden" name="RXStart_4" id="RXStart_4" value="'.$RX_TYPE_4.'">'; ?>
                          <td><input type=text class="presbyopia" id="ODMIDADD_4" name="ODMIDADD_4" value="<?php echo attr($ODMIDADD_4); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="ODADD_4" name="ODADD_4" value="<?php echo attr($ODADD_4); ?>" tabindex="156"></td>
                          <td></td>
                          <td></td>
                          <td><input class="jaeger" type=text id="NEARODVA_4" name="NEARODVA_4" value="<?php echo attr($NEARODVA_4); ?>" tabindex="160"></td>
                        </tr>
                        <tr class="WNEAR">
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td class="WMid"><input type=text class="presbyopia" id="OSMIDADD_4" name="OSMIDADD_4" value="<?php echo attr($OSMIDADD_4); ?>"></td>
                          <td class="WAdd2"><input type=text class="presbyopia" id="OSADD_4" name="OSADD_4" value="<?php echo attr($OSADD_4); ?>" tabindex="157"></td>
                          <td></td>
                          <td></td>
                          <td><input class="jaeger" type=text id="NEAROSVA_4" name="NEAROSVA_4" value="<?php echo attr($NEAROSVA_4); ?>" tabindex="161"></td>
                        </tr>
                        <tr style="top:3.5in;">
                          <td colspan="2" style="text-align:right;vertical-align:top;top:0px;"><b><?php echo xlt('Comments'); ?>:</b>
                          </td>
                          <td colspan="4" class="up" style="text-align:left;vertical-align:middle;top:0px;"></td></tr>
                          <tr><td colspan="8">
                            <textarea style="width:100%;height:3.0em;" id="COMMENTS_4" name="COMMENTS_4"><?php echo text($COMMENTS_4); ?></textarea>     
                          </td>
                          <td colspan="2"> 
                          </td>
                        </tr>
                      </table>
                    </div>
                  </div>

                  <?php ($MR==1) ? ($display_AR = "") : ($display_AR = "nodisplay");?>
                  <div id="LayerVision_MR" class="refraction manifest borderShadow <?php echo $display_AR; ?>">
                    <i onclick="top.restoreSession();  refractions('<?php echo attr($pid); ?>');return false;" 
                     title="<?php echo xla("List of previous refractions"); ?>" class="closeButton3 fa fa-list-ul"></i>
                    <span class="closeButton2 fa fa-print"  style="margin:0 7;" title="<?php echo xla('Dispense this Rx'); ?>" onclick="top.restoreSession();doscript('MR',<?php echo attr($pid); ?>,<?php echo attr($encounter); ?>);return false;"></span>
                    <span class="closeButton fa  fa-close" id="Close_MR" name="Close_MR" title="<?php echo xla('Close this panel and make this a Preference to stay closed'); ?>"></span>
                    <table id="dry_wet_refraction">
                      <th colspan="5"><?php echo xlt('Manifest (Dry) Refraction'); ?></th>
                      <th NOWRAP colspan="2">
                        <input type="checkbox" name="BALANCED" id="Balanced" value="on" <?php if ($BALANCED =='on') echo "checked='checked'"; ?> tabindex="182">
                        <label for="Balanced" class="input-helper input-helper--checkbox"><?php echo xlt('Balanced'); ?></label>
                      </th>

                      <tr>
                        <td></td>
                        <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                        <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                        <td><?php echo xlt('Axis'); ?></td>
                        <td><?php echo xlt('Acuity'); ?></td>
                        <td><?php echo xlt('ADD'); ?></td>
                        <td><?php echo xlt('Jaeger'); ?></td>
                        <td><?php echo xlt('Prism'); ?></td>
                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input type=text id="MRODSPH" name="MRODSPH" value="<?php echo attr($MRODSPH); ?>" tabindex="170"></td>
                        <td><input type=text id="MRODCYL" name="MRODCYL" value="<?php echo attr($MRODCYL); ?>" tabindex="171"></td>
                        <td><input type=text id="MRODAXIS"  name="MRODAXIS" value="<?php echo attr($MRODAXIS); ?>" tabindex="172"></td>
                        <td><input type=text id="MRODVA"  name="MRODVA" value="<?php echo attr($MRODVA); ?>" tabindex="176"></td>
                        <td><input type=text id="MRODADD"  name="MRODADD" value="<?php echo attr($MRODADD); ?>" tabindex="178"></td>
                        <td><input class="jaeger" type=text id="MRNEARODVA"  name="MRNEARODVA" value="<?php echo attr($MRNEARODVA); ?>" tabindex="180"> </td>
                        <td><input type=text id="MRODPRISM"  name="MRODPRISM" value="<?php echo attr($MRODPRISM); ?>"></td>
                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                        <td><input type=text id="MROSSPH" name="MROSSPH" value="<?php echo attr($MROSSPH); ?>" tabindex="173"></td>
                        <td><input type=text id="MROSCYL" name="MROSCYL" value="<?php echo attr($MROSCYL); ?>" tabindex="174"></td>
                        <td><input type=text id="MROSAXIS"  name="MROSAXIS" value="<?php echo attr($MROSAXIS); ?>" tabindex="175"></td>
                        <td><input type=text id="MROSVA"  name="MROSVA" value="<?php echo attr($MROSVA); ?>" tabindex="177"></td>
                        <td><input type=text id="MROSADD"  name="MROSADD" value="<?php echo attr($MROSADD); ?>" tabindex="179"></td>
                        <td><input class="jaeger" type=text id="MRNEAROSVA"  name="MRNEAROSVA" value="<?php echo attr($MRNEAROSVA); ?>" tabindex="181"></td>
                        <td><input type=text id="MROSPRISM"  name="MROSPRISM" value="<?php echo attr($MROSPRISM); ?>"></td>
                      </tr>
                    </table>
                    <table >
                      <th colspan=6 style="padding-top:10px;"><?php echo xlt('Cycloplegic (Wet) Refraction'); ?></th>
                      <th colspan=4 style="text-align:right;"><i title="<?php echo xla("Dispense Rx"); ?>" class="fa fa-print" onclick="top.restoreSession();doscript('CR',<?php echo attr($pid); ?>,<?php echo attr($encounter); ?>);return false;"></i></th>

                      <tr>
                        <td></td>
                        <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                        <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                        <td><?php echo xlt('Axis'); ?></td>
                        <td><?php echo xlt('Acuity'); ?></td>
                        <td colspan="1" style="text-align:left;width:75px;">
                          <input type="radio" name="WETTYPE" id="Flash" value="Flash" <?php if ($WETTYPE == "Flash") echo "checked='checked'"; ?>/>
                          <label for="Flash" class="input-helper input-helper--checkbox"><?php echo xlt('Flash'); ?></label>
                        </td>
                        <td style="font-size:0.7em;"><?php echo xlt('IOP Dilated{{Dilated Intraocular Pressure}}'); ?>
                          <input type="hidden" name="IOPPOSTTIME" id="IOPPOSTTIME" value="">
                        </td>

                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input type=text id="CRODSPH" name="CRODSPH" value="<?php echo attr($CRODSPH); ?>" tabindex="183"></td>
                        <td><input type=text id="CRODCYL" name="CRODCYL" value="<?php echo attr($CRODCYL); ?>" tabindex="184"></td>
                        <td><input type=text id="CRODAXIS" name="CRODAXIS" value="<?php echo attr($CRODAXIS); ?>" tabindex="185"></td>
                        <td><input type=text id="CRODVA" name="CRODVA"  value="<?php echo attr($CRODVA); ?>" tabindex="189"></td>
                        <td colspan="1" style="text-align:left;">
                          <input type="radio" name="WETTYPE" id="Auto" value="Auto" <?php if ($WETTYPE == "Auto") echo "checked='checked'"; ?>>
                          <label for="Auto" class="input-helper input-helper--checkbox"><?php echo xlt('Auto{{autorefraction}}'); ?></label>
                        </td>
                        <td><input type=text id="ODIOPPOST" name="ODIOPPOST"  value="<?php echo attr($ODIOPPOST); ?>">
                        </tr>
                        <tr>
                          <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                          <td><input type=text id="CROSSPH" name="CROSSPH" value="<?php echo attr($CROSSPH); ?>" tabindex="186"></td>
                          <td><input type=text id="CROSCYL" name="CROSCYL" value="<?php echo attr($CROSCYL); ?>" tabindex="187"></td>
                          <td><input type=text id="CROSAXIS" name="CROSAXIS" value="<?php echo attr($CROSAXIS); ?>" tabindex="188"></td>
                          <td><input type=text id="CROSVA" name="CROSVA" value="<?php echo attr($CROSVA); ?>" tabindex="190"></td>
                          <td colspan="1" style="text-align:left;">
                            <input type="radio" name="WETTYPE" id="Manual" value="Manual" <?php if ($WETTYPE == "Manual") echo "checked='checked'"; ?>>
                            <label for="Manual" class="input-helper input-helper--checkbox"><?php echo xlt('Manual'); ?></label>
                          </td>
                          <td><input type=text id="OSIOPPOST" name="OSIOPPOST"  value="<?php echo attr($OSIOPPOST); ?>"></td>
                        </tr>
                    </table>
                  </div>

                  <?php ($CR==1)  ? ($display_Cyclo = "") : ($display_Cyclo = "nodisplay"); ?>
                  <div id="LayerVision_CR" class="refraction autoref borderShadow <?php echo $display_Cyclo; ?>">
                    <i title="<?php echo xla('Dispense this Rx'); ?>" class="closeButton2 fa fa-print" onclick="top.restoreSession();doscript('AR',<?php echo attr($pid); ?>,<?php echo attr($encounter); ?>);return false;"></i>
                    <span title="<?php echo xla('Close this panel and make this a Preference to stay closed'); ?>" class="closeButton fa  fa-close" id="Close_CR" name="Close_CR"></span>
                    <table id="autorefraction">
                      <th colspan="9"><?php echo xlt('Auto Refraction'); ?></th>
                      <tr>
                        <td></td>
                        <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                        <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                        <td><?php echo xlt('Axis'); ?></td>
                        <td><?php echo xlt('Acuity'); ?></td>
                        <td><?php echo xlt('ADD'); ?></td>
                        <td><?php echo xlt('Jaeger{{Near Acuity Type Jaeger}}'); ?></td>
                        <td><?php echo xlt('Prism'); ?></td>
                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input type=text id="ARODSPH" name="ARODSPH" value="<?php echo attr($ARODSPH); ?>" tabindex="220"></td>
                        <td><input type=text id="ARODCYL" name="ARODCYL" value="<?php echo attr($ARODCYL); ?>" tabindex="221"></td>
                        <td><input type=text id="ARODAXIS" name="ARODAXIS" value="<?php echo attr($ARODAXIS); ?>" tabindex="222"></td>
                        <td><input type=text id="ARODVA" name="ARODVA" value="<?php echo attr($ARODVA); ?>" tabindex="228"></td>
                        <td><input type=text id="ARODADD" name="ARODADD" value="<?php echo attr($ARODADD); ?>" tabindex="226"></td>
                        <td><input class="jaeger" type=text id="ARNEARODVA" name="ARNEARODVA" value="<?php echo attr($ARNEARODVA); ?>"></td>
                        <td><input type=text id="ARODPRISM" name="ARODPRISM" value="<?php echo attr($ARODPRISM); ?>"></td>
                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                        <td><input type=text id="AROSSPH" name="AROSSPH" value="<?php echo attr($AROSSPH); ?>" tabindex="223"></td>
                        <td><input type=text id="AROSCYL" name="AROSCYL" value="<?php echo attr($AROSCYL); ?>" tabindex="224"></td>
                        <td><input type=text id="AROSAXIS" name="AROSAXIS" value="<?php echo attr($AROSAXIS); ?>" tabindex="225"></td>
                        <td><input type=text id="AROSVA" name="AROSVA" value="<?php echo attr($AROSVA); ?>" tabindex="229"></td>
                        <td><input type=text id="AROSADD" name="AROSADD" value="<?php echo attr($AROSADD); ?>" tabindex="227"></td>
                        <td><input class="jaeger" type=text id="ARNEAROSVA" name="ARNEAROSVA" value="<?php echo attr($ARNEAROSVA); ?>"></td>
                        <td><input type=text id="AROSPRISM" name="AROSPRISM" value="<?php echo attr($AROSPRISM); ?>"></td>
                      </tr>
                      <tr>
                        <td colspan="2" style="vertical-align:bottom;"><b><?php echo xlt('Comments'); ?>:</b></td>
                        <td colspan="4"></td>

                      </tr>
                      <tr>
                        <td colspan="9" style="text-align:center;"><textarea id="CRCOMMENTS" name="CRCOMMENTS" style="width:98%;height:3.5em;"><?php echo attr($CRCOMMENTS); ?></textarea>
                        </td>
                      </tr>
                    </table>
                  </div>

                  <?php ($CTL==1) ? ($display_CTL = "") : ($display_CTL = "nodisplay"); ?>
                  <div id="LayerVision_CTL" class="refraction CTL borderShadow <?php echo $display_CTL; ?>">
                    <i title="<?php echo xla('Dispense this RX'); ?>" class="closeButton2 fa fa-print" onclick="top.restoreSession();doscript('CTL',<?php echo attr($pid); ?>,<?php echo attr($encounter); ?>);return false;"></i>
                    <span title="<?php echo xla('Close this panel and make this a Preference to stay closed'); ?>" class="closeButton fa  fa-close" id="Close_CTL" name="Close_CTL"></span>
                    <table id="CTL" style="width:100%;">
                      <th colspan="9"><?php echo xlt('Contact Lens Refraction'); ?></th>
                      <tr>
                        <td style="text-align:center;">
                          <div style="box-shadow: 1px 1px 2px #888888;border-radius: 8px; margin: 0 auto 3; position:inline-block; padding-bottom: 3; border: 1.00pt solid #000000; ">
                            <table>
                              <tr class="" style="vertical-align:bottom;">
                                <td></td>
                                <td><a href="<?php echo $GLOBALS['webroot']; ?>/interface/super/edit_list.php?list_id=CTLManufacturer" target="RTop" 
                                  title="<?php echo xla('Click here to Edit the Manufacter List'); ?>" 
                                  name="CTL" style="color:black;"><?php echo xlt('Manufacturer'); ?> <i class="fa fa-pencil fa-fw"></i> </a>
                                </td>
                                <td><a href="<?php echo $GLOBALS['webroot']; ?>/interface/super/edit_list.php?list_id=CTLSupplier" target="RTop" 
                                  title="<?php echo xla('Click here to Edit the Supplier List'); ?>" 
                                  name="CTL" style="color:black;"><?php echo xlt('Supplier'); ?> <i class="fa fa-pencil fa-fw"></i> </a>
                                </td>
                                <td><a href="<?php echo $GLOBALS['webroot']; ?>/interface/super/edit_list.php?list_id=CTLBrand" target="RTop" 
                                  title="<?php echo xla('Click here to Edit the Contact Lens Brand List'); ?>" 
                                  name="CTL" style="color:black;"><?php echo xlt('Brand'); ?> <i class="fa fa-pencil fa-fw"></i> </a>
                                </td>
                              </tr>
                              <tr>
                                <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                                <td>
                                  <!--  Pull from CTL data from list_options which user populates the usual way -->
                                  <?php 
                                                      //build manufacturer list from list_options::list_id::CTLManufacturer
                                  $query = "select * from list_options where list_id like 'CTLManufacturer' order by seq";
                                  $CTLMANUFACTURER_data =sqlStatement($query);
                                  while ($row = sqlFetchArray($CTLMANUFACTURER_data)) {
                                    $CTLMANUFACTURER_list_OD .= '<option value="'.attr($row['option_id']).'"';
                                    if ($CTLMANUFACTUREROD == $row['option_id']) $CTLMANUFACTURER_list_OD .= "selected";
                                    $CTLMANUFACTURER_list_OD .= '>'.text(substr($row['title'],0,12)).'</option>
                                    ' ; 
                                    $CTLMANUFACTURER_list_OS .= '<option value="'.attr($row['option_id']).'"';
                                    if ($CTLMANUFACTUREROS == $row['option_id']) $CTLMANUFACTURER_list_OS .= "selected";
                                    $CTLMANUFACTURER_list_OS .= '>'.text(substr($row['title'],0,12)).'</option>
                                    ' ; 
                                  }
                                                      //build supplier list from list_options::list_id::CTLSupplier
                                  $query = "select * from list_options where list_id like 'CTLSupplier' order by seq";
                                  $CTLSUPPLIER_data =sqlStatement($query);
                                  while ($row = sqlFetchArray($CTLSUPPLIER_data)) {
                                    $CTLSUPPLIER_list_OD .= '<option value="'.attr($row['option_id']).'"';
                                    if ($CTLSUPPLIEROD == $row['option_id']) $CTLSUPPLIER_list_OD .= "selected";
                                    $CTLSUPPLIER_list_OD .= '>'.text(substr($row['title'],0,10)).'</option>
                                    ' ; 
                                    $CTLSUPPLIER_list_OS .= '<option value="'.attr($row['option_id']).'"';
                                    if ($CTLSUPPLIEROS == $row['option_id']) $CTLSUPPLIER_list_OS .= "selected";
                                    $CTLSUPPLIER_list_OS .= '>'.text(substr($row['title'],0,10)).'</option>
                                    ' ; 
                                  }
                                                      //build manufacturer list from list_options::list_id::CTLManufacturer
                                  $query = "select * from list_options where list_id like 'CTLBrand' order by seq";
                                  $CTLBRAND_data =sqlStatement($query);
                                  while ($row = sqlFetchArray($CTLBRAND_data)) {
                                    $CTLBRAND_list_OD .= '<option value="'.attr($row['option_id']).'"';
                                    if ($CTLBRANDOD == $row['option_id']) $CTLBRAND_list_OD .= "selected";
                                    $CTLBRAND_list_OD .= '>'.text(substr($row['title'],0,15)).'</option>
                                    ' ; 
                                    $CTLBRAND_list_OS .= '<option value="'.attr($row['option_id']).'"';
                                    if ($CTLBRANDOS == $row['option_id']) $CTLBRAND_list_OS .= "selected";
                                    $CTLBRAND_list_OS .= '>'.text(substr($row['title'],0,15)).'</option>
                                    ' ; 
                                  }
                                  ?>
                                  <select id="CTLMANUFACTUREROD" name="CTLMANUFACTUREROD" tabindex="230">
                                    <option></option>
                                    <?php echo $CTLMANUFACTURER_list_OD; ?>
                                  </select>
                                </td>
                                <td>
                                  <select id="CTLSUPPLIEROD" name="CTLSUPPLIEROD" tabindex="231">
                                    <option></option>
                                    <?php echo $CTLSUPPLIER_list_OD; ?>
                                  </select>
                                </td>
                                <td>
                                  <select id="CTLBRANDOD" name="CTLBRANDOD" tabindex="232">
                                    <option></option>
                                    <?php echo $CTLBRAND_list_OD; ?>
                                  </select>
                                </td>
                              </tr>
                              <tr >
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td>
                                  <select id="CTLMANUFACTUREROS" name="CTLMANUFACTUREROS" tabindex="233">
                                    <option></option>
                                    <?php echo $CTLMANUFACTURER_list_OS; ?>
                                  </select>
                                </td>
                                <td>
                                  <select id="CTLSUPPLIEROS" name="CTLSUPPLIEROS" tabindex="234">
                                    <option></option>
                                    <?php echo $CTLSUPPLIER_list_OS; ?>
                                  </select>
                                </td>
                                <td>
                                  <select id="CTLBRANDOS" name="CTLBRANDOS" tabindex="235">
                                    <option></option>
                                    <?php echo $CTLBRAND_list_OS; ?>
                                  </select>
                                </td>
                              </tr>
                            </table>
                          </div>
                        </td>
                      </tr>
                    </table>
                    <table>
                      <tr>
                        <td></td>
                        <td><?php echo xlt('Sph{{Sphere}}'); ?></td>
                        <td><?php echo xlt('Cyl{{Cylinder}}'); ?></td>
                        <td><?php echo xlt('Axis'); ?></td>
                        <td><?php echo xlt('BC{{Base Curve}}'); ?></td>
                        <td><?php echo xlt('Diam{{Diameter}}'); ?></td>
                        <td><?php echo xlt('ADD'); ?></td>
                        <td><?php echo xlt('Acuity'); ?></td>
                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input type=text id="CTLODSPH" name="CTLODSPH" value="<?php echo attr($CTLODSPH); ?>" tabindex="236"></td>
                        <td><input type=text id="CTLODCYL" name="CTLODCYL" value="<?php echo attr($CTLODCYL); ?>" tabindex="240"></td>
                        <td><input type=text id="CTLODAXIS" name="CTLODAXIS" value="<?php echo attr($CTLODAXIS); ?>" tabindex="241"></td>
                        <td><input type=text id="CTLODBC" name="CTLODBC" value="<?php echo attr($CTLODBC); ?>" tabindex="237"></td>
                        <td><input type=text id="CTLODDIAM" name="CTLODDIAM" value="<?php echo attr($CTLODDIAM); ?>" tabindex="238"></td>
                        <td><input type=text id="CTLODADD" name="CTLODADD" value="<?php echo attr($CTLODADD); ?>" tabindex="242"></td>
                        <td><input type=text id="CTLODVA" name="CTLODVA" value="<?php echo attr($CTLODVA); ?>" tabindex="239"></td>
                      </tr>
                      <tr >
                        <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                        <td><input type=text id="CTLOSSPH" name="CTLOSSPH" value="<?php echo attr($CTLOSSPH); ?>" tabindex="243"></td>
                        <td><input type=text id="CTLOSCYL" name="CTLOSCYL" value="<?php echo attr($CTLOSCYL); ?>" tabindex="247"></td>
                        <td><input type=text id="CTLOSAXIS" name="CTLOSAXIS" value="<?php echo attr($CTLOSAXIS); ?>" tabindex="248"></td>
                        <td><input type=text id="CTLOSBC" name="CTLOSBC" value="<?php echo attr($CTLOSBC); ?>" tabindex="244"></td>
                        <td><input type=text id="CTLOSDIAM" name="CTLOSDIAM" value="<?php echo attr($CTLOSDIAM); ?>" tabindex="245"></td>
                        <td><input type=text id="CTLOSADD" name="CTLOSADD" value="<?php echo attr($CTLOSADD); ?>" tabindex="249"></td>
                        <td><input type=text id="CTLOSVA" name="CTLOSVA" value="<?php echo attr($CTLOSVA); ?>" tabindex="246"></td>
                      </tr>
                      <tr>
                        <td colspan="2" class="right bold">
                          <?php echo xlt('Comments'); ?>:
                        </td>
                        <td colspan="6" style="text-align:left;">
                          <textarea style="width:95%;height:30px;" name="CTL_COMMENTS" id="CTL_COMMENTS" rows="1" tabindex="250"><?php echo text($CTL_COMMENTS); ?></textarea>
                        </td>
                      </tr>
                    </table>
                  </div>

                  <?php ($ADDITIONAL==1) ? ($display_Add = "") : ($display_Add = "nodisplay"); ?>
                  <div id="LayerVision_ADDITIONAL" class="refraction borderShadow <?php echo $display_Add; ?>">
                    <span title="<?php echo xla('Close and make this a Preference to stay closed'); ?>" class="closeButton fa  fa-close" id="Close_ADDITIONAL" name="Close_ADDITIONAL"></span>

                    <table id="Additional">
                      <th colspan=9><?php echo xlt('Additional Data Points'); ?></th>
                      <tr><td></td>
                        <td title="<?php echo xla('Pinhole Vision'); ?>"><?php echo xlt('PH{{pinhole acuity}}'); ?></td>
                        <td title="<?php echo xla('Potential Acuity Meter'); ?>"><?php echo xlt('PAM{{Potential Acuity Meter}}'); ?></td>
                        <td title="<?php echo xla('Laser Interferometry Acuity'); ?>"><?php echo xlt('LI{{Laser Interferometry Acuity}}'); ?></td>
                        <td title="<?php echo xla('Brightness Acuity testing'); ?>"><?php echo xlt('BAT{{Brightness Acuity testing}}'); ?></td>
                        <td><?php echo xlt('K1{{Keratometry 1}}'); ?></td>
                        <td><?php echo xlt('K2{{Keratometry 2}}'); ?></td>
                        <td><?php echo xlt('Axis'); ?></td>
                      </tr>
                      <tr><td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input type=text id="PHODVA" name="PHODVA" title="<?php echo xla('Pinhole Vision'); ?>" value="<?php echo attr($PHODVA); ?>" tabindex="251"></td>
                        <td><input type=text id="PAMODVA" name="PAMODVA" title="<?php echo xla('Potential Acuity Meter'); ?>" value="<?php echo attr($PAMODVA); ?>" tabindex="253"></td>
                        <td><input type=text id="LIODVA" name="LIODVA"  title="<?php echo xla('Laser Interferometry'); ?>" value="<?php echo attr($LIODVA); ?>" tabindex="255"></td>
                        <td><input type=text id="GLAREODVA" name="GLAREODVA" title="<?php echo xla('Brightness Acuity Testing'); ?>" value="<?php echo attr($GLAREODVA); ?>" tabindex="257"></td>
                        <td><input type=text id="ODK1" name="ODK1" value="<?php echo attr($ODK1); ?>" tabindex="259"></td>
                        <td><input type=text id="ODK2" name="ODK2" value="<?php echo attr($ODK2); ?>" tabindex="260"></td>
                        <td><input type=text id="ODK2AXIS" name="ODK2AXIS" value="<?php echo attr($ODK2AXIS); ?>" tabindex="261"></td>
                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                        <td><input type=text id="PHOSVA" name="PHOSVA" title="<?php echo xla('Pinhole Vision'); ?>" value="<?php echo attr($PHOSVA); ?>" tabindex="252"></td>
                        <td><input type=text id="PAMOSVA" name="PAMOSVA" title="<?php echo xla('Potential Acuity Meter'); ?>" value="<?php echo attr($PAMOSVA); ?>" tabindex="254"></td>
                        <td><input type=text id="LIOSVA" name="LIOSVA" title="<?php echo xla('Laser Interferometry'); ?>" value="<?php echo attr($LIOSVA); ?>" tabindex="256"></td>
                        <td><input type=text id="GLAREOSVA" name="GLAREOSVA" title="<?php echo xla('Brightness Acuity Testing'); ?>" value="<?php echo attr($GLAREOSVA); ?>"  tabindex="258"></td>
                        <td><input type=text id="OSK1" name="OSK1" value="<?php echo attr($OSK1); ?>" tabindex="262"></td>
                        <td><input type=text id="OSK2" name="OSK2" value="<?php echo attr($OSK2); ?>" tabindex="263"></td>
                        <td><input type=text id="OSK2AXIS" name="OSK2AXIS" value="<?php echo attr($OSK2AXIS); ?>" tabindex="264"></td>
                      </tr>
                      <tr><td>&nbsp;</td></tr>
                      <tr>
                        <td></td>
                        <td title="<?php echo xla('Axial Length'); ?>"><?php echo xlt('AxLength{{Axial Length}}'); ?></td>
                        <td title="<?php echo xla('Anterior Chamber Depth'); ?>"><?php echo xlt('ACD{{Anterior Chamber Depth}}'); ?></td>
                        <td title="<?php echo xla('Inter-pupillary distance'); ?>"><?php echo xlt('PD{{Inter-pupillary distance}}'); ?></td>
                        <td title="<?php echo xla('Lens Thickness'); ?>"><?php echo xlt('LT{{Lens Thickness}}'); ?></td>
                        <td title="<?php echo xla('White-to-white'); ?>"><?php echo xlt('W2W{{White-to-white}}'); ?></td>
                        <td title="<?php echo xla('Equivalent contact lens power at the corneal level'); ?>"><?php echo xlt('ECL{{equivalent contact lens power at the corneal level}}'); ?></td>
                        <!-- <td><?php echo xlt('pend'); ?></td> -->
                      </tr>
                      <tr><td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input type=text id="ODAXIALLENGTH" name="ODAXIALLENGTH"  value="<?php echo attr($ODAXIALLENGTH); ?>"  tabindex="265"></td>
                        <td><input type=text id="ODACD" name="ODACD"  value="<?php echo attr($ODACD); ?>" tabindex="267"></td>
                        <td><input type=text id="ODPDMeasured" name="ODPDMeasured"  value="<?php echo attr($ODPDMeasured); ?>" tabindex="269"></td>
                        <td><input type=text id="ODLT" name="ODLT"  value="<?php echo attr($ODLT); ?>" tabindex="271"></td>
                        <td><input type=text id="ODW2W" name="ODW2W"  value="<?php echo attr($ODW2W); ?>" tabindex="273"></td>
                        <td><input type=text id="ODECL" name="ODECL"  value="<?php echo attr($ODECL); ?>" tabindex="275"></td>
                        <!-- <td><input type=text id="pend" name="pend"  value="<?php echo attr($pend); ?>"></td> -->
                      </tr>
                      <tr>
                        <td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                        <td><input type=text id="OSAXIALLENGTH" name="OSAXIALLENGTH" value="<?php echo attr($OSAXIALLENGTH); ?>" tabindex="266"></td>
                        <td><input type=text id="OSACD" name="OSACD" value="<?php echo attr($OSACD); ?>" tabindex="268"></td>
                        <td><input type=text id="OSPDMeasured" name="OSPDMeasured" value="<?php echo attr($OSPDMeasured); ?>" tabindex="270"></td>
                        <td><input type=text id="OSLT" name="OSLT" value="<?php echo attr($OSLT); ?>" tabindex="272"></td>
                        <td><input type=text id="OSW2W" name="OSW2W" value="<?php echo attr($OSW2W); ?>" tabindex="274"></td>
                        <td><input type=text id="OSECL" name="OSECL" value="<?php echo attr($OSECL); ?>" tabindex="276"></td>
                        <!--  <td><input type=text id="pend" name="pend" value="<?php echo attr($pend); ?>"></td> -->
                      </tr>
                    </table>
                  </div>  

                  <?php ($VAX==1) ? ($display_Add = "") : ($display_Add = "nodisplay"); ?>
                  <div id="LayerVision_VAX" class="refraction borderShadow <?php echo $display_Add; ?>">
                    <span title="<?php echo attr('Close this panel and make this a Preference to stay closed'); ?>" class="closeButton fa  fa-close" id="Close_VAX" name="Close_VAX"></span> 
                    <table id="Additional_VA">
                      <th colspan="9"><?php echo xlt('Visual Acuity'); ?></th>
                      <tr><td></td>
                        <td title="<?php echo xla('Acuity without correction'); ?>"><?php echo xlt('SC{{Acuity without correction}}'); ?></td>
                        <td title="<?php echo xla('Acuity with correction'); ?>"><?php echo xlt('W Rx{{Acuity with correction}}'); ?></td>
                        <td title="<?php echo xla('Acuity with Autorefraction'); ?>"><?php echo xlt('AR{{Autorefraction Acuity}}'); ?></td>
                        <td title="<?php echo xla('Acuity with Manifest Refraction'); ?>"><?php echo xlt('MR{{Manifest Refraction}}'); ?></td>
                        <td title="<?php echo xla('Acuity with Cycloplegic Refraction'); ?>"><?php echo xlt('CR{{Cycloplegic refraction}}'); ?></td>
                        <td title="<?php echo xla('Acuity with Pinhole'); ?>"><?php echo xlt('PH{{Pinhole acuity}}'); ?></td>
                        <td title="<?php echo xla('Acuity with Contact Lenses'); ?>"><?php echo xlt('CTL{{Contact Lens}}'); ?></td>

                      </tr>
                      <tr><td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input type=text id="SCODVA_copy_brd" name="SCODVA_copy_brd" value="<?php echo attr($SCODVA); ?>" tabindex="300"></td>
                        <td><input type=text id="ODVA_1_copy_brd" name="ODVA_1_copy_brd" value="<?php echo attr($ODVA_1); ?>" tabindex="302"></td>
                        <td><input type=text id="ARODVA_copy_brd" name="ARODVA_copy_brd" value="<?php echo attr($ARODVA); ?>" tabindex="304"></td>
                        <td><input type=text id="MRODVA_copy_brd" name="MRODVA_copy_brd" value="<?php echo attr($MRODVA); ?>" tabindex="306"></td>
                        <td><input type=text id="CRODVA_copy_brd" name="CRODVA_copy_brd" value="<?php echo attr($CRODVA); ?>" tabindex="308"></td>
                        <td><input type=text id="PHODVA_copy_brd" name="PHODVA_copy_brd" value="<?php echo attr($PHODVA); ?>" tabindex="310"></td>
                        <td><input type=text id="CTLODVA_copy_brd" name="CTLODVA_copy_brd" value="<?php echo attr($CTLODVA); ?>" tabindex="312"></td>
                      </tr>
                      <tr><td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                        <td><input type=text id="SCOSVA_copy"     name="SCOSVA_copy"     value="<?php echo attr($SCOSVA); ?>" tabindex="301"></td>
                        <td><input type=text id="OSVA_1_copy_brd" name="OSVA_1_copy_brd" value="<?php echo attr($OSVA_1); ?>" tabindex="303"></td>
                        <td><input type=text id="AROSVA_copy_brd" name="AROSVA_copy_brd" value="<?php echo attr($AROSVA); ?>" tabindex="305"></td>
                        <td><input type=text id="MROSVA_copy_brd" name="MROSVA_copy_brd" value="<?php echo attr($MROSVA); ?>" tabindex="307"></td>
                        <td><input type=text id="CROSVA_copy_brd" name="CROSVA_copy_brd" value="<?php echo attr($CROSVA); ?>" tabindex="309"></td>
                        <td><input type=text id="PHOSVA_copy_brd" name="PHOSVA_copy_brd" value="<?php echo attr($PHOSVA); ?>" tabindex="311"></td>
                        <td><input type=text id="CTLOSVA_copy_brd" name="CTLOSVA_copy_brd" value="<?php echo attr($CTLOSVA); ?>" tabindex="313"></td>
                      </tr>
                      <tr><td>&nbsp;</td></tr>
                      <tr>
                        <td></td>
                        <td title="<?php echo xla('Near Acuity without correction'); ?>"><?php echo xlt('scNear{{without correct at near}}'); ?></td>
                        <td title="<?php echo xla('Near Acuity with correction'); ?>"><?php echo xlt('ccNear{{with corrction at near}}'); ?></td>
                        <td title="<?php echo xla('Near Acuity with Autorefraction'); ?>"><?php echo xlt('ARNear{{autorefraction near}}'); ?></td>
                        <td title="<?php echo xla('Near Acuity with Manifest (Dry) refraction'); ?>"><?php echo xlt('MRNear{{maifest refraction near}}'); ?></td>
                        <td title="<?php echo xla('Potential Acuity'); ?>"><?php echo xlt('PAM{{Potential Acuity Meter}}'); ?></td>
                        <td title="<?php echo xla('Brightness Acuity Testing'); ?>"><?php echo xlt('BAT{{Brightness Acuity Testing}}'); ?></td>
                        <td title="<?php echo xla('Contrast Acuity'); ?>"><?php echo xlt('Contrast'); ?></td>
                      </tr>
                      <tr><td><b><?php echo xlt('OD{{right eye}}'); ?>:</b></td>
                        <td><input class="jaeger" type=text id="SCNEARODVA" title="<?php echo xla('Near Acuity without Correction'); ?>" name="SCNEARODVA" value="<?php echo attr($SCNEARODVA); ?>" tabindex="320"></td>
                        <td><input class="jaeger" type=text id="ODNEARVA_1_copy_brd" title="<?php echo xla('Near Acuity with Correction'); ?>" name="ODNEARVA_1_copy_brd" value="<?php echo attr($ODNEARVA_1); ?>" tabindex="322"></td>
                        <td><input class="jaeger" type=text id="ARNEARODVA_copy_brd" title="<?php echo xla('Near Acuity AutoRefraction'); ?>" name="ARNEARODVA_copy_brd" value="<?php echo attr($ARNEARODVA); ?>" tabindex="324"></td>
                        <td><input class="jaeger" type=text id="MRNEARODVA_copy_brd" title="<?php echo xla('Near Acuity Manifest Refraction'); ?>" name="MRNEARODVA_copy_brd" value="<?php echo attr($MRNEARODVA); ?>" tabindex="326"></td>
                        <td><input type=text id="PAMODVA_copy_brd" title="<?php echo xla('Potential Acuity Meter'); ?>" name="PAMODVA_copy_brd" value="<?php echo attr($PAMODVA); ?>" tabindex="328"></td>
                        <td><input type=text id="GLAREODVA_copy_brd" title="<?php echo xla('Brightness Acuity Testing'); ?>" name="GLAREODVA_copy_brd" value="<?php echo attr($GLAREODVA); ?>" tabindex="330"></td>
                        <td><input type=text id="CONTRASTODVA_copy_brd" title="<?php echo xla('Contrast Acuity Testing'); ?>" name="CONTRASTODVA_copy_brd" value="<?php echo attr($CONTRASTODVA); ?>" tabindex="332"></td>
                      </tr>
                      <tr><td><b><?php echo xlt('OS{{left eye}}'); ?>:</b></td>
                        <td><input class="jaeger" type=text id="SCNEAROSVA" title="<?php echo xla('Near Acuity without Correction'); ?>" name="SCNEAROSVA" value="<?php echo attr($SCNEAROSVA); ?>" tabindex="321"></td>
                        <td><input class="jaeger" type=text id="OSNEARVA_1_copy_brd" title="<?php echo xla('Near Acuity with Correction'); ?>" name="OSNEARVA_1_copy_brd" value="<?php echo attr($OSNEARVA_1); ?>" tabindex="323"></td>
                        <td><input class="jaeger" type=text id="ARNEAROSVA_copy" title="<?php echo xla('Near Acuity AutoRefraction'); ?>" name="ARNEAROSVA_copy" value="<?php echo attr($ARNEAROSVA); ?>" tabindex="325"></td>
                        <td><input class="jaeger" type=text id="MRNEAROSVA_copy" title="<?php echo xla('Near Acuity Manifest Refraction'); ?>" name="MRNEAROSVA_copy" value="<?php echo attr($MRNEAROSVA); ?>" tabindex="327"></td>
                        <td><input type=text id="PAMOSVA_copy_brd" title="<?php echo xla('Potential Acuity Meter'); ?>" name="PAMOSVA_copy_brd" value="<?php echo attr($PAMOSVA); ?>" tabindex="329"></td>
                        <td><input type=text id="GLAREOSVA_copy_brd" title="<?php echo xla('Brightness Acuity Testing'); ?>" name="GLAREOSVA_copy_brd" value="<?php echo attr($GLAREOSVA); ?>" tabindex="331"></td>
                        <td><input type=text id="CONTRASTOSVA" title="<?php echo xla('Contrast Acuity Testing'); ?>" name="CONTRASTOSVA" value="<?php echo attr($CONTRASTOSVA); ?>" tabindex="333"></td>
                      </tr>
                    </table>
                  </div>
                </div>
              </div>   
              <!-- end of the refraction box -->
              <!-- start of the exam selection/middle menu row -->
              <div class="sections" name="mid_menu" id="mid_menu" style="font-size:1.0em;white-space:nowrap;margin:10px;">
                <span id="EXAM_defaults" name="EXAM_defaults" value="Defaults" class="borderShadow"><i class="fa fa-newspaper-o"></i>&nbsp;<b><?php echo xlt('Defaults'); ?></b></span> 
                <span id="EXAM_TEXT" name="EXAM_TEXT" value="TEXT" class="borderShadow"><i class="fa fa-hospital-o"></i>&nbsp;<b><?php echo xlt('Text'); ?></b></span>
                <span id="EXAM_DRAW" name="EXAM_DRAW" value="DRAW" class="borderShadow">
                  <i class="fa fa-paint-brush fa-sm"> </i>&nbsp;<b><?php echo xlt('Draw'); ?></b></span>
                  <span id="EXAM_QP" name="EXAM_QP" title="<?php echo xla('Open the Quick Pick panels'); ?>" value="QP" class="borderShadow">
                    <i class="fa fa-database fa-sm"> </i>&nbsp;<b><?php echo xlt('Quick Picks'); ?></b>
                  </span>
                  <?php 
                  // output is defined above and if there are old visits, check for orders in eye_mag_functions: 
                  // $output = priors_select("ALL",$id,$id,$pid); 
                  ($output_priors =='') ? ($title = "There are no prior visits documented to display for this patient.") : ($title="Display old exam findings and copy forward if desired");?>
                  <span id="PRIORS_ALL_left_text" name="PRIORS_ALL_left_text" 
                  class="borderShadow" style="padding-right:5px;"><i class="fa fa-paste" title="<?php echo xla($title); ?>"></i>
                  <?php 
                  if ($output_priors !='') {  echo $output_priors; } else { echo "<b>". xlt("First visit: No Old Records")."</b>"; }
                  ?>&nbsp;
                </span> 
                <span id="EXAM_ALL_keyboard_left" name="EXAM_ALL_keyboard_left" class="borderShadow" style="padding-right:5px;width:250px;">
                  <i class="fa fa-user-md fa-sm" name="Shorthand_kb" title="<?php echo xla("Open/Close the Shorthand Window and display Shorthand Codes"); ?>"></i>&nbsp;
                  <span id="BAR_kb" name="BAR_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class=""><b><?php echo xlt('Shorthand'); ?></b>
                  </span>
                  &nbsp;
                  <a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php');">
                    <i title="<?php echo xla('Click for Shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i>
                  </a>
                </span>
              </div>  
              <!-- end of the exam selection row -->

              <!-- start of the Shorthand Entry Box -->
              <div style="margin: 0 auto;text-align: center;font-size:1.4em;Xmin-width:300px;" class="kb borderShadow nodisplay" id="EXAM_KB" name="EXAM_KB">   
                <span class="closeButton fa fa-close" id="CLOSE_kb" name="CLOSE_kb"></span>
                <span class="BAR2_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class="ke"><b><?php echo xlt('Shorthand'); ?></b>
                </span>
                  
                <a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php?zone=all');">
                <i title="<?php echo xla('Click for Shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i>
                </a><br />
                <textarea id="ALL_keyboard_left" name="ALL_keyboard_left" style="color:#0C0C0C;size:0.6em;height: 6em;width:85%;margin:15px;z-index:100;" tabindex='1000'></textarea>
              </div> 
              <!-- end of the Shorthand Entry Box -->

              <!-- end reporting div -->
              <span class="anchor" id="SELECTION_ROW_anchor"></span>

              <!-- Start of the exam sections -->
              <div class="loading" id="EXAM_sections_loading" name="EXAM_sections_loading">
                  <hr></hr>
                  <i class="fa fa-spinner fa-spin"></i>
              </div>              
              <div style="margin:0px 100px;text-align: center;display:inline-table;max-width: 95%;" class="nodisplay" id="DA_EXAM_sections" name="DA_EXAM_sections">   
                <!-- start External Exam -->
                <div id="EXT_1" name="EXT_1" class="clear_both">
                  <span class="anchor" id="EXT_anchor"></span>
                  <div id="EXT_left" class="exam_section_left borderShadow" >
                    <div id="EXT_left_text" style="height: 2.5in;text-align:left;" class="TEXT_class">
                      <span class="closeButton fa fa-paint-brush" title="<?php echo xla('Open/Close the External drawing panel'); ?>" id="BUTTON_DRAW_EXT" name="BUTTON_DRAW_EXT"></span>
                      <i class="closeButton_2 fa fa-database" title="<?php echo xla('Open/Close the External Exam Quick Picks panel'); ?>" id="BUTTON_QP_EXT" name="BUTTON_QP_EXT"></i>
                      <i class="closeButton_3 fa fa-user-md fa-sm fa-2" name="Shorthand_kb" title="<?php echo xla("Open/Close the Shorthand Window and display Shorthand Codes"); ?>"></i>
                      <b><?php echo xlt('External Exam'); ?>:</b><br />
                      <div style="position:relative;float:right;top:0.2in;text-align:right;">
                        <table style="text-align:center;font-weight:600;font-size:0.8em;">
                           <?php 
                              list($imaging,$episode) = display($pid,$encounter, "EXT"); 
                              echo $episode;
                            ?>
                        </table>
                        <table style="text-align:center;font-size:1.0em;">
                              <tr>
                                  <td></td><td><?php echo xlt('R'); ?></td><td><?php echo xlt('L'); ?></td>
                              </tr>
                              <tr>
                                  <td class="right" title="<?php echo xla('Levator Function'); ?>">
                                    <div class="kb kb_left"><?php echo xlt('LF{{levator function}}'); ?></div><?php echo xlt('Lev Fn{{levator function}}'); ?></td>
                                  <td><input  type="text"  name="RLF" id="RLF" class="EXT" value="<?php echo attr($RLF); ?>"></td>
                                  <td><input  type="text"  name="LLF" id="LLF" class="EXT" value="<?php echo attr($LLF); ?>"></td>
                              </tr>
                              <tr>
                                  <td class="right" title="<?php echo xla('Marginal Reflex Distance'); ?>">
                                    <div class="kb kb_left"><?php echo xlt('MRD{{marginal reflex distance}}'); ?></div><?php echo xlt('MRD{{marginal reflex distance}}'); ?></td>
                                  <td><input type="text" size="1" name="RMRD" id="RMRD" class="EXT" value="<?php echo attr($RMRD); ?>"></td>
                                  <td><input type="text" size="1" name="LMRD" id="LMRD" class="EXT" value="<?php echo attr($LMRD); ?>"></td>
                              </tr>
                              <tr>
                                  <td class="right" title="<?php echo xla('Vertical Fissure: central height between lid margins'); ?>">
                                    <div class="kb kb_left"><?php echo xlt('VF{{vertical fissure}}'); ?></div><?php echo xlt('Vert Fissure{{vertical fissure}}'); ?></td>
                                  <td><input type="text" size="1" name="RVFISSURE" id="RVFISSURE" class="EXT" value="<?php echo attr($RVFISSURE); ?>"></td>
                                  <td><input type="text" size="1" name="LVFISSURE" id="LVFISSURE" class="EXT" value="<?php echo attr($LVFISSURE); ?>"></td>
                              </tr>
                              <tr>
                                  <td class="right" title="<?php echo xla('Any carotid bruits appreciated?'); ?>">
                                    <div class="kb kb_left"><?php echo xlt('CAR{{carotid arteries}}'); ?></div><?php echo xlt('Carotid{{carotid arteries}}'); ?></td>
                                  <td><input  type="text"  name="RCAROTID" id="RCAROTID" class="EXT" class="EXT" value="<?php echo attr($RCAROTID); ?>"></td>
                                  <td><input  type="text"  name="LCAROTID" id="LCAROTID" class="EXT" value="<?php echo attr($LCAROTID); ?>"></td>
                              </tr>
                              <tr>
                                  <td class="right" title="<?php echo xla('Temporal Arteries'); ?>">
                                    <div class="kb kb_left"><?php echo xlt('TA{{temporal arteries}}'); ?></div>
                                    <?php echo xlt('Temp. Art.{{temporal arteries}}'); ?></td>
                                  <td><input type="text" size="1" name="RTEMPART" id="RTEMPART" class="EXT" value="<?php echo attr($RTEMPART); ?>"></td>
                                  <td><input type="text" size="1" name="LTEMPART" id="LTEMPART" class="EXT" value="<?php echo attr($LTEMPART); ?>"></td>
                              </tr>
                              <tr>
                                  <td class="right" title="<?php echo xla('Cranial Nerve 5: Trigeminal Nerve'); ?>">
                                    <div class="kb kb_left"><?php echo xlt('CN5{{cranial nerve five}}'); ?></div><?php echo xlt('CN V{{cranial nerve five}}'); ?></td>
                                  <td><input type="text" size="1" name="RCNV" id="RCNV" class="EXT" value="<?php echo attr($RCNV); ?>"></td>
                                  <td><input type="text" size="1" name="LCNV" id="LCNV" class="EXT" value="<?php echo attr($LCNV); ?>"></td>
                              </tr>
                              <tr>
                                  <td class="right" title="<?php echo xla('Cranial Nerve 7: Facial Nerve'); ?>">
                                    <div class="kb kb_left"><?php echo xlt('CN7{{cranial nerve five}}'); ?></div><?php echo xlt('CN VII{{cranial nerve seven}}'); ?></td>
                                  <td><input type="text" size="1" name="RCNVII" class="EXT" id="RCNVII" value="<?php echo attr($RCNVII); ?>"></td>
                                  <td><input type="text" size="1" name="LCNVII" class="EXT" id="LCNVII" value="<?php echo attr($LCNVII); ?>"></td>
                              </tr>
                         
                              <tr><td colspan=3 style="padding-top:0.05in;text-decoration:underline;"><br /><?php echo xlt('Hertel Exophthalmometry'); ?></td></tr>
                              <tr style="text-align:center;">
                                  <td>
                                      <input type="text" size="1" id="ODHERTEL" name="ODHERTEL" class="EXT" value="<?php echo attr($ODHERTEL); ?>">
                                      <i class="fa fa-minus"></i>
                                      <div class="kb kb_center"><?php echo xlt('RH{{right hertel measurement}}'); ?></div>
                                  </td>
                                  <td>
                                      <input type=text size=3  id="HERTELBASE" name="HERTELBASE" class="EXT" value="<?php echo attr($HERTELBASE); ?>">
                                      <i class="fa fa-minus"></i><div class="kb kb_center"><?php echo xlt('HERT{{Hertel exophthalmometry}}'); ?></div>
                                  </td>
                                  <td>
                                      <input type=text size=1  id="OSHERTEL" name="OSHERTEL" class="EXT" value="<?php echo attr($OSHERTEL); ?>">
                                      <div class="kb kb_center"><?php echo xlt('LH{{left hertel measurement}}'); ?></div>
                                  </td>
                              </tr>
                              <tr><td>&nbsp;</td></tr>
                        </table>
                      </div>

                      <?php ($EXT_VIEW ==1) ? ($display_EXT_view = "wide_textarea") : ($display_EXT_view= "narrow_textarea");?>                                 
                      <?php ($display_EXT_view == "wide_textarea") ? ($marker ="fa-minus-square-o") : ($marker ="fa-plus-square-o");?>
                      <div id="EXT_text_list" name="EXT_text_list" class="borderShadow  <?php echo attr($display_EXT_view); ?>">
                          <span class="top_right fa <?php echo attr($marker); ?>" name="EXT_text_view" id="EXT_text_view"></span>
                          <table cellspacing="0" cellpadding="0">
                              <tr>
                                  <th><?php echo xlt('Right'); ?></th><td style="width:100px;"></td><th><?php echo xlt('Left'); ?></th>
                              </tr>
                              <tr>
                                  <td><textarea name="RBROW" id="RBROW" class="right EXT"><?php echo text($RBROW); ?></textarea></td>
                                  <td><div class="ident"><?php echo xlt('Brow'); ?></div>
                                      <div class="kb kb_left"><?php echo xlt('RB{{right brow}}'); ?></div>
                                      <div class="kb kb_right"><?php echo xlt('LB{{left brow}}'); ?></div>
                                    </td>
                                  <td><textarea name="LBROW" id="LBROW" class="EXT"><?php echo text($LBROW); ?></textarea>
                                  </td>
                              </tr> 
                              <tr>
                                  <td><textarea name="RUL" id="RUL" class="right EXT"><?php echo text($RUL); ?></textarea></td>
                                  <td><div class="ident"><?php echo xlt('Upper Lids'); ?></div>
                                  <div class="kb kb_left"><?php echo xlt('RUL{{right upper eyelid}}'); ?></div>
                                  <div class="kb kb_right"><?php echo xlt('LUL{{left upper eyelid}}'); ?></div></td>
                                  <td><textarea name="LUL" id="LUL" class="EXT"><?php echo text($LUL); ?></textarea></td>
                              </tr> 
                              <tr>
                                  <td><textarea name="RLL" id="RLL" class="right EXT"><?php echo text($RLL); ?></textarea></td>
                                  <td><div class="ident"><?php echo xlt('Lower Lids'); ?></div>
                                      <div class="kb kb_left"><?php echo xlt('RLL{{right lower eyelid}}'); ?></div>
                                      <div class="kb kb_right"><?php echo xlt('LLL{{left lower eyelid}}'); ?></div></td>
                                  <td><textarea name="LLL" id="LLL" class="EXT"><?php echo text($LLL); ?></textarea></td>
                              </tr>
                              <tr>
                                  <td><textarea name="RMCT" id="RMCT" class="right EXT"><?php echo text($RMCT); ?></textarea></td>
                                  <td><div class="ident"><?php echo xlt('Medial Canthi'); ?></div>
                                      <div class="kb kb_left"><?php echo xlt('RMC{{right medial canthus}}'); ?></div>
                                      <div class="kb kb_right"><?php echo xlt('LMC{{left medial chathus}}'); ?></div></td>
                                  <td><textarea name="LMCT" id="LMCT" class="EXT"><?php echo text($LMCT); ?></textarea></td>
                              </tr>
                               <tr>
                                  <td><textarea name="RADNEXA" id="RADNEXA" class="right EXT"><?php echo text($RADNEXA); ?></textarea></td>
                                  <td><div class="ident"><?php echo xlt('Adnexa'); ?></div>
                                        <div class="kb kb_left"><?php echo xlt('RAD{{right adnexa}}'); ?></div>
                                        <div class="kb kb_right"><?php echo xlt('LAD{{left adnexa}}'); ?></div></td>
                                  <td><textarea name="LADNEXA" id="LADNEXA" class=" EXT"><?php echo text($LADNEXA); ?></textarea></td>
                              </tr>
                          </table>
                      </div>  <br />
                      <div id="EXT_COMMENTS_DIV" class="QP_lengthen" >
                        <b><?php echo xlt('Comments'); ?>:</b><div class="kb kb_left"><?php echo xlt('ECOM{{external comments abbreviation}}'); ?></div>
                        <br />
                        <textarea id="EXT_COMMENTS" name="EXT_COMMENTS" class=" EXT"><?php echo text($EXT_COMMENTS); ?></textarea>
                      </div> 
                      <div class="QP_not nodisplay" id="EXT_keyboard_left" style="position: absolute;bottom:0.05in;right:0.1in;font-size:0.7em;text-align:left;padding-left:25px;">
                        <span id="EXT_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class="ke"><b><?php echo xlt('Shorthand'); ?></b></span>
                        &nbsp;<a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php?zone=ext');">
                          <i title="<?php echo xla('Click for External shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i></a><br />
                          <textarea id="EXT_keyboard_left" name="EXT_keyboard_left" style="color:#0C0C0C;size:0.8em;height: 3em;" tabindex='1000'></textarea>
                      </div>
                    </div>  
                  </div>
                  <div id="EXT_right" name="EXT_right" class="exam_section_right borderShadow text_clinical">
                      <?php display_draw_section ("EXT",$encounter,$pid); ?>
                      <div id="PRIORS_EXT_left_text" style="height: 2.5in;text-align:left;" name="PRIORS_EXT_left_text" class="PRIORS_class PRIORS"> 
                          <i class="fa fa-spinner fa-spin"></i>
                      </div>
                      <div id="QP_EXT" name="QP_EXT" class="QP_class" style="text-align:left;max-height: 2.5in;">
                          <input type="hidden" id="EXT_prefix" name="EXT_prefix" value="<?php echo attr($EXT_prefix); ?>">
                           
                          <span class="closeButton fa fa-close pull-right z100" id="BUTTON_TEXTD_EXT" name="BUTTON_TEXTD_EXT" value="1"></span>
                          <div class="z10" style="position:relative;top:0.0in;left:0.00in;margin:auto;">
                              <span class="eye_button eye_button_selected" id="EXT_prefix_off" name="EXT_prefix_off" onclick="$('#EXT_prefix').val('').trigger('change');"><?php echo xlt('Off'); ?></span>
                              <span class="eye_button" id="EXT_defaults" name="EXT_defaults"><?php echo xlt('Defaults'); ?></span>  
                              <span class="eye_button" id="EXT_prefix_no" name="EXT_prefix_no" onclick="$('#EXT_prefix').val('no').trigger('change');"> <?php echo xlt('no'); ?> </span>  
                              <span class="eye_button" id="EXT_prefix_trace" name="EXT_prefix_trace"  onclick="$('#EXT_prefix').val('trace').trigger('change');"> <?php echo xlt('tr'); ?> </span>  
                              <span class="eye_button" id="EXT_prefix_1" name="EXT_prefix_1"  onclick="$('#EXT_prefix').val('+1').trigger('change');"> <?php echo xlt('+1'); ?> </span>  
                              <span class="eye_button" id="EXT_prefix_2" name="EXT_prefix_2"  onclick="$('#EXT_prefix').val('+2').trigger('change');"> <?php echo xlt('+2'); ?> </span>  
                              <span class="eye_button" id="EXT_prefix_3" name="EXT_prefix_3"  onclick="$('#EXT_prefix').val('+3').trigger('change');"> <?php echo xlt('+3'); ?> </span>  
                              <?php echo $selector = priors_select("EXT",$id,$id,$pid); ?>
                          </div>
                          <div style="float:left;width:40px;text-align:left;">
                              <span class="eye_button" id="EXT_prefix_1mm" name="EXT_prefix_1mm"  onclick="$('#EXT_prefix').val('1mm').trigger('change');"> 1<?php echo xlt('mm{{millimeters}}'); ?> </span>  <br />
                              <span class="eye_button" id="EXT_prefix_2mm" name="EXT_prefix_2mm"  onclick="$('#EXT_prefix').val('2mm').trigger('change');"> 2<?php echo xlt('mm{{millimeters}}'); ?> </span>  <br />
                              <span class="eye_button" id="EXT_prefix_3mm" name="EXT_prefix_3mm"  onclick="$('#EXT_prefix').val('3mm').trigger('change');"> 3<?php echo xlt('mm{{millimeters}}'); ?> </span>  <br />
                              <span class="eye_button" id="EXT_prefix_4mm" name="EXT_prefix_4mm"  onclick="$('#EXT_prefix').val('4mm').trigger('change');"> 4<?php echo xlt('mm{{millimeters}}'); ?> </span>  <br />
                              <span class="eye_button" id="EXT_prefix_5mm" name="EXT_prefix_5mm"  onclick="$('#EXT_prefix').val('5mm').trigger('change');"> 5<?php echo xlt('mm{{millimeters}}'); ?> </span>  <br />
                              <span class="eye_button" id="EXT_prefix_medial" name="EXT_prefix_medial"  onclick="$('#EXT_prefix').val('medial').trigger('change');"><?php echo xlt('med{{medial}}'); ?></span>   
                              <span class="eye_button" id="EXT_prefix_lateral" name="EXT_prefix_lateral"  onclick="$('#EXT_prefix').val('lateral').trigger('change');"><?php echo xlt('lat{{lateral}}'); ?></span>  
                              <span class="eye_button" id="EXT_prefix_superior" name="EXT_prefix_superior"  onclick="$('#EXT_prefix').val('superior').trigger('change');"><?php echo xlt('sup{{superior}}'); ?></span>  
                              <span class="eye_button" id="EXT_prefix_inferior" name="EXT_prefix_inferior"  onclick="$('#EXT_prefix').val('inferior').trigger('change');"><?php echo xlt('inf{{inferior}}'); ?></span> 
                              <span class="eye_button" id="EXT_prefix_anterior" name="EXT_prefix_anterior"  onclick="$('#EXT_prefix').val('anterior').trigger('change');"><?php echo xlt('ant{{anterior}}'); ?></span>  <br /> 
                              <span class="eye_button" id="EXT_prefix_mid" name="EXT_prefix_mid"  onclick="$('#EXT_prefix').val('mid').trigger('change');"><?php echo xlt('mid{{middle}}'); ?></span>  <br />
                              <span class="eye_button" id="EXT_prefix_posterior" name="EXT_prefix_posterior"  onclick="$('#EXT_prefix').val('posterior').trigger('change');"><?php echo xlt('post{{posterior}}'); ?></span>  <br />
                              <span class="eye_button" id="EXT_prefix_deep" name="EXT_prefix_deep"  onclick="$('#EXT_prefix').val('deep').trigger('change');"><?php echo xlt('deep'); ?></span> 
                              <br />
                              <br />
                              <span class="eye_button" id="EXT_prefix_clear" name="EXT_prefix_clear" title="<?php echo xla('This will clear the data from all External Exam fields'); ?>"style="background-color:red;" onclick="$('#EXT_prefix').val('clear').trigger('change');"><?php echo xlt('clear'); ?></span> 
                          </div>   
                               
                          <div id="EXT_QP_block1" name="EXT_QP_block1" class="QP_block borderShadow text_clinical" >

                            <?php  
                            echo $QP_ANTSEG = display_QP("EXT",$providerID); ?>
                          </div>      
                                    
                          <div class="QP_block_outer borderShadow nodisplay" style="z-index:1;text-align:center;border:1pt solid black;padding:7 10 7 10;font-weight:600;">
                            <span id="EXT1_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class="ke"><?php echo xlt('Shorthand'); ?></span>&nbsp;
                              <a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php?zone=ext');">
                              <i title="<?php echo xla('Click for External shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i></a><br />
                              <textarea id="EXT_keyboard" name="EXT_keyboard" style="color:#0C0C0C;size:0.8em;height: 0.48in;" tabindex='1000'></textarea>
                              <span style="font-size:0.9em;font-weight:400;color:#0C0C0C;"><?php echo xlt('Type: location:text; ENTER'); ?><br />
                              <?php echo xlt('eg. right lower lid stye laterally'); ?>:<br /> <b>RLL:stye lat;</b></span>
                            </span>
                          </div>            
                      </div>
                  </div>
                </div>
                <!-- end External Exam -->

                <!-- start Anterior Segment -->
                <div id="ANTSEG_1" class="clear_both">
                  <span class="anchor" id="ANTSEG_anchor"></span>
                  <div id="ANTSEG_left" name="ANTSEG_left" class="exam_section_left borderShadow">
                    <div class="TEXT_class" id="ANTSEG_left_text" style="height: 2.5in;text-align:left;">
                      <span class="closeButton fa fa-paint-brush" title="<?php echo xla('Open/Close the Anterior Segment drawing panel'); ?>" id="BUTTON_DRAW_ANTSEG" name="BUTTON_DRAW_ANTSEG"></span>
                      <i class="closeButton_2 fa fa-database"title="<?php echo xla('Open/Close the Anterior Segment Exam Quick Picks panel'); ?>" id="BUTTON_QP_ANTSEG" name="BUTTON_QP_ANTSEG"></i>
                      <i class="closeButton_3 fa fa-user-md fa-sm fa-2" name="Shorthand_kb" title="<?php echo xla("Open/Close the Shorthand Window and display Shorthand Codes"); ?>"></i>
                      <b><?php echo xlt('Anterior Segment'); ?>:</b><br />
                      <div class="text_clinical" style="position:relative;float:right;top:0.2in;">
                            <table style="text-align:center;font-weight:600;font-size:0.8em;">
                              <?php 
                                  list($imaging,$episode) = display($pid,$encounter, "ANTSEG"); 
                                  echo $episode;
                              ?>
                            </table>
                              <table style="text-align:center;font-size:1.0em;width:170px;padding-left:5px;"> 
                                  <tr >
                                      <td></td><td><?php echo xlt('R{{right}}'); ?></td><td><?php echo xlt('L{{left}}'); ?></td>
                                  </tr>
                                  <tr>
                                      <td class="right" title="<?php echo xla('Gonio'); ?>">
                                        <div class="kb kb_left"><?php echo xlt('R/LG{{right/left gonioscopy}}'); ?></div>
                                        <?php echo xlt('Gonio{{Gonioscopy}}'); ?> 
                                      </td>
                                      <td><input type="text" class="ANTSEG" name="ODGONIO" id="ODGONIO" value="<?php echo attr($ODGONIO); ?>"></td>
                                      <td><input type="text" class="ANTSEG" name="OSGONIO" id="OSGONIO" value="<?php echo attr($OSGONIO); ?>"></td>
                                  </tr>
                                  <tr>
                                      <td class="right" title="<?php echo xla('Pachymetry: Central Corneal Thickness'); ?>">
                                        <div class="kb kb_left"><?php echo xlt('R/LPACH{{right/left pachymetry}}'); ?></div>
                                        <?php echo xlt('Pachy{{Pachymetry}}'); ?> 
                                      </td>
                                      <td><input type="text" size="1" class="ANTSEG" name="ODKTHICKNESS" id="ODKTHICKNESS" value="<?php echo attr($ODKTHICKNESS); ?>">
                                      </td>
                                      <td><input type="text" size="1" class="ANTSEG" name="OSKTHICKNESS" id="OSKTHICKNESS" value="<?php echo attr($OSKTHICKNESS); ?>">
                                      </td>
                                  </tr>
                                  <tr>
                                      <td class="right" title="<?php echo xla('Schirmers I (w/o anesthesia)'); ?>">
                                        <div class="kb kb_left"><?php echo xlt('R/LSCH1{{right/left Schirmers I (w/o anesthesia)}}'); ?></div>
                                        <?php echo xlt('Schirmers I'); ?> </td>
                                      <td><input type="text" size="1" class="ANTSEG" name="ODSCHIRMER1" id="ODSCHIRMER1" value="<?php echo attr($ODSCHIRMER1); ?>">
                                        </td>
                                      <td><input type="text" size="1" class="ANTSEG" name="OSSCHIRMER1" id="OSSCHIRMER1" value="<?php echo attr($OSSCHIRMER1); ?>">
                                        </td>
                                  </tr>
                                   <tr>
                                      <td class="right" title="<?php echo xla('Schirmers II (w/ anesthesia)'); ?>">
                                        <div class="kb kb_left"><?php echo xlt('R/LSCH2{{right/left Schirmers II (w/ anesthesia)}}'); ?></div>
                                        <?php echo xlt('Schirmers II'); ?> </td>
                                      <td><input type="text" size="1" class="ANTSEG" name="ODSCHIRMER2" id="ODSCHIRMER2" value="<?php echo attr($ODSCHIRMER2); ?>">
                                      </td>
                                      <td><input type="text" size="1" class="ANTSEG" name="OSSCHIRMER2" id="OSSCHIRMER2" value="<?php echo attr($OSSCHIRMER2); ?>">
                                      </td>
                                  </tr>
                                  <tr style="padding-bottom:15px;">
                                      <td class="right" title="<?php echo xla('Tear Break Up Time'); ?>">
                                        <div class="kb kb_left"><?php echo xlt('R/LTBUT{{right/left Tear Break Up Time}}'); ?></div>
                                        <?php echo xlt('TBUT{{tear breakup time}}'); ?> </td>
                                      <td><input type="text" size="1" class="ANTSEG" name="ODTBUT" id="ODTBUT" value="<?php echo attr($ODTBUT); ?>"></td>
                                      <td><input type="text" size="1" class="ANTSEG" name="OSTBUT" id="OSTBUT" value="<?php echo attr($OSTBUT); ?>"></td>
                                  </tr>
                                  <tr style="text-align:center;" >
                                    <td colspan="3" rowspan="4" style="text-align:left;bottom:0px;width:75px;">
                                      <br />
                                      <span style="text-decoration:underline;font-size: 1.1em;"><?php echo xlt('Dilated with'); ?>:</span><br />
                                      <?php //convert to list.  How about a jquery multiselect box, stored in DIL_MEDS field with "|" as a delimiter? OK...
                                      //create a list of all our options for dilation Eye_Drug_Dilation
                                      //create the jquery selector.  Store results in DB.
                                      //on loading page, and on RED-ONLY, need to convert DIL_MEDS to correct thing here.
                                      //We need times too...
                                      //OK. Second delimiter @ for time, within "|" delimiters
                                      //Do we know what time it is?  Yes from IOPTIME code?....


                                      ?>
                                      <table style="font-size:0.9em;padding:4px;">
                                        <tr>
                                          <td>
                                                <input type="checkbox" class="dil_drug" id="CycloMydril" name="CYCLOMYDRIL" value="Cyclomydril" <?php if ($CYCLOMYDRIL == 'Cyclomydril') echo "checked='checked'"; ?> />
                                                <label for="CycloMydril" class="input-helper input-helper--checkbox"><?php echo xlt('CycloMydril'); ?></label>
                                          </td>
                                          <td>        
                                                <input type="checkbox" class="dil_drug" id="Tropicamide" name="TROPICAMIDE" value="Tropicamide 2.5%" <?php if ($TROPICAMIDE == 'Tropicamide 2.5%') echo "checked='checked'"; ?> />
                                                <label for="Tropicamide" class="input-helper input-helper--checkbox"><?php echo xlt('Tropic 2.5%'); ?></label>
                                          </td>
                                        </tr>
                                        <tr>
                                          <td>        
                                                <input type="checkbox" class="dil_drug" id="Neo25" name="NEO25" value="Neosynephrine 2.5%"  <?php if ($NEO25 =='Neosynephrine 2.5%') echo "checked='checked'"; ?> />
                                                <label for="Neo25" class="input-helper input-helper--checkbox"><?php echo xlt('Neo 2.5%'); ?></label>
                                          </td>
                                          <td>        
                                                <input type="checkbox" class="dil_drug" id="Neo10" name="NEO10" value="Neosynephrine 10%"  <?php if ($NEO10 =='Neosynephrine 10%') echo "checked='checked'"; ?> />
                                                <label for="Neo10" class="input-helper input-helper--checkbox"><?php echo xlt('Neo 10%'); ?></label>
                                          </td>
                                        </tr>
                                        <tr>
                                          <td>        
                                                <input type="checkbox" class="dil_drug" id="Cyclogyl" style="left:150px;" name="CYCLOGYL" value="Cyclopentolate 1%"  <?php if ($CYCLOGYL == 'Cyclopentolate 1%') echo "checked='checked'"; ?> />
                                                <label for="Cyclogyl" class="input-helper input-helper--checkbox"><?php echo xlt('Cyclo 1%'); ?></label>
                                          </td>
                                          <td>      <input type="checkbox" class="dil_drug" id="Atropine" name="ATROPINE" value="Atropine 1%"  <?php if ($ATROPINE == 'Atropine 1%') echo "checked='checked'"; ?> />
                                                <label for="Atropine" class="input-helper input-helper--checkbox"><?php echo xlt('Atropine 1%'); ?></label>
                                          </td>
                                        </tr>
                                      </table>
                                    </td>
                                  </tr>
                              </table>
                      </div>

                      <?php ($ANTSEG_VIEW =='1') ? ($display_ANTSEG_view = "wide_textarea") : ($display_ANTSEG_view= "narrow_textarea");?>
                      <?php ($display_ANTSEG_view == "wide_textarea") ? ($marker ="fa-minus-square-o") : ($marker ="fa-plus-square-o");?>
                      <div id="ANTSEG_text_list"  name="ANTSEG_text_list" class="borderShadow <?php echo attr($display_ANTSEG_view); ?>" >
                              <span class="top_right fa <?php echo attr($marker); ?>" name="ANTSEG_text_view" id="ANTSEG_text_view"></span>
                              <table class="" style="" cellspacing="0" cellpadding="0">
                                  <tr>
                                      <th><?php echo xlt('OD{{right eye}}'); ?></th><td style="width:100px;"></td><th><?php echo xlt('OS{{left eye}}'); ?></th></td>
                                  </tr>
                                  <tr>
                                      <td>
                                        <textarea name="ODCONJ" id="ODCONJ" class="ANTSEG right"><?php echo text($ODCONJ); ?></textarea></td>
                                      <td><div class="ident"><?php echo xlt('Conj'); ?> / <?php echo xlt('Sclera'); ?></div>
                                        <div class="kb kb_left"><?php echo xlt('RC{{right conjunctiva}}'); ?></div>
                                        <div class="kb kb_right"><?php echo xlt('LC{{left conjunctiva}}'); ?></div></td>
                                      <td><textarea name="OSCONJ" id="OSCONJ" class="ANTSEG"><?php echo text($OSCONJ); ?></textarea></td>
                                  </tr> 
                                  <tr>
                                      <td><textarea name="ODCORNEA" id="ODCORNEA" class="ANTSEG right"><?php echo text($ODCORNEA); ?></textarea></td>
                                      <td><div class="ident"><?php echo xlt('Cornea'); ?></div>
                                        <div class="kb kb_left"><?php echo xlt('RK{{right cornea}}'); ?>RK</div>
                                        <div class="kb kb_right"><?php echo xlt('LK{{left cornea}}'); ?></div></td></td>
                                      <td><textarea name="OSCORNEA" id="OSCORNEA" class="ANTSEG"><?php echo text($OSCORNEA); ?></textarea></td>
                                  </tr> 
                                  <tr>
                                      <td><textarea name="ODAC" id="ODAC" class="ANTSEG right"><?php echo text($ODAC); ?></textarea></td>
                                      <td><div class="ident"><?php echo xlt('A/C'); ?></div>
                                        <div class="kb kb_left"><?php echo xlt('RAC{{right anterior chamber}}'); ?></div>
                                        <div class="kb kb_right"><?php echo xlt('LAC{{left anterior chamber}}'); ?></div></td></td>
                                      <td><textarea name="OSAC" id="OSAC" class="ANTSEG"><?php echo text($OSAC); ?></textarea></td>
                                  </tr>
                                  <tr>
                                      <td><textarea name="ODLENS" id="ODLENS" class="ANTSEG right"><?php echo text($ODLENS); ?></textarea></td>
                                      <td><div class="ident dropShadow"><?php echo xlt('Lens'); ?></div>
                                        <div class="kb kb_left"><?php echo xlt('RL{{right lens}}'); ?></div>
                                        <div class="kb kb_right"><?php echo xlt('LL{{left lens}}'); ?></div></td></td>
                                      <td><textarea name="OSLENS" id="OSLENS" class="ANTSEG"><?php echo text($OSLENS); ?></textarea></td>
                                  </tr>
                                  <tr>
                                      <td><textarea name="ODIRIS" id="ODIRIS" class="ANTSEG right"><?php echo text($ODIRIS); ?></textarea></td>
                                      <td><div class="ident"><?php echo xlt('Iris'); ?></div>
                                        <div class="kb kb_left"><?php echo xlt('RI{{right iris}}'); ?>RI</div><div class="kb kb_right"><?php echo xlt('LL{{left iris}}'); ?></div></td></td>
                                      <td><textarea name="OSIRIS" id="OSIRIS" class="ANTSEG"><?php echo text($OSIRIS); ?></textarea></td>
                                  </tr>
                              </table>
                      </div>  <br />
                      <div class="QP_lengthen" id="ANTSEG_COMMENTS_DIV"> 
                        <b><?php echo xlt('Comments'); ?>:</b><div class="kb kb_left"><?php echo xlt('ACOM{{Anterior Segment}}'); ?> </div><br />
                          <textarea id="ANTSEG_COMMENTS" class="ANTSEG" name="ANTSEG_COMMENTS"><?php echo text($ANTSEG_COMMENTS); ?></textarea>
                      </div>   
                      <div class="QP_not nodisplay" id="ANTSEG_keyboard_left" style="position: absolute;bottom:0.05in;right:0.1in;font-size:0.7em;text-align:left;padding-left:25px;">
                        <span id="ANTSEG_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class="ke"><b><?php echo xlt('Shorthand'); ?></b></span>
                        &nbsp;<a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php?zone=antseg');">
                        <i title="<?php echo xla('Click for Anterior Segment shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i></a><br />
                        <textarea id="ANTSEG_keyboard_left" name="EXT_keyboard_left" class="ANTSEG" style="color:#0C0C0C;size:0.8em;height: 3em;" tabindex='1000'></textarea>
                      </div>
                    </div>  
                  </div>
                  
                  <div id="ANTSEG_right" NAME=="ANTSEG_right" class="exam_section_right borderShadow text_clinical ">
                      <div id="PRIORS_ANTSEG_left_text" style="height: 2.5in;text-align:left;" name="PRIORS_ANTSEG_left_text" class="PRIORS_class PRIORS">                                     
                                      <i class="fa fa-spinner fa-spin"></i>
                      </div>
                      <?php display_draw_section ("ANTSEG",$encounter,$pid); ?>
                      <div id="QP_ANTSEG" name="QP_ANTSEG" class="QP_class"  style="text-align:left;height: 2.5in;">
                          <input type="hidden" id="ANTSEG_prefix" name="ANTSEG_prefix" value="">
                                 
                          <div style="position:relative;top:0.0in;left:0.00in;margin: auto;">
                              <span  class="eye_button eye_button_selected" id="ANTSEG_prefix_off" name="ANTSEG_prefix_off"  onclick="$('#ANTSEG_prefix').val('off').trigger('change');"><?php echo xlt('Off'); ?> </span> 
                              <span  class="eye_button" id="ANTSEG_defaults" name="ANTSEG_defaults"><?php echo xlt('Defaults'); ?></span>  
                              <span  class="eye_button" id="ANTSEG_prefix_no" name="ANTSEG_prefix_no" onclick="$('#ANTSEG_prefix').val('no').trigger('change');"> <?php echo xlt('no'); ?> </span>  
                              <span  class="eye_button" id="ANTSEG_prefix_trace" name="ANTSEG_prefix_trace"  onclick="$('#ANTSEG_prefix').val('trace').trigger('change');"> <?php echo xlt('tr'); ?> </span>  
                              <span  class="eye_button" id="ANTSEG_prefix_1" name="ANTSEG_prefix_1"  onclick="$('#ANTSEG_prefix').val('+1').trigger('change');"> <?php echo xlt('+1'); ?> </span>  
                              <span  class="eye_button" id="ANTSEG_prefix_2" name="ANTSEG_prefix_2"  onclick="$('#ANTSEG_prefix').val('+2').trigger('change');"> <?php echo xlt('+2'); ?> </span>  
                              <span  class="eye_button" id="ANTSEG_prefix_3" name="ANTSEG_prefix_3"  onclick="$('#ANTSEG_prefix').val('+3').trigger('change');"> <?php echo xlt('+3'); ?> </span>  
                              <?php echo $selector = priors_select("ANTSEG",$id,$id,$pid); ?>
                          </div>
                          <div style="float:left;width:40px;text-align:left;">
                              <span  class="eye_button" id="ANTSEG_prefix_1mm" name="ANTSEG_prefix_1mm"  onclick="$('#ANTSEG_prefix').val('1mm').trigger('change');"> <?php echo xlt('1mm'); ?> </span>  <br />
                              <span  class="eye_button" id="ANTSEG_prefix_2mm" name="ANTSEG_prefix_2mm"  onclick="$('#ANTSEG_prefix').val('2mm').trigger('change');"> <?php echo xlt('2mm'); ?> </span>  <br />
                              <span  class="eye_button" id="ANTSEG_prefix_3mm" name="ANTSEG_prefix_3mm"  onclick="$('#ANTSEG_prefix').val('3mm').trigger('change');"> <?php echo xlt('3mm'); ?> </span>  <br />
                              <span  class="eye_button" id="ANTSEG_prefix_4mm" name="ANTSEG_prefix_4mm"  onclick="$('#ANTSEG_prefix').val('4mm').trigger('change');"> <?php echo xlt('4mm'); ?> </span>  <br />
                              <span  class="eye_button" id="ANTSEG_prefix_5mm" name="ANTSEG_prefix_5mm"  onclick="$('#ANTSEG_prefix').val('5mm').trigger('change');"> <?php echo xlt('5mm'); ?> </span>  <br />
                              <span  class="eye_button" id="ANTSEG_prefix_medial" name="ANTSEG_prefix_medial"  onclick="$('#ANTSEG_prefix').val('medial').trigger('change');"><?php echo xlt('med{{medial}}'); ?></span>   
                              <span  class="eye_button" id="ANTSEG_prefix_lateral" name="ANTSEG_prefix_lateral"  onclick="$('#ANTSEG_prefix').val('lateral').trigger('change');"><?php echo xlt('lat{{lateral}}'); ?></span>  
                              <span  class="eye_button" id="ANTSEG_prefix_superior" name="ANTSEG_prefix_superior"  onclick="$('#ANTSEG_prefix').val('superior').trigger('change');"><?php echo xlt('sup{{su[erior}}'); ?></span>  
                              <span  class="eye_button" id="ANTSEG_prefix_inferior" name="ANTSEG_prefix_inferior"  onclick="$('#ANTSEG_prefix').val('inferior').trigger('change');"><?php echo xlt('inf{{inferior}}'); ?></span> 
                              <span  class="eye_button" id="ANTSEG_prefix_anterior" name="ANTSEG_prefix_anterior"  onclick="$('#ANTSEG_prefix').val('anterior').trigger('change');"><?php echo xlt('ant{{anterior}}'); ?></span>  <br /> 
                              <span  class="eye_button" id="ANTSEG_prefix_mid" name="ANTSEG_prefix_mid"  onclick="$('#ANTSEG_prefix').val('mid').trigger('change');"><?php echo xlt('mid'); ?></span>  <br />
                              <span  class="eye_button" id="ANTSEG_prefix_posterior" name="ANTSEG_prefix_posterior"  onclick="$('#ANTSEG_prefix').val('posterior').trigger('change');"><?php echo xlt('post'); ?></span>  <br />
                              <span  class="eye_button" id="ANTSEG_prefix_deep" name="ANTSEG_prefix_deep"  onclick="$('#ANTSEG_prefix').val('deep').trigger('change');"><?php echo xlt('deep'); ?></span> 
                              <br />
                              <br />
                              <span class="eye_button" id="ANTSEG_prefix_clear" name="ANTSEG_prefix_clear" title="<?php echo xla('This will clear the data from all Anterior Segment Exam fields'); ?>" style="background-color:red;" onclick="$('#ANTSEG_prefix').val('clear').trigger('change');"><?php echo xlt('clear'); ?></span> 
                     
                          </div>         
                          <div class="QP_block borderShadow text_clinical " >
                            <?php echo $QP_ANTSEG = display_QP("ANTSEG",$providerID); ?>
                          </div>  
                          <div class="QP_block_outer borderShadow nodisplay" style="z-index:1;text-align:center;border:1pt solid black;padding:7 10 7 10;font-weight:600;">
                            <span id="ANTSEG1_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class="ke"><?php echo xlt('Shorthand'); ?></span>
                            &nbsp;
                            <a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php?zone=antseg');" title="<?php echo xla('Click for Ant. Seg. shorthand help.'); ?>">
                              <i class="fa fa-info-circle fa-1"></i></a><br />
                            <textarea id="ANTSEG_keyboard" name="ANTSEG_keyboard" style="color:#0C0C0C;size:0.8em;height: 0.48in;" tabindex='1000' title="AntSeg Keyboard"></textarea>
                            <span style="font-size:0.9em;font-weight:400;color:#0C0C0C;"><?php echo xlt('Type location:text; ENTER'); ?><br />
                            <?php echo xlt('eg. OU cornea +2 kruckenberg spindle'); ?>:<br /> <b>k:+2 ks;</b>
                            </span>
                          </div>  
                          <span class="closeButton fa fa-close pull-right z100" id="BUTTON_TEXTD_ANTSEG" name="BUTTON_TEXTD_ANTSEG"></span>
                          
                      </div>
                  </div>
                </div>
                <!-- end Ant Seg -->
                       
                <!-- start Retina -->               
                <div id="RETINA_1" class="clear_both" >
                  <span class="anchor" id="RETINA_anchor"></span>
                  <div id="RETINA_left" class="exam_section_left borderShadow">
                    <div class="TEXT_class" id="RETINA_left_text" style="height: 2.5in;text-align:left;">
                      <!-- 
                      <span class="closeButton fa fa-plus-square-o" id="MAX_RETINA" name="MAX_RETINA"></span>
                      -->
                      <span class="closeButton fa fa-paint-brush" title="<?php echo xla('Open/Close the Retina drawing panel'); ?>" id="BUTTON_DRAW_RETINA" name="BUTTON_DRAW_RETINA"></span>
                      <i class="closeButton_2 fa fa-database"title="<?php echo xla('Open/Close the Retinal Exam Quick Picks panel'); ?>" id="BUTTON_QP_RETINA" name="BUTTON_QP_RETINA"></i>
                      <i class="closeButton_3 fa fa-user-md fa-sm fa-2" name="Shorthand_kb" title="<?php echo xla("Open/Close the Shorthand Window and display Shorthand Codes"); ?>"></i>
                
                      <b><?php echo xlt('Retina'); ?>:</b>
                              <?php
                            /*
                                  OCT, FA/ICG,Photos - External,Photos - AntSeg,Optic Disc,Photos - Retina,Radiology, VF
                                  are the Imaging categories we started with.  If you add more they are listed
                                  Here in retina we want to see:
                                  OCT, FA/ICG, Optic Disc, Fundus Photos, Electrophys
                                  for viewing images, if (count($category['OCT']) >0) show image and href= a popupform to display all the results
                                  build a get string for this:
                                  for ($i=0; $i < count($category['OCT']); $i++) {
                                    $get .= $category['OCT'][$i]."%20".
                                  }
                                  $href="/eye_mag/imaging.php?display=".$get;
                            */
                                  
                                    ?>
                                    <input style="margin-left:100px;" type="checkbox" id="DIL_RISKS" name="DIL_RISKS" value="on" <?php if ($DIL_RISKS =='on') echo "checked='checked'"; ?>>
                                        <label for="DIL_RISKS" class="input-helper input-helper--checkbox"><?php echo xlt('Dilation orders/risks reviewed'); ?></label>
                                    <?php
                                  
                                  ?><br />

                      <div style="position:relative;float:right;top:0.2in;border:0pt solid black;">
                        <table style="float:right;text-align:right;font-size:0.8em;font-weight:bold;">
                            <?php 
                              list($imaging,$episode) = display($pid,$encounter, "POSTSEG"); 
                              echo $episode;
                            ?>
                        </table>
                        <br />
                        <table style="width:50%;text-align:right;right:0px;font-size:1.0em;font-weight:bold;padding:10px;margin: 5px 0px;">
                              <tr style="text-align:center;vertical-align:middle;">
                                  <td></td>
                                  <td> <br /><?php echo xlt('OD{{right eye}}'); ?></td><td> <br /><?php echo xlt('OS{{left eye}}'); ?></td>
                                </tr>
                                <tr>
                                  <td>
                                      <span id="CMT" name="CMT" title="<?php echo xla('Central Macular Thickness'); ?>"><?php echo xlt('CMT{{Central Macular Thickness}}'); ?>:</span>
                                  </td>
                                  <td>
                                      <input name="ODCMT" class="RETINA" size="4" id="ODCMT" value="<?php echo attr($ODCMT); ?>">
                                      <div class="kb kb_center"><?php echo xlt('RCMT{{right Central Macular Thickness}}'); ?></div>
                                  </td>
                                  <td>
                                      <input name="OSCMT" class="RETINA" size="4" id="OSCMT" value="<?php echo attr($OSCMT); ?>">
                                      <div class="kb kb_center"><?php echo xlt('LCMT{{left Central Macular Thickness}}'); ?></div>
                                  </td>
                              </tr>
                        </table>
                        <br />
                        <table style="text-align:right;font-size:0.8em;font-weight:bold;float:right;">
                          <?php 
                            list($imaging,$episode) = display($pid,$encounter, "NEURO"); 
                            echo $episode;
                          ?>
                        </table>
                      </div>

                      <?php ($RETINA_VIEW ==1) ? ($display_RETINA_view = "wide_textarea") : ($display_RETINA_view= "narrow_textarea");?>
                      <?php ($display_RETINA_view == "wide_textarea") ? ($marker ="fa-minus-square-o") : ($marker ="fa-plus-square-o");?>
                      <div>
                        <div id="RETINA_text_list" name="RETINA_text_list" class="borderShadow  <?php echo attr($display_RETINA_view); ?>">
                              <span class="top_right fa <?php echo attr($marker); ?>" name="RETINA_text_view" id="RETINA_text_view"></span>
                              <table  cellspacing="0" cellpadding="0">
                                      <tr>
                                          <th><?php echo xlt('OD{{right eye}}'); ?></th><td style="width:100px;"></td><th><?php echo xlt('OS{{left eye}}'); ?></th></td>
                                      </tr>
                                      <tr>
                                          <td><textarea name="ODDISC" id="ODDISC"  class="RETINA right"><?php echo text($ODDISC); ?></textarea></td>
                                          <td>
                                            <div class="ident"><?php echo xlt('Disc'); ?></div>
                                            <div class="kb kb_left"><?php echo xlt('RD{{right disc}}'); ?></div>
                                            <div class="kb kb_right"><?php echo xlt('LD{{left disc}}'); ?></div></td>
                                          <td><textarea name="OSDISC" id="OSDISC" class="RETINA"><?php echo text($OSDISC); ?></textarea></td>
                                      </tr> 
                                      <tr>
                                          <td><textarea name="ODCUP" id="ODCUP" class="RETINA right"><?php echo text($ODCUP); ?></textarea></td>
                                          <td>
                                            <div class="ident"><?php echo xlt('Cup'); ?></div>
                                            <div class="kb kb_left"><?php echo xlt('RCUP{{right cup}}'); ?></div>
                                            <div class="kb kb_right"><?php echo xlt('LCUP{{left cup}}'); ?></div></td>
                                          <td><textarea name="OSCUP" id="OSCUP" class="RETINA"><?php echo text($OSCUP); ?></textarea></td>
                                      </tr> 
                                      <tr>
                                          <td><textarea name="ODMACULA" id="ODMACULA" class="RETINA right"><?php echo text($ODMACULA); ?></textarea></td>
                                          <td>
                                            <div class="ident"><?php echo xlt('Macula'); ?></div>
                                            <div class="kb kb_left"><?php echo xlt('RMAC{{right macula}}'); ?></div>
                                            <div class="kb kb_right"><?php echo xlt('LMAC{{left macula}}'); ?></div></td>
                                          <td><textarea name="OSMACULA" id="OSMACULA" class="RETINA"><?php echo text($OSMACULA); ?></textarea></td>
                                      </tr>
                                      <tr>
                                          <td><textarea name="ODVESSELS" id="ODVESSELS" class="RETINA right"><?php echo text($ODVESSELS); ?></textarea></td>
                                          <td>
                                            <div class="ident"><?php echo xlt('Vessels'); ?></div>
                                            <div class="kb kb_left"><?php echo xlt('RV{{right vessels}}'); ?></div>
                                            <div class="kb kb_right"><?php echo xlt('LV{{left vessels}}'); ?></div></td>
                                          <td><textarea name="OSVESSELS" id="OSVESSELS" class="RETINA"><?php echo text($OSVESSELS); ?></textarea></td>
                                      </tr>
                                      <tr>
                                          <td><textarea name="ODPERIPH" id="ODPERIPH" class="RETINA right"><?php echo text($ODPERIPH); ?></textarea></td>
                                          <td>
                                            <div class="ident"><?php echo xlt('Periph{{peripheral retina}}'); ?></div>
                                            <div class="kb kb_left"><?php echo xlt('RP{{right peripheral retina}}'); ?></div>
                                            <div class="kb kb_right"><?php echo xlt('LP{{left peripheral retina}}'); ?></div></td>
                                          <td><textarea name="OSPERIPH" id="OSPERIPH" class="RETINA"><?php echo text($OSPERIPH); ?></textarea></td>
                                      </tr>
                              </table>
                        </div>
                      </div>
                      <div class="QP_lengthen" id="RETINA_COMMENTS_DIV">
                          <b><?php echo xlt('Comments'); ?>:</b><div class="kb kb_left"><?php echo xlt('RCOM{{right comments}}'); ?></div><br />
                          <textarea id="RETINA_COMMENTS" class="RETINA" name="RETINA_COMMENTS"><?php echo text($RETINA_COMMENTS); ?></textarea>
                      </div>  
                      <div class="QP_not" id="RETINA_keyboard_left" style="position: absolute;bottom:0.05in;right:0.1in;font-size:0.7em;text-align:left;padding-left:25px;">
                        <span id="RETINA_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class="ke"><b><?php echo xlt('Shorthand'); ?></b></span>
                        &nbsp;<a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php?zone=retina');">
                        <i title="<?php echo xla('Click for Retina shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i></a><br />
                        <textarea id="RETINA_keyboard_left" class="RETINA"  name="RETINA_keyboard_left" style="color:#0C0C0C;size:0.8em;height: 3em;" tabindex='1000'></textarea>
                      </div>
                    </div>
                  </div>
                  
                  <div id="RETINA_right" class="exam_section_right borderShadow text_clinical">
                    <div id="PRIORS_RETINA_left_text" style="height: 2.5in;text-align:left;" 
                         name="PRIORS_RETINA_left_text" 
                         class="PRIORS_class PRIORS"><i class="fa fa-spinner fa-spin"></i>
                    </div>
                    <?php display_draw_section ("RETINA",$encounter,$pid); ?>
                    <div id="QP_RETINA" name="QP_RETINA" class="QP_class" style="text-align:left;height: 2.5in;">
                      <input type="hidden" id="RETINA_prefix" name="RETINA_prefix" value="" />
                             
                      <div style="position:relative;top:0.0in;left:0.00in;margin: auto;">
                           <span  class="eye_button  eye_button_selected" id="RETINA_prefix_off" name="RETINA_prefix_off"  onclick="$('#RETINA_prefix').val('').trigger('change');"><?php echo xlt('Off'); ?></span> 
                           <span  class="eye_button" id="RETINA_defaults" name="RETINA_defaults"><?php echo xlt('Defaults'); ?></span>  
                           <span  class="eye_button" id="RETINA_prefix_no" name="RETINA_prefix_no" onclick="$('#RETINA_prefix').val('no').trigger('change');"> <?php echo xlt('no'); ?> </span>  
                           <span  class="eye_button" id="RETINA_prefix_trace" name="RETINA_prefix_trace"  onclick="$('#RETINA_prefix').val('trace').trigger('change');"> <?php echo xlt('tr'); ?> </span>  
                           <span  class="eye_button" id="RETINA_prefix_1" name="RETINA_prefix_1"  onclick="$('#RETINA_prefix').val('+1').trigger('change');"> <?php echo xlt('+1'); ?> </span>  
                           <span  class="eye_button" id="RETINA_prefix_2" name="RETINA_prefix_2"  onclick="$('#RETINA_prefix').val('+2').trigger('change');"> <?php echo xlt('+2'); ?> </span>  
                           <span  class="eye_button" id="RETINA_prefix_3" name="RETINA_prefix_3"  onclick="$('#RETINA_prefix').val('+3').trigger('change');"> <?php echo xlt('+3'); ?> </span>  
                           <?php echo $selector = priors_select("RETINA",$id,$id,$pid); ?>
                      </div>
                      <div style="float:left;width:40px;text-align:left;">

                          <span  class="eye_button" id="RETINA_prefix_1mm" name="RETINA_prefix_1mm"  onclick="$('#RETINA_prefix').val('1mm').trigger('change');"> <?php echo xlt('1mm'); ?> </span>  <br />
                          <span  class="eye_button" id="RETINA_prefix_2mm" name="RETINA_prefix_2mm"  onclick="$('#RETINA_prefix').val('2mm').trigger('change');"> <?php echo xlt('2mm'); ?> </span>  <br />
                          <span  class="eye_button" id="RETINA_prefix_3mm" name="RETINA_prefix_3mm"  onclick="$('#RETINA_prefix').val('3mm').trigger('change');"> <?php echo xlt('3mm'); ?> </span>  <br />
                          <span  class="eye_button" id="RETINA_prefix_4mm" name="RETINA_prefix_4mm"  onclick="$('#RETINA_prefix').val('4mm').trigger('change');"> <?php echo xlt('4mm'); ?> </span>  <br />
                          <span  class="eye_button" id="RETINA_prefix_5mm" name="RETINA_prefix_5mm"  onclick="$('#RETINA_prefix').val('5mm').trigger('change');"> <?php echo xlt('5mm'); ?> </span>  <br />
                          <span  class="eye_button" id="RETINA_prefix_nasal" name="RETINA_prefix_nasal"  onclick="$('#RETINA_prefix').val('nasal').trigger('change');"><?php echo xlt('nasal'); ?></span>   
                          <span  class="eye_button" id="RETINA_prefix_temp" name="RETINA_prefix_temp"  onclick="$('#RETINA_prefix').val('temp').trigger('change');"><?php echo xlt('temp{{temporal}}'); ?></span>  
                          <span  class="eye_button" id="RETINA_prefix_superior" name="RETINA_prefix_superior"  onclick="$('#RETINA_prefix').val('superior').trigger('change');"><?php echo xlt('sup{{superior}}'); ?></span>  
                          <span  class="eye_button" id="RETINA_prefix_inferior" name="RETINA_prefix_inferior"  onclick="$('#RETINA_prefix').val('inferior').trigger('change');"><?php echo xlt('inf{{inferior}}'); ?></span> 
                          <span  class="eye_button" id="RETINA_prefix_anterior" name="RETINA_prefix_anterior"  onclick="$('#RETINA_prefix').val('anterior').trigger('change');"><?php echo xlt('ant{{anterior}}'); ?></span>  <br /> 
                          <span  class="eye_button" id="RETINA_prefix_mid" name="RETINA_prefix_mid"  onclick="$('#RETINA_prefix').val('mid').trigger('change');"><?php echo xlt('mid{{middle}}'); ?></span>  <br />
                          <span  class="eye_button" id="RETINA_prefix_posterior" name="RETINA_prefix_posterior"  onclick="$('#RETINA_prefix').val('posterior').trigger('change');"><?php echo xlt('post{{posterior}}'); ?></span>  <br />
                          <span  class="eye_button" id="RETINA_prefix_deep" name="RETINA_prefix_deep"  onclick="$('#RETINA_prefix').val('deep').trigger('change');"><?php echo xlt('deep'); ?></span> 
                          <br />
                          <br />
                          <span class="eye_button" id="RETINA_prefix_clear" name="RETINA_prefix_clear" title="<?php echo xla('This will clear the data from all Retina Exam fields'); ?>"style="background-color:red;" onclick="$('#RETINA_prefix').val('clear').trigger('change');"><?php echo xlt('clear'); ?></span> 
                       
                      </div>         
                      <div class="QP_block borderShadow text_clinical" >
                           <?php echo $QP_RETINA = display_QP("RETINA",$providerID); ?>
                      </div>
                      <div class="QP_block_outer borderShadow nodisplay" style="z-index:1;text-align:center;border:1pt solid black;padding:7px 10px 7px 10px;font-weight:600;">
                        <span id="RETINA_kb" title="<?php echo xla('Click to display shorthand field names.'); ?>" class="ke"><?php echo xlt('Shorthand'); ?></span>&nbsp;
                        <a onclick="goto_url('<?php echo $GLOBALS['webroot']; ?>/interface/forms/eye_mag/help.php?zone=retina');">
                        <i title="<?php echo xla('Click for Ant. Seg. shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i></a><br />
                        <textarea id="RETINA_keyboard" name="RETINA_keyboard" style="color:#0C0C0C;size:0.8em;height: 0.48in;" tabindex='1000'></textarea>
                        <span style="font-size:0.9em;font-weight:400;color:#0C0C0C;"><?php echo xlt('Type location:text; ENTER'); ?><br />
                          <?php echo xlt('eg. OD C/D 0.5 with inferior notch'); ?>:<br /> <b>RC:0.5 w/ inf notch;</b>
                        </span>
                      </div>  
                      <span class="closeButton fa fa-close pull-right z100" id="BUTTON_TEXTD_RETINA" name="BUTTON_TEXTD_RETINA" value="1"></span>
                    </div>
                  </div>
                </div>
                <!-- end Retina -->

                <!-- start Neuro -->
                <div id="NEURO_1" class="clear_both">
                  <span class="anchor" id="NEURO_anchor"></span>

                  <div id="NEURO_left" class="exam_section_left borderShadow">
                        <span class="closeButton fa fa-paint-brush" id="BUTTON_DRAW_NEURO" title="<?php echo xla('Open/Close the Neuro drawing panel'); ?>" name="BUTTON_DRAW_NEURO"></span>
                        <i class="closeButton_2 fa fa-database" title="<?php echo xla('Open/Close the Neuro Exam Quick Picks panel'); ?>" id="BUTTON_QP_NEURO" name="BUTTON_QP_NEURO"></i>

                        <i class="closeButton_3 fa fa-user-md fa-sm fa-2" name="Shorthand_kb" title="<?php echo xla("Open/Close the Shorthand Window and display Shorthand Codes"); ?>"></i>
                        <div class="TEXT_class" id="NEURO_left_text" name="NEURO_left_text" style="margin:auto 5;min-height: 2.5in;text-align:left;">
                            <b><?php echo xlt('Neuro'); ?>:</b>
                            <div style="float:left;margin-top:8px;font-size:0.8em;">
                                <div id="NEURO_text_list" class="borderShadow" style="border:1pt solid black;float:left;width:175px;padding:5px;text-align:center;margin:2 2;font-weight:bold;">
                                    <table style="font-weight:600;font-size:1.0em;">
                                        <tr>
                                            <td></td><td style="text-align:center;"><?php echo xlt('OD{{right eye}}'); ?></td>
                                            <td style="text-align:center;"><?php echo xlt('OS{{left eye}}'); ?></td></tr>
                                        <tr>
                                            <td class="right">
                                                <?php echo xlt('Color'); ?>: 
                                            </td>
                                            <td>
                                                <input type="text"  name="ODCOLOR" id="ODCOLOR" value="<?php if ($ODCOLOR) { echo  text($ODCOLOR); } else { echo ""; } ?>"/>
                                            </td>
                                            <td>
                                                <input type="text" name="OSCOLOR" id="OSCOLOR" value="<?php if ($OSCOLOR) { echo  text($OSCOLOR); } else { echo ""; } ?>"/>
                                            </td>
                                            <td style="text-align:bottom;"><!-- //Normals may be 11/11 or 15/15.  Need to make a preference here for the user.
                                                //or just take the normal they use and incorporate that ongoing?
                                            -->
                                            &nbsp;   <span title="<?php echo xlt('Insert normals'); ?> - 11/11" class="fa fa-share-square-o fa-flip-horizontal" id="NEURO_COLOR" name="NEURO_COLOR" ></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="right" style="white-space: nowrap;font-size:0.9em;">
                                                <span title="Variation in red color discrimination between the eyes (eg. OD=100, OS=75)"><?php echo xlt('Red Desat{{red desaturation}}'); ?>:</span>
                                            </td>
                                            <td>
                                                <input type="text" Xsize="6" name="ODREDDESAT" id="ODREDDESAT" value="<?php echo attr($ODREDDESAT); ?>"/> 
                                            </td>
                                            <td>
                                                <input type="text" Xsize="6" name="OSREDDESAT" id="OSREDDESAT" value="<?php echo attr($OSREDDESAT); ?>"/>
                                            </td>
                                            <td>
                                              &nbsp; <span title="<?php echo xlt('Insert normals - 100/100'); ?>" class="fa fa-share-square-o fa-flip-horizontal" id="NEURO_REDDESAT" name="NEURO_REDDESAT"></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="right" style="white-space: nowrap;">
                                                <span title="<?php echo xlt('Variation in white (muscle) light brightness discrimination between the eyes (eg. OD=$1.00, OS=$0.75)'); ?>"><?php echo xlt('Coins'); ?>:</span>
                                            </td>
                                            <td>
                                                <input type="text" name="ODCOINS" id="ODCOINS" value="<?php echo attr($ODCOINS); ?>"/> 
                                            </td>
                                            <td>
                                                <input type="text" name="OSCOINS" id="OSCOINS" value="<?php echo attr($OSCOINS); ?>"/>
                                            </td>
                                            <td>
                                               &nbsp;<span title="<?php echo xla('Insert normals'); ?> - 100/100" class="fa fa-share-square-o fa-flip-horizontal" id="NEURO_COINS" name="NEURO_COINS"></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="borderShadow" style="position:relative;float:right;text-align:center;width:238px;height:250scpx;z-index:1;margin:2 0 2 2;">                               
                                    <i class="fa fa-th fa-fw closeButton " id="Close_ACTMAIN" style="right:0.15in;z-index:10;" name="Close_ACTMAIN"></i>
                                    <table style="position:relative;float:left;font-size:1.0em;width:210px;font-weight:600;"> 
                                        <tr style="text-align:left;height:26px;vertical-align:middle;width:180px;">
                                            <td >
                                                <span id="ACTTRIGGER" name="ACTTRIGGER" style="text-decoration:underline;padding-left:2px;"><?php echo xlt('Alternate Cover Test'); ?>:</span>
                                            </td>
                                            <td>
                                                <span id="ACTNORMAL_CHECK" name="ACTNORMAL_CHECK">
                                                <label for="ACT" class="input-helper input-helper--checkbox"><?php echo xlt('Ortho{{orthophoric}}'); ?></label>
                                                <input type="checkbox" name="ACT" id="ACT" <?php if ($ACT =='on' or $ACT=='1') echo "checked='checked'"; ?> /></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:center;"> <br />
                                                <div id="ACTMAIN" name="ACTMAIN" class="nodisplay ACT_TEXT" style="position:relative;z-index:1;margin auto;">
                                                  <table cellpadding="0" style="position:relative;text-align:center;font-size:1.0em;margin: 7 5 10 5;border-collapse: separate;">
                                                        <tr>
                                                            <td id="ACT_tab_SCDIST" name="ACT_tab_SCDIST" class="ACT_selected"> <?php echo xlt('scDist{{without correction distance}}'); ?> </td>
                                                            <td id="ACT_tab_CCDIST" name="ACT_tab_CCDIST" class="ACT_deselected"> <?php echo xlt('ccDist{{with correction distance}}'); ?> </td>
                                                            <td id="ACT_tab_SCNEAR" name="ACT_tab_SCNEAR" class="ACT_deselected"> <?php echo xlt('scNear{{without correction near}}'); ?> </td>
                                                            <td id="ACT_tab_CCNEAR" name="ACT_tab_CCNEAR" class="ACT_deselected"> <?php echo xlt('ccNear{{with correction near}}'); ?> </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="4" style="text-align:center;font-size:0.8em;"><div id="ACT_SCDIST" name="ACT_SCDIST" class="ACT_box">
                                                                <br />
                                                                <table> 
                                                                        <tr> 
                                                                            <td style="text-align:center;"><?php echo xlt('R{{right}}'); ?></td>   
                                                                            <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                            <textarea id="ACT1SCDIST" name="ACT1SCDIST" class="ACT"><?php echo text($ACT1SCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                            <textarea id="ACT2SCDIST"  name="ACT2SCDIST"class="ACT"><?php echo text($ACT2SCDIST); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                            <textarea id="ACT3SCDIST"  name="ACT3SCDIST" class="ACT"><?php echo text($ACT3SCDIST); ?></textarea></td>
                                                                            <td style="text-align:center;"><?php echo xlt('L{{left}}'); ?></td> 
                                                                        </tr>
                                                                        <tr>    
                                                                            <td style="text-align:middle;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                            <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                            <textarea id="ACT4SCDIST" name="ACT4SCDIST" class="ACT"><?php echo text($ACT4SCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;text-align:center;">
                                                                            <textarea id="ACT5SCDIST"  class="neurosens2 ACT" name="ACT5SCDIST"><?php echo text($ACT5SCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                            <textarea id="ACT6SCDIST" name="ACT6SCDIST" class="ACT"><?php echo text($ACT6SCDIST); ?></textarea></td>
                                                                            <td><i class="fa fa-share rotate-right"></i></td> 
                                                                        </tr> 
                                                                        <tr> 
                                                                            <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                                <textarea id="ACT10SCDIST" name="ACT10SCDIST" class="ACT"><?php echo text($ACT10SCDIST); ?></textarea></td>
                                                                            <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                                <textarea id="ACT7SCDIST" name="ACT7SCDIST" class="ACT"><?php echo text($ACT7SCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                                <textarea id="ACT8SCDIST" name="ACT8SCDIST" class="ACT"><?php echo text($ACT8SCDIST); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                                <textarea id="ACT9SCDIST" name="ACT9SCDIST" class="ACT"><?php echo text($ACT9SCDIST); ?></textarea></td>
                                                                            <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                                <textarea id="ACT11SCDIST" name="ACT11SCDIST" class="ACT"><?php echo text($ACT11SCDIST); ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                    <br />
                                                                </div>
                                                                <div id="ACT_CCDIST" name="ACT_CCDIST" class="nodisplay ACT_box">
                                                                    <br />
                                                                    <table> 
                                                                       <tr> 
                                                                            <td style="text-align:center;"><?php echo xlt('R{{right}}'); ?></td>   
                                                                            <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                            <textarea id="ACT1CCDIST" name="ACT1CCDIST" class="ACT"><?php echo text($ACT1CCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                            <textarea id="ACT2CCDIST"  name="ACT2CCDIST"class="ACT"><?php echo text($ACT2CCDIST); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                            <textarea id="ACT3CCDIST"  name="ACT3CCDIST" class="ACT"><?php echo text($ACT3CCDIST); ?></textarea></td>
                                                                            <td style="text-align:center;"><?php echo xlt('L{{left}}'); ?></td> 
                                                                        </tr>
                                                                        <tr>    
                                                                            <td style="text-align:middle;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                            <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                            <textarea id="ACT4CCDIST" name="ACT4CCDIST" class="ACT"><?php echo text($ACT4CCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;text-align:center;">
                                                                            <textarea id="ACT5CCDIST" name="ACT5CCDIST" class="neurosens2 ACT"><?php echo text($ACT5CCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                            <textarea id="ACT6CCDIST" name="ACT6CCDIST" class="ACT"><?php echo text($ACT6CCDIST); ?></textarea></td>
                                                                            <td><i class="fa fa-share rotate-right"></i></td> 
                                                                        </tr> 
                                                                        <tr> 
                                                                            <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                                <textarea id="ACT10CCDIST" name="ACT10CCDIST" class="ACT"><?php echo text($ACT10CCDIST); ?></textarea></td>
                                                                            <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                                <textarea id="ACT7CCDIST" name="ACT7CCDIST" class="ACT"><?php echo text($ACT7CCDIST); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                                <textarea id="ACT8CCDIST" name="ACT8CCDIST" class="ACT"><?php echo text($ACT8CCDIST); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                                <textarea id="ACT9CCDIST" name="ACT9CCDIST" class="ACT"><?php echo text($ACT9CCDIST); ?></textarea></td>
                                                                            <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                                <textarea id="ACT11CCDIST" name="ACT11CCDIST" class="ACT"><?php echo text($ACT11CCDIST); ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                    <br />
                                                                </div>
                                                                <div id="ACT_SCNEAR" name="ACT_SCNEAR" class="nodisplay ACT_box">
                                                                    <br />
                                                                    <table> 
                                                                        <tr> 
                                                                            <td style="text-align:center;"><?php echo xlt('R{{right}}'); ?></td>    
                                                                            <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                            <textarea id="ACT1SCNEAR" name="ACT1SCNEAR" class="ACT"><?php echo text($ACT1SCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                            <textarea id="ACT2SCNEAR"  name="ACT2SCNEAR"class="ACT"><?php echo text($ACT2SCNEAR); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                            <textarea id="ACT3SCNEAR"  name="ACT3SCNEAR" class="ACT"><?php echo text($ACT3SCNEAR); ?></textarea></td>
                                                                            <td style="text-align:center;"><?php echo xlt('L{{left}}'); ?></td> 
                                                                        </tr>
                                                                        <tr>    
                                                                            <td style="text-align:middle;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                            <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                            <textarea id="ACT4SCNEAR" name="ACT4SCNEAR" class="ACT"><?php echo text($ACT4SCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;text-align:center;">
                                                                            <textarea id="ACT5SCNEAR" name="ACT5SCNEAR" class="neurosens2 ACT"><?php echo text($ACT5SCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                            <textarea id="ACT6SCNEAR" name="ACT6SCNEAR" class="ACT"><?php echo text($ACT6SCNEAR); ?></textarea></td>
                                                                            <td><i class="fa fa-share rotate-right"></i></td> 
                                                                        </tr> 
                                                                        <tr> 
                                                                            <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                                <textarea id="ACT10SCNEAR" name="ACT10SCNEAR" class="ACT"><?php echo text($ACT10SCNEAR); ?></textarea></td>
                                                                            <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                                <textarea id="ACT7SCNEAR" name="ACT7SCNEAR" class="ACT"><?php echo text($ACT7SCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                                <textarea id="ACT8SCNEAR" name="ACT8SCNEAR" class="ACT"><?php echo text($ACT8SCNEAR); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                                <textarea id="ACT9SCNEAR" name="ACT9SCNEAR" class="ACT"><?php echo text($ACT9SCNEAR); ?></textarea></td>
                                                                            <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                                <textarea id="ACT11SCNEAR" name="ACT11SCNEAR" class="ACT"><?php echo text($ACT11SCNEAR); ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                    <br />
                                                                </div>
                                                                <div id="ACT_CCNEAR" name="ACT_CCNEAR" class="nodisplay ACT_box">
                                                                    <br />
                                                                    <table> 
                                                                        <tr> 
                                                                            <td style="text-align:center;"><?php echo xlt('R{{right}}'); ?></td>    
                                                                            <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                            <textarea id="ACT1CCNEAR" name="ACT1CCNEAR" class="ACT"><?php echo text($ACT1CCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                            <textarea id="ACT2CCNEAR"  name="ACT2CCNEAR"class="ACT"><?php echo text($ACT2CCNEAR); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                            <textarea id="ACT3CCNEAR"  name="ACT3CCNEAR" class="ACT"><?php echo text($ACT3CCNEAR); ?></textarea></td>
                                                                            <td style="text-align:center;"><?php echo xlt('L{{left}}'); ?></td>
                                                                        </tr>
                                                                        <tr>    
                                                                            <td style="text-align:middle;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                            <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                            <textarea id="ACT4CCNEAR" name="ACT4CCNEAR" class="ACT"><?php echo text($ACT4CCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;text-align:center;">
                                                                            <textarea id="ACT5CCNEAR" name="ACT5CCNEAR" class="neurosens2 ACT"><?php echo text($ACT5CCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                            <textarea id="ACT6CCNEAR" name="ACT6CCNEAR" class="ACT"><?php echo text($ACT6CCNEAR); ?></textarea></td><td><i class="fa fa-share rotate-right"></i></td> 
                                                                        </tr> 
                                                                        <tr> 
                                                                            <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                                <textarea id="ACT10CCNEAR" name="ACT10CCNEAR" class="ACT"><?php echo text($ACT10CCNEAR); ?></textarea></td>
                                                                            <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                                <textarea id="ACT7CCNEAR" name="ACT7CCNEAR" class="ACT"><?php echo text($ACT7CCNEAR); ?></textarea></td>
                                                                            <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                                <textarea id="ACT8CCNEAR" name="ACT8CCNEAR" class="ACT"><?php echo text($ACT8CCNEAR); ?></textarea></td>
                                                                            <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                                <textarea id="ACT9CCNEAR" name="ACT9CCNEAR" class="ACT"><?php echo text($ACT9CCNEAR); ?></textarea></td>
                                                                            <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                                <textarea id="ACT11CCNEAR" name="ACT11CCNEAR" class="ACT"><?php echo text($ACT11CCNEAR); ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                   <br />
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <br />
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                    <div id="NPCNPA" name="NPCNPA">
                                        <table style="position:relative;float:left;text-align:center;margin: 4 2;width:100%;font-size:1.0em;padding:4px;">
                                            <tr style="font-weight:bold;"><td style="width:50%;"></td><td><?php echo xlt('OD{{right eye}}'); ?></td><td><?php echo xlt('OS{{left eye}}'); ?></td></tr>
                                            <tr>
                                                <td class="right"><span title="<?php xla('Near Point of Accomodation'); ?>"><?php echo xlt('NPA{{near point of accomodation}}'); ?>:</span></td>
                                                <td><input type="text" id="ODNPA" style="width:70%;" class="neurosens2" name="ODNPA" value="<?php echo attr($ODNPA); ?>"></td>
                                                <td><input type="text" id="OSNPA" style="width:70%;" class="neurosens2" name="OSNPA" value="<?php echo attr($OSNPA); ?>"></td>
                                            </tr>
                                            <tr>
                                                <td class="right"><span title="<?php xla('Near Point of Convergence'); ?>"><?php echo xlt('NPC{{near point of convergence}}'); ?>:</span></td>
                                                <td colspan="2" ><input type="text" style="width:85%;" class="neurosens2" id="NPC" name="NPC" value="<?php echo attr($NPC); ?>">
                                                </td>
                                            </tr>
                                             <tr>
                                                <td class="right">
                                                    <?php echo xlt('Stereopsis'); ?>:
                                                </td>
                                                <td colspan="2">
                                                    <input type="text" style="width:85%;" class="neurosens" name="STEREOPSIS" id="STEREOPSIS" value="<?php echo attr($STEREOPSIS); ?>">
                                                </td>
                                            </tr>
                                            <tr><td colspan="3" style="font-weight:bold;"><br /><u><?php echo xlt('Amplitudes'); ?></u><br />
                                                </td></tr>
                                            <tr><td ></td><td ><?php echo xlt('Distance'); ?></td><td><?php echo xlt('Near'); ?></td></tr>
                                            <tr>
                                                <td style="text-align:right;"><?php echo xlt('Divergence'); ?>: </td>
                                                <td><input type="text" id="DACCDIST" class="neurosens2" name="DACCDIST" value="<?php echo attr($DACCDIST); ?>"></td>
                                                <td><input type="text" id="DACCNEAR" class="neurosens2" name="DACCNEAR" value="<?php echo attr($DACCNEAR); ?>"></td></tr>
                                            <tr>
                                                <td style="text-align:right;"><?php echo xlt('Convergence'); ?>: </td>
                                                <td><input type="text" id="CACCDIST" class="neurosens2" name="CACCDIST" value="<?php echo attr($CACCDIST); ?>"></td>
                                                <td><input type="text" id="CACCNEAR" class="neurosens2" name="CACCNEAR" value="<?php echo attr($CACCNEAR); ?>"></td></tr>
                                            </tr>
                                             <tr>
                                                <td class="right">
                                                    <?php echo xlt('Vertical Fusional'); ?>:
                                                </td>
                                                <td colspan="2">
                                                    <input type="text" style="width:90%;" class="neurosens2" name="VERTFUSAMPS" id="VERTFUSAMPS" value="<?php echo attr($VERTFUSAMPS); ?>">
                                                    <br />
                                                </td>
                                            </tr>
                                        </table>
                                        <br />
                                    </div>
                                </div>
                                <div id="NEURO_MOTILITY" class="text_clinical borderShadow" 
                                    style="float:left;font-size:1.0em;margin:2 2;padding:0 10;font-weight:bold;height:120px;width:175px;">
                                    <div>
                                        <table style="width:100%;margin:0 0 1 0;">
                                            <tr>
                                                <td style="width:40%;font-size:1.0em;margin:0 auto;font-weight:bold;"><?php echo xlt('Motility'); ?>:</td>
                                                <td style="font-size:1.0em;vertical-align:middle;text-align:right;top:0.0in;right:0.1in;height:20px;">
                                                    <label for="MOTILITYNORMAL" class="input-helper input-helper--checkbox"><?php echo xlt('Normal'); ?></label>
                                                    <input id="MOTILITYNORMAL" name="MOTILITYNORMAL" type="checkbox" <?php if ($MOTILITYNORMAL =='on') echo "checked='checked'"; ?>>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <input type="hidden" name="MOTILITY_RS"  id="MOTILITY_RS" value="<?php echo attr($MOTILITY_RS); ?>">
                                    <input type="hidden" name="MOTILITY_RI"  id="MOTILITY_RI" value="<?php echo attr($MOTILITY_RI); ?>">
                                    <input type="hidden" name="MOTILITY_RR"  id="MOTILITY_RR" value="<?php echo attr($MOTILITY_RR); ?>">
                                    <input type="hidden" name="MOTILITY_RL"  id="MOTILITY_RL" value="<?php echo attr($MOTILITY_RL); ?>">
                                    <input type="hidden" name="MOTILITY_LS"  id="MOTILITY_LS" value="<?php echo attr($MOTILITY_LS); ?>">
                                    <input type="hidden" name="MOTILITY_LI"  id="MOTILITY_LI" value="<?php echo attr($MOTILITY_LI); ?>">
                                    <input type="hidden" name="MOTILITY_LR"  id="MOTILITY_LR" value="<?php echo attr($MOTILITY_LR); ?>">
                                    <input type="hidden" name="MOTILITY_LL"  id="MOTILITY_LL" value="<?php echo attr($MOTILITY_LL); ?>">
                                    
                                    <input type="hidden" name="MOTILITY_RRSO" id="MOTILITY_RRSO" value="<?php echo attr($MOTILITY_RRSO); ?>">
                                    <input type="hidden" name="MOTILITY_RRIO" id="MOTILITY_RRIO" value="<?php echo attr($MOTILITY_RLIO); ?>">
                                    <input type="hidden" name="MOTILITY_RLSO" id="MOTILITY_RLSO" value="<?php echo attr($MOTILITY_RLSO); ?>">
                                    <input type="hidden" name="MOTILITY_RLIO" id="MOTILITY_RLIO" value="<?php echo attr($MOTILITY_RLIO); ?>">
                                    
                                    <input type="hidden" name="MOTILITY_LRSO" id="MOTILITY_LRSO" value="<?php echo attr($MOTILITY_LRSO); ?>">
                                    <input type="hidden" name="MOTILITY_LRIO" id="MOTILITY_LRIO" value="<?php echo attr($MOTILITY_LLIO); ?>">
                                    <input type="hidden" name="MOTILITY_LLSO" id="MOTILITY_LLSO" value="<?php echo attr($MOTILITY_LLSO); ?>">
                                    <input type="hidden" name="MOTILITY_LLIO" id="MOTILITY_LLIO" value="<?php echo attr($MOTILITY_LLIO); ?>">
                                    
                                    <div style="float:left;left:0.4in;"><?php echo xlt('OD{{right eye}}'); ?></div>
                                    <div style="float:right;right:0.4in;"><?php echo xlt('OS{{left eye}}'); ?></div><br />
                                    <div class="divTable" style="background: url(../../forms/<?php echo $form_folder; ?>/images/eom.bmp) no-repeat center center;background-size: 90% 75%;height:0.77in;width:0.71in;padding:1px;margin:6 1 1 2;">
                                        <div class="divRow">
                                            <div class="divCell">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RRSO_4" id="MOTILITY_RRSO_4">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRSO_4_2" id="MOTILITY_RRSO_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRSO_3_2" id="MOTILITY_RRSO_3_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_4_3" id="MOTILITY_RS_4_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_4_1" id="MOTILITY_RS_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_4" id="MOTILITY_RS_4" value="<?php echo attr($MOTILITY_RS); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_4_2" id="MOTILITY_RS_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_4_4" id="MOTILITY_RS_4_4">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_3_1" id="MOTILITY_RLSO_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_4_1" id="MOTILITY_RLSO_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_4" id="MOTILITY_RLSO_4">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RRSO_4_1" id="MOTILITY_RRSO_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRSO_3" id="MOTILITY_RRSO_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRSO_2_2" id="MOTILITY_RRSO_2_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_3_1" id="MOTILITY_RS_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_3" id="MOTILITY_RS_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_3_2" id="MOTILITY_RS_3_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_2_1" id="MOTILITY_RLSO_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_3" id="MOTILITY_RLSO_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_4_2" id="MOTILITY_RLSO_4_2">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RRSO_3_1" id="MOTILITY_RRSO_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRSO_2_1" id="MOTILITY_RRSO_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRSO_2" id="MOTILITY_RRSO_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_2_1" id="MOTILITY_RS_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_2" id="MOTILITY_RS_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_2_2" id="MOTILITY_RS_2_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_2" id="MOTILITY_RLSO_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_2_2" id="MOTILITY_RLSO_2_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_232" id="MOTILITY_RLSO_3_2">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRSO_1" id="MOTILITY_RRSO_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_1_1" id="MOTILITY_RS_1_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_1" id="MOTILITY_RS_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_1_2" id="MOTILITY_RS_1_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLSO_1" id="MOTILITY_RLSO_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RR_4_3" id="MOTILITY_RR_4_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_4_1" id="MOTILITY_RR_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_3_1" id="MOTILITY_RR_3_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_0_1" id="MOTILITY_RS_0_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_0" id="MOTILITY_RS_0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RS_0_2" id="MOTILITY_RS_0_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_3_1" id="MOTILITY_RL_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_4_1" id="MOTILITY_RL_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_4_3" id="MOTILITY_RL_4_3">&nbsp;</div>
                                        </div>
                                        <div class="divMiddleRow">
                                            <div class="divCell" name="MOTILITY_RR_4_4" id="MOTILITY_RR_4_4">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_4" id="MOTILITY_RR_4" value="<?php echo attr($MOTILITY_RR); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_3" id="MOTILITY_RR_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_2" id="MOTILITY_RR_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_1" id="MOTILITY_RR_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_0" id="MOTILITY_RR_0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_R0" id="MOTILITY_R0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_0" id="MOTILITY_RL_0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_1" id="MOTILITY_RL_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_2" id="MOTILITY_RL_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_3" id="MOTILITY_RL_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_4" id="MOTILITY_RL_4" value="<?php echo attr($MOTILITY_RL); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_4_4" id="MOTILITY_RL_4_4">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RR_4_5" id="MOTILITY_RR_4_5">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_4_2" id="MOTILITY_RR_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RR_3_2" id="MOTILITY_RR_3_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_0_1" id="MOTILITY_RI_0_1">&nbsp;</div>
                                            <div class="divCell" id="MOTILITY_RI_0" name="MOTILITY_RI_0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_0_2" id="MOTILITY_RI_0_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_3_2" id="MOTILITY_RL_3_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_4_2" id="MOTILITY_RL_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RL_4_5" id="MOTILITY_RL_4_5">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRIO_1" id="MOTILITY_RRIO_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_1_1" id="MOTILITY_RI_1_1">&nbsp;</div>
                                            <div class="divCell" id="MOTILITY_RI_1" name="MOTILITY_RI_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_1_2" id="MOTILITY_RI_1_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_1" id="MOTILITY_RLIO_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RRIO_3_1" id="MOTILITY_RRIO_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRIO_2_1" id="MOTILITY_RRIO_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRIO_2"   id="MOTILITY_RRIO_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_2_1" id="MOTILITY_RI_2_1">&nbsp;</div>
                                            <div class="divCell" id="MOTILITY_RI_2" name="MOTILITY_RI_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_2_2" id="MOTILITY_RI_2_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_2" id="MOTILITY_RLIO_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_2_1" id="MOTILITY_RLIO_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_3_1" id="MOTILITY_RLIO_3_1">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RRIO_4_1" id="MOTILITY_RRIO_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRIO_3" id="MOTILITY_RRIO_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRIO_2_2" id="MOTILITY_RRIO_2_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_3_1" id="MOTILITY_RI_3_1">&nbsp;</div>
                                            <div class="divCell" id="MOTILITY_RI_3" name="MOTILITY_RI_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_3_2" id="MOTILITY_RI_3_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_2_2" id="MOTILITY_RLIO_2_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLI0_3" id="MOTILITY_RLIO_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_4_1" id="MOTILITY_RLIO_4_1">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_RRIO_4" id="MOTILITY_RRIO_4" value="<?php echo attr($MOTILITY_RRIO); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRIO_4_2" id="MOTILITY_RRIO_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RRIO_3_2" id="MOTILITY_RRIO_3_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_4_3" id="MOTILITY_RI_4_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_4_1" id="MOTILITY_RI_4_1">&nbsp;</div>
                                            <div class="divCell" id="MOTILITY_RI_4" name="MOTILITY_RI_4" value="<?php echo attr($MOTILITY_RI); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_4_2" id="MOTILITY_RI_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RI_4_4" id="MOTILITY_RI_4_4">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_3_2" id="MOTILITY_RLIO_3_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_4_2" id="MOTILITY_RLIO_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_RLIO_4" id="MOTILITY_RLIO_4" value="<?php echo attr($MOTILITY_RLIO); ?>">&nbsp;</div>
                                        </div>   
                                        <div class="divRow">
                                          <div class="divCell">&nbsp;</div>
                                        </div>
                                    </div> 
                                    <div class="divTable" style="float:right;background: url(../../forms/<?php echo $form_folder; ?>/images/eom.bmp) no-repeat center center;background-size: 90% 75%;height:0.77in;width:0.71in;padding:1px;margin:6 2 0 0;">
                                        <div class="divRow">
                                            <div class="divCell">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_LRSO_4" id="MOTILITY_LRSO_4" value="<?php echo attr($MOTILITY_LRSO); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LRSO_4_2" id="MOTILITY_LRSO_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LRSO_3_3" id="MOTILITY_LRSO_3_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_4_3" id="MOTILITY_LS_4_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_4_1" id="MOTILITY_LS_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_4" id="MOTILITY_LS_4" value="<?php echo attr($MOTILITY_LS); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_4_2" id="MOTILITY_LS_4_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_4_4" id="MOTILITY_LS_4_4">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_3_1" id="MOTILITY_LLSO_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_4_1" id="MOTILITY_LLSO_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_4" id="MOTILITY_LLSO_4" value="<?php echo attr($MOTILITY_LLSO); ?>">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_LRSO_4_1" id="MOTILITY_LRSO_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LRSO_3" id="MOTILITY_LRSO_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LRSO_2_2" id="MOTILITY_LRSO_2_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_3_1" id="MOTILITY_LS_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_3" id="MOTILITY_LS_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_3_2" id="MOTILITY_LS_3_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_2_1" id="MOTILITY_LLSO_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_3" id="MOTILITY_LLSO_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_4_2" id="MOTILITY_LLSO_4_2">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_LRSO_3_1" id="MOTILITY_LRSO_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LRSO_2_1" id="MOTILITY_LRSO_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LRSO_2" id="MOTILITY_LRSO_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_2_1" id="MOTILITY_LS_2_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_2" id="MOTILITY_LS_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_2_2" id="MOTILITY_LS_2_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_2" id="MOTILITY_LLSO_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_2_2" id="MOTILITY_LLSO_2_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_3_2" id="MOTILITY_LLSO_3_2">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LRSO_1" id="MOTILITY_LRSO_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_1_1" id="MOTILITY_LS_1_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_1" id="MOTILITY_LS_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_1_2" id="MOTILITY_LS_1_2">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LLSO_1" id="MOTILITY_LLSO_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                        </div>
                                        <div class="divRow">
                                            <div class="divCell" name="MOTILITY_LR_4_3" id="MOTILITY_LR_4_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LR_4_1" id="MOTILITY_LR_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LR_3_1" id="MOTILITY_LR_3_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_0_1" id="MOTILITY_LS_0_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_0" id="MOTILITY_LS_0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LS_0_1" id="MOTILITY_LS_0_1">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LL_3_1" id="MOTILITY_LL_3_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LL_4_1" id="MOTILITY_LL_4_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LL_4_3" id="MOTILITY_LL_4_3">&nbsp;</div>
                                        </div>
                                        <div class="divMiddleRow">
                                            <div class="divCell" name="MOTILITY_LR_4_4" id="MOTILITY_LR_4_4">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LR_4" id="MOTILITY_LR_4" value="<?php echo attr($MOTILITY_LR); ?>">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LR_3" id="MOTILITY_LR_3">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LR_2" id="MOTILITY_LR_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LR_1" id="MOTILITY_LR_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LR_0" id="MOTILITY_LR_0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_L0" id="MOTILITY_L0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LL_0" id="MOTILITY_LL_0">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LL_1" id="MOTILITY_LL_1">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LL_2" id="MOTILITY_LL_2">&nbsp;</div>
                                            <div class="divCell" name="MOTILITY_LL_3" id="MOTILITY_LL_3">&nbsp;</div>
      