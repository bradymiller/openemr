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
    error_reporting(E_ALL & ~E_NOTICE);

    include_once("../../globals.php");
    include_once("$srcdir/api.inc");
    include_once("$srcdir/sql.inc");
    require_once("$srcdir/formatting.inc.php");


    $form_name = "eye_mag";
    $form_folder = "eye_mag";

    include_once("../../forms/".$form_folder."/php/".$form_folder."_functions.php");
    //@extract($_REQUEST); //working on removing
    //@extract($_SESSION); //working on removing

    $choice = $_REQUEST['choice'];
    //$encounter = $_REQUEST['encounter'];
    if ($_REQUEST['ptid']) $pid = $_REQUEST['ptid'];
    if ($_REQUEST['encid']) $encounter=$_REQUEST['encid'];
    $form_id = $_REQUEST['formid'];
    $form_name=$_REQUEST['formname'];
    $id=$form_id;
    // Get users preferences, for this user 
    // (and if not the default where a fresh install begins from, or someone else's) 
    $query  = "SELECT * FROM form_eye_mag_prefs where PEZONE='PREFS' AND id=? ORDER BY ZONE_ORDER,ordering";
    $result = sqlStatement($query,array($_SESSION['authUserID']));
    while ($prefs= sqlFetchArray($result))   {    
        @extract($prefs);    
        $$LOCATION = $VALUE; 
    }

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
    formHeader("Chart: ".$pat_data['fname']." ".$pat_data['lname']." ".$visit_date);

    ?><html>
        <head>
            <script src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="<?php echo $GLOBALS['webroot'] ?>/library/js/bootstrap.min.js"></script>  

        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>
            <!-- Add Font stuff for the look and feel.  -->
            <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
            <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/pure-min.css">
            <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/font-awesome-4.2.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/style.css" type="text/css">  
                

    <link rel=stylesheet href="<?php echo $GLOBALS['css_header']; ?>" type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/font-awesome-4.2.0/css/font-awesome.min.css">


    <style>
            .report_text {
                font-size:0.8em;width:200px;
            }
            .middle {
                width:30%;text-align:center;font-size:0.9em;font-weight:600;
            }
            .border {
                border:1pt solid black;font-size:0.9em; min-height: 2.0in; width:350px;margin:10px;
                padding-top:100px;
            }
            .refraction {
                position:relative;
                
            }
            input {
                background-color:#fff;
            }
            textarea.ACT { 
                border: none; 
                height:70px;
                width:70px;
                outline: none;
                top:50%;
                font-size: 0.8em;
                vertical-align:middle;
                text-align:right;
            }
        </style>  

        <script type="text/javascript" >
             var inputs = document.getElementsByTagName('input');
            for (var i = inputs.length, n = 0; n < i; n++) {
                inputs[n].disabled = !inputs[n].disabled;
            }
        </script>
        </head>
    <body>
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
                    } else {
                        $filetoshow = "../../forms/".$form_folder."/images/".$side."_".$zone[$i]."_BASE.png?".rand();
                    } 
                    ?>
                    <div class='bordershadow' style='position:relative;float:left;width:100px;height:75px;'>
                        <img src='<?php echo $filetoshow; ?>' width=100 heght=75>
                    </div>
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
                narrative($pid, $encounter, $cols, $form_id);
            }
            ?>
    </body>
    </html>

    <?

    exit;
    /***************************************/
        $count = 0;
        $data = formFetch($table_name, $id);
       
        if ($data) {
            foreach($data as $key => $value) {
                $$key=$value;
            }
        }
        if ($target =="W") {
            //we are printing the current RX
           
            ?>
             <table id="SpectacleRx">
                                                    <th colspan="9"><?=$fname?><?=$lname?></th>
                                                    <tr style="font-style:bold;">
                                                        <td></td>
                                                        <td></td>
                                                        <td>sph</td>
                                                        <td>cyl</td>
                                                        <td>axis</td>
                                                        <td>Prism</td>
                                                        <td>Acuity</td>
                                                        <td rowspan="7" class="right">
                                                            <b style="font-weight:bold;text-decoration:none;">Rx Type</b><br />
                                                            <b id="SingleVision_span">Single<input type=radio value="0" id="RX1" name="RX" class="input-helper--radio input-helper--radio" check="checked" /></b><br />
                                                            <b id="Bifocal_span">Bifocal<input type=radio value="1" id="RX1" name="RX" /></b><br />
                                                            <b id="Trifocal_span" name="Trifocal_span">Trifocal
                                                                <input type=radio value="2" id="RX1" name="RX" /></b><br />
                                                            <b id="Progressive_span">Prog.<input type=radio value="3" id="RX1" name="RX" /></b><br />

                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td rowspan="2">Distance</td>    
                                                        <td><b>OD</b></td>
                                                        <td><input type=text id="ODSph" name=="ODSph" /></td>
                                                        <td><input type=text id="ODCyl" name="ODCyl" /></td>
                                                        <td><input type=text id="ODAxis" name="ODAxis" /></td>
                                                        <td><input type=text id="ODPrism" name="ODPrism" /></td>
                                                        <td><input type=text id="ODVA" name="ODVA" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>OS</b></td>
                                                        <td><input type=text id="OSSph" name="OSSph" /></td>
                                                        <td><input type=text id="OSCyl" name="OSCyl" /></td>
                                                        <td><input type=text id="OSAxis" name="OSAxis" /></td>
                                                        <td><input type=text id="OSPrism" name="OSPrism" /></td>
                                                        <td><input type=text id="OSVA" name="OSVA" /></td>
                                                    </tr>
                                                    <tr class="NEAR">
                                                        <td rowspan=2><span style="text-decoration:none;">Mid/<br />Near</span></td>    
                                                        <td><b>OD</b></td>
                                                        <td class="Mid nodisplay"><input type=text id="ODADD1" name="ODADD1" value=""></td>
                                                        <td class="Add2"><input type=text id="ODADD2" name="ODADD2" value=""></td>
                                                        <td class="HIDECYL"><input type=text id="ODCYLNEAR" name="ODCYLNEAR" value=""></td>
                                                        <td><input type=text id="ODAXISNEAR" name="ODAXISNEAR" value=""></td>
                                                        <td><input type=text id="ODPRISMNEAR" name="ODPRISMNEAR" value=""></td>
                                                        <td><input type=text id="ODVANear" name="ODVANear" value=""></td>
                                                    </tr>
                                                    <tr class="NEAR">
                                                        <td><b>OS</b></td>
                                                        <td class="Mid nodisplay"><input type=text id="OSADD1" name="OSADD1" value=""></td>
                                                        <td class="Add2"><input type=text id="OSADD2" name="OSADD2" value=""></td>
                                                        <td class="HIDECYL"><input type=text id="OSCYLNEAR" name"OSCYLNEAR" value=""></td>
                                                        <td><input type=text id="OSAXISNEAR" name="OSAXISNEAR" value=""></td>
                                                        <td><input type=text id="OSPRISMNEAR" name="OSPRISMNEAR" value=""></td>
                                                        <td><input type=text id="OSVANear" name="OSVANear" value=""></td>

                                                    </tr>
                                                    <tr style="">
                                                        <td colspan="2" class="up" style="text-align:right;vertical-align:top;top:0px;"><b>Comments:</b>
                                                        </td>
                                                        <td colspan="5" class="up" style="text-align:left;vertical-align:middle;top:0px;">
                                                            <textarea style="idth:100%;height:2.1em;" id="COMMENTS" name="COMMENTS"></textarea>     
                                                        </td>
                                                        <td> 
                                                            <span class="ui-icon ui-icon-clock" >&nbsp; </span>
                                                            <span href="print.php?target=W" class="ui-icon ui-icon-cancel" onclick="indow.print(); return false;" style="display:inline-block"></span><span>Print</span> 
                                                        </td>
                                                    </tr>
                                                </table>
                <?
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
    <div style="width:850px;background-color:#fff;margin:auto;">  
    <!-- Begin left column PMSFH -->
            <div style="float:left;width:170px;border-right:0.5pt solid grey;">
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
                <div id="PMFSH_block_1" name="PMFSH_block_1" class="text_clinical" style="text-align:left;">
                    <?php
                    $encount = 0;
                    $lasttype = "";
                    $first = 1; // flag for first section
                    $counter="0";
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
                        
                        $counter++; //at 19 lines we make a new row
                        $pres = sqlStatement("SELECT * FROM lists WHERE pid = ? AND type = ? " .
                            "ORDER BY begdate DESC", array($pid,$focustype) );
                        $counter_previous = $counter;
                        $counter = $counter + sqlNumRows($pres);
                       
                       
                           ?>
                           <table style="width:1.6in;">
                                <tr>
                                    <td width="90%">
                          <?php          
                        echo '<span class="left" style="font-weight:800;font-size:0.7em;">'.$disptype."</span>";
                        ?>
                                    </td>
                                    <td >
                                    </td>
                                </tr>
                                </table>
                            <?                  
                       
                        //echo "<br />";
                        echo "
                        <table style='margin-bottom:20px;max-height:1.5in;max-width:1.5in;
                        background-color: #fff; font-size:0.8em;overflow:auto;' class='borderShadow'>
                            <tr>
                                <td style='min-height:1.2in;min-width:1.5in;padding-left:5px;'>
                                ";

                        // if no issues (will place a 'None' text vs. toggle algorithm here)
                        if (sqlNumRows($pres) < 1) {
                            echo  "".xla("None") ."<br /><br /><br />";
                            echo "</td></tr></table>";
                            $counter = $counter+4; 
                            continue;
                        }

                        $section_count='4';

                        while ($row = sqlFetchArray($pres)) {
                            $section_count--;
                            $rowid = $row['id'];
                            $disptitle = trim($row['title']) ? $row['title'] : "[Missing Title]";

                            $ierow = sqlQuery("SELECT count(*) AS count FROM issue_encounter WHERE " .
                              "list_id = ?", array($rowid) );

                            // encount is used to toggle the color of the table-row output below
                            ++$encount;
                            $bgclass = (($encount & 1) ? "bg1" : "bg2");

                            // look up the diag codes
                            $codetext = "";
                            if ($row['diagnosis'] != "") {
                                $diags = explode(";", $row['diagnosis']);
                                foreach ($diags as $diag) {
                                    $codedesc = lookup_code_descriptions($diag);
                                    $codetext .= xlt($diag) . " (" . xlt($codedesc) . ")<br>";
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
                            
                            echo "<span name='QP_PMH_".$rowid."' href='#PMH_anchor' id='QP_PMH_".$rowid."' onclick=\"alter_issue('".$rowid."','".$row[type]."');\">".xlt($disptitle)."</span>";
                            if ($focustype == "allergy" && $row['reaction'] > '') {
                                echo " (" . htmlspecialchars($row['reaction'],ENT_NOQUOTES).") " ;
                            }
                               echo "\n<br />";
                        }

                        echo " </td></tr></table> <br />\n";
                        $counter++; 

                    }
                    echo '<span class="left" style="font-weight:800;">ROS</span><br />';
                   
                    echo "
                        <table style='margin-bottom:20px;max-height:1.5in;max-width:1.5in;
                        background-color: #fff; font-size:0.8em;overflow:auto;' class='borderShadow'>
                            <tr>
                                <td style='min-height:1.2in;min-width:1.5in;padding-left:5px;'>

                       ";

                        // if no issues (will place a 'None' text vs. toggle algorithm here)
                        if ($count_ROS < 1) {
                            echo  xla("None") ."<br /><br /><br />";
                            echo " ";
                            $counter= $counter-3;
                        } else 
                        { 
                            //print out a ROS.  Give ?expand option to see full list in a pop-up?
                        }
                            echo "<td>
                            </tr>
                        </table>";

                        
                    ?>
                </div>
            </div>
    <!-- End left column PMSFH -->

    <!-- Begin right column -->
        
            <div style="float:left;width:630px;background-color:#fff;text-align:left;margin:30 auto 30;padding-left:0.5in">
                <b id="tension_tab" style=""><?php echo xlt('History of Present Illness'); ?>:</b> 
                <!-- start  HPI  -->
                    <div style="float:left;margin-top:20px;">

                            <div id="tabs_wrapper" class="text_clinical">
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
                                </div>
                                <?php 
                                if ($CC2) {
                                    echo "<br />
                                    <br />";
                                    echo "<b>".xlt('Chief Complaint 2'); ?>:</b> &nbsp;<?php echo text($CC2); ?>
                                    <br />
                                    <br />
                                    <div style="padding-left:10px;">
                                        <?php 
                                        if ($TIMING1) {
                                            echo "<i>".xlt('Timing'); ?>:</i>  &nbsp;<?php echo text($TIMING1); 
                                        }
                                        if ($CONTEXT1) {
                                            echo "<i>".xlt('Context'); ?>:</i> &nbsp;<?php echo text($CONTEXT1); 
                                        }
                                        if ($SEVERITY1) {
                                            echo "<i>".xlt('Severity'); ?>:</i> &nbsp;<?php echo text($SEVERITY1); 
                                        }
                                        if ($MODIFY1) {
                                            echo "<i>".xlt('Modifying'); ?>:</i> &nbsp;<?php echo text($MODIFY1);
                                        }
                                        if ($ASSOCIATED1) {
                                            echo "<i>".xlt('Associated'); ?>:</i> &nbsp;<?php echo text($ASSOCIATED1); }
                                        if ($LOCATION1) {
                                            echo "<i>".xlt('Location'); ?>:</i> &nbsp;<?php echo text($LOCATION1);}
                                        if ($QUALITY1) {
                                            echo "<i>".xlt('Quality'); ?>:</i> &nbsp;<?php echo text($QUALITY1);}
                                        if ($DURATION1) {
                                            echo "<i>".xlt('Duration'); ?>:</i> &nbsp;<?php echo text($DURATION1);
                                        }
                                        ?>
                                    </div>
                                    <br />
                                    <br />
                                    <?php echo "<b>".xlt('Chief Complaint 3'); ?>:</b> &nbsp;<?php echo text($CC3); ?>
                                    <br />
                                    <?php echo xlt('HPI'); ?>&nbsp; <?php echo text($HPI3); ?>
                                    <br />
                                    <div style="padding-left:10px;">
                                        <?php 
                                        if ($TIMING1) {
                                            echo "<i>".xlt('Timing'); ?>:</i>  &nbsp;<?php echo text($TIMING1); 
                                        }
                                        if ($CONTEXT1) {
                                            echo "<i>".xlt('Context'); ?>:</i> &nbsp;<?php echo text($CONTEXT1); 
                                        }
                                        if ($SEVERITY1) {
                                            echo "<i>".xlt('Severity'); ?>:</i> &nbsp;<?php echo text($SEVERITY1); 
                                        }
                                        if ($MODIFY1) {
                                            echo "<i>".xlt('Modifying'); ?>:</i> &nbsp;<?php echo text($MODIFY1);
                                        }
                                        if ($ASSOCIATED1) {
                                            echo "<i>".xlt('Associated'); ?>:</i> &nbsp;<?php echo text($ASSOCIATED1); }
                                        if ($LOCATION1) {
                                            echo "<i>".xlt('Location'); ?>:</i> &nbsp;<?php echo text($LOCATION1);}
                                        if ($QUALITY1) {
                                            echo "<i>".xlt('Quality'); ?>:</i> &nbsp;<?php echo text($QUALITY1);}
                                        if ($DURATION1) {
                                            echo "<i>".xlt('Duration'); ?>:</i> &nbsp;<?php echo text($DURATION1);
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
                    </div>
            </div>

        <!-- START OF THE PRESSURE BOX -->
            <div id="LayerTension" class="vitals" style="float:left;width: 1.5in; height: 1.05in;margin:10 20 10 40;padding: 0.02in; border: 1.00pt solid #000000;">
              
                <div id="Lyr4.0" style="position:absolute; left:0.05in; width: 1.4in; top:0.0in; padding: 0in; ">
                    <span class="top_left">
                          <b id="tension_tab"><?php echo xlt('Tension'); ?>:</b> 
                          <div style="position:absolute;background-color:#ffffff;text-align:left;width:50px; top:0.7in;font-size:0.9em;left:0.02in;">
                              <?php echo attr($IOPTIME); ?>
                          </div>    
                    </span>
                </div>
                <div id="Lyr4.1" style="position: absolute; top: 0.3in; left: 0.12in; width: 0.17in;height: 0.45in; border: none; padding: 0in;">
                      <font style="font-face:arial; font-size:3.5em;">T</font>
                      <font style="font-face:arial; font-size: 0.9em;"></font>
                </div>
                <div id="Lyr4.2" style="position: absolute; top: 0.4in; text-align:left;left:3.5em; height: 0.22in;  padding: 0in; border: 1pt black;">
                      <span style="position: absolute;top:0.02in;left:1em;font-size: 0.8em;font-weight:600;"><?php echo xlt('OD'); ?></span>
                      <span style="position: absolute;top:0.02in;left:3em;font-size: 0.8em;"><?php echo attr($ODIOPAP); ?></span>
                      <span style="position: absolute;top:0.02in;left:5em;font-size: 0.8em;"><?php echo attr($ODIOPTPN); ?></span>
                      <span style="position: absolute;top:0.02in;left:7em;font-size: 0.8em;"><?php echo attr($ODIOPTPN); ?></span>
                      <br />
                      <span style="position: absolute;top:0.16in;left:1em;font-size: 0.8em;font-weight:600;"><?php echo xlt('OS'); ?></span>
                      <span style="position: absolute;top:0.16in;left:3em;font-size: 0.8em;"><?php echo attr($OSIOPAP); ?></span>
                      <span style="position: absolute;top:0.16in;left:5em;font-size: 0.8em;"><?php echo attr($OSIOPTPN); ?></span>
                      <span style="position: absolute;top:0.16in;left:7em;font-size: 0.8em;"><?php echo attr($OSIOPFTN); ?></span>
                      <br /><br />
                      <span style="position: absolute;top:0.32in;left:3em;font-size: 0.8em;font-weight:600;"><?php echo xlt('AP'); ?></span>
                      <span style="position: absolute;top:0.32in;left:5em;font-size: 0.8em;font-weight:600;"><?php echo xlt('TP'); ?></span>
                      <span style="position: absolute;top:0.32in;left:7em;font-size: 0.8em;font-weight:600;"><?php echo xlt('FT'); ?></span>
                </div>
            </div>
        <!-- END OF THE PRESSURE BOX -->
            <br />
        <!-- start of the Amsler box -->
            <div id="LayerAmsler" class="vitals" style="float:left;width: 1.5in; height: 1.05in;margin:10 20;padding: 0.02in; border: 1.00pt solid #000000;">
              <div id="Lyr5.0" style="position:absolute;  left:0.05in; width: 1.4in; top:0in; padding: 0in;">
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
                  <input id="Amsler-Normal" disabled type="checkbox" <?php echo attr($checked); ?>>
              </div>     
              <div id="Lyr5.1" style="position: absolute; top: 0.2in; left: 0.12in; display:inline-block;border: none; padding: 0.0in;">
                  <table cellpadding=0 cellspacing=0 style="padding:0px;margin:auto;width:90%;align:auto;font-size:0.8em;text-align:center;">
                      <tr>
                          <td colspan=3 style="text-align:center;"><b><?php echo xlt('OD'); ?></b>
                          </td>
                          <td></td>
                          <td colspan=3 style="text-align:center;"><b><?php echo xlt('OS'); ?></b>
                          </td>
                      </tr>

                      <tr>
                          <td colspan=3>
                              <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROD); ?>.jpg" id="AmslerOD" style="margin:0.05in;height:0.45in;width:0.5in;" /></td>
                          <td></td>
                          <td colspan=3>
                              <img src="../../forms/<?php echo $form_folder; ?>/images/Amsler_<?php echo attr($AMSLEROS); ?>.jpg" id="AmslerOS" style="margin:0.05in;height:0.45in;width:0.5in;" /></td>
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
            <div id="LayerFields" class="vitals" style="float:left;width: 1.42in;height:1.05in;margin:10 20;padding: 0.02in; border: 1.00pt solid #000000;">
              <div  id="Lyr6.0" style="position:absolute;  left:0.05in; width: 1.4in; top:0.0in; padding: 2px; " dir="LTR">
                  <span class="top_left">
                      <b id="fields"><?php echo xlt('Fields'); ?>:</b>
                             
                  </span>
              </div> 
                  <?php 
                      // if the VF zone is checked, display it
                      // if ODVF1 = 1 (true boolean) the value="0" checked="true"
                      $bad='';
                      for ($z=1; $z <5; $z++) {
                          $ODzone = "ODVF".$z;
                          if ($$ODzone =='1') {
                              $ODVF[$z] = 'checked value="true"';
                              $bad++;
                          } else {
                              $ODVF[$z] = 'value="false"';
                          }
                          $OSzone = "OSVF".$z;
                          if ($$OSzone =="1") {
                              $OSVF[$z] = 'checked value="1"';
                              $bad++;
                          } else {
                              $OSVF[$z] = 'value="0"';
                          }
                      }
                      if (!$bad)  $VFFTCF = "checked";
                  ?>
              <div style="position:relative;text-align:right; top:0.03in;font-size:0.8em;right:0.1in;">
                          <label for="FieldsNormal" class="input-helper input-helper--checkbox"><?php echo xlt('FTCF'); ?></label>
                          <input id="FieldsNormal" disabled type="checkbox" value="1" <?php echo attr($VFFTCF); ?>>
              </div>   
              <div id="Lyr5.1" style="position: relative; top: 0.08in; left: 0.0in; border: none; background: white">
                  <table cellpadding='1' cellspacing="1" style="font-size: 0.8em;margin:auto;"> 
                      <tr>    
                          <td style="width:0.6in;" colspan="2"><b><?php echo xlt('OD'); ?></b><br /></td>

                          <td style="width:0.05in;"> </td>
                          <td style="width:0.6in;" colspan="2"><b><?php echo xlt('OS'); ?></b></td>
                      </tr> 
                      <tr>    
                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                              <input name="ODVF1" id="ODVF1" disabled type="checkbox" <?php echo attr($ODVF['1'])?> class="hidden"> 
                              <label for="ODVF1" class="input-helper input-helper--checkbox boxed"></label>
                          </td>
                          <td style="border-left:1pt solid black;border-bottom:1pt solid black;">
                              <input name="ODVF2" id="ODVF2" disabled type="checkbox" <?php echo attr($ODVF['2'])?> class="hidden"> 
                              <label for="ODVF2" class="input-helper input-helper--checkbox boxed"></label>
                          </td>
                          <td></td>
                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                              <input name="OSVF1" id="OSVF1" disabled type="checkbox" <?php echo attr($OSVF['1']); ?> class="hidden" >
                              <label for="OSVF1" class="input-helper input-helper--checkbox boxed"></label>
                          </td>
                          <td style="border-left:1pt solid black;border-bottom:1pt solid black;">
                              <input name="OSVF2" id="OSVF2" disabled type="checkbox" <?php echo attr($OSVF['2']); ?> class="hidden">                                                         
                              <label for="OSVF2" class="input-helper input-helper--checkbox boxed"> </label>
                          </td>
                      </tr>       
                      <tr>    
                          <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                              <input name="ODVF3" id="ODVF3" disabled type="checkbox"  class="hidden" <?php echo attr($ODVF['3']); ?>> 
                              <label for="ODVF3" class="input-helper input-helper--checkbox boxed"></label>
                          </td>
                          <td style="border-left:1pt solid black;border-top:1pt solid black;">
                              <input  name="ODVF4" id="ODVF4" disabled type="checkbox"  class="hidden" <?php echo attr($ODVF['4']); ?>>
                              <label for="ODVF4" class="input-helper input-helper--checkbox boxed"></label>  
                          </td>
                          <td></td>
                          <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                              <input name="OSVF3" id="OSVF3" disabled type="checkbox"  class="hidden" <?php echo attr($OSVF['3']); ?>>
                              <label for="OSVF3" class="input-helper input-helper--checkbox boxed"></label>
                          </td>
                          <td style="border-left:1pt solid black;border-top:1pt solid black;">
                              <input name="OSVF4" id="OSVF4" disabled type="checkbox"  class="hidden" <?php echo attr($OSVF['4']); ?>>
                              <label for="OSVF4" class="input-helper input-helper--checkbox boxed"></label>
                          </td>                    
                      </tr>
                  </table>
              </div>
            </div>
        <!-- end of the Fields box -->

        <!-- Start of the Vision box -->            
            <?php ($VAX!=1) ? ($display_Add = "display") : ($display_Add = "nodisplay"); ?>
            <div id="LayerVision_VAX" class="refraction borderShadow <?php echo $display_Add; ?>" 
                style="float:left;margin:5 10 10 10;">
                <table id="Additional_VA">
                    <th colspan="9"><?php echo xlt('Visual Acuity'); ?></th>
                    <tr><td></td>
                        <td><?php echo xlt('SC'); ?></td>
                        <td><?php echo xlt('W Rx'); ?></td>
                        <td><?php echo xlt('AR'); ?></td>
                        <td><?php echo xlt('MR'); ?></td>
                        <td><?php echo xlt('CR'); ?></td>
                        <td><?php echo xlt('PH'); ?></td>
                        <td><?php echo xlt('CTL'); ?></td>
                        
                    </tr>
                    <tr><td><b><?php echo xlt('OD'); ?>:</b></td>
                        <td><input type=text id="SCODVA_copy_brd" name="SCODVA_copy_brd" value="<?php echo attr($SCODVA); ?>" tabindex="99"></td>
                        <td><input type=text id="WODVA_copy_brd" name="WODVA_copy_brd" value="<?php echo attr($WODVA); ?>" tabindex="102"></td>
                        <td><input type=text id="ARODVA_copy_brd" name="ARODVA_copy_brd" value="<?php echo attr($ARODVA); ?>" tabindex="104"></td>
                        <td><input type=text id="MRODVA_copy_brd" name="MRODVA_copy_brd" value="<?php echo attr($MRODVA); ?>" tabindex="106"></td>
                        <td><input type=text id="CRODVA_copy_brd" name="CRODVA_copy_brd" value="<?php echo attr($CRODVA); ?>" tabindex="108"></td>
                        <td><input type=text id="PHODVA_copy_brd" name="PHODVA_copy_brd" value="<?php echo attr($PHODVA); ?>" tabindex="110"></td>
                        <td><input type=text id="CTLODVA_copy_brd" name="CTLODVA_copy_brd" value="<?php echo attr($CTLODVA); ?>" tabindex="100"></td>
                        </tr>
                     <tr><td><b><?php echo xlt('OS'); ?>:</b></td>
                        <td><input type=text id="SCOSVA_copy" name="SCOSVA_copy" value="<?php echo attr($SCOSVA); ?>" tabindex="100"></td>
                        <td><input type=text id="WOSVA_copy_brd" name="WOSVA_copy_brd" value="<?php echo attr($WOSVA); ?>" tabindex="101"></td>
                        <td><input type=text id="AROSVA_copy_brd" name="AROSVA_copy_brd" value="<?php echo attr($AROSVA); ?>" tabindex="103"></td>
                        <td><input type=text id="MROSVA_copy_brd" name="MROSVA_copy_brd" value="<?php echo attr($MROSVA); ?>" tabindex="105"></td>
                        <td><input type=text id="CROSVA_copy_brd" name="CROSVA_copy_brd" value="<?php echo attr($CROSVA); ?>" tabindex="107"></td>
                        <td><input type=text id="PHOSVA_copy_brd" name="PHOSVA_copy_brd" value="<?php echo attr($PHOSVA); ?>" tabindex="109"></td>
                        <td><input type=text id="CTLOSVA_copy_brd" name="CTLOSVA_copy_brd" value="<?php echo attr($CTLOSVA); ?>" tabindex="111"></td>
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
                        <td><input type=text id="SCNEARODVA" name="SCNEARODVA" value="<?php echo attr($SCNEARODVA); ?>"></td>
                        <td><input type=text id="WNEARODVA_copy_brd" name="WNEARODVA_copy_brd" value="<?php echo attr($WNEARODVA); ?>"></td>
                        <td><input type=text id="ARNEARODVA_copy_brd" name="ARNEARODVA_copy_brd" value="<?php echo attr($ARNEARODVA); ?>"></td>
                        <td><input type=text id="MRNEARODVA_copy_brd" name="MRNEARODVA_copy_brd" value="<?php echo attr($MRNEARODVA); ?>"></td>
                        <td><input type=text id="PAMODVA_copy_brd" name="PAMODVA_copy_brd" value="<?php echo attr($PAMODVA); ?>"></td>
                        <td><input type=text id="GLAREODVA_copy_brd" name="GLAREODVA_copy_brd" value="<?php echo attr($GLAREODVA); ?>"></td>
                        <td><input type=text id="CONTRASTODVA_copy_brd" name="CONTRASTODVA_copy_brd" value="<?php echo attr($CONTRASTODVA); ?>"></td>
                    </tr>
                    <tr><td><b><?php echo xlt('OS'); ?>:</b></td>
                        <td><input type=text id="SCNEAROSVA" name="SCNEAROSVA" value="<?php echo attr($SCNEAROSVA); ?>"></td>
                        <td><input type=text id="WNEAROSVA_copy_brd" name="WNEAROSVA_copy_brd" value="<?php echo attr($WNEAROSVA); ?>"></td>
                        <td><input type=text id="ARNEAROSVA_copy" name="ARNEAROSVA_copy" value="<?php echo attr($ARNEAROSVA); ?>"></td>
                        <td><input type=text id="MRNEAROSVA_copy" name="MRNEAROSVA_copy" value="<?php echo attr($MRNEAROSVA); ?>"></td>
                        <td><input type=text id="PAMOSVA_copy_brd" name="PAMOSVA_copy_brd" value="<?php echo attr($PAMOSVA); ?>"></td>
                        <td><input type=text id="GLAREOSVA_copy_brd" name="GLAREOSVA_copy_brd" value="<?php echo attr($GLAREOSVA); ?>"></td>
                        <td><input type=text id="CONTRASTOSVA" name="CONTRASTOSVA" value="<?php echo attr($CONTRASTOSVA); ?>"></td>
                    </tr>
                </table>
            </div>
        <!-- End of the Vision box -->     

        <!-- start of the Pupils box -->
            <div id="LayerPupils" class="vitals" style="margin:5px;float:left;width: 2.2in; height: 1.05in; padding: 0.02in; border: 1.00pt solid #000000; ">  
              <span class="top_left"><b id="pupils"><?php echo xlt('Pupils'); ?>:</b> </span>
              <div style="position:absolute;text-align:right; top:0.03in;font-size:0.8em;right:0.1in;">
                          <label for="Pupil_normal" class="input-helper input-helper--checkbox"><?php echo xlt('Normal'); ?></label>
                          <input id="Pupil_normal" disabled type="checkbox" value="1" checked="checked">
              </div>
              <div id="Lyr7.0" style="position: absolute; top: 0.3in; left: 0.15in; border: none;">
                  <table cellpadding=2 cellspacing=1 style="font-size: 0.9em;;"> 
                      <tr>    
                          <th style="width:0.2in;"> &nbsp;
                          </th>
                          <th style="width:0.7in;padding: 0.1;"><?php echo xlt('size'); ?> (<?php echo xlt('mm'); ?>)
                          </th>
                          <th style="width:0.2in;padding: 0.1;"><?php echo xlt('react'); ?> 
                          </th>
                          <th style="width:0.2in;padding: 0.1;"><?php echo xlt('APD'); ?>
                          </th>
                      </tr>
                      <tr>    
                          <td><b><?php echo xlt('OD'); ?></b>
                          </td>
                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;">
                            <?php echo attr($ODPUPILSIZE1); ?>
                              <font>&#8594;</font>
                              <?php echo attr($ODPUPILSIZE2); ?>
                          </td>
                          <td style="border-left:1pt solid black;border-right:1pt solid black;border-bottom:1pt solid black;">
                            <?php echo attr($ODPUPILREACTIVITY); ?>
                          </td>
                          <td style="border-bottom:1pt solid black;">
                            <?php echo attr($ODAPD); ?>
                          </td>
                      </tr>
                      <tr>    
                          <td><b><?php echo xlt('OS'); ?></b>
                          </td>
                          <td style="border-right:1pt solid black;border-top:1pt solid black;">
                            <?php echo attr($OSPUPILSIZE1); ?>
                              <font>&#8594;</font>
                              <?php echo attr($OSPUPILSIZE2); ?>
                          </td>
                          <td style="border-left:1pt solid black;border-right:1pt solid black;border-top:1pt solid black;">
                            <?php echo attr($OSPUPILREACTIVITY); ?>
                          </td>
                          <td style="border-top:1pt solid black;">
                            <?php echo attr($OSAPD); ?>
                          </td>
                      </tr>
                  </table>
              </div>  
            </div>
        <!-- end of the Pupils box -->

        <!-- start of slide down pupils_panel --> 
            <div id="dim_pupils_panel" class="vitals <?php echo attr($display_dim_pupils_panel); ?>" style="position:relative;float:left;margin:5px;height: 1.05in; width:2.2in;padding: 0.02in; border: 1.00pt solid #000000; ">  

                <span class="top_left"><b id="pupils_DIM" style="width:100px;"><?php echo xlt('Pupils') ?>: <?php echo xlt('Dim'); ?></b> </span>
                  <div id="Lyr7.1" style="position: absolute; top: 0.3in; left: 0.1in; border: none;padding: auto;">
                      <table cellpadding="2" cellpadding="0" style="font-size: 0.9em;"> 
                          <tr>    
                              <th></th>
                              <th style="width:0.7in;padding: 0;"><?php echo xlt('size'); ?> (<?php echo xlt('mm'); ?>)
                              </th>
                          </tr>
                          <tr>    
                              <td><b><?php echo xlt('OD'); ?></b>
                              </td>
                              <td style="border-bottom:1pt solid black;padding-left:0.2in;">
                                <?php echo attr($DIMODPUPILSIZE1); ?>
                                  <font style="font-size:1.0em;">&#8594;</font>
                                  <?php echo attr($DIMODPUPILSIZE2); ?>
                              </td>
                          </tr>
                          <tr>    
                              <td ><b><?php echo xlt('OS'); ?></b>
                              </td>
                              <td style="border-top:1pt solid black;padding-left:0.2in;">
                                <?php echo attr($DIMOSPUPILSIZE1); ?>
                                  <font style="font-size:1.0em;">&#8594;</font>
                                  <?php echo attr($DIMOSPUPILSIZE2); ?>
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
                
            <div style="clear:both;width:850px;left:0px;text-align:center;margin:auto;">
            
                <!-- start IOP chart section -->
                <div style="text-align:left;float:left;margin-left:10px;" class="section" >
                    <?php ($IOP ==1) ? ($display_IOP = "display") : ($display_IOP = "nodisplay"); ?>
                    <div id="LayerVision_IOP" class="borderShadow <?php echo $display_IOP; ?>"style="float:left;" >
                         <table id="iopgraph" name "iopgraph" >
                            <h4> Placeholder: plot pressures over time</h4>
                         </table>
                    </div>
                </div>
                <!-- end IOP chart section -->

                <!-- start of the refraction boxes -->
                <div style="text-align:left;float:left;margin-left:10px;" class="section" >
                    <?php ($WODSPH) ? ($display_W = "display") : ($display_W = "display"); ?>
                    <div id="LayerVision_W" class="refraction borderShadow <?php echo $display_W; ?>">
                        <table id="wearing" >
                            <tr>
                                <th colspan="9" id="wearing_title"><?php echo xlt('Current Glasses'); ?>
                                    
                                </th>
                            </tr>
                            <tr style="font-weight:400;">
                                <td ></td>
                                <td></td>
                                <td><?php echo xlt('Sph'); ?></td>
                                <td><?php echo xlt('Cyl'); ?></td>
                                <td><?php echo xlt('Axis'); ?></td>
                                <td><?php echo xlt('Prism'); ?></td>
                                <td><?php echo xlt('Acuity'); ?></td>
                                <td rowspan="7" class="right" style="padding:10 0 10 0;">
                                    <b style="font-weight:600;text-decoration:underline;">Rx Type</b><br />
                                    <label for="Single" class="input-helper input-helper--checkbox"><?php echo xlt('Single'); ?></label>
                                    <input type="radio" disabled value="0" id="Single" name="RX1" <?php if ($RX1 == '0') echo 'checked="checked"'; ?> /></span><br /><br />
                                    <label for="Bifocal" class="input-helper input-helper--checkbox"><?php echo xlt('Bifocal'); ?></label>
                                    <input type="radio" disabled value="1" id="Bifocal" name="RX1" <?php if ($RX1 == '1') echo 'checked="checked"'; ?> /></span><br /><br />
                                    <label for="Trifocal" class="input-helper input-helper--checkbox"><?php echo xlt('Trifocal'); ?></label>
                                    <input type="radio" disabled value="2" id="Trifocal" name="RX1" <?php if ($RX1 == '2') echo 'checked="checked"'; ?> /></span><br /><br />
                                    <label for="Progressive" class="input-helper input-helper--checkbox"><?php echo xlt('Prog.'); ?></label>
                                    <input type="radio" disabled value="3" id="Progressive" name="RX1" <?php if ($RX1 == '3') echo 'checked="checked"'; ?> /></span><br />
                                </td>
                            </tr>
                            <tr>
                                <td rowspan="2"><?php echo xlt('Dist'); ?></td>    
                                <td><b><?php echo xlt('OD'); ?>:</b></td>
                                <td><input type=text id="WODSPH" name="WODSPH"  value="<?php echo attr($WODSPH); ?>"></td>
                                <td><input type=text id="WODCYL" name="WODCYL"  value="<?php echo attr($WODCYL); ?>"></td>
                                <td><input type=text id="WODAXIS" name="WODAXIS" value="<?php echo attr($WODAXIS); ?>"></td>
                                <td><input type=text id="WODPRISM" name="WODPRISM" value="<?php echo attr($WODPRISM); ?>"></td>
                                <td><input type=text id="WODVA" name="WODVA" value="<?php echo attr($WODVA); ?>"></td>
                            </tr>
                            <tr>
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td><input type=text id="WOSSPH" name="WOSSPH" value="<?php echo attr($WOSSPH); ?>"></td>
                                <td><input type=text id="WOSCYL" name="WOSCYL" value="<?php echo attr($WOSCYL); ?>"></td>
                                <td><input type=text id="WOSAXIS" name="WOSAXIS" value="<?php echo attr($WOSAXIS); ?>"></td>
                                <td><input type=text id="WOSPRISM" name="WOSPRISM" value="<?php echo attr($WOSPRISM); ?>"></td>
                                <td><input type=text id="WOSVA" name="WOSVA" value="<?php echo attr($WOSVA); ?>"></td>
                            </tr>
                            <tr class="WNEAR">
                                <td rowspan=2><span style="text-decoration:none;"><?php echo xlt('Mid'); ?>/<br /><?php echo xlt('Near'); ?></span></td>    
                                <td><b><?php echo xlt('OD'); ?>:</b></td>
                                <td class="WMid nodisplay"><input type=text id="WODADD1" name="WODADD1" value="<?php echo attr($WODADD1); ?>"></td>
                                <td class="WAdd2"><input type=text id="WODADD2" name="WODADD2" value="<?php echo attr($WODADD2); ?>"></td>
                                <td class="WHIDECYL"><input type=text id="WNEARODCYL" name="WNEARODCYL" value="<?php echo attr($WNEARODCYL); ?>"></td>
                                <td><input type=text id="WNEARODAXIS" name="WNEARODAXIS" value="<?php echo attr($WNEARODAXIS); ?>"></td>
                                <td><input type=text id="WNEARODPRISM" name="WODPRISMNEAR" value="<?php echo attr($WNEARODPRISM); ?>"></td>
                                <td><input type=text id="WNEARODVA" name="WNEARODVA" value="<?php echo attr($WNEARODVA); ?>"></td>
                            </tr>
                            <tr class="WNEAR">
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td class="WMid nodisplay"><input type=text id="WOSADD1" name="WOSADD1" value="<?php echo attr($WOSADD1); ?>"></td>
                                <td class="WAdd2"><input type=text id="WOSADD2" name="WOSADD2" value="<?php echo attr($WOSADD2); ?>"></td>
                                <td class="WHIDECYL"><input type=text id="WNEAROSCYL" name="WNEAROSCYL" value="<?php echo attr($WNEAROSCYL); ?>"></td>
                                <td><input type=text id="WNEAROSAXIS" name="WNEAROSAXIS" value="<?php echo attr($WNEAROSAXIS); ?>"></td>
                                <td><input type=text id="WNEAROSPRISM" name="WNEAROSPRISM" value="<?php echo attr($WNEAROSPRISM); ?>"></td>
                                <td><input type=text id="WNEAROSVA" name="WNEAROSVA" value="<?php echo attr($WNEAROSVA); ?>"></td>
                            </tr>
                            <tr style="top:3.5in;">
                                <td colspan="2" style="text-align:right;vertical-align:top;top:0px;"><b><?php echo xlt('Comments'); ?>:</b>
                                </td>
                                <td colspan="4" class="up" style="text-align:left;vertical-align:middle;top:0px;"></td></tr>
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
                            <th colspan=9>Autorefraction Refraction</th>
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
                                <td><input type=text id="ARODSPH" name="ARODSPH" value="<?php echo attr($ARODSPH); ?>"></td>
                                <td><input type=text id="ARODCYL" name="ARODCYL" value="<?php echo attr($ARODCYL); ?>"></td>
                                <td><input type=text id="ARODAXIS" name="ARODAXIS" value="<?php echo attr($ARODAXIS); ?>"></td>
                                <td><input type=text id="ARODVA" name="ARODVA" value="<?php echo attr($ARODVA); ?>"></td>
                                <td><input type=text id="ARODADD" name="ARODADD" value="<?php echo attr($ARODADD); ?>"></td>
                                <td><input type=text id="ARNEARODVA" name="ARNEARODVA" value="<?php echo attr($ARNEARODVA); ?>"></td>
                                <td><input type=text id="ARODPRISM" name="ARODPRISM" value="<?php echo attr($ARODPRISM); ?>"></td>
                            </tr>
                             <tr>
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td><input type=text id="AROSSPH" name="AROSSPH" value="<?php echo attr($AROSSPH); ?>"></td>
                                <td><input type=text id="AROSCYL" name="AROSCYL" value="<?php echo attr($AROSCYL); ?>"></td>
                                <td><input type=text id="AROSAXIS" name="AROSAXIS" value="<?php echo attr($AROSAXIS); ?>"></td>
                                <td><input type=text id="AROSVA" name="AROSVA" value="<?php echo attr($AROSVA); ?>"></td>
                                <td><input type=text id="AROSADD" name="AROSADD" value="<?php echo attr($AROSADD); ?>"></td>
                                <td><input type=text id="ARNEAROSVA" name="ARNEAROSVA" value="<?php echo attr($ARNEAROSVA); ?>"></td>
                                <td><input type=text id="AROSPRISM" name="AROSPRISM" value="<?php echo attr($AROSPRISM); ?>"></td>
                            </tr>
                            <th colspan="7">Manifest (Dry) Refraction</th>
                            <th colspan="2" style="text-align:right;"></th>
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
                                <td><input type=text id="MRODSPH" name="MRODSPH" value="<?php echo attr($MRODSPH); ?>"></td>
                                <td><input type=text id="MRODCYL" name="MRODCYL" value="<?php echo attr($MRODCYL); ?>"></td>
                                <td><input type=text id="MRODAXIS"  name="MRODAXIS" value="<?php echo attr($MRODAXIS); ?>"></td>
                                <td><input type=text id="MRODVA"  name="MRODVA" value="<?php echo attr($MRODVA); ?>"></td>
                                <td><input type=text id="MRODADD"  name="MRODADD" value="<?php echo attr($MRODADD); ?>"></td>
                                <td><input type=text id="MRNEARODVA"  name="MRNEARODVA" value="<?php echo attr($MRNEARODVA); ?>"></td>
                                <td><input type=text id="MRODPRISM"  name="MRODPRISM" value="<?php echo attr($MRODPRISM); ?>"></td>
                            </tr>
                            <tr>
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td><input type=text id="MROSSPH" name="MROSSPH" value="<?php echo attr($MROSSPH); ?>"></td>
                                <td><input type=text id="MROSCYL" name="MROSCYL" value="<?php echo attr($MROSCYL); ?>"></td>
                                <td><input type=text id="MROSAXIS"  name="MROSAXIS" value="<?php echo attr($MROSAXIS); ?>"></td>
                                <td><input type=text id="MROSVA"  name="MROSVA" value="<?php echo attr($MROSVA); ?>"></td>
                                <td><input type=text id="MROSADD"  name="MROSADD" value="<?php echo attr($MROSADD); ?>"></td>
                                <td><input type=text id="MRNEAROSVA"  name="MRNEAROSVA" value="<?php echo attr($MRNEAROSVA); ?>"></td>
                                <td><input type=text id="MROSPRISM"  name="MROSPRISM" value="<?php echo attr($MROSPRISM); ?>"></td>
                            </tr>
                        </table>
                    </div>

                    <?php ($CRODSPH)  ? ($display_Cyclo = "display") : ($display_Cyclo = "nodisplay"); ?>
                    <div id="LayerVision_CR" class="refraction borderShadow <?php echo $display_Cyclo; ?>">
                        <table id="cycloplegia">
                            <th colspan=9><?php echo xlt('Cycloplegic (Wet) Refraction'); ?></th>
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
                                    </br>
                                    <input type="checkbox" id="Neo25" name="NEO25" value="Neosynephrine 2.5%"  <?php if ($NEO25 =='Neosynephrine 2.5%') echo "checked='checked'"; ?> />
                                    <label for="Neo25" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Neo 2.5%'); ?></label>
                                    <br />
                                    <input type="checkbox" id="Cyclogyl" name="CYCLOGYL" value="Cyclopentolate 1%"  <?php if ($CYCLOGYL == 'Cyclopentolate 1%') echo "checked='checked'"; ?> />
                                    <label for="Cyclogyl" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Cyclo 1%'); ?></label>
                                    </br>
                                    <input type="checkbox" id="Atropine" name="ATROPINE" value="Atropine 1%"  <?php if ($ATROPINE == 'Atropine 1%') echo "checked='checked'"; ?> />
                                    <label for="Atropine" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Atropine 1%'); ?></label>
                                    </br>
                                </td>
                            </tr>
                            <tr>
                                <td><b><?php echo xlt('OD'); ?>:</b></td>
                                <td><input type=text id="CRODSPH" name="CRODSPH" value="<?php echo attr($CRODSPH); ?>"></td>
                                <td><input type=text id="CRODCYL" name="CRODCYL" value="<?php echo attr($CRODCYL); ?>"></td>
                                <td><input type=text id="CRODAXIS" name="CRODAXIS" value="<?php echo attr($CRODAXIS); ?>"></td>
                                <td><input type=text id="CRODVA" name="CRODVA"  value="<?php echo attr($CRODVA); ?>"></td>
                                <td colspan="1" style="text-align:left;">
                                    <input type="radio" name="WETTYPE" id="Auto" value="Auto" <?php if ($WETTYPE == "Auto") echo "checked='checked'"; ?>>
                                    <label for="Auto" disabled class="input-helper input-helper--checkbox"><?php echo xlt('Auto'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td><input type=text id="CROSSPH" name="CROSSPH" value="<?php echo attr($CROSSPH); ?>"></td>
                                <td><input type=text id="CROSCYL" name="CROSCYL" value="<?php echo attr($CROSCYL); ?>"></td>
                                <td><input type=text id="CROSAXIS" name="CROSAXIS" value="<?php echo attr($CROSAXIS); ?>"></td>
                                <td><input type=text id="CROSVA" name="CROSVA" value="<?php echo attr($CROSVA); ?>"></td>
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
                                </tD>
                            </tr>
                        </table>
                    </div>

                    <?php ($CTLODSPH) ? ($display_CTL = "display") : ($display_CTL = "nodisplay"); ?>
                    <div id="LayerVision_CTL" class="refraction borderShadow <?php echo $display_CTL; ?>">
                        <table id="CTL" style="width:100%;">
                            <th colspan="9"><?php echo xlt('Contact Lens Refraction'); ?></th>
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
                                <td><input type=text id="CTLODSPH" name="CTLODSPH" value="<?php echo attr($CTLODSPH); ?>"></td>
                                <td><input type=text id="CTLODCYL" name="CTLODCYL" value="<?php echo attr($CTLODCYL); ?>"></td>
                                <td><input type=text id="CTLODAXIS" name="CTLODAXIS" value="<?php echo attr($CTLODAXIS); ?>"></td>
                                <td><input type=text id="CTLODBC" name="CTLODBC" value="<?php echo attr($CTLODBC); ?>"></td>
                                <td><input type=text id="CTLODDIAM" name="CTLODDIAM" value="<?php echo attr($CTLODDIAM); ?>"></td>
                                <td><input type=text id="CTLODADD" name="CTLODADD" value="<?php echo attr($CTLODADD); ?>"></td>
                                <td><input type=text id="CTLODVA" name="CTLODVA" value="<?php echo attr($CTLODVA); ?>"></td>
                            </tr>
                            <tr >
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td><input type=text id="CTLOSSPH" name="CTLOSSPH" value="<?php echo attr($CTLOSSPH); ?>"></td>
                                <td><input type=text id="CTLOSCYL" name="CTLOSCYL" value="<?php echo attr($CTLOSCYL); ?>"></td>
                                <td><input type=text id="CTLOSAXIS" name="CTLOSAXIS" value="<?php echo attr($CTLOSAXIS); ?>"></td>
                                <td><input type=text id="CTLOSBC" name="CTLOSBC" value="<?php echo attr($CTLOSBC); ?>"></td>
                                <td><input type=text id="CTLOSDIAM" name="CTLOSDIAM" value="<?php echo attr($CTLOSDIAM); ?>"></td>
                                <td><input type=text id="CTLOSADD" name="CTLOSADD" value="<?php echo attr($CTLOSADD); ?>"></td>
                                <td><input type=text id="CTLOSVA" name="CTLOSVA" value="<?php echo attr($CTLOSVA); ?>"></td>
                            </tr>
                        </table>
                    </div>

                    <?php ($ADDITIONAL!=1) ? ($display_Add = "display") : ($display_Add = "nodisplay"); ?>
                    <div id="LayerVision_ADDITIONAL" class="refraction borderShadow <?php echo $display_Add; ?>">
                        <table id="Additional">
                            <th colspan=9><?php echo xlt('Additional Data Points'); ?></th>
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
                                <td><input type=text id="PHODVA" name="PHODVA" value="<?php echo attr($PHODVA); ?>"></td>
                                <td><input type=text id="PAMODVA" name="PAMODVA" value="<?php echo attr($PAMODVA); ?>"></td>
                                <td><input type=text id="LIODVA" name="LIODVA"  title="test" value="<?php echo attr($LIODVA); ?>"></td>
                                <td><input type=text id="GLAREODVA" name="GLAREODVA" value="<?php echo attr($GLAREODVA); ?>"></td>
                                <td><input type=text id="ODK1" name="ODK1" value="<?php echo attr($ODK1); ?>"></td>
                                <td><input type=text id="ODK2" name="ODK2" value="<?php echo attr($ODK2); ?>"></td>
                                <td><input type=text id="ODK2AXIS" name="ODK2AXIS" value="<?php echo attr($ODK2AXIS); ?>"></td>
                            </tr>
                            <tr>
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td><input type=text id="PHOSVA" name="PHOSVA" value="<?php echo attr($PHOSVA); ?>"></td>
                                <td><input type=text id="PAMOSVA" name="PAMOSVA" value="<?php echo attr($PAMOSVA); ?>"></td>
                                <td><input type=text id="LIOSVA" name="LIOSVA" value="<?php echo attr($LIOSVA); ?>"></td>
                                <td><input type=text id="GLAREOSVA" name="GLAREOSVA" value="<?php echo attr($GLAREOSVA); ?>"></td>
                                <td><input type=text id="OSK1" name="OSK1" value="<?php echo attr($OSK1); ?>"></td>
                                <td><input type=text id="OSK2" name="OSK2" value="<?php echo attr($OSK2); ?>"></td>
                                <td><input type=text id="OSK2AXIS" name="OSK2AXIS" value="<?php echo attr($OSK2AXIS); ?>"></td>
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
                                <td><input type=text id="ODAXIALLENGTH" name="ODAXIALLENGTH"  value="<?php echo attr($ODAXIALLENGTH); ?>"></td>
                                <td><input type=text id="ODACD" name="ODACD"  value="<?php echo attr($ODACD); ?>"></td>
                                <td><input type=text id="ODPDMeasured" name="ODPDMeasured"  value="<?php echo attr($ODPDMeasured); ?>"></td>
                                <td><input type=text id="ODLT" name="ODLT"  value="<?php echo attr($ODLT); ?>"></td>
                                <td><input type=text id="ODW2W" name="ODW2W"  value="<?php echo attr($ODW2W); ?>"></td>
                                <td><input type=text id="ODECL" name="ODECL"  value="<?php echo attr($ODECL); ?>"></td>
                                <!-- <td><input type=text id="pend" name="pend"  value="<?php echo attr($pend); ?>"></td> -->
                            </tr>
                            <tr>
                                <td><b><?php echo xlt('OS'); ?>:</b></td>
                                <td><input type=text id="OSAXIALLENGTH" name="OSAXIALLENGTH" value="<?php echo attr($OSAXIALLENGTH); ?>"></td>
                                <td><input type=text id="OSACD" name="OSACD" value="<?php echo attr($OSACD); ?>"></td>
                                <td><input type=text id="OSPDMeasured" name="OSPDMeasured" value="<?php echo attr($OSPDMeasured); ?>"></td>
                                    <td><input type=text id="OSLT" name="OSLT" value="<?php echo attr($OSLT); ?>"></td>
                                    <td><input type=text id="OSW2W" name="OSW2W" value="<?php echo attr($OSW2W); ?>"></td>
                                    <td><input type=text id="OSECL" name="OSECL" value="<?php echo attr($OSECL); ?>"></td>
                                    <!--  <td><input type=text id="pend" name="pend" value="<?php echo attr($pend); ?>"></td> -->
                                </tr>
                            </table>
                    </div>  
                </div>
                <!-- end of the refraction boxes -->

                <div class="page-break"></div>

                <!-- start of external exam -->
                <div style="clear:both;width:850px;left:0px;text-align:center;background-color:#fff;">    
                    <div style="float:left;left:0px;top:0px;width:4in;text-align:left;">
                        <br />
                        <br />
                        <b><?php echo xlt('External Exam'); ?>:</b><br />
                        <table>
                                    <tr>
                                        <td> <br />
                                            <table class="borderShadow" style="padding:15px;">
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
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><br />
                                            <table class="borderShadow">
                                                <tr>
                                                  <td class="report_text right title" style="text-decoration:underline;"><?php echo xlt('Right'); ?></td>
                                                  <td></td>
                                                  <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></th></td>
                                                </tr>
                                                
                                                <tr>
                                                  <td class="report_text right" style="width:100px;"><?php echo $RLF; ?></td>
                                                  <td class="middle"><?php echo xlt('Levator Function'); ?></td>
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
                                                    <td colspan="3" style="padding-top:0.02in;text-align:center;"><br />
                                                        <span style="text-decoration:underline;"><?php echo xlt('Hertel Exophthalmometry'); ?>
                                                        </span>
                                                        <br />
                                                        <? if ($HERTELBASE) { ?>
                                                  
                                                    <?php echo attr($ODHERTEL); ?> <i class="fa fa-minus"></i> <?php echo attr($HERTELBASE); ?>
                                                    <i class="fa fa-minus"></i> <?php echo attr($OSHERTEL); ?>
                                                    <br />
                                                <? } ?>
                                                  </td>
                                                </tr>
                                            
                                            </table>
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
                            display_draw_image ("EXT"); 
                        ?>
                </div>
                <!-- end of external exam -->

                <!-- start of Anterior Segment exam -->
                <div style="clear:both;width:850px;left:0px;text-align:center;background-color:#fff;">    
                    <div style="float:left;left:0px;top:0px;width:4in;text-align:left;">
                        <br />
                        <br />
                        <b><?php echo xlt('Anterior Segment'); ?>:</b><br />
                        <table>
                                    <tr>
                                        <td> <br />
                                            <table class="borderShadow">
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
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><br />
                                            <table class="borderShadow">
                                                <tr>
                                                  <td class="report_text right title" style="text-decoration:underline;"><?php echo xlt('Right'); ?></td>
                                                  <td></td>
                                                  <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left'); ?></th></td>
                                                </tr>
                                                
                                                <tr>
                                                  <td class="report_text right" style="width:100px;"><?php echo $RLF; ?></td>
                                                  <td class="middle"><?php echo xlt('Levator Function'); ?></td>
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
                                                    <td colspan="3" style="padding-top:0.02in;text-align:center;"><br />
                                                        <span style="text-decoration:underline;"><?php echo xlt('Hertel Exophthalmometry'); ?>
                                                        </span>
                                                        <br />
                                                        <? if ($HERTELBASE) { ?>
                                                  
                                                    <?php echo attr($ODHERTEL); ?> <i class="fa fa-minus"></i> <?php echo attr($HERTELBASE); ?>
                                                    <i class="fa fa-minus"></i> <?php echo attr($OSHERTEL); ?>
                                                    <br />
                                                <? } ?>
                                                  </td>
                                                </tr>
                                            
                                            </table>
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
                        display_draw_image ("ANTSEG"); 
                    ?>
                </div>
                <!-- end of Anterior Segment exam -->
                
                <!-- start of RETINA exam -->
                <div style="clear:both;width:850px;left:0px;text-align:center;background-color:#fff;">    
                    <div style="float:left;left:0px;top:0px;width:4in;text-align:left;">
                        <br />
                        <br />
                        <b><?php echo xlt('Retina'); ?>:</b><br />
                        <table>
                                <tr>
                                    <td>   <br />
                                        <table class="borderShadow">
                                            <tr>
                                                <td class="report_text right title" style="text-decoration:underline;">
                                                <?php echo xlt('Right Eye'); ?></td>
                                                <td></td>
                                                <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left Eye'); ?></td>
                                            </tr>
                                                  <tr>
                                                      <td class="right report_text"><?php echo $ODDISC; ?></td>
                                                      <td class="middle"><?php echo xlt('Disc'); ?></td>
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
                                    </td>
                                </tr>
                                <tr>
                                    <td>  <br /><br />
                                        <table class="borderShadow"> 
                                                 <tr>
                                                <td class="report_text right title" style="text-decoration:underline;">
                                                <?php echo xlt('Right Eye'); ?></td>
                                                <td></td>
                                                <td class="report_text title" style="text-align:left;text-decoration:underline;"><?php echo xlt('Left Eye'); ?></td>
                                            </tr>
                                              <tr>
                                                  <td class="report_text right">&nbsp;<?php echo $ODCMT; ?></td>
                                                  <td class="middle"><?php echo xlt('Central Macular Thickness'); ?> </td>
                                                  <td class="report_text" >&nbsp;<?php echo $OSCMT; ?></td>
                                              </tr>
                                        </table>
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
                            display_draw_image ("RETINA"); 
                        ?>
                   
                </div>
                <!-- end of RETINA exam -->
                
                <!-- start of NEURO exam -->
                <div style="clear:both;width:850px;left:0px;text-align:center;background-color:#fff;">    
                    <div style="float:left;left:0px;top:0px;width:4in;text-align:left;">
                        <br />
                        <br />
                        <b><?php echo xlt('Neoro-physiology'); ?>:</b><br />
                        <table>
                            <tr>
                                <td><br />
                                    <table class="borderShadow">
                                        <tr>
                                            <td >
                                                <div id="NPCNPA" name="NPCNPA">
                                                    <table style="float:left;text-align:center;padding:2px;font-size:0.9em;">
                                                        <tr style="text-decoration:underline;font-weight:600;"  class="report_text"><td style="width:120px;"></td><td><?php echo xlt('OD'); ?></td><td><?php echo xlt('OS'); ?></td></tr>
                                                        <tr>
                                                          <td class="right report_text" style="font-weight:600;"><?php echo xlt('NPA'); ?>:&nbsp;</td>
                                                          <td><?php echo attr($ODNPA); ?></td>
                                                          <td><?php echo attr($OSNPA); ?></td>
                                                        </tr>
                                                        <tr>
                                                          <td class="right report_text" style="font-weight:600;"><?php echo xlt('NPC'); ?>: &nbsp;</td>
                                                          <td colspan="2" ><?php echo attr($NPC); ?>
                                                          </td>
                                                        </tr>
                                                        <tr>
                                                          <td class="right report_text" style="font-weight:600;">
                                                              <?php echo xlt('Stereopsis'); ?>:
                                                          </td>
                                                          <td colspan="2">
                                                              <?php echo attr($STEREOPSIS); ?>
                                                          </td>
                                                        </tr>
                                                        <tr><td colspan="3" class="title underline" style="text-align:center"><br /><br /><?php echo xlt('Amplitudes'); ?> *<br />
                                                          </td></tr>
                                                        <tr style="text-decoration:underline;"><td ></td><td style="width:75px;"><?php echo xlt('Distance'); ?> </td><td style="width:75px;"> <?php echo xlt('Near'); ?></td></tr>
                                                        <tr>
                                                          <td  class="right report_text"><?php echo xlt('Divergence'); ?>: </td>
                                                          <td><?php echo attr($CASCDIST); ?></td>
                                                          <td><?php echo attr($CASCNEAR); ?></td></tr>
                                                        <tr>
                                                          <td class="right report_text"><?php echo xlt('Convergence'); ?>: </td>
                                                          <td><?php echo attr($CACCDIST); ?></td>
                                                          <td><?php echo attr($CACCNEAR); ?></td></tr>
                                                        </tr>
                                                        <tr>
                                                          <td class="right report_text">
                                                              <?php echo xlt('Vertical Fusional'); ?>:
                                                          </td>
                                                          <td colspan="2">
                                                              <?php echo attr($VERTFUSAMPS); ?>
                                                              <br />
                                                          </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="3">
                                                                <small>* best corrected</small>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                            <td style="border-right:1pt solid black;">
                                            <td style="text-align:center;margin:50;">
                                                <div id="NEURO_MOTILITY" class="text_clinical XborderShadow" 
                                                      style="font-size:0.9em;margin:auto;padding:10px;font-weight:bold;height:135px;width:175px;">
                                                      <div>
                                                          <table style="margin:2 4 2 2;">
                                                              <tr>
                                                                  <td style="width:40%;font-size:1.0em;margin:0 auto;font-weight:bold;"><?php echo xlt('Motility'); ?>:</td>
                                                                  <td style="font-size:0.9em;vertical-align:top;text-align:right;top:0.0in;right:0.1in;height:0px;">
                                                                      <?php echo xlt('Normal'); ?>
                                                                      <input id="MOTILITYNORMAL" name="MOTILITYNORMAL" disabled type="checkbox" <?php if ($MOTILITYNORMAL =='on') echo "checked='checked'"; ?>>
                                                                  </td>
                                                              </tr>
                                                          </table>
                                                      </div>
                                                    <div style="float:left;left:0.4in;text-decoration:underline;"><?php echo xlt('OD'); ?></div>
                                                    <div style="float:right;right:0.4in;text-decoration:underline;"><?php echo xlt('OS'); ?></div>
                                                    <br />
                                                    <div class="divTable" style="background: url(../../forms/<?php echo $form_folder; ?>/images/eom.bmp) no-repeat center center;background-size: 90% 90%;height:0.77in;width:0.71in;padding:1px;margin:6 1 1 2;">
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>


                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_4_3" id="MOTILITY_RS_4_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_4_1" id="MOTILITY_RS_4_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_4" id="MOTILITY_RS_4" value="<?php echo attr($MOTILITY_RS); ?>">
                                                        <?php if ($MOTILITY_RS >'3') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RS_4_2" id="MOTILITY_RS_4_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_4_4" id="MOTILITY_RS_4_4">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_3_1" id="MOTILITY_RS_3_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_3" id="MOTILITY_RS_3"><?php if ($MOTILITY_RS >'2') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RS_3_2" id="MOTILITY_RS_3_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_2_1" id="MOTILITY_RS_2_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_2" id="MOTILITY_RS_2"><?php if ($MOTILITY_RS >'1') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RS_2_2" id="MOTILITY_RS_2_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_1_1" id="MOTILITY_RS_1_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_1" id="MOTILITY_RS_1"><?php if ($MOTILITY_RS >'0') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RS_1_2" id="MOTILITY_RS_1_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_0_1" id="MOTILITY_RS_0_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_0" id="MOTILITY_RS_0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RS_0_1" id="MOTILITY_RS_0_1">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divMiddleRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RR_4" id="MOTILITY_RR_4" value="<?php echo attr($MOTILITY_RR); ?>">
                                                        <?php if ($MOTILITY_RR >'3') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RR_3" id="MOTILITY_RR_3">
                                                        <?php if ($MOTILITY_RR >'2') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RR_2" id="MOTILITY_RR_2">
                                                        <?php if ($MOTILITY_RR >'1') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RR_1" id="MOTILITY_RR_1">
                                                        <?php if ($MOTILITY_RR >'0') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RR_0" id="MOTILITY_RR_0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_R0" id="MOTILITY_R0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RL_0" id="MOTILITY_RL_0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RL_1" id="MOTILITY_RL_1">
                                                        <?php if ($MOTILITY_RL >'0') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RL_2" id="MOTILITY_RL_2">
                                                        <?php if ($MOTILITY_RL >'1') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RL_3" id="MOTILITY_RL_3">
                                                        <?php if ($MOTILITY_RL >'2') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RL_4" id="MOTILITY_RL_4" value="<?php echo attr($MOTILITY_RL); ?>">
                                                        <?php if ($MOTILITY_RL >'3') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_0_1" id="MOTILITY_RI_0_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_RI_0" name="MOTILITY_RI_0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_0_2" id="MOTILITY_RI_0_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_1_1" id="MOTILITY_RI_1_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_RI_1" name="MOTILITY_RI_1">
                                                        <?php if ($MOTILITY_RI >'0') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RI_1_2" id="MOTILITY_RI_1_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_2_1" id="MOTILITY_RI_2_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_RI_2" name="MOTILITY_RI_2">
                                                        <?php if ($MOTILITY_RI >'1') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RI_2_2" id="MOTILITY_RI_2_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_3_5" id="MOTILITY_RI_3_5">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_3_3" id="MOTILITY_RI_3_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_3_1" id="MOTILITY_RI_3_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_RI_3" name="MOTILITY_RI_3">
                                                        <?php if ($MOTILITY_RI >'2') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RI_3_2" id="MOTILITY_RI_3_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_3_4" id="MOTILITY_RI_3_4">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_3_6" id="MOTILITY_RI_3_6">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_4_5" id="MOTILITY_RI_4_5">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_4_3" id="MOTILITY_RI_4_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_4_1" id="MOTILITY_RI_4_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_RI_4" name="MOTILITY_RI_4" value="<?php echo attr($MOTILITY_RI); ?>">
                                                        <?php if ($MOTILITY_RI >'3') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_RI_4_2" id="MOTILITY_RI_4_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_4_4" id="MOTILITY_RI_4_4">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RI_4_6" id="MOTILITY_RI_4_6">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>   
                                                    <div class="divRow"><div class="divCell">&nbsp;</div>
                                                    </div>
                                                    </div> 
                                                    <div class="divTable" style="float:right;background: url(../../forms/<?php echo $form_folder; ?>/images/eom.bmp) no-repeat center center;background-size: 90% 90%;height:0.77in;width:0.71in;padding:1px;margin:6 2 0 0;">
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_4_3" id="MOTILITY_LS_4_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_4_1" id="MOTILITY_LS_4_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_4" id="MOTILITY_LS_4" value="<?php echo attr($MOTILITY_LS); ?>">
                                                        <?php if ($MOTILITY_LS >'3') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LS_4_2" id="MOTILITY_LS_4_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_4_4" id="MOTILITY_LS_4_4">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_3_1" id="MOTILITY_LS_3_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_3" id="MOTILITY_LS_3">
                                                        <?php if ($MOTILITY_LS >'2') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LS_3_2" id="MOTILITY_LS_3_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_2_1" id="MOTILITY_LS_2_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_2" id="MOTILITY_LS_2">
                                                        <?php if ($MOTILITY_LS >'1') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LS_2_2" id="MOTILITY_LS_2_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_1_1" id="MOTILITY_LS_1_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_1" id="MOTILITY_LS_1">
                                                        <?php if ($MOTILITY_LS >'0') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LS_1_2" id="MOTILITY_LS_1_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_0_1" id="MOTILITY_LS_0_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_0" id="MOTILITY_LS_0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LS_0_1" id="MOTILITY_LS_0_1">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divMiddleRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LR_4" id="MOTILITY_LR_4" value="<?php echo attr($MOTILITY_LR); ?>">
                                                        <?php if ($MOTILITY_LR >'3') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LR_3" id="MOTILITY_LR_3">
                                                        <?php if ($MOTILITY_LR >'2') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LR_2" id="MOTILITY_LR_2">
                                                        <?php if ($MOTILITY_LR >'1') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LR_1" id="MOTILITY_LR_1">
                                                        <?php if ($MOTILITY_LR >'0') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LR_0" id="MOTILITY_LR_0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_L0" id="MOTILITY_L0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LL_0" id="MOTILITY_LL_0">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LL_1" id="MOTILITY_LL_1">
                                                        <?php if ($MOTILITY_LL >'0') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LL_2" id="MOTILITY_LL_2">
                                                        <?php if ($MOTILITY_LL >'1') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LL_3" id="MOTILITY_LL_3">
                                                        <?php if ($MOTILITY_LL >'2') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LL_4" id="MOTILITY_LL_4" value="<?php echo attr($MOTILITY_LL); ?>">
                                                        <?php if ($MOTILITY_LL >'3') echo "<i class='fa fa-minus rotate-left'></i>"; ?></div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LR_4_1" id="MOTILITY_LR_4_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LR_3_1" id="MOTILITY_LR_3_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LR_2_1" id="MOTILITY_LR_2_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RO_I_1" id="MOTILITY_RO_I_1">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_LI_0" name="MOTILITY_LI_0">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LO_I_1" id="MOTILITY_LO_I_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LL_2_2" id="MOTILITY_LL_2_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LL_3_2" id="MOTILITY_LL_3_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LL_4_2" id="MOTILITY_LL_4_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LR_4_3" id="MOTILITY_LR_4_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LR_3_3" id="MOTILITY_LR_3_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RO_I_2" id="MOTILITY_RO_I_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_LI_1" name="MOTILITY_LI_1">
                                                        <?php if ($MOTILITY_LI >'0') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LO_I_2" id="MOTILITY_LO_I_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LL_3_4" id="MOTILITY_LL_3_4">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LL_4_4" id="MOTILITY_LL_4_4">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell" name="MOTILITY_RO_I_3_1" id="MOTILITY_RO_I_3_1">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_RO_I_3" id="MOTILITY_RO_I_3">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_2_1" id="MOTILITY_LI_2_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_LI_2" name="MOTILITY_LI_2">
                                                        <?php if ($MOTILITY_LI >'1') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LI_2_2" id="MOTILITY_LI_2_2">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LO_I_2" id="MOTILITY_RO_I_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LO_I_3_1" id="MOTILITY_LO_I_3_1">&nbsp;</div>
                                                      </div>
                                                    <div class="divRow">
                                                      <div class="divCell" name="MOTILITY_LO_I_3" id="MOTILITY_RO_I_3">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_3_5" id="MOTILITY_LI_3_5">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_3_3" id="MOTILITY_LI_3_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_3_1" id="MOTILITY_LI_3_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_LI_3" name="MOTILITY_LI_3">
                                                        <?php if ($MOTILITY_LI >'2') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LI_3_2" id="MOTILITY_LI_3_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_3_4" id="MOTILITY_LI_3_4">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_3_6" id="MOTILITY_LI_3_6">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LO_I_3" id="MOTILITY_LO_I_3">&nbsp;</div>
                                                      
                                                    </div>
                                                    <div class="divRow">
                                                      <div class="divCell" name="MOTILITY_RO_I_4" id="MOTILITY_RO_I_4">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_4_5" id="MOTILITY_LI_4_5">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_4_3" id="MOTILITY_LI_4_3">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_4_1" id="MOTILITY_LI_4_1">&nbsp;</div>
                                                      <div class="divCell" id="MOTILITY_LI_4" name="MOTILITY_LI_4"  value="<?php echo attr($MOTILITY_LI); ?>">
                                                        <?php if ($MOTILITY_LI >'3') echo "<i class='fa fa-minus'></i>"; ?></div>
                                                      <div class="divCell" name="MOTILITY_LI_4_2" id="MOTILITY_LI_4_2">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_4_4" id="MOTILITY_LI_4_4">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LI_4_6" id="MOTILITY_LI_4_6">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell">&nbsp;</div>
                                                      <div class="divCell" name="MOTILITY_LO_I_4" id="MOTILITY_LO_I_4">&nbsp;</div>
                                                    </div>   
                                                    <div class="divRow"><div class="divCell">&nbsp;</div>
                                                    </div>
                                                    </div> 
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td > 
                                    <table class="borderShadow">
                                        <tr>
                                          <td class="report_text right title"  style="text-decoration:underline;"><?php echo xlt('Right Eye'); ?></td>
                                          <td></td>
                                          <td class="report_text title"  style="text-align:left;text-decoration:underline;"><?php echo xlt('Left Eye'); ?></td>
                                        </tr>
                                        <tr>
                                              <td class="report_text right"><?php echo  $ODCOLOR; ?></td>
                                              <td class="middle"><?php echo xlt('Color Vision'); ?></td>
                                              <td class="report_text"><?php echo  $OSCOLOR; ?></td>
                                        </tr>
                                        <tr>
                                              <td class="report_text right" style="white-space: nowrap;font-size:0.9em;">
                                                  <?php echo attr($ODREDDESAT); ?>
                                              </td>
                                              <td class="middle" style="white-space: nowrap;font-size:0.9em;">
                                                  <span title="Variation in red color discrimination between the eyes (eg. OD=100, OS=75)"><?php echo xlt('Red Desaturation'); ?></span>
                                              </td>
                                              <td class="report_text">
                                               <?php echo attr($OSREDDESAT); ?>
                                              </td>
                                              
                                        </tr>
                                        <tr>
                                              <td class="report_text right" style="white-space: nowrap;font-size:0.9em;">
                                                  <?php echo attr($ODCOINS); ?>
                                              </td>
                                              <td class="middle">
                                                  <span title="<?php echo xlt('Variation in white (muscle) light brightness discrimination between the eyes (eg. OD=$1.00, OS=$0.75)'); ?>"><?php echo xlt('Coins'); ?>:</span>
                                              </td>
                                              <td class="report_text">
                                                  <?php echo attr($OSCOINS); ?>
                                              </td>
                                              
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <div style="clear:both;width:850px;left:0px;text-align:center;background-color:#fff;">    
                            <div style="float:left;left:0px;top:0px;width:4in;text-align:left;margin:20 auto;">

                                <table class="borderShadow"> 
                                        <tr style="text-align:left;height:26px;vertical-align:middle;width:180px;">
                                              <td>
                                                  <span id="ACTTRIGGER" name="ACTTRIGGER" style="text-decoration:underline;padding-left:2px;"><?php echo xlt('Alternate Cover Test'); ?>:</span>
                                              </td>
                                              <td>
                                                  <span id="ACTNORMAL_CHECK" name="ACTNORMAL_CHECK">
                                                  <label for="ACT" class="input-helper input-helper--checkbox"><?php echo xlt('Ortho'); ?></label>
                                                  <input disabled type="checkbox" name="ACT" id="ACT" <?php if ($ACT =='on') echo "checked='checked'"; ?> /></span>
                                              </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:center;"> <!-- scDIST -->
                                                <div class="ACT_TEXT" style="position:relative;z-index:1;">
                                                    <table cellpadding="0" style="position:relative;text-align:center;font-size:0.9em;margin: 7 5 10 5;border-collapse: separate;">
                                                        <tr>
                                                          <td id="ACT_tab_SCDIST" name="ACT_tab_SCDIST" class="ACT_deselected"> <?php echo xlt('sc Distance'); ?> </td>
                                                          </tr>
                                                        <tr>
                                                            <td colspan="4" style="text-align:center;font-size:0.9em;">
                                                            <div id="ACT_SCDIST" name="ACT_SCDIST" class="ACT_box">
                                                              <br />
                                                              <table> 
                                                                      <tr> 
                                                                          <td style="text-align:center;"><?php echo xlt('R'); ?></td>   
                                                                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                          <textarea id="ACT1SCDIST" name="ACT1SCDIST" class="ACT"><?php echo text($ACT1SCDIST); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                          <textarea id="ACT2SCDIST"  name="ACT2SCDIST"class="ACT"><?php echo text($ACT2SCDIST); ?></textarea></td>
                                                                          <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                          <textarea id="ACT3SCDIST"  name="ACT3SCDIST" class="ACT"><?php echo text($ACT3SCDIST); ?></textarea></td>
                                                                          <td style="text-align:center;"><?php echo xlt('L'); ?></td> 
                                                                      </tr>
                                                                      <tr>    
                                                                          <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                          <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                          <textarea id="ACT4SCDIST" name="ACT4SCDIST" class="ACT"><?php echo text($ACT4SCDIST); ?></textarea></td>
                                                                          <td style="border:1pt solid black;text-align:center;">
                                                                          <textarea id="ACTPRIMSCDIST" name="ACTPRIMSCDIST" class="ACT"><?php echo text($ACTPRIMSCDIST); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                          <textarea id="ACT6SCDIST" name="ACT6SCDIST" class="ACT"><?php echo text($ACT6SCDIST); ?></textarea></td>
                                                                          <td><i class="fa fa-share rotate-right"></i></td> 
                                                                      </tr> 
                                                                      <tr> 
                                                                          <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                              <textarea id="ACTRTILTSCDIST" name="ACTRTILTSCDIST" class="ACT"><?php echo text($ACTRTILTSCDIST); ?></textarea></td>
                                                                          <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                              <textarea id="ACT7SCDIST" name="ACT7SCDIST" class="ACT"><?php echo text($ACT7SCDIST); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                              <textarea id="ACT8SCDIST" name="ACT8SCDIST" class="ACT"><?php echo text($ACT8SCDIST); ?></textarea></td>
                                                                          <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                              <textarea id="ACT9SCDIST" name="ACT9SCDIST" class="ACT"><?php echo text($ACT9SCDIST); ?></textarea></td>
                                                                          <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                              <textarea id="ACTLTILTSCDIST" name="ACTLTILTSCDIST" class="ACT"><?php echo text($ACTLTILTSCDIST); ?></textarea>
                                                                          </td>
                                                                      </tr>
                                                              </table>
                                                            </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                            <td style="text-align:center;"> <!-- ccDIST -->
                                                <div class="ACT_TEXT" style="position:relative;z-index:1;margin auto;">
                                                    <table cellpadding="0" style="position:relative;text-align:center;font-size:0.9em;margin: 7 5 10 5;border-collapse: separate;">
                                                        <tr>
                                                            <td class="ACT_deselected"> <?php echo xlt('cc Distance'); ?> </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="4" style="text-align:center;font-size:0.8em;">
                                                            <div id="ACT_CCDIST" name="ACT_CCDIST" class="ACT_box">
                                                                <br />
                                                                <table> 
                                                                    <tr> 
                                                                          <td style="text-align:center;"><?php echo xlt('R'); ?></td>   
                                                                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                          <textarea id="ACT1CCDIST" name="ACT1CCDIST" class="ACT"><?php echo text($ACT1CCDIST); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                          <textarea id="ACT2CCDIST"  name="ACT2CCDIST"class="ACT"><?php echo text($ACT2CCDIST); ?></textarea></td>
                                                                          <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                          <textarea id="ACT3CCDIST"  name="ACT3CCDIST" class="ACT"><?php echo text($ACT3CCDIST); ?></textarea></td>
                                                                          <td style="text-align:center;"><?php echo xlt('L'); ?></td> 
                                                                    </tr>
                                                                    <tr>    
                                                                        <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                        <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                        <textarea id="ACT4CCDIST" name="ACT4CCDIST" class="ACT"><?php echo text($ACT4CCDIST); ?></textarea></td>
                                                                        <td style="border:1pt solid black;text-align:center;">
                                                                        <textarea id="ACTPRIMCCDIST" name="ACTPRIMCCDIST" class="ACT"><?php echo text($ACTPRIMCCDIST); ?></textarea></td>
                                                                        <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                        <textarea id="ACT6CCDIST" name="ACT6CCDIST" class="ACT"><?php echo text($ACT6CCDIST); ?></textarea></td>
                                                                        <td><i class="fa fa-share rotate-right"></i></td> 
                                                                    </tr> 
                                                                    <tr> 
                                                                        <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                        <textarea id="ACTRTILTCCDIST" name="ACTRTILTCCDIST" class="ACT"><?php echo text($ACTRTILTCCDIST); ?></textarea></td>
                                                                        <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                        <textarea id="ACT7CCDIST" name="ACT7CCDIST" class="ACT"><?php echo text($ACT7CCDIST); ?></textarea></td>
                                                                        <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                        <textarea id="ACT8CCDIST" name="ACT8CCDIST" class="ACT"><?php echo text($ACT8CCDIST); ?></textarea></td>
                                                                        <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                        <textarea id="ACT9CCDIST" name="ACT9CCDIST" class="ACT"><?php echo text($ACT9CCDIST); ?></textarea></td>
                                                                        <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                        <textarea id="ACTLTILTCCDIST" name="ACTLTILTCCDIST" class="ACT"><?php echo text($ACTLTILTCCDIST); ?></textarea>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:center;"> <!-- scNEAR -->
                                                <div class="ACT_TEXT" style="position:relative;z-index:1;margin auto;">
                                                    <table cellpadding="0" style="position:relative;text-align:center;font-size:0.9em;margin: 7 5 10 5;border-collapse: separate;">
                                                        <tr>
                                                            <td class="ACT_deselected"> <?php echo xlt('sc Near'); ?> </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="4" style="text-align:center;font-size:0.8em;">
                                                                <div class="ACT_box">
                                                                    <br />
                                                                    <table> 
                                                                        <tr> 
                                                                          <td style="text-align:center;"><?php echo xlt('R'); ?></td>    
                                                                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                          <textarea id="ACT1SCNEAR" name="ACT1SCNEAR" class="ACT"><?php echo text($ACT1SCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                          <textarea id="ACT2SCNEAR"  name="ACT2SCNEAR"class="ACT"><?php echo text($ACT2SCNEAR); ?></textarea></td>
                                                                          <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                          <textarea id="ACT3SCNEAR"  name="ACT3SCNEAR" class="ACT"><?php echo text($ACT3SCNEAR); ?></textarea></td>
                                                                          <td style="text-align:center;"><?php echo xlt('L'); ?></td> 
                                                                        </tr>
                                                                        <tr>    
                                                                          <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                          <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                          <textarea id="ACT4SCNEAR" name="ACT4SCNEAR" class="ACT"><?php echo text($ACT4SCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;text-align:center;">
                                                                          <textarea id="ACTPRIMSCNEAR" name="ACTPRIMSCNEAR" class="ACT"><?php echo text($ACTPRIMSCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                          <textarea id="ACT6SCNEAR" name="ACT6SCNEAR" class="ACT"><?php echo text($ACT6SCNEAR); ?></textarea></td>
                                                                          <td><i class="fa fa-share rotate-right"></i></td> 
                                                                        </tr> 
                                                                        <tr> 
                                                                          <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                              <textarea id="ACTRTILTSCNEAR" name="ACTRTILTSCNEAR" class="ACT"><?php echo text($ACTRTILTSCNEAR); ?></textarea></td>
                                                                          <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                              <textarea id="ACT7SCNEAR" name="ACT7SCNEAR" class="ACT"><?php echo text($ACT7SCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                              <textarea id="ACT8SCNEAR" name="ACT8SCNEAR" class="ACT"><?php echo text($ACT8SCNEAR); ?></textarea></td>
                                                                          <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                              <textarea id="ACT9SCNEAR" name="ACT9SCNEAR" class="ACT"><?php echo text($ACT9SCNEAR); ?></textarea></td>
                                                                          <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                              <textarea id="ACTLTILTSCNEAR" name="ACTLTILTSCNEAR" class="ACT"><?php echo text($ACTLTILTSCNEAR); ?></textarea>
                                                                          </td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                            <td style="text-align:center;"> <!-- ccNEAR -->
                                                <div class="ACT_TEXT" style="position:relative;z-index:1;margin auto;">
                                                    <table cellpadding="0" style="position:relative;text-align:center;font-size:0.9em;margin: 7 5 10 5;border-collapse: separate;">
                                                        <tr>
                                                            <td class="ACT_deselected"> <?php echo xlt('cc Near'); ?> </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="4" style="text-align:center;font-size:0.8em;">
                                                                <div class="ACT_box">
                                                                    <br />
                                                                    <table> 
                                                                      <tr> 
                                                                          <td style="text-align:center;"><?php echo xlt('R'); ?></td>    
                                                                          <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                                          <textarea id="ACT1CCNEAR" name="ACT1CCNEAR" class="ACT"><?php echo text($ACT1CCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                                          <textarea id="ACT2CCNEAR"  name="ACT2CCNEAR"class="ACT"><?php echo text($ACT2CCNEAR); ?></textarea></td>
                                                                          <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                                          <textarea id="ACT3CCNEAR"  name="ACT3CCNEAR" class="ACT"><?php echo text($ACT3CCNEAR); ?></textarea></td>
                                                                          <td style="text-align:center;"><?php echo xlt('L'); ?></td>
                                                                      </tr>
                                                                      <tr>    
                                                                          <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                                                          <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                                          <textarea id="ACT4CCNEAR" name="ACT4CCNEAR" class="ACT"><?php echo text($ACT4CCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;text-align:center;">
                                                                          <textarea id="ACTPRIMCCNEAR" name="ACTPRIMCCNEAR" class="ACT"><?php echo text($ACTPRIMCCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                                          <textarea id="ACT6CCNEAR" name="ACT6CCNEAR" class="ACT"><?php echo text($ACT6CCNEAR); ?></textarea></td><td><i class="fa fa-share rotate-right"></i></td> 
                                                                      </tr> 
                                                                      <tr> 
                                                                          <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                                              <textarea id="ACTRTILTCCNEAR" name="ACTRTILTCCNEAR" class="ACT"><?php echo text($ACTRTILTCCNEAR); ?></textarea></td>
                                                                          <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                                              <textarea id="ACT7CCNEAR" name="ACT7CCNEAR" class="ACT"><?php echo text($ACT7CCNEAR); ?></textarea></td>
                                                                          <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                                              <textarea id="ACT8CCNEAR" name="ACT8CCNEAR" class="ACT"><?php echo text($ACT8CCNEAR); ?></textarea></td>
                                                                          <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                                              <textarea id="ACT9CCNEAR" name="ACT9CCNEAR" class="ACT"><?php echo text($ACT9CCNEAR); ?></textarea></td>
                                                                          <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                                              <textarea id="ACTLTILTCCNEAR" name="ACTLTILTCCNEAR" class="ACT"><?php echo text($ACTLTILTCCNEAR); ?></textarea>
                                                                          </td>
                                                                      </tr>
                                                                    </table>
                                                 
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                </table>
                            </div>
                        </div>
                        <table>
                            <tr>
                                 <td colspan="2"  style="font-size:0.7em;">
                                    <b><?php echo xlt('Comments'); ?>:</b><br />
                                    <span style="width:4.0in;height:3.0em;">
                                        <?php echo text($NEURO_COMMENTS); ?>
                                    </span>
                                    <br /><br />
                                </td>
                            </tr>
                        </table>

                        
                    </div>
                    <?php 
                        display_draw_image ("NEURO"); 
                    ?>                  
                </div>
                <!-- end of NEURO exam -->

                <!-- start of IMPPLAN exam -->
                <div style="clear:both;width:850px;left:0px;text-align:center;background-color:#fff;">    
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
                            display_draw_image ("IMPPLAN"); 
                        ?>
                   
                </div>
                <!-- end of IMPPLAN exam -->
                

                
            </div>
        </div>


        <?php
      //end central_wrapper
              
    }
function display_draw_image($zone){
    global $pid;
    global $encounter;
    global $form_folder;
    $side = "OU";

    $file_location = $GLOBALS["OE_SITES_BASE"]."/".$_SESSION['site_id']."/documents/".$pid."/".$form_folder."/".$encounter."/".$side."_".$zone."_VIEW.png";
            $sql = "SELECT * from documents where url='file://".$file_location."'";
            $doc = sqlQuery($sql);
            // random to not pull from cache.
            if (file_exists($file_location) && ($doc['id'] > '0')) {
                $filetoshow = $GLOBALS['web_root']."/controller.php?document&retrieve&patient_id=$pid&document_id=".$doc['id']."&as_file=false&blahblah=".rand();
            } else {
                //base image. 
                $filetoshow = "../../forms/".$form_folder."/images/".$side."_".$zone."_BASE.png"; 
            }
            echo '<div style="float:left;margin:70 10 auto 10;border:2pt solid grey;">';
            echo "<img src='".$filetoshow."' style='padding: 0px 0px 0px 5px;'>
            </div>";

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
