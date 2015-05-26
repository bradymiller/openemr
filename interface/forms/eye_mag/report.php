    <?php

    /** 
     * forms/eye_mag/report.php 
     * 
     * Central report form for the eye_mag form.  Here is where all new data for display
     * is created.  New reports are created via new.php and then this script is displayed.
     * Edit are performed in view.php.  Nothing is editable here, but it is scrollable 
     * across time...
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
    include_once("$srcdir/api.inc");
    include_once("$srcdir/sql.inc");
    require_once("$srcdir/formatting.inc.php");

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
        @extract($prefs);    
        $$LOCATION = $VALUE; 
    }
function eye_mag_report($pid, $encounter, $cols, $id, $formname) {
    global $form_folder;
    global $form_name;
    // get pat_data and user_data
    $query = "SELECT * FROM patient_data where pid=?";
    $pat_data =  sqlQuery($query,array($pid));
    @extract($pat_data);

    $query = "SELECT * FROM users where id = ?";
    $prov_data =  sqlQuery($query,array($_SESSION['authUserID']));
    $providerID = $prov_data['fname']." ".$prov_data['lname'];

    /** openEMR note:  eye_mag Index is id, 
      * linked to encounter in form_encounter 
      * whose encounter is linked to id in forms.
      * Would a DB VIEW be a better way to access this data?
      * If it matters we can create the VIEW right here in eye_mag
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
    //var_dump($objQuery);
    $dated = new DateTime($encounter_date);
    $visit_date = $dated->format('m/d/Y'); 
    /*
    There is a global setting for displaying dates...
    If this form only uses visit_date for display purposes then use the global preference above instead.
    */
   // formHeader("Chart: ".$pat_data['fname']." ".$pat_data['lname']." ".$visit_date);

    ?>

        
        <?php 
            /**  Time to decide what to display.
              *  Suggestions for this time:
              *  1. Dictation style report with printed data
              *  2. If drawing is all they want
              *  3. Legal document.
              *  4. Word processor to edit.  Stored as unique document.
              *  5. Create a new, additional report.
              */
            //  see save.php

            /*
            This displays the first two drawings in the encounter page, which calls report.php in main openEMR
            If you want to display something else in this pop-up area, alter this.
            The variable $choice will tell us what to display.
            * @param string $choice options NULL,TEXT,DRAW,NARRATIVE
            * @param string $encounter  encounter number
            * @param string $pid value = patient id
            * @return string returns the HTML old record selector widget for the desired zone 
            */    
            //$choice = 'drawing';    
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
                   ?><div class='bordershadow' style='position:relative;float:left;width:100px;height:75px;'>
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
                <Xdiv class="XXXXXXborderShadow">
                    <?php display_draw_section ("VISION",$encounter,$pid); ?>
                </Xdiv>
                <Xdiv class="XXXborderShadow">
                    <br />
                    <?php display_draw_section ("NEURO",$encounter,$pid); ?>
                </Xdiv>
                <Xdiv class="XXXborderShadow">
                    <br />
                    <?php display_draw_section ("EXT",$encounter,$pid); ?>
                </Xdiv>
                <Xdiv class="XXXborderShadow">
                    <br />
                    <?php display_draw_section ("ANTSEG",$encounter,$pid); ?>
                </Xdiv>
                <Xdiv class="XXXborderShadow">
                    <br />
                    <?php display_draw_section ("RETINA",$encounter,$pid); ?>
                </Xdiv>
                <Xdiv class="XXXborderShadow">
                    <br />
                    <?php display_draw_section ("IMPPLAN",$encounter,$pid); ?>
                </Xdiv>
                <? 
            } else if ($choice !="narrative") {
              narrative($pid, $encounter, $cols, $id);
             //   echo "hello $pid, $encounter, $cols, $form_id";
            }
            ?>
    <?
}
function left_overs() {
    /***************************************/
        $count = 0;
        $data = formFetch($table_name, $id);
       
        if ($data) {
            foreach($data as $key => $value) {
                $$key=$value;
            }
        }
 }
   
    
function narrative($pid, $encounter, $cols, $form_id) {
    // Create a narrative
    // Patient data, Practice Data, Exam Data
    global $form_folder;
    //menu_overhaul_left($pid,$encounter);
     //
    $query="select form_encounter.date as encounter_date,form_encounter.*, form_eye_mag.* from form_eye_mag ,forms,form_encounter 
                    where 
                    form_encounter.encounter =? and 
                    form_encounter.encounter = forms.encounter and 
                    form_eye_mag.id=forms.form_id and
                    forms.deleted != '1' and 
                    form_eye_mag.pid=? ";        

    $encounter_data =sqlQuery($query,array($encounter,$pid));
    @extract($encounter_data);
    ?>
    <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/pure-min.css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/bootstrap-3-2-0.min.css">
    <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/font-awesome-4.2.0/css/font-awesome.css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['css_header']; ?>" type="text/css">
    <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/style.css" type="text/css">    
    
    <style>
 
        .refraction_panel {
    display:none;
    width:95%;
    height:auto;
}

.refraction2 {
    float: left;
    border: 1.00pt solid #000000; 
    padding: 0.1in; 
    background: #ffffff;
    box-shadow: 10px 10px 50px #888888;
    border-radius: 8px;
    margin-right: 4px; 
    border:1pt solid; 
    font-size:0.6em;
    webkit-box-flex: 0;
    -moz-box-flex: 0;
    box-flex: 0;
    input[type="text"] {
        width:20px;
        padding: 0.2em 0.4em;
        display: inline-block;
    }
}
.refraction {
    display:inline-block;
    min-height:2.2in;
    
    border: 1.00pt solid #000000; 
    padding: 0.2in; 
    box-sizing: content-box !important;
    box-shadow: 10px 10px 5px #888888;
    border-radius: 8px;
    margin: 5 auto;
    margin-right: 4px; 
    width:4.0in;
    font-size: 1.1em;
    
}
.refraction td {
    text-align:center;
    font-size:0.7em;
    width:0.35in;
    vertical-align: text-middle;
    text-decoration: none;
    padding: 3px;
}
.refraction th {
    height:0.17in;
    font-size: 0.8em;
    text-align: left;
    padding: 5 0 2 0;
    color: black; 
}
.refraction ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

.refraction li {
    float: left;
}

input[type=text].refraction {
    padding: 0.2em 0.4em;
    display: inline-block;
    text-align: right;
    width:150px;
}
.refraction  b{
    text-decoration:bold;
}
.report_text {
    padding:2 10 2 10;
}
.label {
    color:black;
    border-radius:none;
}
table {
    background-color: #fff;

}
.middle {
    text-align: center;
    font-weight: bold;
}
</style>
    <table>
        <tr>
            <td style="width:170px;border-right:0.5pt solid grey;vertical-align:top;">
                <?
                require_once($GLOBALS['fileroot'].'/custom/code_types.inc.php');
                require_once($GLOBALS['srcdir'].'/options.inc.php');
                 // Check authorization.
                if (acl_check('patients','med')) {
                    $tmp = getPatientData($pid);
                }
                 // Collect parameter(s)
                $category = empty($_REQUEST['category']) ? '' : $_REQUEST['category'];
                ?>
                <br />
                    <?php
                    $encount = 0;
                    $lasttype = "";
                    $first = 1; // flag for first section
                    $counter="0";
                    global $ISSUE_TYPES;
                    foreach ($ISSUE_TYPES as $focustype => $focustitles) {
                        
                        if ($category) {
                            //    Only show this category
                         //  if ($focustype != $category) continue;
                        }
                        $column_length = '15';
                        $disptype = $focustitles[0];
                        $dispzone_name= $ISSUE_TYPES[$counter];
                        //str_replace(' ', '_', strtolower($focustitles[0]));
                        $dispzone_numb=$focustitles[2];
                        
                        $pres = sqlStatement("SELECT * FROM lists WHERE pid = ? AND type = ? " .
                            "ORDER BY begdate DESC", array($pid,$focustype) );
                        $counter_previous = $counter;
                        $counter = $counter + sqlNumRows($pres);
                       
                       
                           ?>
                        <b><?php echo $disptype; ?></b>
                        <br />
                        <?

                        // if no issues (will place a 'None' text vs. toggle algorithm here)
                        if (sqlNumRows($pres) < 1) {
                            echo  xla("None") ."<br /><br /><br />";
                            continue;
                        }

                        while ($row = sqlFetchArray($pres)) {
                            $rowid = $row['id'];
                            $disptitle = trim($row['title']) ? $row['title'] : "[Missing Title]";
                            echo $disptitle."<br />";
                            continue;

                            $ierow = sqlQuery("SELECT count(*) AS count FROM issue_encounter WHERE " .
                              "list_id = ?", array($rowid) );

                            $codetext = "";
                            if ($row['diagnosis'] != "") {
                                $diags = explode(";", $row['diagnosis']);
                                foreach ($diags as $diag) {
                                    $codedesc = lookup_code_descriptions($diag);
                                    $codetext .= xlt($diag) . " (" . xlt($codedesc) . ")<br />";
                                }
                            }

                            // calculate the status
                            if ($row['outcome'] == "1" && $row['enddate'] != NULL) {
                              // Resolved
                             $statusCompute = generate_display_field(array('data_type'=>'1','list_id'=>'outcome'), $row['outcome']);
                            }
                            else if($row['enddate'] == NULL) {
                             $statusCompute = htmlspecialchars( xl("Active") ,ENT_NOQUOTES);
                            }
                            else {
                              $statusCompute = htmlspecialchars( xl("Inactive") ,ENT_NOQUOTES);
                            }
                            $click_class='statrow';
                            if($row['erx_source']==1 && $focustype=='allergy')
                            $click_class='statrow'; //changed from '' on 2/23/15
                            elseif($row['erx_uploaded']==1 && $focustype=='medication')
                            $click_class='';
                            // output the TD row of info
                             if ($focustype == "surgery")    echo "  <span style='text-align:right'>" . htmlspecialchars($row['begdate'],ENT_NOQUOTES) . "&nbsp;</span>\n";
                            
                            echo xlt($disptitle);
                            if ($focustype == "allergy" && $row['reaction'] > '') {
                                echo " (" . htmlspecialchars($row['reaction'],ENT_NOQUOTES).") " ;
                            }
                               echo "<br />";
                        }

                        echo " <br /> <br />";
                    }
                    echo '<b>ROS</b><br />';
                   
                        // if no issues (will place a 'None' text vs. toggle algorithm here)
                        if ($count_ROS < 1) {
                            echo  xla("Reviewed") ."<br /><br /><br />";
                            
                            $counter= $counter-3;
                        } else 
                        { 
                            //print out a ROS.  Give ?expand option to see full list in a pop-up?
                        }
                            
                        ?>
            </td>
            <!-- End left column PMSFH -->

            <!-- Begin right column -->
            <td style="float:left;width:580px;background-color:#fff;text-align:left;padding-left:0.1in">
                    <b><?php echo xlt('Chief Complaint'); ?>:</b> <br />&nbsp;<?php echo text($CC1); ?>
                    <br /><br />
                    <b><?php echo xlt('HPI'); ?>:</b> <?php echo text($HPI1); ?>
                    <br />
                    <div style="padding-left:10px;">
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
                            echo "<br />
                            <br />";
                            echo "<b>".xlt('Chief Complaint 2'); ?>:</b> &nbsp;<?php echo text($CC2); ?>
                            <br />
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
                                    echo "<i>".xlt('Associated'); ?>:</i> &nbsp;<?php echo text($ASSOCIATED2)."<br />"; }
                                if ($LOCATION2) {
                                    echo "<i>".xlt('Location'); ?>:</i> &nbsp;<?php echo text($LOCATION2)."<br />";}
                                if ($QUALITY2) {
                                    echo "<i>".xlt('Quality'); ?>:</i> &nbsp;<?php echo text($QUALITY2)."<br />";}
                                if ($DURATION2) {
                                    echo "<i>".xlt('Duration'); ?>:</i> &nbsp;<?php echo text($DURATION2)."<br />";
                                }
                                ?>
                            </div>
                            <?
                        }
                        if ($CC3) {
                          ?>
                            <br />
                            <br />
                            <?php echo "<b>".xlt('Chief Complaint 3'); ?>:</b> &nbsp;<?php echo text($CC3); ?>
                            <br />
                            <?php echo xlt('HPI'); ?>&nbsp; <?php echo text($HPI3); ?>
                            <br />
                            <div style="padding-left:10px;">
                                <?php 
                                if ($TIMING3) {
                                    echo "<i>".xlt('Timing'); ?>:</i>  &nbsp;<?php echo text($TIMING3); 
                                }
                                if ($CONTEXT3) {
                                    echo "<i>".xlt('Context'); ?>:</i> &nbsp;<?php echo text($CONTEXT3); 
                                }
                                if ($SEVERITY3) {
                                    echo "<i>".xlt('Severity'); ?>:</i> &nbsp;<?php echo text($SEVERITY3); 
                                }
                                if ($MODIFY3) {
                                    echo "<i>".xlt('Modifying'); ?>:</i> &nbsp;<?php echo text($MODIFY3);
                                }
                                if ($ASSOCIATED3) {
                                    echo "<i>".xlt('Associated'); ?>:</i> &nbsp;<?php echo text($ASSOCIATED3); }
                                if ($LOCATION3) {
                                    echo "<i>".xlt('Location'); ?>:</i> &nbsp;<?php echo text($LOCATION3);}
                                if ($QUALITY3) {
                                    echo "<i>".xlt('Quality'); ?>:</i> &nbsp;<?php echo text($QUALITY3);}
                                if ($DURATION3) {
                                    echo "<i>".xlt('Duration'); ?>:</i> &nbsp;<?php echo text($DURATION3)."<br />";
                                }
                                ?>
                            </div>
                    
                       
                            <div id="tab2_HPI_text" class="nodisplay tab_content" style="min-height: 2.0in;text-align:left;">                 
                            </div>
                             <? 
                        } 
                        ?>
                        <br /><br />
                    </div>
                <!-- START OF THE PRESSURE BOX -->
                <table style="padding:5;">
                    <tr>
                        <td>
                            <div style="min-height:1.3in;text-align:center;padding: 10; margin:2;border: 1pt solid black;">
                                <table style="font-size:14px;"> 
                                <tr><td colspan="3"><b class="underline"><?php echo xlt('Intraocular Pressures'); ?>:</b><br />
                                 <br />@ <?php echo attr($IOPTIME); ?></td></tr>
                                <tr><td><B>Method</b></td><td><b>OD</b></td><td><b>OS</b></td></tr>
                                <?php
                                    echo "<tr><td>Applanation:</td><td>".$ODIOPAP."</td><td>".attr($OSIOPAP)."</td></tr>";
                                    echo "<tr><td>Tonopen:</td><td>".$ODIOPTPN."</td><td>".attr($OSIOPTPN)."</td></tr>";
                                    echo "<tr><td>Palpation:</td><td>".$ODIOPFTN."</td><td>".attr($OSIOPFTN)."</td></tr>";
                                ?>
                                </table>
                            </div>
                        </td>
                        <td>
                            <?php 
                              if (!$AMSLEROD) $AMSLEROD= "0";
                              if (!$AMSLEROS) $AMSLEROS= "0";
                              
                            ?> 
                            <div style="min-height:1.3in;text-align:center;padding: 10; margin:2;border: 1pt solid black;">
                                <table style="font-size:14px;">
                                <tr>
                                    <td style="text-align:left;"><b class="underline">Amsler:</b><br /></td>
                                    <td></td>
                                    <td style="text-align:right;"></td>
                                </tr>
                                <tr>
                                    <td style="text-align:center;"><b><?php echo xlt('OD'); ?></b>
                                      </td>
                                      <td></td>
                                      <td style="text-align:center;"><b><?php echo xlt('OS'); ?></b>
                                      </td>
                                </tr>

                                <tr>
                                      <td style="text-align:center;">
                                          <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROD); ?>.jpg" id="AmslerOD" style="margin:0.05in;height:0.5in;width:0.6in;" />
                                          <br /><small><?php echo text($AMSLEROD); ?>/5</small></td>
                                      <td></td>
                                      <td style="text-align:center;">
                                          <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROS); ?>.jpg" id="AmslerOS" style="margin:0.05in;height:0.5in;width:0.6in;" />
                                          <br /><small><?php echo text($AMSLEROS); ?>/5</small></td>
                                </tr>
                                </table>
                            </div>
                        </td>
                        <td>
                            <!-- start of the Fields box -->
                            <div style="min-height:1.3in;text-align:center;padding: 10; margin:2;border: 1pt solid black;">
                            
                                <table style="font-size:14px;">
                                <tr>
                                    <td style="text-align:left;"><b class="underline"><?php echo xlt('Fields'); ?>:</b></td>
                                    <td style="text-align:right;">                                    
                                          <?php 
                                              // if the VF zone is checked, display it
                                              // if ODVF1 = 1 (true boolean) the value="0" checked="true"
                                              for ($z=1; $z <5; $z++) {
                                                  $ODzone = "ODVF".$z;
                                                  if ($$ODzone =='1') {
                                                      $ODVF[$z] = '<i class="fa fa-square fa-3"></i>';
                                                  } else {
                                                    $ODVF[$z] = '<i class="fa fa-square-o fa-3"></i>';
                                                  }
                                                  $OSzone = "OSVF".$z;
                                                  if ($$OSzone =="1") {
                                                      $OSVF[$z] = '<i class="fa fa-square fa-3"></i>';
                                                      $bad++;
                                                  } else {
                                                   $OSVF[$z] = '<i class="fa fa-square-o fa-3"></i>';
                                                  }
                                              }
                                              
                                          ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="text-align:center;">   <br />          
                                        <table cellpadding='0' cellspacing="0"> 
                                          <tr>    
                                              <td style="width:0.5in;text-align:center;" colspan="2"><b><?php echo xlt('OD'); ?></b><br /></td>

                                              <td style="width:0.1in;"> </td>
                                              <td style="width:0.5in;text-align:center;" colspan="2"><b><?php echo xlt('OS'); ?></b></td>
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
                                    
                                    </td>
                                </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <div style="min-height:1.3in;width:100%;margin:5 auto 5 auto;">
                                <!-- start of the Pupils box -->
                                <div style="min-height:1.2in;margin:2;float:left; width:2.25in;padding: 5; border: 1.00pt solid #000000; ">  
                              <b class="underline"><?php echo xlt('Pupils'); ?>:</b> <br />
                              <div id="Lyr7.0" style="border: none;padding:15;">
                                  <table cellpadding=1 cellspacing=1 style="font-size: 0.9em;"> 
                                      <tr>    
                                          <th style="width:0.4in;"> &nbsp;
                                          </th>
                                          <th style="width:1.0in;padding: 0.1;"><?php echo xlt('size'); ?> (<?php echo xlt('mm'); ?>)
                                          </th>
                                          <th style="width:0.4in;padding: 0.1;"><?php echo xlt('react'); ?> 
                                          </th>
                                          <th style="width:0.4in;padding: 0.1;"><?php echo xlt('APD'); ?>
                                          </th>
                                      </tr>
                                      <tr>    
                                          <td><b><?php echo xlt('OD'); ?></b>
                                          </td>
                                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;">
                                            <?php echo attr($ODPUPILSIZE1); ?>
                                               --> 
                                              <?php echo attr($ODPUPILSIZE2); ?>
                                          </td>
                                          <td style="text-align:center;border-left:1pt solid black;border-right:1pt solid black;border-bottom:1pt solid black;">
                                            <?php echo attr($ODPUPILREACTIVITY); ?>
                                          </td>
                                          <td style="text-align:center;border-bottom:1pt solid black;">
                                            <?php echo attr($ODAPD); ?>
                                          </td>
                                      </tr>
                                      <tr>    
                                          <td><b><?php echo xlt('OS'); ?></b>
                                          </td>
                                          <td style="border-right:1pt solid black;border-top:1pt solid black;">
                                            <?php echo attr($OSPUPILSIZE1); ?>
                                               -->
                                              <?php echo attr($OSPUPILSIZE2); ?>
                                          </td>
                                          <td style="text-align:center;border-left:1pt solid black;border-right:1pt solid black;border-top:1pt solid black;">
                                            <?php echo attr($OSPUPILREACTIVITY); ?>
                                          </td>
                                          <td style="text-align:center;border-top:1pt solid black;">
                                            <?php echo attr($OSAPD); ?>
                                          </td>
                                      </tr>
                                  </table>
                              </div>  
                                </div>
                                    <!-- start of slide down pupils_panel --> 
                                <div id="dim_pupils_panel" style="min-height:1.2in;float:left;margin:2;width:2.25in;padding: 5; border: 1.00pt solid #000000; ">  
                <b  class="underline"><?php echo xlt('Pupils') ?>: <?php echo xlt('Dim'); ?></b>
                  <div id="Lyr7.1" style="top: 0.4in; left: 0.15in; border: none;padding:5;">
                      <table cellpadding="1" cellpadding="1" style="font-size: 0.9em;"> 
                          <tr>    
                              <th></th>
                              <th style="width:0.7in;padding: 0;"><?php echo xlt('size'); ?> (<?php echo xlt('mm'); ?>)
                              </th>
                          </tr>
                          <tr>    
                              <td><b><?php echo xlt('OD'); ?></b>
                              </td>
                              <td style="border-bottom:1pt solid black;padding-left:0.1in;">
                                <?php echo attr($DIMODPUPILSIZE1); ?>
                                   --> 
                                  <?php echo attr($DIMODPUPILSIZE2); ?>
                              </td>
                          </tr>
                          <tr>    
                              <td ><b><?php echo xlt('OS'); ?></b>
                              </td>
                              <td style="border-top:1pt solid black;padding-left:0.1in;">
                                <?php echo attr($DIMOSPUPILSIZE1); ?>
                                   --> 
                                  <?php echo attr($DIMOSPUPILSIZE2); ?>
                              </td>
                          </tr>
                          <tr>
                            <td colspan="2">
                                <b><?php echo xlt('Comments'); ?>:</b><br />
                                <?php echo text($PUPIL_COMMENTS); ?>
                            </td>
                        </tr>
                      </table>
                  </div>   
                  
                                </div> 
                            </div>
                                <!-- end of slide down pupils_panel --> 
                                <!-- end of the Pupils boxes --><br />
                            <!-- Start of the Vision box -->            
                            <table id="Additional_VA" cellspacing="2" style="text-align:center;">
                                <tr style="text-align:left;"><td colspan="8"><b class="underline"><?php echo xlt('Visual Acuities'); ?>:</b></td></tr>
                                <tr class="underline"><td style="width:0.4in;"></td>
                                    <td><?php echo xlt('sc'); ?></td>
                                    <td><?php echo xlt('cc'); ?></td>
                                    <td><?php echo xlt('AR'); ?></td>
                                    <td><?php echo xlt('MR'); ?></td>
                                    <td><?php echo xlt('CR'); ?></td>
                                    <td><?php echo xlt('PH'); ?></td>
                                    <td><?php echo xlt('CTL'); ?></td>
                                </tr>
                                <tr><td><b><?php echo xlt('OD'); ?></b></td>
                                    <td><?php echo $SCODVA; ?></td>
                                    <td><?php echo $WODVA; ?></td>
                                    <td><?php echo $ARODVA; ?></td>
                                    <td><?php echo $MRODVA; ?></td>
                                    <td><?php echo $CRODVA; ?></td>
                                    <td><?php echo $PHODVA; ?></td>
                                    <td><?php echo $CTLODVA; ?></td>
                                </tr>
                                <tr><td><b><?php echo xlt('OS'); ?></b></td>
                                    <td><?php echo $SCOSVA; ?></td>
                                    <td><?php echo $WOSVA; ?></td>
                                    <td><?php echo $AROSVA; ?></td>
                                    <td><?php echo $MROSVA; ?></td>
                                    <td><?php echo $CROSVA; ?></td>
                                    <td><?php echo $PHOSVA; ?></td>
                                    <td><?php echo $CTLOSVA; ?></td>
                                </tr>
                                <tr><td>&nbsp;</td></tr>
                                <tr>
                                    <td></td>
                                    <td><?php echo xlt('scNear'); ?></td>
                                    <td><?php echo xlt('ccNear'); ?></td>
                                    <td><?php echo xlt('ARNear'); ?></td>
                                    <td><?php echo xlt('MRNear'); ?></td>
                                    <td><?php echo xlt('PAM'); ?></td>
                                    <td><?php echo xlt('Glare'); ?></td>
                                    <td><?php echo xlt('Contrast'); ?></td>
                                </tr>
                                <tr><td><b><?php echo xlt('OD'); ?>:</b></td>
                                    <td><?php echo $SCNEARODVA; ?></td>
                                    <td><?php echo $WNEARODVA; ?></td>
                                    <td><?php echo $ARNEARODVA; ?></td>
                                    <td><?php echo $MRNEARODVA; ?></td>
                                    <td><?php echo $PAMODVA; ?></td>
                                    <td><?php echo $GLAREODVA; ?></td>
                                    <td><?php echo $CONTRASTODVA; ?></td>
                                </tr>
                                <tr><td><b><?php echo xlt('OS'); ?>:</b></td>
                                    <td><?php echo $SCNEAROSVA; ?></td>
                                    <td><?php echo $WNEAROSVA; ?></td>
                                    <td><?php echo $ARNEAROSVA; ?></td>
                                    <td><?php echo $MRNEAROSVA; ?></td>
                                    <td><?php echo $PAMOSVA; ?></td>
                                    <td><?php echo $GLAREOSVA; ?></td>
                                    <td><?php echo $CONTRASTOSVA; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                       
            </td>
        </tr>
            <tr>
            <td colspan="2">
                <!-- start of the refraction boxes -->
                <div style="text-align:left;float:left;margin-left:10px;" class="section" >
                <?php ($WODSPH) ? ($display_W = "display") : ($display_W = "display"); ?>
                <div id="LayerVision_W" class="refraction <?php echo $display_W; ?>">
                    <table id="wearing" >
                        <tr>
                            <th colspan="9" id="wearing_title"><?php echo xlt('Current Glasses'); ?>
                            </th>
                        </tr>
                        <tr style="font-weight:400;">
                            <td></td>
                            <td></td>
                            <td><?php echo xlt('Sph'); ?></td>
                            <td><?php echo xlt('Cyl'); ?></td>
                            <td><?php echo xlt('Axis'); ?></td>
                            <td><?php echo xlt('Prism'); ?></td>
                            <td><?php echo xlt('Acuity'); ?></td>
                            <td rowspan="7" class="right" style="padding:10 0 10 0;">
                                 </td>
                        </tr>
                        <tr>
                            <td rowspan="2"><?php echo xlt('Dist'); ?></td>    
                            <td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td><?php echo $WODSPH; ?></td>
                            <td><?php echo $WODCYL; ?></td>
                            <td><?php echo $WODAXIS; ?></td>
                            <td><?php echo $WODPRISM; ?></td>
                            <td><?php echo $WODVA; ?></td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td><?php echo $WOSSPH; ?></td>
                            <td><?php echo $WOSCYL; ?></td>
                            <td><?php echo $WOSAXIS; ?></td>
                            <td><?php echo $WOSPRISM; ?></td>
                            <td><?php echo $WOSVA; ?></td>
                        </tr>
                        <tr class="WNEAR">
                            <td rowspan=2><span style="text-decoration:none;"><?php echo xlt('Mid'); ?>/<br /><?php echo xlt('Near'); ?></span></td>    
                            <td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td class="WMid nodisplay"><?php echo $WODADD1; ?></td>
                            <td class="WAdd2"><?php echo $WODADD2; ?></td>
                            <td class="WHIDECYL"><?php echo $WNEARODCYL; ?></td>
                            <td><?php echo $WNEARODAXIS; ?></td>
                            <td><?php echo $WNEARODPRISM; ?></td>
                            <td><?php echo $WNEARODVA; ?></td>
                        </tr>
                        <tr class="WNEAR">
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td class="WMid nodisplay"><?php echo $WOSADD1; ?></td>
                            <td class="WAdd2"><?php echo $WOSADD2; ?></td>
                            <td class="WHIDECYL"><?php echo $WNEAROSCYL; ?></td>
                            <td><?php echo $WNEAROSAXIS; ?></td>
                            <td><?php echo $WNEAROSPRISM; ?></td>
                            <td><?php echo $WNEAROSVA; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;vertical-align:top;top:0px;"><b><?php echo xlt('Comments'); ?>:</b>
                            </td>
                            <td colspan="4" class="up" style="text-align:left;vertical-align:middle;top:0px;"></td>
                        </tr>
                        <tr><td colspan="8">
                                <textarea style="width:100%;height:3.0em;" id="WCOMMENTS" name="WCOMMENTS"><?php echo text($WCOMMENTS); ?></textarea>     
                            </td>
                            <td colspan="2"> 
                                
                            </td>
                        </tr>
                        <tr id="signature_W" class="nodisplay">
                            <td colspan="5">
                                <span style="font-size:0.7em;font-weight:bold;"><?php echo xlt('e-signature'); ?>:</span> <i><?php echo text($providerID); ?></i>
                            </td>
                            <td colspan="3" style="text-align:right;text-decoration:underline;font-size:0.8em;font-weight:bold;"><?php echo xlt('DATE'); ?>: <?php echo $date; ?></td>
                        </tr>
                    </table>
                </div>

                <?php ($MRODSPH) ? ($display_AR = "display") : ($display_AR = "nodisplay");?>
                <div id="LayerVision_MR" class="refraction borderShadow <?php echo $display_AR; ?>">
                    <table id="autorefraction">
                        <tr><th colspan=9>Autorefraction Refraction</th></tr>
                        <tr>
                            <td></td>
                            <td><?php echo xlt('Sph'); ?></td>
                            <td><?php echo xlt('Cyl'); ?></td>
                            <td><?php echo xlt('Axis'); ?></td>
                            <td><?php echo xlt('Acuity'); ?></td>
                            <td><?php echo xlt('ADD'); ?></td>
                            <td><?php echo xlt('Jaeger'); ?></td>
                            <td><?php echo xlt('Prism'); ?></td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td><?php echo $ARODSPH; ?></td>
                            <td><?php echo $ARODCYL; ?></td>
                            <td><?php echo $ARODAXIS; ?></td>
                            <td><?php echo $ARODVA; ?></td>
                            <td><?php echo $ARODADD; ?></td>
                            <td><?php echo $ARNEARODVA; ?></td>
                            <td><?php echo $ARODPRISM; ?></td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td><?php echo $AROSSPH; ?></td>
                            <td><?php echo $AROSCYL; ?></td>
                            <td><?php echo $AROSAXIS; ?></td>
                            <td><?php echo $AROSVA; ?></td>
                            <td><?php echo $AROSADD; ?></td>
                            <td><?php echo $ARNEAROSVA; ?></td>
                            <td><?php echo $AROSPRISM; ?></td>
                        </tr>
                        <tr><th colspan="7">Manifest (Dry) Refraction</th>
                        <th colspan="2" style="text-align:right;"></th></tr>
                        <tr>
                            <td></td>
                            <td><?php echo xlt('Sph'); ?></td>
                            <td><?php echo xlt('Cyl'); ?></td>
                            <td><?php echo xlt('Axis'); ?></td>
                            <td><?php echo xlt('Acuity'); ?></td>
                            <td><?php echo xlt('ADD'); ?></td>
                            <td><?php echo xlt('Jaeger'); ?></td>
                            <td><?php echo xlt('Prism'); ?></td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td><?php echo $MRODSPH; ?></td>
                            <td><?php echo $MRODCYL; ?></td>
                            <td><?php echo $MRODAXIS; ?></td>
                            <td><?php echo $MRODVA; ?></td>
                            <td><?php echo $MRODADD; ?></td>
                            <td><?php echo $MRNEARODVA; ?></td>
                            <td><?php echo $MRODPRISM; ?></td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td><?php echo $MROSSPH; ?></td>
                            <td><?php echo $MROSCYL; ?></td>
                            <td><?php echo $MROSAXIS; ?></td>
                            <td><?php echo $MROSVA; ?></td>
                            <td><?php echo $MROSADD; ?></td>
                            <td><?php echo $MRNEAROSVA; ?></td>
                            <td><?php echo $MROSPRISM; ?></td>
                        </tr>
                    </table>
                </div>

                <?php ($CRODSPH)  ? ($display_Cyclo = "display") : ($display_Cyclo = "nodisplay"); ?>
                <div id="LayerVision_CR" class="refraction borderShadow <?php echo $display_Cyclo; ?>">
                    <table id="cycloplegia">
                        <tr><th colspan=9><?php echo xlt('Cycloplegic (Wet) Refraction'); ?></th></tr>
                        <tr>
                            <td></td>
                            <td><?php echo xlt('Sph'); ?></td>
                            <td><?php echo xlt('Cyl'); ?></td>
                            <td><?php echo xlt('Axis'); ?></td>
                            <td><?php echo xlt('Acuity'); ?></td>
                            <td colspan="1" style="text-align:left;width:60px;">
                                <input type="radio" name="WETTYPE" id="Flash" value="Flash" <?php if ($WETTYPE == "Flash") echo "checked='checked'"; ?>/>
                                <label for="Flash" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Flash'); ?></label>
                            </td>
                            <td colspan="2" rowspan="4" style="text-align:left;width:75px;font-size:0.5em;"><b style="text-align:center;width:70px;text-decoration:underline;"><?php echo xlt('Dilated with'); ?>:</b><br />
                                <input type="checkbox" id="CycloMydril" name="CYCLOMYDRIL" value="Cyclomydril" <?php if ($CYCLOMYDRIL == 'Cyclomydril') echo "checked='checked'"; ?> />
                                <label for="CycloMydril" disabled class="input-helper input-helper--checkbox"><?php echo xlt('CycloMydril'); ?></label>
                                <br />
                                <input type="checkbox" id="Tropicamide" name="TROPICAMIDE" value="Tropicamide 2.5%" <?php if ($TROPICAMIDE == 'Tropicamide 2.5%') echo "checked='checked'"; ?> />
                                <label for="Tropicamide" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Tropic 2.5%'); ?></label>
                                <br />
                                <input type="checkbox" id="Neo25" name="NEO25" value="Neosynephrine 2.5%"  <?php if ($NEO25 =='Neosynephrine 2.5%') echo "checked='checked'"; ?> />
                                <label for="Neo25" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Neo 2.5%'); ?></label>
                                <br />
                                <input type="checkbox" id="Cyclogyl" name="CYCLOGYL" value="Cyclopentolate 1%"  <?php if ($CYCLOGYL == 'Cyclopentolate 1%') echo "checked='checked'"; ?> />
                                <label for="Cyclogyl" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Cyclo 1%'); ?></label>
                                <br />
                                <input type="checkbox" id="Atropine" name="ATROPINE" value="Atropine 1%"  <?php if ($ATROPINE == 'Atropine 1%') echo "checked='checked'"; ?> />
                                <label for="Atropine" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Atropine 1%'); ?></label>
                                <br />
                            </td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td><?php echo $CRODSPH; ?></td>
                            <td><?php echo $CRODCYL; ?></td>
                            <td><?php echo $CRODAXIS; ?></td>
                            <td><?php echo $CRODVA; ?></td>
                            <td colspan="1" style="text-align:left;">
                                <input type="radio" name="WETTYPE" id="Auto" value="Auto" <?php if ($WETTYPE == "Auto") echo "checked='checked'"; ?>>
                                <label for="Auto" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Auto'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td><?php echo $CROSSPH; ?></td>
                            <td><?php echo $CROSCYL; ?></td>
                            <td><?php echo $CROSAXIS; ?></td>
                            <td><?php echo $CROSVA; ?></td>
                            <td colspan="1" style="text-align:left;">
                                <input type="radio" name="WETTYPE" id="Manual" value="Manual" <?php if ($WETTYPE == "Manual") echo "checked='checked'"; ?>>
                                <label for="Manual" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Manual'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" style="vertical-align:text-top;">
                                <input type="checkbox" id="DIL_RISKS" name="DIL_RISKS" value="on" <?php if ($DIL_RISKS =='on') echo "checked='checked'"; ?>>
                                <label for="DIL_RISKS" class="disabled input-helper input-helper--checkbox"><?php echo xlt('Dilation risks reviewed'); ?></label>
                            </td>
                            <td colspan="1" style="text-align:left;">
                                <input type="checkbox" name="BALANCED" id="Balanced" value="on" <?php if ($BALANCED =='on') echo "checked='checked'"; ?>>
                                <label for="Balanced" class="disabled input-helper input-helper--checkbox"><?php echo xlt('Balanced'); ?></label>
                            </td>
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

                <?php ($CTLODSPH) ? ($display_CTL = "display") : ($display_CTL = "nodisplay"); ?>
                <div id="LayerVision_CTL" class="refraction borderShadow <?php echo $display_CTL; ?>">
                    <table id="CTL" style="width:100%;">
                        <tr><th colspan="9"><?php echo xlt('Contact Lens Refraction'); ?></th></tr>
                        <tr>
                            <td style="text-align:center;">
                                <div style="box-shadow: 1px 1px 2px #888888;border-radius: 8px; margin: 5 auto; position:inline-block; Xpadding: 0.02in; border: 1.00pt solid #000000; ">
                                    <table>
                                        <tr>
                                            <td></td>
                                            <td><?php echo xlt('Manufacturer'); ?></td>
                                            <td><?php echo xlt('Supplier'); ?></td>
                                            <td><?php echo xlt('Brand'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><b><?php echo xlt('OD'); ?>:</b></td>
                                            <td>
                                                <!--  these will need to be pulled from a CTL specific table probably -->
                                                <select id="CTLMANUFACTUREROD" name="CTLMANUFACTUREROD">
                                                    <option></option>
                                                    <option value="BL"><?php echo xlt('Bausch and Lomb'); ?></option>
                                                    <option value="JNJ"><?php echo xlt('JNJ'); ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <select id="CTLSUPPLIEROD" name="CTLMANUFACTUREROD">
                                                    <option></option>
                                                    <option value="ABB"><?php echo xlt('ABB'); ?></option>
                                                    <option value="JNJ"><?php echo xlt('JNJ'); ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <select id="CTLBRANDOD" name="CTLBRANDOD">
                                                    <option></option>
                                                    <option value="Accuvue"><?php echo xlt('Accuvue'); ?></option>
                                                    <option value="ExtremeH2O"><?php echo xlt('Extreme H2O'); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr >
                                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                                            <td>
                                                <select id="CTLMANUFACTUREROS" name="CTLMANUFACTUREROS">
                                                    <option></option>
                                                    <option value="BL"><?php echo xlt('Bausch and Lomb'); ?></option>
                                                    <option value="JNJ"><?php echo xlt('JNJ'); ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <select id="CTLSUPPLIEROS" name="CTLSUPPLIEROS">
                                                    <option></option>
                                                    <option value="ABB"><?php echo xlt('ABB'); ?></option>
                                                    <option value="JNJ"><?php echo xlt('JNJ'); ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <select id="CTLBRANDOS" name="CTLBRANDOS">
                                                    <option></option>
                                                    <option value="Accuvue"><?php echo xlt('Accuvue'); ?></option>
                                                    <option value="ExtremeH2O"><?php echo xlt('Extreme H2O'); ?></option>
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
                            <td><?php echo xlt('Sph'); ?></td>
                            <td><?php echo xlt('Cyl'); ?></td>
                            <td><?php echo xlt('Axis'); ?></td>
                            <td><?php echo xlt('BC'); ?></td>
                            <td><?php echo xlt('Diam'); ?></td>
                            <td><?php echo xlt('ADD'); ?></td>
                            <td><?php echo xlt('Acuity'); ?></td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td><?php echo $CTLODSPH; ?></td>
                            <td><?php echo $CTLODCYL; ?></td>
                            <td><?php echo $CTLODAXIS; ?></td>
                            <td><?php echo $CTLODBC; ?></td>
                            <td><?php echo $CTLODDIAM; ?></td>
                            <td><?php echo $CTLODADD; ?></td>
                            <td><?php echo $CTLODVA; ?></td>
                        </tr>
                        <tr >
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td><?php echo $CTLOSSPH; ?></td>
                            <td><?php echo $CTLOSCYL; ?></td>
                            <td><?php echo $CTLOSAXIS; ?></td>
                            <td><?php echo $CTLOSBC; ?></td>
                            <td><?php echo $CTLOSDIAM; ?></td>
                            <td><?php echo $CTLOSADD; ?></td>
                            <td><?php echo $CTLOSVA; ?></td>
                        </tr>
                    </table>
                </div>

                <?php ($ADDITIONAL!=1) ? ($display_Add = "display") : ($display_Add = "nodisplay"); ?>
                <div id="LayerVision_ADDITIONAL" class="refraction borderShadow <?php echo $display_Add; ?>">
                    <table id="Additional">
                        <tr><th colspan=9><?php echo xlt('Additional Data Points'); ?></th></tr>
                        <tr><td></td>
                            <td><?php echo xlt('PH'); ?></td>
                            <td><?php echo xlt('PAM'); ?></td>
                            <td><?php echo xlt('LI'); ?></td>
                            <td><?php echo xlt('BAT'); ?></td>
                            <td><?php echo xlt('K1'); ?></td>
                            <td><?php echo xlt('K2'); ?></td>
                            <td><?php echo xlt('Axis'); ?></td>
                          </tr>
                        <tr><td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td><?php echo $PHODVA; ?></td>
                            <td><?php echo $PAMODVA; ?></td>
                            <td><?php echo $LIODVA; ?></td>
                            <td><?php echo $GLAREODVA; ?></td>
                            <td><?php echo $ODK1; ?></td>
                            <td><?php echo $ODK2; ?></td>
                            <td><?php echo $ODK2AXIS; ?></td>
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td><?php echo $PHOSVA; ?></td>
                            <td><?php echo $PAMOSVA; ?></td>
                            <td><?php echo $LIOSVA; ?></td>
                            <td><?php echo $GLAREOSVA; ?></td>
                            <td><?php echo $OSK1; ?></td>
                            <td><?php echo $OSK2; ?></td>
                            <td><?php echo $OSK2AXIS; ?></td>
                        </tr>
                        <tr><td>&nbsp;</td></tr>
                        <tr>
                            <td></td>
                            <td><?php echo xlt('AxLength'); ?></td>
                            <td><?php echo xlt('ACD'); ?></td>
                            <td><?php echo xlt('PD'); ?></td>
                            <td><?php echo xlt('LT'); ?></td>
                            <td><?php echo xlt('W2W'); ?></td>
                            <td><?php echo xlt('ECL'); ?></td>
                            <!-- <td><?php echo xlt('pend'); ?></td> -->
                        </tr>
                        <tr><td><b><?php echo xlt('OD'); ?>:</b></td>
                            <td><?php echo $ODAXIALLENGTH; ?></td>
                            <td><?php echo $ODACD; ?></td>
                            <td><?php echo $ODPDMeasured; ?></td>
                            <td><?php echo $ODLT; ?></td>
                            <td><?php echo $ODW2W; ?></td>
                            <td><?php echo $ODECL; ?></td>
                            <!-- <td><?php echo $pend; ?></td> -->
                        </tr>
                        <tr>
                            <td><b><?php echo xlt('OS'); ?>:</b></td>
                            <td><?php echo $OSAXIALLENGTH; ?></td>
                            <td><?php echo $OSACD; ?></td>
                            <td><?php echo $OSPDMeasured; ?></td>
                                <td><?php echo $OSLT; ?></td>
                                <td><?php echo $OSW2W; ?></td>
                                <td><?php echo $OSECL; ?></td>
                                <!--  <td>$pend</td> -->
                            </tr>
                        </table>
                </div>  
                </div>
                <!-- end of the refraction boxes -->
            </td>
        </tr>
        <tr>
            <td colspan="2">           
                
                <!-- start of external exam -->
 
                <div style="float:left;left:0px;top:0px;width:100%;text-align:left;margin:5;padding:5;">
                    <br />
                    <br />
                    <b><?php echo xlt('External Exam'); ?>:</b><br />
                    <table cellspacing="2" style="text-align:left;margin:5;padding:5;">
                        <tr>
                            <td> 
                                <div class="borderShadow" style="width:5.2in;min-height:260px;text-align:left;margin:0;padding:5;">
                                
                                <table>
                                    <tr>
                                      <td class="report_text right title" style="text-decoration:underline;"><?php echo xlt('Right'); ?></td>
                                      <td style="width:100px;"></td>
                                      <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right"><?php echo text($RBROW); ?></td>
                                      <td class="middle"><?php echo xlt('Brow'); ?></td>
                                      <td class="report_text"><?php echo text($LBROW); ?></td>
                                    </tr> 
                                    <tr>
                                      <td class="report_text right "><?php echo text($RUL); ?></td>
                                      <td  class="middle"><?php echo xlt('Upper Lids'); ?></td>
                                      <td class="report_text"><?php echo text($LUL); ?></td>
                                    </tr> 
                                    <tr>
                                      <td class="report_text right "><?php echo text($RLL); ?></td>
                                      <td class="middle"><?php echo xlt('Lower Lids'); ?></td>
                                      <td class="report_text"><?php echo text($LLL); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right "><?php echo text($RMCT); ?></td>
                                      <td class="middle"><?php echo xlt('Medial Canthi'); ?></td>
                                      <td class="report_text"><?php echo text($LMCT); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right"><?php echo text($RADNEXA); ?></td>
                                      <td class="middle"><?php echo xlt('Adnexa'); ?></td>
                                      <td class="report_text"><?php echo text($LADNEXA); ?></td>
                                    </tr>
                                </table>
                            </div>
                            </td>
                        
                            <td style="padding-left:10px;">
                                <div class="borderShadow" style="width:3.5in;min-height:260px;text-align:left;margin:0;padding:5;">
                                <table>
                                    <tr>
                                      <td class="report_text right title" style="text-decoration:underline;"><?php echo xlt('Right'); ?></td>
                                      <td></td>
                                      <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></td>
                                    </tr>
                                    
                                    <tr>
                                      <td class="report_text right" style="width:100px;"><?php echo $RLF; ?></td>
                                      <td class="middle" style="width:175px;"><?php echo xlt('Levator Function'); ?></td>
                                      <td class="report_text" style="width:100px;"><?php echo text($LLF); ?></td>
                                    </tr> 
                                    
                                    <tr>
                                      <td class="report_text right"><?php echo text($RMRD); ?></td>
                                      <td class="middle" title="<?php echo xla('Marginal Reflex Distance'); ?>"><?php echo xlt('MRD'); ?></td>
                                      <td  class="report_text"><?php echo text($LMRD); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right"><?php echo attr($RVFISSURE); ?></td>
                                      <td class="middle" title="<?php echo xla('Vertical Fissure: central height between lid margins'); ?>"><?php echo xlt('Vert Fissure'); ?></td>
                                      <td class="report_text"><?php echo attr($LVFISSURE); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right"><?php echo attr($RCAROTID); ?></td>
                                      <td class="middle" title="<?php echo xla('Any carotid bruits appreciated?'); ?>"><?php echo xlt('Carotid'); ?></td>
                                      <td class="report_text"><?php echo attr($LCAROTID); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right"><?php echo attr($RTEMPART); ?></td>
                                      <td class="middle" title="<?php echo xla('Temporal Arteries'); ?>"><?php echo xlt('Temp. Art.'); ?></td>
                                      <td class="report_text"><?php echo attr($LTEMPART); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right"><?php echo attr($RCNV); ?></td>
                                      <td class="middle" title="<?php echo xla('Cranial Nerve 5: Trigeminal Nerve'); ?>"><?php echo xlt('CN V'); ?></td>
                                      <td class="report_text"><?php echo attr($LCNV); ?></td>
                                    </tr>
                                    <tr>
                                      <td class="report_text right"><?php echo text($RCNVII); ?></td>
                                      <td class="middle" title="<?php echo xla('Cranial Nerve 7: Facial Nerve'); ?>"><?php echo xlt('CN VII'); ?></td>
                                      <td class="report_text"><?php echo attr($LCNVII); ?></td>
                                    </tr>
                                
                                    <tr>
                                        <td colspan="3" style="text-align:center;"><br />
                                            <span style="text-decoration:underline;"><?php echo xlt('Hertel Exophthalmometry'); ?>
                                            </span>
                                            <br />
                                            <? if ($HERTELBASE) { ?>
                                      
                                        <span style="border:1pt solid black;width:30px;text-align:center;padding:0 5;">
                                            <?php echo attr($ODHERTEL); ?>
                                        </span>
                                         <i class="fa fa-minus"></i> 
                                         <span style="border:1pt solid black;width:40px;text-align:center;padding:0 5;">
                                            <?php echo attr($HERTELBASE); ?>
                                        </span>
                                        <i class="fa fa-minus"></i> 
                                        <span style="border:1pt solid black;width:30px;text-align:center;padding:0 5;">
                                            <?php echo attr($OSHERTEL); ?>
                                        </span>
                                        <br />
                                    <? } ?>
                                      </td>
                                    </tr>
                                
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-size:0.7em;">
                                <br />
                                <b><?php echo xlt('Comments'); ?>:</b><br />
                                <span style="width:4.0in;height:3.0em;">
                                    <?php echo text($EXT_COMMENTS); ?>
                                </span>
                                <br /><br />
                            </td>
                        </tr>
                    </table>
                </div>

                    <?php 
                        display_draw_image ("EXT",$encounter,$pid); 
                    ?>
           
                <!-- end of external exam -->
            </td></tr><tr><td colspan="2">
                <!-- start of Anterior Segment exam -->
             
                    <div style="float:left;left:0px;top:0px;width:4in;text-align:left;">
                        <br />
                        <br />
                        <b><?php echo xlt('Anterior Segment'); ?>:</b><br />
                        <table>
                                    <tr>
                                        <td> 
                                            <div class="borderShadow" style="width:5.2in;min-height:160px;text-align:left;margin:0;padding:5;">
                                            <table>
                                                <tr>
                                                  <td class="report_text right title" style="text-decoration:underline;"><?php echo xlt('Right'); ?></td>
                                                  <td style="width:100px;"></td>
                                                  <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></td>
                                                </tr>
                                                <tr>
                                                  <td class="report_text right"><?php echo text($ODCONJ); ?></td>
                                                  <td class="middle"><?php echo xlt('Conj'); ?></td>
                                                  <td class="report_text"><?php echo text($OSCONJ); ?></td>
                                                </tr> 
                                                <tr>
                                                  <td class="report_text right "><?php echo text($ODCORNEA); ?></td>
                                                  <td  class="middle"><?php echo xlt('Cornea'); ?></td>
                                                  <td class="report_text"><?php echo text($OSCORNEA); ?></td>
                                                </tr> 
                                                <tr>
                                                  <td class="report_text right "><?php echo text($ODAC); ?></td>
                                                  <td class="middle"><?php echo xlt('A/C'); ?></td>
                                                  <td class="report_text"><?php echo text($OSAC); ?></td>
                                                </tr>
                                                <tr>
                                                  <td class="report_text right "><?php echo text($ODLENS); ?></td>
                                                  <td class="middle"><?php echo xlt('Lens'); ?></td>
                                                  <td class="report_text"><?php echo text($OSLENS); ?></td>
                                                </tr>
                                                <tr>
                                                  <td class="report_text right"><?php echo text($ODIRIS); ?></td>
                                                  <td class="middle"><?php echo xlt('Iris'); ?></td>
                                                  <td class="report_text"><?php echo text($OSIRIS); ?></td>
                                                </tr>
                                            </table>
                                            <div>
                                        </td>
                                    
                                        <td>
                                            <div class="borderShadow" style="width:3.5in;min-height:160px;text-align:left;margin:0;padding:5;">
                                
                                            <table>
                                                <tr>
                                                  <td class="report_text right title" style="text-decoration:underline;"><?php echo xlt('Right'); ?></td>
                                                  <td></td>
                                                  <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></td>
                                                </tr>
                                                
                                                <tr>
                                                  <td class="report_text right" style="width:100px;"><?php echo $ODGONIO; ?></td>
                                                  <td class="middle"><?php echo xlt('Gonioscopy'); ?></td>
                                                  <td class="report_text" style="width:100px;"><?php echo text($OSGONIO); ?></td>
                                                </tr> 
                                                
                                                <tr>
                                                  <td class="report_text right"><?php echo text($ODKTHICKNESS); ?></td>
                                                  <td class="middle" title="<?php echo xla('Pachymetry'); ?>"><?php echo xlt('Pachymetry'); ?></td>
                                                  <td  class="report_text"><?php echo text($OSKTHICKNESS); ?></td>
                                                </tr>
                                                <tr>
                                                  <td class="report_text right"><?php echo attr($ODSCHIRMER1); ?></td>
                                                  <td class="middle" title="<?php echo xla('Schirmers I'); ?>"><?php echo xlt('Schirmers I'); ?></td>
                                                  <td class="report_text"><?php echo attr($OSSCHIRMER1); ?></td>
                                                </tr>
                                                <tr>
                                                  <td class="report_text right"><?php echo attr($ODSCHIRMER2); ?></td>
                                                  <td class="middle" title="<?php echo xla('Schirmers II'); ?>"><?php echo xlt('Schirmers II'); ?></td>
                                                  <td class="report_text"><?php echo attr($OSSCHIRMER2); ?></td>
                                                </tr>
                                                <tr>
                                                  <td class="report_text right"><?php echo attr($ODTBUT); ?></td>
                                                  <td class="middle" title="<?php echo xla('Tear Break Up Time'); ?>"><?php echo xlt('TBUT'); ?></td>
                                                  <td class="report_text"><?php echo attr($OSTBUT); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="font-size:0.7em;">
                                            <br />
                                            <b><?php echo xlt('Comments'); ?>:</b><br />
                                            <span style="width:4.0in;height:3.0em;">
                                                <?php echo text($EXT_COMMENTS); ?>
                                            </span>
                                            <br /><br />
                                        </td>
                                    </tr>
                        </table>
                    </div>
                    <?php 
                        display_draw_image ("ANTSEG",$encounter,$pid); 
                    ?>
           
                <!-- end of Anterior Segment exam -->    

            </td>
        </tr>


            <tr><td colspan="2">
            <div class="page-break"></div>
            </td>
            </tr>
       
            <tr><td colspan="2">
            <!-- start of RETINA exam -->
            <div style="display:inline-block;width:600px;left:0px;text-align:center;background-color:#fff;">    
                <div style="float:left;left:0px;top:0px;width:4in;text-align:left;">
                    <br />
                    <br />
                    <b><?php echo xlt('Retina'); ?>:</b><br />
                    <table>
                            <tr>
                                <td>  
                                    <div class="borderShadow" style="width:5.2in;min-height:200px;text-align:left;margin:0;padding:5;">
                                            
                                    <table>
                                        <tr>
                                            <td class="report_text right title" style="text-decoration:underline;">
                                            <?php echo xlt('Right'); ?></td>
                                            <td></td>
                                            <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></td>
                                        </tr>
                                              <tr>
                                                  <td class="right report_text"><?php echo $ODDISC; ?></td>
                                                  <td class="middle" style="width:100px;"><?php echo xlt('Disc'); ?></td>
                                                  <td class="report_text"><?php echo $OSDISC; ?></td>
                                              </tr> 
                                              <tr>
                                                  <td class="right report_text"><?php echo $ODCUP; ?></td>
                                                  <td class="middle"><?php echo xlt('Cup'); ?></td>
                                                  <td class="report_text"><?php echo $OSCUP; ?></td>
                                              </tr> 
                                              <tr>
                                                  <td class="right report_text"><?php echo $ODMACULA; ?></td>
                                                  <td class="middle"><?php echo xlt('Macula'); ?></td>
                                                  <td class="report_text"><?php echo $OSMACULA; ?></td>
                                              </tr>
                                              <tr>
                                                  <td class="right report_text"><?php echo $ODVESSELS; ?></td>
                                                  <td class="middle"><?php echo xlt('Vessels'); ?></td>
                                                  <td class="report_text"><?php echo $OSVESSELS; ?></td>
                                              </tr>
                                              <tr>
                                                  <td class="right report_text"><?php echo $ODPERIPH; ?></td>
                                                  <td class="middle"><?php echo xlt('Periph'); ?></td>
                                                  <td class="report_text"><?php echo $OSPERIPH; ?></td>
                                              </tr>
                                    </table>
                                </div>
                                </td>
                                <td>  <div class="borderShadow" style="width:3in;min-height:200px;text-align:left;margin:0;padding:5;">
                                             <table > 
                                             <tr>
                                            <td class="report_text right title" style="text-decoration:underline;">
                                            <?php echo xlt('Right'); ?></td>
                                            <td></td>
                                            <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></td>
                                        </tr>
                                          <tr>
                                              <td class="report_text right">&nbsp;<?php echo $ODCMT; ?></td>
                                              <td class="middle"><?php echo xlt('Central Macular Thickness'); ?> </td>
                                              <td class="report_text" >&nbsp;<?php echo $OSCMT; ?></td>
                                          </tr>
                                    </table>
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"  style="font-size:0.7em;">
                                    <br />
                                    <b><?php echo xlt('Comments'); ?>:</b><br />
                                    <span style="width:4.0in;height:3.0em;">
                                        <?php echo text($RETINA_COMMENTS); ?>
                                    </span>
                                    <br /><br />
                                </td>
                            </tr>
                    </table>
                </div>
                    <?php 
                        display_draw_image ("RETINA",$encounter,$pid); 
                    ?>           
            </div>
            <!-- end of RETINA exam -->
            </td></tr>
            <tr><td colspan="2">
            <!-- start of IMPPLAN exam -->
            <div style="display:inline-block;width:600px;left:0px;text-align:center;background-color:#fff;">    
                <div style="float:left;left:0px;top:0px;width:4in;text-align:left;">
                    <br />
                    <table>
                            <tr>
                                <td>   <br />
                                    <b><?php echo xlt('Impression'); ?>:</b>
                                    <textarea rows=5 id="IMP" name="IMP" style="height:1.3in;width:90%;"><?php echo text($IMP); ?></textarea>
                                     <b><?php echo xlt('Plan'); ?>/<?php echo xlt('Recommendation'); ?>:</b>
                                     <textarea rows=5 id="PLAN" name="PLAN" style="height:1.3in;width:90%;"><?php echo text($PLAN); ?></textarea>
                                </td>

                            </tr>
                    </table>
                </div>
                    <?php 
                        display_draw_image ("IMPPLAN",$encounter,$pid); 
                    ?>
            </div>
            <!-- end of IMPPLAN exam -->    
            </td></tr>
            </table>  
  

<?php
      //end central_wrapper
              //return;
    }
function display_draw_image($zone,$encounter,$pid){
    global $form_folder;
return;
    $side = "OU";

    $file_location = $GLOBALS["OE_SITES_BASE"]."/".$_SESSION['site_id']."/documents/".$pid."/".$form_folder."/".$encounter."/".$side."_".$zone."_VIEW.png";
    
            $sql = "SELECT * from documents where url='file://".$file_location."'";
            $doc = sqlQuery($sql);
            // random to not pull from cache.
            if (file_exists($file_location) && ($doc['id'] > '0')) {
                $filetoshow = $GLOBALS['web_root']."/controller.php?document&retrieve&patient_id=$pid&document_id=".$doc['id']."&as_file=false&blahblah=".rand();
                echo '<div style="float:left;margin:70 10 auto 10;border:2pt solid grey;">';
                echo "<img src='".$filetoshow."' style='padding: 0px 0px 0px 5px;'>
                </div>";
            } else {
                //base image. 
                //$filetoshow = "../../forms/".$form_folder."/images/".$side."_".$zone."_BASE.png"; 
            }
           

    return;

}


    function full_report( $pid, $encounter, $cols='2', $id) {
        
        /** CHANGE THIS - name of the database table associated with this form **/
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
                print "<td><span class=bold>$key: </span><span class=text>$value</span></td>";
                $count++;
                if ($count == $cols) {
                    $count = 0;
                    print "</tr><tr>\n";
                }
            }
        }
        print "</tr></table>";
    }

    ?> 
