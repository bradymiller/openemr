<?php

/** 
* forms/eye_mag/refractions.php 
* 
* This file outputs the past refractions performed 
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
include_once($GLOBALS["srcdir"]."/api.inc");
include_once("$srcdir/acl.inc");
include_once("$srcdir/forms.inc");
include_once("$srcdir/lists.inc");
include_once("$srcdir/options.inc.php");
include_once("$srcdir/sql.inc");
include_once("$srcdir/formatting.inc.php");

$form_name = "Eye Form";
$form_folder = "eye_mag";
include_once("php/".$form_folder."_functions.php");

$query = "SELECT * FROM patient_data where pid=?";
$pat_data =  sqlQuery($query,array($data['pid']));

$query = "SELECT * FROM users where id = ?";
$prov_data =  sqlQuery($query,array($_SESSION['authUserID']));

$providerID  =  getProviderIdOfEncounter($encounter);
$providerNAME = getProviderName($providerID);
$query = "SELECT * FROM users where id = ?";
$prov_data =  sqlQuery($query,array($providerID));


$query = "SELECT * FROM facility WHERE primary_business_entity='1'";
$practice_data = sqlQuery($query); 

if (!$_REQUEST['pid']) $_REQUEST['pid'] = $_REQUEST['id'];
$query = "SELECT * FROM patient_data where pid=?";
$pat_data =  sqlQuery($query,array($_REQUEST['pid']));

        
$table_name = "form_eye_mag";

formHeader("Rx Vision: ".$prov_data[facility]);

  ?><html>
        <title><?php echo xlt('Rx Dispensed History'); ?></title>
        <head>         
            <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/font-awesome-4.2.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/pure-min.css">
            <link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/bootstrap-3-2-0.min.css">
            <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/css/bootstrap-responsive.min.css">
            <link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/style.css" type="text/css">   
                 <!-- jQuery library -->
            <script src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.min.js"></script>
            <script src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.min.js"></script>
            <!-- Latest compiled JavaScript -->
            <script src="<?php echo $GLOBALS['webroot'] ?>/library/js/bootstrap.min.js"></script>  
              <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
              <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
              <!--[if lt IE 9]>
                  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
                  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
              <![endif]-->
              <!-- have to bring this into library/code base -->
            <script src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui.js"></script>

            
            <style>
                .refraction {
                    top:1in;
                    float:left;
                    min-height:1.0in;
                    border: 1.00pt solid #000000; 
                    padding: 5; 
                    box-shadow: 10px 10px 5px #888888;
                    border-radius: 8px;
                    margin: 5 auto;
                }
                .refraction td {
                    text-align:center;
                    font-size:8pt;
                    padding:2;
                    text-align: text-middle;
                    text-decoration: none;
                }
                table {
                    font-size: 0.8em;
                    padding: 2px;
                    color: black;
                    vertical-align: text-top;
                }

                input[type=text] {
                    text-align: right;
                    width:50px;
                    background-color: white;
                }
                .refraction  b{
                    text-decoration:bold;
                }
                .refraction td.right {
                    text-align: right;
                    text-decoration: none;
                    vertical-align: text-top;
                }
                .refraction td.left {
                    text-align: left;
                    vertical-align: top;
                }

                .right {
                    text-align:right;
                    vertical-align: text-top;
                }
                .left {
                    text-align:left;
                    vertical-align: top;
                }
                .title {
                    font-size: 0.9em;
                    font-weight:normal;
                }
                .nodisplay {
                    display:none;
                }
            </style>
  
        </head>
        <body>
            <?php report_header($pid,"web"); ?>
            
            <div style="margin:5;display:inline-block;text-align:center;width:100%;">

                <table style="display: table; margin: 0 auto; width:75%;" cellpadding="5" cellspacing="5" border=1>
                    <tr>
                        <td><?php echo xlt('Visit Date'); ?></td></tr>
                    <?php 
                    //iterate through all the past visits with refractions in them, say past five.
                    //option to go further back, or show them all?
                    $newcell = "</td><td style='text-align:center;'>";
                    $newline = "</td></tr><tr><td style='text-align:center;'>";
                    $query = "Select * from ".$table_name." where pid=? ORDER by `date` desc";
                    $visits = sqlStatement($query,array($pid));
                    while ($visit = sqlFetchArray($visits))   {   
                        $this_date++;
                        $show_date[$this_date] = "nodisplay";
                        $dated = new DateTime($visit['date']);
                        $dated = $dated->format('Y/m/d');
                        $oeexam_date = text(oeFormatShortDate($dated));
            
                        echo "<tr id='visit_".$this_date."' class='nodisplay' style='height:25px;background-color:#C0C0C0;'><td style='text-align:left;'><b>".$oeexam_date."</b></td>"; ?>
                        <td style='text-align:center;font-weight:600;'><?php echo xlt('Eye'); ?></td>
                        <td style='text-align:center;font-weight:600;'><?php echo xlt('Sphere'); ?></td>
                        <td style='text-align:center;font-weight:600;'><?php echo xlt('Cyl{{cylinder}}'); ?></td>
                        <td style='text-align:center;font-weight:600;'><?php echo xlt('Axis'); ?></td>
                        <td style='text-align:center;font-weight:600;'><?php echo xlt('Acuity'); ?></td>
                        <td style='text-align:center;font-weight:600;'><?php echo xlt('Add'); ?></td>
                        <td></td><td></td>
                    </tr>


                        <?php 
                        $query = "select * from form_eye_mag_wearing where ENCOUNTER=? and FORM_ID=? and PID=? ORDER BY rx_number ASC";
                        $wear = sqlStatement($query,array($visit['encounter'],$visit['form_id'],$visit['pid']));
                        while ($wearing = sqlFetchArray($wear)) {
                            echo $newcell. xlt('OD{{right eye}}');
                            echo $newcell. $wearing['ODSPH'];
                            echo $newcell. $wearing['ODAXIS'];
                            echo $newcell. $wearing['ODCYL'];
                            echo $newcell. $wearing['ODVA'];
                            echo $newcell. $wearing['ODADD'];
                            echo $newcell. $wearing['ODPRISM'];
                            echo $newcell. $wearing['ODMIDADD'];
                            
                            
                            echo $newline;
                            echo $newcell. xlt('OS{{left eye}}');
                            echo $newcell. $wearing['OSSPH'];
                            echo $newcell. $wearing['OSCYL'];
                            echo $newcell. $wearing['OSAXIS'];
                            echo $newcell. $wearing['OSVA'];
                            echo $newcell. $wearing['OSADD'];
                            echo $newcell. $wearing['OSPRISM'];
                            echo $newcell. $wearing['OSMIDADD'];
                            
                            echo $newline;
                            echo "</td><td colspan='8'>". $wearing['COMMENTS']; 
                            echo "</td></tr>";
                            $show_date[$this_date] = '';
                        }
                        if ($visit['ARODSPH']||$visit['AROSSPH']) { 
                            $show_date[$this_date] = '';

                            echo "<tr><td style='text-align:left;'><b>".xlt('Autorefraction')."</b>";
                            echo $newcell. xlt('OD{{right eye}}');
                            echo $newcell. $visit['ARODSPH'];
                            echo $newcell. $visit['ARODCYL'];
                            echo $newcell. $visit['ARODAXIS'];
                            echo $newcell. $visit['ARODVA'];
                            echo $newcell. $visit['ARODADD'];
                            echo $newcell. $visit['ARODPRISM'];
                            echo $newcell. $visit['ARODMIDADD'];
                            
                            echo $newline. $newcell. xlt('OS{{left eye}}');
                            echo $newcell. $visit['AROSSPH'];
                            echo $newcell. $visit['AROSCYL'];
                            echo $newcell. $visit['AROSAXIS'];
                            echo $newcell. $visit['AROSVA'];
                            echo $newcell. $visit['AROSADD'];
                            echo $newcell. $visit['AROSPRISM'];
                            echo $newcell. $visit['AROSMIDADD'];
                           
                            echo "</td></tr>";
                               
                        } 
                        if ($visit['MRODSPH']||$visit['MROSSPH']) {
                            $show_date[$this_date] = '';
                            echo "<tr><td style='text-align:left;'><b>".xlt('Manifest')."</b>";
                            echo $newcell. xlt('OD{{right eye}}');
                            echo $newcell. $visit['MRODSPH'];
                            echo $newcell. $visit['MRODCYL'];
                            echo $newcell. $visit['MRODAXIS'];
                            echo $newcell. $visit['MRODVA'];
                            echo $newcell. $visit['MRODADD'];
                            echo $newcell. $visit['MRODPRISM'];
                            echo $newcell. $visit['MRODMIDADD'];
                            
                            
                            echo $newline. $newcell. xlt('OS{{left eye}}');
                            echo $newcell. $visit['MROSSPH'];
                            echo $newcell. $visit['MROSCYL'];
                            echo $newcell. $visit['MROSAXIS'];
                            echo $newcell. $visit['MROSVA'];
                            echo $newcell. $visit['MROSADD'];
                            echo $newcell. $visit['MROSPRISM'];
                            echo $newcell. $visit['MROSMIDADD'];
                            
                            echo "</td></tr>";

                        } 
                        if ($visit['CRODSPH']||$visit['CROSSPH']) {
                            $show_date[$this_date] = '';
                            echo "<tr><td style='text-align:left;'><b>".xlt('Cycloplegic')."</b>";
                            echo $newcell. xlt('OD{{right eye}}');
                            echo $newcell. $visit['CRODSPH'];
                            echo $newcell. $visit['CRODCYL'];
                            echo $newcell. $visit['CRODAXIS'];
                            echo $newcell. $visit['CRODVA'];
                            echo $newcell. $visit['CRODADD'];
                            echo $newcell. $visit['CRODPRISM'];
                            echo $newcell. $visit['CRODMIDADD'];
                            
                            
                            echo $newline. $newcell. xlt('OS{{left eye}}');
                            echo $newcell. $visit['CROSSPH'];
                            echo $newcell. $visit['CROSCYL'];
                            echo $newcell. $visit['CROSAXIS'];
                            echo $newcell. $visit['CROSVA'];
                            echo $newcell. $visit['CROSADD'];
                            echo $newcell. $visit['CROSPRISM'];
                            echo $newcell. $visit['CROSMIDADD'];
                            
                            echo "</td></tr>";
                        } 
                        if ($visit['CRCOMMENTS']) {
                            $show_date[$this_date] = '';
                            echo $newline."</td><td colspan='8'>". $visit['CRCOMMENTS']; 
                            echo "</td></tr>";
                        }

                        if ($visit['CTLODSPH']||$visit['CTLOSSPH']) {
                            $COMMENTS = $visit['CTL_COMMENTS']; 
                            $CTLMANUFACTUREROD  = getListItemTitle('CTLManufacturer', $visit['CTLMANUFACTUREROD']);
                            $CTLMANUFACTUREROS  = getListItemTitle('CTLManufacturer', $visit['CTLMANUFACTUREROS']);
                            $CTLSUPPLIEROD      = getListItemTitle('CTLManufacturer', $visit['CTLSUPPLIEROD']);
                            $CTLSUPPLIEROS      = getListItemTitle('CTLManufacturer', $visit['CTLSUPPLIEROS']);
                            $CTLBRANDOD         = getListItemTitle('CTLManufacturer', $visit['CTLBRANDOD']);
                            $CTLBRANDOS         = getListItemTitle('CTLManufacturer', $visit['CTLBRANDOS']);

                            echo "<tr><td style='text-align:left;'><b>".xlt('Contact Lens')."</b>";
                            echo $newcell. xlt('OD{{right eye}}');
                            echo $newcell. $visit['CTLODSPH'];
                            echo $newcell. $visit['CTLODCYL'];
                            echo $newcell. $visit['CTLODAXIS'];
                            echo $newcell. $visit['CTLODVA'];
                            echo $newcell. $visit['CTLODADD'];
                            echo $newcell. $visit['CTLODBC'];
                            echo $newcell. $visit['CTLODDIAM'];
                            echo $newcell. $visit['CTLODPRISM'];
                            echo $newline;
                            echo "</td><td colspan='2' style='text-align:middle;'>".$CTLMANUFACTUREROD;
                            echo "</td><td colspan='2' style='text-align:middle;'>".$CTLBRANDOD;
                            echo "</td><td colspan='2' style='text-align:middle;'>".$CTLSUPPLIEROD;
                            
                            echo $newline. $newcell.xlt('OS{{left eye}}');
                            echo $newcell. $visit['CTLOSSPH'];
                            echo $newcell. $visit['CTLOSCYL'];
                            echo $newcell. $visit['CTLOSAXIS'];
                            echo $newcell. $visit['CTLOSVA'];
                            echo $newcell. $visit['CTLOSADD'];
                            echo $newcell. $visit['CTLOSBC'];
                            echo $newcell. $visit['CTLOSDIAM'];
                            echo $newcell. $visit['CTLOSPRISM'];
                            
                            echo $newline;
                            echo "</td><td colspan='2' style='text-align:middle;'>".$CTLMANUFACTUREROS;
                            echo "</td><td colspan='2' style='text-align:middle;'>".$CTLBRANDOS;
                            echo "</td><td colspan='2' style='text-align:middle;'>".$CTLSUPPLIEROS;
                            echo "</td></tr>";
                            if ($visit['CTLCOMMENTS']) {
                            echo $newline."</td><td colspan='8'>". $visit['CTLCOMMENTS']; 
                            echo "</td></tr>";
                            }
                        }
                        $count++;
                    }
                    if (!$count) {
                        echo "<tr><td colspan='2' style='font-size:1.2em;text-align:middle;padding:25px;'>".xlt('There are no refractions on file for this patient')."</td></tr>";
                    }

                    ?>
                </table>
            </div>
        </body>    
        <script>
        $(document).ready(function() {
            <?php 
            for ($i=1;$i <= $this_date; $i++) {
                if ($show_date[$i] != "nodisplay") { 
                    ?>
                    $('#visit_<?php echo $i; ?>').removeClass('nodisplay');
                <?php 
                }
            }
            ?>
        });
        </script>

    </html>
