<?php
/** 
 * openemr/interface/menu/menu_globals.php
 * 
 * This is a MAYBE file, called from the previously used and soon to be repurposed
 * function html_header_show found in library/translation.inc.php?
 * It might insert a jquery/bootstrap based menu header at the top of the page.
 * Logic will be included to:
 *   decide if the menu should show at all, ie. it shouldn't if it is part of the 
 *      base openEMR
 *   change what the menu will show based on what is called for
 *
 * but hey we are just working this out...  For now we just "return;"
 * 
 * Copyright (C) 2015 Raymond Magauran <magauran@MedFetch.com> 
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
return;
//echo "YOOOOOOOO";

//function make_my_menu()
//Don't show the menu if the are using the framed version
/*
if ((!preg_match('#load_form.php#', $_SERVER['HTTP_REFERER'] )) || 
    (!preg_match('#left_nav.php#', $_SERVER['HTTP_REFERER'] )) && 
    (($_GET['menu']=='1')||(preg_match('#menu=1#', $_SERVER['HTTP_REFER'] ))||
    ($_SESSION['menu']=='1')))) {
    //echo "<html><head>";

    $_SESSION['menu'] = '1';
    $head = menu_head();
    echo $head;
    echo "</head><body>";
    menu_overhaul_top();
    echo "</body>";
}
*/
function menu_head() {
    $header ='<!-- jQuery library -->
    <script src="'.$GLOBALS['webroot'].'/library/js/jquery.min.js"></script>
    <!-- Latest compiled JavaScript -->
    <script src="'.$GLOBALS['webroot'].'/library/js/bootstrap.min.js"></script>  
      <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!-- WARNING: Respond.js doesn\'t work if you view the page via file:// -->
      <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
      <![endif]-->
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

    <script language="JavaScript"> ';   
    //$header .= require_once("$srcdir/restoreSession.php"); 
    $header .= '</script>
      <link rel="stylesheet" href="'.$GLOBALS['webroot'].'/library/css/pure-min.css">
      <link rel="stylesheet" href="'.$GLOBALS['webroot'].'/library/css/bootstrap-3-2-0.min.css">
      <link rel="stylesheet" href="'.$GLOBALS['webroot'].'/interface/forms/eye_mag/css/bootstrap-responsive.min.css">
      <link rel="stylesheet" href="'.$GLOBALS['webroot'].'/interface/forms/eye_mag/style.css" type="text/css">    
      <link rel="stylesheet" href="'.$GLOBALS['css_header'].'" type="text/css">
      <link rel="stylesheet" href="'.$GLOBALS['webroot'].'/library/css/font-awesome-4.2.0/css/font-awesome.css">
     ';
    return $header;
}

function menu_overhaul_top($pid,$encounter,$title="Eye Exam") {
    global $form_folder;
    global $prov_data;
    global $encounter;
    global $form_id;
    global $display;

    $providerNAME = $prov_data['fname']." ".$prov_data['lname'];

    if ($_REQUEST['display'] == "fullscreen") { $fullscreen_disable = 'class="disabled"'; } else { $frame_disabled ='class="disabled"'; }

    //? ><div id="wrapper" style="font-size: 1.4em;">
    ?> 
       <!-- Navigation -->
    <nav class="navbar-fixed-top navbar-custom navbar-bright navbar-inner" role="banner" role="navigation" style="margin-bottom: 0;z-index:1999999;font-size: 1.4em;">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="container-fluid" style="margin-top:0px;padding:2px;">
            <div class="navbar-header brand" style="color:black;">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#oer-navbar-collapse-1">
                    <span class="sr-only"><?php echo xlt("Toggle navigation"); ?></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                &nbsp;<img src="/openemr/sites/default/images/login_logo.gif" class="little_image">
                Eye Exam
            </div>
            <div class="navbar-collapse collapse" id="oer-navbar-collapse-1">
                <ul class="navbar-nav">
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" id="menu_dropdown_file" role="button" aria-expanded="true"><?php echo xlt("File"); ?> </a>
                        <ul class="dropdown-menu" role="menu">
                            <li id="menu_PREFERENCES" name="menu_PREFERENCES" <?php echo $fullscreen_disabled; ?>><a id="BUTTON_PREFERENCES_menu" target="RTop" href="/openemr/interface/super/edit_globals.php">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                            <?php echo xlt("Preferences"); ?></a></li>
                            <li id="menu_PRINT_narrative" name="menu_PRINT_report"><a id="BUTTON_PRINT_report" target="_new" href="/openemr/interface/patient_file/report/custom_report.php?printable=1&pdf=0&<?php echo $form_folder."_".$form_id."=".$encounter; ?>"><?php echo xlt("Print Report"); ?></a></li>
                            <li id="menu_PRINT_narrative_2" name="menu_PRINT_report_2"><a id="BUTTON_PRINT_report_2" target="_new" href="/openemr/interface/patient_file/report/custom_report.php?printable=1&pdf=1&<?php echo $form_folder."_".$form_id."=".$encounter; ?>"><?php echo xlt("Print PDF"); ?></a></li>
                            <li class="divider"></li>
                            <li id="menu_HPI" name="menu_HPI" <?php echo $frame_disable; ?>><a href="#" onclick='window.close();'><?php echo xlt("Quit"); ?></a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" id="menu_dropdown_edit" role="button" aria-expanded="true"><?php echo xlt("Edit"); ?> </a>
                        <ul class="dropdown-menu" role="menu">
                            <li id="menu_Undo" name="menu_Undo"> <a  id="BUTTON_Undo_menu" href="#"> <?php echo xlt("Undo"); ?> <span class="menu_icon">Ctl-Z</span></a></li>
                            <li id="menu_Redo" name="menu_Redo"> <a  id="BUTTON_Redo_menu" href="#"> <?php echo xlt("Redo"); ?> <span class="menu_icon">Ctl-Shift-Z</span></a></li>
                        </ul>
                    </li> 
                   
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" id="menu_dropdown_view" role="button" aria-expanded="true"><?php echo xlt("View"); ?> </a>
                        <ul class="dropdown-menu" role="menu">
                            <li id="menu_TEXT" name="menu_TEXT" class="active"><a><?php echo xlt("Text"); ?><span class="menu_icon">Ctl-T</span></a></li>
                            <li id="menu_DRAW" name="menu_DRAW"><a id="BUTTON_DRAW_menu" name="BUTTON_DRAW_menu"><?php echo xlt("Draw"); ?><span class="menu_icon">Ctl-D</span></a></li>
                            <li id="menu_QP" name="menu_QP"><a id="BUTTON_QP_menu" name="BUTTON_QP_menu"><?php echo xlt("Quick Picks"); ?><span class="menu_icon">Ctl-B</span></a></li>
                            <li id="menu_PRIORS" name="menu_PRIORS"><a><?php echo xlt("Prior Visits"); ?><span class="menu_icon">Ctl-P</span></a></li>
                            <li id="menu_KB" name="menu_KB"><a><?php echo xlt("Shorthand"); ?><span class="menu_icon">Ctl-K</span></a></li>
                            <li class="divider"></li>
                            <li ><a onclick='$(window).scrollTop( $("#HPI_anchor").offset().top -55);'><?php echo xlt("HPI"); ?></a></li>
                            <li id="menu_PMH" name="menu_PMH" ><a><?php echo xlt("PMH"); ?></a></li>
                            <li id="menu_EXT" name="menu_EXT" ><a><?php echo xlt("External"); ?></a></li>
                            <li id="menu_ANTSEG" name="menu_ANTSEG" ><a><?php echo xlt("Anterior Segment"); ?></a></li>
                            <li id="menu_POSTSEG" name="menu_POSTSEG" ><a><?php echo xlt("Posterior Segment"); ?></a></li>
                            <li id="menu_NEURO" name="menu_NEURO" ><a><?php echo xlt("Neuro"); ?></a></li>
                            <li class="divider"></li>
                            <li id="menu_Right_Panel" name="menu_Right_Panel"><a><?php echo xlt("PMSFH Panel"); ?><span class="menu_icon"><i class="fa fa-list" ></i></span></a></li>
                            
                            <?php 
                            /*
                            // This only shows up in fullscreen currently so hide it.
                            // If the decision is made to show this is framed openEMR, then display it 
                            */
                            if ($display !== "fullscreen") { ?>
                            <li class="divider"></li>
                            <li id="menu_fullscreen" name="menu_fullscreen" <?php echo $fullscreen; ?>>
                                <a onclick="top.restoreSession();openNewForm('<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/encounter/load_form.php?formname=fee_sheet');dopopup('<?php echo $_SERVER['REQUEST_URI']. '&display=fullscreen&encounter='.$encounter; ?>');" class="">Fullscreen</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li> 
                    <li class="dropdown">
                        <a class="dropdown-toggle"  class="disabled" role="button" id="menu_dropdown_patients" data-toggle="dropdown"><?php echo xlt("Patients"); ?> </a>
                        <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">
                          <li role="presentation"><a role="menuitem" tabindex="-1" target="RTop" href="/openemr/interface/main/finder/dynamic_finder.php">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                            <?php echo xlt("Patients"); ?></a></li>
                          <li role="presentation"><a tabindex="-1" target="RTop" href="/openemr/interface/new/new.php">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                            <?php echo xlt("New/Search"); ?></a> </li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" target="RTop" href="/openemr/interface/patient_file/summary/demographics.php">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                            <?php echo xlt("Summary"); ?></a></li>
                          <!--    <li role="presentation" class="divider"></li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" href="#"><?php echo xlt("Create Visit"); ?></a></span></li>
                          <li class="active"><a role="menuitem" id="BUTTON_DRAW_menu" tabindex="-1" href="/openemr/interface/patient_file/encounter/forms.php">  <?php echo xlt("Current"); ?></a></li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" href="/openemr/interface/patient_file/history/encounters.php"><?php echo xlt("Visit History"); ?></a></li>
                          --> 
                          <li role="presentation" class="divider"></li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" target="RTop" href="/openemr/interface/patient_file/transaction/record_request.php">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                            <?php echo xlt("Record Request"); ?></a></li>
                          <li role="presentation" class="divider"></li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" target="RTop" href="/openemr/interface/patient_file/ccr_import.php">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                            <?php echo xlt("Upload Item"); ?></a></li>
                          <li role="presentation" ><a role="menuitem" tabindex="-1" target="RTop" href="/openemr/interface/patient_file/ccr_pending_approval.php">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                            <?php echo xlt("Pending Approval"); ?></a></li>
                        </ul>
                    </li>
                    <!--
                    <li class="dropdown">
                        <a class="dropdown-toggle" role="button" id="menu_dropdown_clinical" data-toggle="dropdown"><?php echo xlt("Encounter"); ?></a>
                        <?php
                        /*
                         *  Here we need to incorporate the menu from openEMR too.  What Forms are active for this installation?
                         *  openEMR uses Encounter Summary - Administrative - Clinical.  Think about the menu as a new entity with
                         *  this + new functionaity.  It is OK to keep or consider changing any NAMES when creating the menu.  I assume
                         *  a consensus will develop. 
                        */
                        ?>
                        <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">
                            <li role="presentation" class="disabled"><a role="menuitem" tabindex="-1" href="#"><?php echo xlt("Eye Exam"); ?></a></li>
                            <li role="presentation" class="disabled"><a role="menuitem" tabindex="-1" href="#"><?php echo xlt("Documents"); ?></a></li>
                            <li role="presentation" class="disabled"><a role="menuitem" tabindex="-1" href="#"><?php echo xlt("Imaging"); ?></a></li>
                            <li role="presentation" class="divider"></li>
                            <li role="presentation" class="disabled"><a role="menuitem" tabindex="-1" href="#IOP_CHART"><?php echo xlt("IOP Chart"); ?></a></li>
                        </ul>
                    </li>
                    -->
                    
                   <!-- let's import the original openEMR menu_bar here.  Needs to add restoreSession stuff? -->
                    <?php
                        $reg = Menu_myGetRegistered();
                        if (!empty($reg)) {
                            $StringEcho= '<li class="dropdown">';
                            if ( $encounterLocked === false || !(isset($encounterLocked))) {
                                foreach ($reg as $entry) {
                                    $new_category = trim($entry['category']);
                                    $new_nickname = trim($entry['nickname']);
                                    if ($new_category == '') {$new_category = htmlspecialchars(xl('Miscellaneous'),ENT_QUOTES);}
                                    if ($new_nickname != '') {$nickname = $new_nickname;}
                                    else {$nickname = $entry['name'];}
                                    if ($old_category != $new_category) { //new category, new menu section
                                        $new_category_ = $new_category;
                                        $new_category_ = str_replace(' ','_',$new_category_);
                                        if ($old_category != '') {
                                            $StringEcho.= "
                                                </ul>
                                            </li>
                                            <li class='dropdown'>
                                            ";
                                        }
                                      $StringEcho.= '
                                      <a class="dropdown-toggle" data-toggle="dropdown" 
                                        id="menu_dropdown_'.$new_category_.'" role="button" 
                                        aria-expanded="false">'.$new_category.' </a>
                                        <ul class="dropdown-menu" role="menu">
                                        ';
                                      $old_category = $new_category;
                                    } 
                                    $StringEcho.= "<li>
                                    <a target='RBot' href='".$GLOBALS['webroot']."/interface/patient_file/encounter/load_form.php?formname=" .urlencode($entry['directory'])."'>
                                    <i class='fa fa-angle-double-down' title='". xla('Opens in Bottom frame')."'></i>". 
                                    xl_form_title($nickname) . "</a></li>";
                              }
                          }
                          $StringEcho.= '
                            </ul>
                          </li>
                          ';
                        } else { $StringEcho .= xlt("nada here que pasa?"); }
                        echo $StringEcho;
                    ?>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" 
                           id="menu_dropdown_library" role="button" 
                           aria-expanded="true"><?php echo xlt("Library"); ?> </a>
                        <ul class="dropdown-menu" role="menu">
                            <li role="presentation"><a role="menuitem" tabindex="-1" target="RTop"  
                            href="/openemr/interface/main/calendar/index.php?module=PostCalendar&viewtype=day&func=view&framewidth=1020">
                            <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>&nbsp;<?php echo xlt("Calendar"); ?><span class="menu_icon"><i class="fa fa-calendar"></i>  </span></a></li>
                            <li role="presentation" class="divider"></li>
                            <li role="presentation"><a target="RTop" role="menuitem" tabindex="-1" 
                                href="/openemr/controller.php?document&list&patient_id=<?php echo xla($pid); ?>">
                                <i class="fa fa-angle-double-up" title="<?php echo xla('Opens in Top frame'); ?>"></i>
                                <?php echo xlt("Documents"); ?></a></li>
                          
                                <li><?php echo   $episode .= '<a href="/openemr/interface/forms/'.$form_folder.'/css/AnythingSlider/simple.php?display=i&category_id='.$documents['zones'][$category_value][$j]['id'].'&encounter='.$encounter.'&category_name='.urlencode(xla($category_value)).'"
                            onclick="return dopopup(\'/openemr/interface/forms/'.$form_folder.'/css/AnythingSlider/simple.php?display=i&category_id='.$documents['zones'][$category_value][$j]['id'].'&encounter='.$encounter.'&category_name='.urlencode(xla($category_value)).'\')">
                            Imaging<span class="menu_icon"><img src="/openemr/interface/forms/'.$form_folder.'/images/jpg.png" class="little_image" />'; ?></span></a></li>
                            <li role="presentation" class="divider"></li>
                            <li id="menu_IOP_graph" name="menu_IOP_graph" ><a><?php echo xlt("IOP Graph"); ?></a></li>
                            
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" 
                           id="menu_dropdown_help" role="button" 
                           aria-expanded="true"><?php echo xlt("Help"); ?> </a>
                        <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">
                            <li role="presentation"><a role="menuitem" tabindex="-1" target="_blank" href="/openemr/interface/forms/eye_mag/help.php">
                                <i class="fa fa-help"></i>  <?php echo xlt("Shorthand Help"); ?><span class="menu_icon"><i title="<?php echo xla('Click for Shorthand Help.'); ?>" class="fa fa-info-circle fa-1"></i></span></a>
                            </li>
                        </ul>
                    </li>
                </ul>
                   
                 <ul >
                    
                    <li style="position:absolute;right:150px;"><span id="active_flag" name="active_flag" style="margin-right:15px;color:red;"> Active Chart </span>
                        <span name="active_icon" id="active_icon" style="color:black;"><i class='fa fa-toggle-on'></i></span></li>
                </ul>           
            </div><!-- /.navbar-collapse -->
        </div>
    </nav>
   

    <?php 

        return;
}
?>
