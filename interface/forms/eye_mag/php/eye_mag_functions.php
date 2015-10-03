<?php
/** 
 * forms/eye_mag/php/eye_mag_functions.php 
 * 
 * Function which extend the eye_mag form
 *   
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
 */


$form_folder = "eye_mag";
require_once($GLOBALS['fileroot'].'/custom/code_types.inc.php');
require_once($GLOBALS['srcdir'].'/options.inc.php');
require_once("$srcdir/formatting.inc.php");
global $PMSFH;

/*
 *  This function returns HTML old record selector widget when needed (4 input values)
 * 
 * @param string $zone options ALL,EXT,ANTSEG,RETINA,NEURO, DRAW_PRIORS_$zone 
 * @param string $visit_date Future functionality to limit result set. UTC DATE Formatted 
 * @param string $pid value = patient id
 * @param string $type options text(default) image 
 * @return string returns the HTML old record/image selector widget for the desired zone and type
 */         
function priors_select($zone,$orig_id,$id_to_show,$pid,$type='text') {
    global $form_folder;
    global $form_name;
    global $visit_date;
    global $priors;
    global $form_id;
    $Form_Name = "Eye Exam"; 
    $output_return ="<span style='right:0.241in;
                                font-size:0.72em;
                                padding:1 0 0 10;
                                margin:0 0 5 0;
                                z-index:10;
                                display: nowrap;' 
                                id='".attr($zone)."_prefix_oldies' 
                                name='".attr($zone)."_prefix_oldies'  
                                class='' >";
    $selected='';
    $current='';
    if (!$priors) {
        $query="select form_encounter.date as encounter_date,form_eye_mag.id as form_id, form_eye_mag.* 
                    from form_eye_mag,forms,form_encounter 
                    where 
                    form_encounter.encounter = forms.encounter and 
                    form_eye_mag.id=forms.form_id and
                    forms.form_name =? and 
                    forms.deleted != '1' and 
                    forms.pid =form_eye_mag.pid and form_eye_mag.pid=? ORDER BY encounter_date DESC";
        $result = sqlStatement($query,array($Form_Name,$pid));
        $counter = sqlNumRows($result);
        //global $current;
        $priors = array();
        if ($counter < 2) return;
        $i="0";
        while ($prior= sqlFetchArray($result))   {   
            // Work towards integrating openemr date convention/global
            $dated = new DateTime($prior['encounter_date']);
            $dated = $dated->format('Y/m/d');
            $oeexam_date = oeFormatShortDate($dated);
            
            $visit_date_local = date_create($prior['encounter_date']);
            $exam_date = date_format($visit_date_local, 'm/d/Y'); 
            $priors[$i] = $prior;
            $selected ='';
            $priors[$i]['exam_date'] = $oeexam_date;
            if ($id_to_show ==$prior['form_id']) {
                $selected = 'selected="selected"';
                $current = $i;
            }
           $output .= "<option value='".attr($prior['id'])."' ".attr($selected).">".text($priors[$i]['exam_date'])."</option>";
           $selected ='';
           $i++;
        }
    } else {
        //priors[] exists, containing the visits data AND the priors[earlier] field at the end, so iterate through all but the last one.
        $visit_count = count($priors)-1;
        for ($i=0; $i< count($priors); $i++) {
            if ($form_id ==$priors[$i]['id']) {
                $selected = 'selected=selected';
                $current = $i;
            } else {
                $selected ='';
            }
            $output .= "<option value='".attr($priors[$i]['id'])."' ".attr($selected).">".text($priors[$i]['exam_date'])."</option>";
        }
    }
    $i--;
    if ($current < $i)  { $earlier = $current + 1;} else { $earlier = $current; }
    if ($current > '0') { $later   = ($current - 1);} else { $later   = "0"; }
    if ($GLOBALS['date_display_format'] == 1)      // mm/dd/yyyy 
    {   $priors[$i]['encounter_date'] = date("m/d/Y", strtotime($priors[$i]['encounter_date']));
        $priors[$earlier]['encounter_date'] = date("m/d/Y", strtotime($priors[$earlier]['encounter_date']));
        $priors[$later]['encounter_date'] = date("m/d/Y", strtotime($priors[$later]['encounter_date']));
        $priors[0]['encounter_date'] = date("m/d/Y", strtotime($priors[0]['encounter_date']));
        $priors[$current]['encounter_date'] = date("m/d/Y", strtotime($priors[$current]['encounter_date']));
    } else {
        $priors[$i]['encounter_date'] = date("d/m/Y", strtotime($priors[$i]['encounter_date']));
        $priors[$earlier]['encounter_date'] = date("d/m/Y", strtotime($priors[$earlier]['encounter_date']));
        $priors[$later]['encounter_date'] = date("d/m/Y", strtotime($priors[$later]['encounter_date']));
        $priors[0]['encounter_date'] = date("d/m/Y", strtotime($priors[0]['encounter_date']));
        $priors[$current]['encounter_date'] = date("d/m/Y", strtotime($priors[$current]['encounter_date']));
    }
    $earlier['PLAN'] = $priors[$earlier]['PLAN'];
    if ($id_to_show != $orig_id) {
        $output_return .= '
                <span title="'.xla($zone).': '.xla("Copy these values into current visit.").'
                '.xla("Updated fields will be purple."). '"

                    id="COPY_'.attr($zone).'"
                    name="COPY_'.attr($zone).'"
                    value="'.attr($id_to_show).'" onclick=\'$("#COPY_SECTION").val("'.attr($zone).'-'.attr($id_to_show).'").trigger("change");\'>
                    <i class="fa fa-paste fa-lg"></i>
                </span>
                &nbsp;&nbsp;';
    }
    $output_return .= '
        <span onclick=\'$("#PRIOR_'.attr($zone).'").val("'.attr($priors[$i][id]).'").trigger("change");\' 
                id="PRIORS_'.attr($zone).'_earliest" 
                name="PRIORS_'.attr($zone).'_earliest" 
                class="fa fa-fast-backward fa-sm PRIORS"
                title="'.attr($zone).': '.attr($priors[$i]['encounter_date']).'">
        </span>
        &nbsp;
        <span onclick=\'$("#PRIOR_'.attr($zone).'").val("'.attr($priors[$earlier][id]).'").trigger("change");\' 
                id="PRIORS_'.attr($zone).'_minus_one" 
                name="PRIORS_'.attr($zone).'_minus_one" 
                class="fa fa-step-backward fa-sm PRIORS"
                title="'.attr($zone).': '.attr($priors[$earlier]['encounter_date']).'">
        </span>&nbsp;&nbsp;
        <select name="PRIOR_'.attr($zone).'" 
                id="PRIOR_'.attr($zone).'" 
                style="padding:0 0;font-size:1.2em;" 
                class="PRIORS">
                '.$output.'
        </select>
                  &nbsp;            
        <span onclick=\'$("#PRIOR_'.attr($zone).'").val("'.attr($priors[$later][id]).'").trigger("change");\'  
                id="PRIORS_'.attr($zone).'_plus_one" 
                name="PRIORS_'.attr($zone).'_plus_one" 
                class="fa  fa-step-forward PRIORS"
                title="'.attr($zone).': '.attr($priors[$later]['encounter_date']).'"> 
        </span>&nbsp;&nbsp;
        <span onclick=\'$("#PRIOR_'.attr($zone).'").val("'.attr($priors[0][id]).'").trigger("change");\'  
                id="PRIORS_'.attr($zone).'_latest" 
                name="PRIORS_'.attr($zone).'_latest" 
                class="fa  fa-fast-forward PRIORS"
                title="'.attr($zone).': '.attr($priors[0]['encounter_date']).'"> &nbsp;
        </span>
    </span>';
    return $output_return;   
}

/*
 *  This function returns ZONE specific HTML for a PRIOR record (3 input values)
 * 
 *  This is where the magic of displaying the old records happens.
 *  Each section is a duplicate of the base html except the values are from a prior visit,
 *    the background and background-color are different, and the input fields are disabled.
 *
 * @param string $zone options ALL,EXT,ANTSEG,RETINA,NEURO. DRAW_PRIORS_$zone and IMPPLAN to do.
 * @param string $visit_date. Future functionality to limit result set. UTC DATE Formatted 
 * @param string $pid value = patient id
 * @return true : when called outputs the ZONE specific HTML for a prior record + "priors_select" widget for the desired zone 
 */ 
function display_PRIOR_section($zone,$orig_id,$id_to_show,$pid,$report = '0') {
    global $form_folder;
    global $id;
    global $ISSUE_TYPES;
    global $ISSUE_TYPE_STYLES;

    $query  = "SELECT * FROM form_eye_mag_prefs 
                where PEZONE='PREFS' AND id=? 
                ORDER BY ZONE_ORDER,ordering";

    $result = sqlStatement($query,array($_SESSION['authUserID']));
    while ($prefs= sqlFetchArray($result))   {   
        @extract($prefs);    
        $$LOCATION = $VALUE; //same as concept ${$prefs['LOCATION']} = $prefs['VALUE'];
    }
    
    $query = "SELECT * FROM form_".$form_folder." where pid =? and id = ?";
    $result = sqlQuery($query, array($pid,$id_to_show));
    @extract($result); 
    ob_start();
    if ($zone == "EXT") {
        if ($report =='0') $output = priors_select($zone,$orig_id,$id_to_show,$pid);
        ?> 
        <input disabled type="hidden" id="PRIORS_<?php echo attr($zone); ?>_prefix" name="PRIORS_<?php echo attr($zone); ?>_prefix" value="">
        <span class="closeButton pull-right fa fa-close" id="Close_PRIORS_<?php echo attr($zone); ?>" name="Close_PRIORS_<?php echo attr($zone); ?>"></span> 
            <div style="position:absolute;top:0.083in;right:0.241in;">
                 <?php
                 echo $output;
                  ?>
            </div>
                <b> 
                    <?php 
                        if ($report =='0') { echo xlt('Prior Exam'); } else { echo xlt($zone);}
                     ?>: </b><br />
                <div style="position:relative;float:right;top:0.2in;">
                    <table style="text-align:center;font-weight:600;font-size:0.8em;width:166px;">
                        <?php 
                            list($imaging,$episode) = display($pid,$encounter, "EXT"); 
                            echo $episode;
                        ?>
                    </table>
                    <table style="text-align:center;font-size:1.0em;">
                        <tr><td></td><td><?php echo xlt('OD'); ?></td><td><?php echo xlt('OS'); ?></td>
                        </tr>
                        <tr>
                            <td class="right"><?php echo xlt('Lev Fn'); ?></td>
                            <td><input disabled  type="text" size="1" name="PRIOR_RLF" id="PRIOR_RLF" value="<?php echo attr($RLF); ?>"></td>
                            <td><input disabled  type="text" size="1" name="PRIOR_LLF" id="PRIOR_LLF" value="<?php echo attr($LLF); ?>"></td>
                        </tr>
                        <tr>
                            <td class="right"><?php echo xlt('MRD'); ?></td>
                            <td><input disabled type="text" size="1" name="PRIOR_RMRD" id="PRIOR_RMRD" value="<?php echo attr($RMRD); ?>"></td>
                            <td><input disabled type="text" size="1" name="PRIOR_LMRD" id="PRIOR_LMRD" value="<?php echo attr($LMRD); ?>"></td>
                        </tr>
                        <tr>
                            <td class="right"><?php echo xlt('Vert Fissure'); ?></td>
                            <td><input disabled type="text" size="1" name="PRIOR_RVFISSURE" id="PRIOR_RVFISSURE" value="<?php echo attr($RVFISSURE); ?>"></td>
                            <td><input disabled type="text" size="1" name="PRIOR_LVFISSURE" id="PRIOR_LVFISSURE" value="<?php echo attr($LVFISSURE); ?>"></td>
                        </tr>
                          <tr>
                            <td class="right"><?php echo xlt('Carotid Bruit'); ?></td>
                            <td><input  disabled type="text"  name="PRIOR_RCAROTID" id="PRIOR_RCAROTID" value="<?php echo attr($RCAROTID); ?>"></td>
                            <td><input  disabled type="text"  name="PRIOR_LCAROTID" id="PRIOR_LCAROTID" value="<?php echo attr($LCAROTID); ?>"></td>
                        </tr>
                        <tr>
                            <td class="right"><?php echo xlt('Temporal Art.'); ?></td>
                            <td><input  disabled type="text" size="1" name="PRIOR_RTEMPART" id="PRIOR_RTEMPART" value="<?php echo attr($RTEMPART); ?>"></td>
                            <td><input  disabled type="text" size="1" name="PRIOR_LTEMPART" id="PRIOR_LTEMPART" value="<?php echo attr($LTEMPART); ?>"></td>
                        </tr>
                        <tr>
                            <td class="right"><?php echo xlt('CN V'); ?></td>
                            <td><input  disabled type="text" size="1" name="PRIOR_RCNV" id="PRIOR_RCNV" value="<?php echo attr($RCNV); ?>"></td>
                            <td><input  disabled type="text" size="1" name="PRIOR_LCNV" id="PRIOR_LCNV" value="<?php echo attr($LCNV); ?>"></td>
                        </tr>
                        <tr>
                            <td class="right"><?php echo xlt('CN VII'); ?></td>
                            <td><input disabled type="text" size="1" name="PRIOR_RCNVII" id="PRIOR_RCNVII" value="<?php echo attr($RCNVII); ?>"></td>
                            <td><input disabled type="text" size="1" name="PRIOR_LCNVII" id="PRIOR_LCNVII" value="<?php echo attr($LCNVII); ?>"></td>
                        </tr>
                        <tr><td colspan=3 style="padding-top:0.05in;background-color:none;text-decoration:underline;"><br /><?php echo xlt('Hertel Exophthalmometry'); ?></td></tr>
                        <tr style="text-align:center;">
                            <td>
                                <input disabled type=text size=1 id="PRIOR_ODHERTEL" name="PRIOR_ODHERTEL" value="<?php echo attr($ODHERTEL); ?>">
                                <span style="width:40px;-moz-text-decoration-line: line-through;text-align:center;"> &nbsp;&nbsp;&nbsp;&nbsp; </span>
                            </td>
                            <td>
                                <input disabled type=text size=3  id="PRIOR_HERTELBASE" name="PRIOR_HERTELBASE" value="<?php echo attr($HERTELBASE); ?>">
                                <span style="width:400px;-moz-text-decoration-line: line-through;"> &nbsp;&nbsp;&nbsp;&nbsp; </span>
                            </td>
                            <td>
                                <input disabled type=text size=1  id="PRIOR_OSHERTEL" name="PRIOR_OSHERTEL" value="<?php echo attr($OSHERTEL); ?>">
                            </td>
                        </tr>
                        <tr><td>&nbsp;</td></tr>
                    </table>
                </div>

            <?php ($EXT_VIEW ==1) ? ($display_EXT_view = "wide_textarea") : ($display_EXT_view= "narrow_textarea");?>                                 
            <?php ($display_EXT_view == "wide_textarea") ? ($marker ="fa-minus-square-o") : ($marker ="fa-plus-square-o");?>
            <div id="PRIOR_EXT_text_list" name="PRIOR_EXT_text_list" class="borderShadow PRIORS <?php echo attr($display_EXT_view); ?>" >
                <span class="top_right fa <?php echo attr($marker); ?>" name="PRIOR_EXT_text_view" id="PRIOR_EXT_text_view"></span>
                <table cellspacing="0" cellpadding="0" >
                    <tr>
                        <th><?php echo xlt('Right'); ?></th><td style="width:100px;"></td><th><?php echo xlt('Left'); ?> </th>
                    </tr>
                    <tr>
                        <td><textarea disabled name="PRIOR_RBROW" id="PRIOR_RBROW" class="right "><?php echo text($RBROW); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Brow'); ?></td>
                        <td><textarea disabled name="PRIOR_LBROW" id="PRIOR_LBROW" class=""><?php echo text($LBROW); ?></textarea></td>
                    </tr> 
                    <tr>
                        <td><textarea disabled name="PRIOR_RUL" id="PRIOR_RUL" class="right"><?php echo text($RUL); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Upper Lids'); ?></td>
                        <td><textarea disabled name="PRIOR_LUL" id="PRIOR_LUL" class=""><?php echo text($LUL); ?></textarea></td>
                    </tr> 
                    <tr>
                        <td><textarea disabled name="PRIOR_RLL" id="PRIOR_RLL" class="right"><?php echo text($RLL); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Lower Lids'); ?></td>
                        <td><textarea disabled name="PRIOR_LLL" id="PRIOR_LLL" class=""><?php echo text($LLL); ?></textarea></td>
                    </tr>
                    <tr>
                        <td><textarea disabled name="PRIOR_RMCT" id="PRIOR_RMCT" class="right"><?php echo text($RMCT); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Medial Canthi'); ?></td>
                        <td><textarea disabled name="PRIOR_LMCT" id="PRIOR_LMCT" class=""><?php echo text($LMCT); ?></textarea></td>
                    </tr>
                     <tr>
                        <td><textarea disabled name="PRIOR_RADNEXA" id="PRIOR_RADNEXA" class="right"><?php echo text($RADNEXA); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Adnexa'); ?></td>
                        <td><textarea disabled name="PRIOR_LADNEXA" id="PRIOR_LADNEXA" class=""><?php echo text($LADNEXA); ?></textarea></td>
                    </tr>
                </table>
            </div>  <br />
            <div class="QP_lengthen"> <b><?php echo xlt('Comments'); ?>:</b><br />
                  <textarea disabled id="PRIOR_EXT_COMMENTS" name="PRIOR_EXT_COMMENTS" style="width:4.0in;height:3em;"><?php echo text($EXT_COMMENTS); ?></textarea>
            </div>  

            <?php
    } elseif ($zone =="ANTSEG") {
        if ($report =='0') $output = priors_select($zone,$orig_id,$id_to_show,$pid);
        ?> 
        <input disabled type="hidden" id="PRIORS_<?php echo attr($zone); ?>_prefix" name="PRIORS_<?php echo attr($zone); ?>_prefix" value="">
        <span class="closeButton pull-right fa  fa-close" id="Close_PRIORS_<?php echo attr($zone); ?>" name="Close_PRIORS_<?php echo attr($zone); ?>"></span> 
        <div style="position:absolute;top:0.083in;right:0.241in;">
             <?php
             echo $output;
              ?>
        </div>

        <b> <?php echo xlt('Prior Exam'); ?>:</b><br />
        <div class="text_clinical" style="position:relative;float:right;top:0.2in;">
            <table style="text-align:center;font-weight:600;font-size:0.8em;">
                <?php 
                    list($imaging,$episode) = display($pid,$encounter, "ANTSEG"); 
                    echo $episode;
                ?>
            </table>
            <table style="text-align:center;font-size:1.0em;width:170px;padding-left:5px;"> 
                <tr >
                    <td></td><td><?php echo xlt('OD'); ?></td><td><?php echo xlt('OS'); ?></td>
                </tr>
                <tr>
                    <td class="right" ><?php echo xlt('Gonioscopy'); ?></td>
                    <td><input disabled  type="text" name="PRIOR_ODGONIO" id="PRIOR_ODGONIO" value="<?php echo attr($ODGONIO); ?>"></td>
                    <td><input disabled  type="text" name="PRIOR_OSGONIO" id="PRIOR_OSGONIO" value="<?php echo attr($OSGONIO); ?>"></td>
                </tr>
                <tr>
                    <td class="right" ><?php echo xlt('Pachymetry'); ?></td>
                    <td><input disabled type="text" size="1" name="PRIOR_ODKTHICKNESS" id="PRIOR_ODKTHICKNESS" value="<?php echo attr($ODKTHICKNESS); ?>"></td>
                    <td><input disabled type="text" size="1" name="PRIOR_OSKTHICKNESS" id="PRIOR_OSKTHICKNESS" value="<?php echo attr($OSKTHICKNESS); ?>"></td>
                </tr>
                <tr>
                    <td class="right" title="<?php echo xla('Schirmers I (w/o anesthesia)'); ?>"><?php echo xlt('Schirmer I'); ?></td>
                    <td><input disabled type="text" size="1" name="PRIOR_ODSCHIRMER1" id="PRIOR_ODSCHIRMER1" value="<?php echo attr($ODSCHIRMER1); ?>"></td>
                    <td><input disabled type="text" size="1" name="PRIOR_OSSCHRIMER2" id="PRIOR_OSSCHIRMER1" value="<?php echo attr($OSSCHIRMER1); ?>"></td>
                </tr>
                <tr>
                    <td class="right" title="<?php echo xla('Schirmers II (w/ anesthesia)'); ?>"><?php echo xlt('Schirmer II'); ?></td>
                    <td><input disabled type="text" size="1" name="PRIOR_ODSCHIRMER2" id="PRIOR_ODSCHIRMER2" value="<?php echo attr($ODSCHIRMER2); ?>"></td>
                    <td><input disabled type="text" size="1" name="PRIOR_OSSCHRIMER2" id="PRIOR_OSSCHIRMER2" value="<?php echo attr($OSSCHIRMER2); ?>"></td>
                </tr>
                <tr>
                    <td class="right" title="<?php echo xla('Tear Break Up Time'); ?>"><?php echo xlt('TBUT'); ?></td>
                    <td><input disabled type="text" size="1" name="PRIOR_ODTBUT" id="PRIOR_ODTBUT" value="<?php echo attr($ODTBUT); ?>"></td>
                    <td><input disabled type="text" size="1" name="PRIOR_OSTBUT" id="PRIOR_OSTBUT" value="<?php echo attr($OSTBUT); ?>"></td>
                </tr>
                <tr style="text-align:center;" >
                                      <td colspan="3" rowspan="4" style="text-align:left;bottom:0px;width:75px;">
                                        <? // ideal spot to build another list --> dilating drops and extract the here... ?><br />
                                        <span style="width:70px;text-decoration:underline;font-size: 1.1em;"><?php echo xlt('Dilated with'); ?>:</span><br />
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
        <div id="PRIOR_ANTSEG_text_list"  name="PRIOR_ANTSEG_text_list" class="borderShadow PRIORS <?php echo attr($display_ANTSEG_view); ?>" >
                <span class="top_right fa <?php echo attr($marker); ?>" name="PRIOR_ANTSEG_text_view" id="PRIOR_ANTSEG_text_view"></span>
                <table class="" style="" cellspacing="0" cellpadding="0">
                    <tr>
                        <th><?php echo xlt('OD'); ?></th><td style="width:100px;"></td><th><?php echo xlt('OS'); ?></th></td>
                    </tr>
                    <tr>
                        <td><textarea disabled name="PRIOR_ODCONJ" id="PRIOR_ODCONJ" class="right"><?php echo text($ODCONJ); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Conj'); ?> / <?php echo xlt('Sclera'); ?></td>
                        <td><textarea disabled name="PRIOR_OSCONJ" id="PRIOR_OSCONJ" class=""><?php echo text($OSCONJ); ?></textarea></td>
                    </tr> 
                    <tr>
                        <td><textarea disabled name="PRIOR_ODCORNEA" id="PRIOR_ODCORNEA" class="right"><?php echo text($ODCORNEA); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Cornea'); ?></td>
                        <td><textarea disabled name="PRIOR_OSCORNEA" id="PRIOR_OSCORNEA" class=""><?php echo text($OSCORNEA); ?></textarea></td>
                    </tr> 
                    <tr>
                        <td><textarea disabled name="PRIOR_ODAC" id="PRIOR_ODAC" class="right"><?php echo text($ODAC); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;"><?php echo xlt('A/C'); ?></td>
                        <td><textarea disabled name="PRIOR_OSAC" id="PRIOR_OSAC" class=""><?php echo text($OSAC); ?></textarea></td>
                    </tr>
                    <tr>
                        <td><textarea disabled name="PRIOR_ODLENS" id="PRIOR_ODLENS" class=" right"><?php echo text($ODLENS); ?></textarea></td>
                        <td style="text-align:center;font-size:0.9em;font-size:0.9em;" class="dropShadow"><?php echo xlt('Lens'); ?></td>
                        <td><textarea disabled name="PRIOR_OSLENS" id="PRIOR_OSLENS" class=""><?php echo text($OSLENS); ?></textarea></td>
                    </tr>
                    <tr>
                        <td><textarea disabled name="PRIOR_ODIRIS" id="PRIOR_ODIRIS" class="right"><?php echo text($ODIRIS); ?></textarea></td>
                        <td style="text-align:center;"><?php echo xlt('Iris'); ?></td>
                        <td><textarea disabled name="PRIOR_OSIRIS" id="PRIOR_OSIRIS" class=""><?php echo text($OSIRIS); ?></textarea></td>
                    </tr>
                </table>
        </div>  <br />
        <div class="QP_lengthen"> <b><?php echo xlt('Comments'); ?>:</b><br />
            <textarea disabled id="PRIOR_ANTSEG_COMMENTS" name="PRIOR_ANTSEG_COMMENTS" style="width:4.0in;height:3.0em;"><?php echo text($ANTSEG_COMMENTS); ?></textarea>
        </div>   
       
        <?php       
    } elseif ($zone=="RETINA") {
        if ($report =='0') $output = priors_select($zone,$orig_id,$id_to_show,$pid);
        ?> 
        <input disabled type="hidden" id="PRIORS_<?php echo attr($zone); ?>_prefix" name="PRIORS_<?php echo attr($zone); ?>_prefix" value="">
        <span class="closeButton pull-right fa fa-close" id="Close_PRIORS_<?php echo attr($zone); ?>" name="Close_PRIORS_<?php echo attr($zone); ?>"></span> 
        <div style="position:absolute;top:0.083in;right:0.241in;">                              
             <?php
             echo $output;
              ?>
        </div>
        <b><?php echo xlt('Prior Exam'); ?>:</b><br />
        <div style="position:relative;float:right;top:0.2in;">
            <table style="float:right;text-align:right;font-size:0.8em;font-weight:bold;">
              <?php 
                list($imaging,$episode) = display($pid,$encounter, "POSTSEG"); 
                echo $episode;
              ?>
            </table>
            <br />
            <table style="width:50%;text-align:right;font-size:1.0em;font-weight:bold;padding:10px;margin: 5px 0px;">
                <tr style="text-align:center;">
                    <td></td>
                    <td><br /><?php echo xlt('OD'); ?> </td><td><br /><?php echo xlt('OS'); ?> </td>
                </tr>
                <tr>
                    <td>
                        <?php echo xlt('CMT'); ?>:</td>
                    <td>
                        <input disabled name="PRIOR_ODCMT" size="4" id="PRIOR_ODCMT" value="<?php echo attr($ODCMT); ?>">
                    </td>
                    <td>
                        <input disabled name="PRIOR_OSCMT" size="4" id="PRIOR_OSCMT" value="<?php echo attr($OSCMT); ?>">
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
            <div id="PRIOR_RETINA_text_list" name="PRIOR_RETINA_text_list" class="borderShadow PRIORS <?php echo attr($display_RETINA_view); ?>">
                    <span class="top_right fa <?php echo attr($marker); ?>" name="PRIOR_RETINA_text_view" id="PRIOR_RETINA_text_view"></span>
                    <table cellspacing="0" cellpadding="0">
                            <tr>
                                <th><?php echo xlt('OD'); ?></th><td style="width:100px;"></td><th><?php echo xlt('OS'); ?></th></td>
                            </tr>
                            <tr>
                                <td><textarea disabled name="ODDISC" id="ODDISC" class="right"><?php echo text($ODDISC); ?></textarea></td>
                                <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Disc'); ?></td>
                                <td><textarea disabled name="OSDISC" id="OSDISC" class=""><?php echo text($OSDISC); ?></textarea></td>
                            </tr> 
                            <tr>
                                <td><textarea disabled name="ODCUP" id="ODCUP" class="right"><?php echo text($ODCUP); ?></textarea></td>
                                <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Cup'); ?></td>
                                <td><textarea disabled name="OSCUP" id="OSCUP" class=""><?php echo text($OSCUP); ?></textarea></td>
                            </tr> 
                            <tr>
                                <td><textarea disabled name="ODMACULA" id="ODMACULA" class="right"><?php echo text($ODMACULA); ?></textarea></td>
                                <td style="text-align:center;font-size:0.9em;"><?php echo xlt('Macula'); ?></td>
                                <td><textarea disabled name="OSMACULA" id="OSMACULA" class=""><?php echo text($OSMACULA); ?></textarea></td>
                            </tr>
                            <tr>
                                <td><textarea disabled name="ODVESSELS" id="ODVESSELS" class="right"><?php echo text($ODVESSELS); ?></textarea></td>
                                <td style="text-align:center;font-size:0.9em;" class=""><?php echo xlt('Vessels'); ?></td>
                                <td><textarea disabled name="OSVESSELS" id="OSVESSELS" class=""><?php echo text($OSVESSELS); ?></textarea></td>
                            </tr>
                            <tr>
                                <td><textarea disabled name="ODPERIPH" id="ODPERIPH" class="right"><?php echo text($ODPERIPH); ?></textarea></td>
                                <td style="text-align:center;font-size:0.9em;" class=""><?php echo xlt('Periph'); ?></td>
                                <td><textarea disabled name="OSPERIPH" id="OSPERIPH" class=""><?php echo text($OSPERIPH); ?></textarea></td>
                            </tr>
                    </table>
            </div>
        </div>                           
        <br />
        <br />
        <div class="QP_lengthen"> 
            <b><?php echo xlt('Comments'); ?>:</b><br />
            <textarea disabled id="RETINA_COMMENTS" name="RETINA_COMMENTS" style="width:4.0in;height:3.0em;"><?php echo text($RETINA_COMMENTS); ?></textarea>
        </div> 
        <?php 
    } elseif ($zone=="NEURO") {
        if ($report =='0') $output = priors_select($zone,$orig_id,$id_to_show,$pid);
        ?>
        <input disabled type="hidden" id="PRIORS_<?php echo attr($zone); ?>_prefix" name="PRIORS_<?php echo attr($zone); ?>_prefix" value="">
        <span class="closeButton pull-right fa fa-close" id="Close_PRIORS_<?php echo attr($zone); ?>" name="Close_PRIORS_<?php echo attr($zone); ?>"></span> 
        <div style="position:absolute;top:0.083in;right:0.241in;">
             <?php
             echo $output;
              ?>
        </div>
        <b><?php echo xlt('Prior Exam'); ?>:</b><br />
        <div style="float:left;margin-top:0.8em;font-size:0.8em;">
            <div id="PRIOR_NEURO_text_list" class="borderShadow PRIORS" style="border:1pt solid black;float:left;width:175px;padding:10px;text-align:center;margin:2 2;font-weight:bold;">
                <table style="font-size:1.0em;font-weight:600;">
                    <tr>
                        <td></td><td style="text-align:center;"><?php echo xlt('OD'); ?></td><td style="text-align:center;"><?php echo xlt('OS'); ?></td></tr>
                    <tr>
                        <td class="right">
                            <?php echo xlt('Color'); ?>: 
                        </td>
                        <td>
                            <input disabled type="text" id="PRIOR_ODCOLOR" name="PRIOR_ODCOLOR" value="<?php if ($ODCOLOR) { echo  attr($ODCOLOR); } else { echo "   /   "; } ?>"/>
                        </td>
                        <td>
                            <input disabled type="text" id="PRIOR_OSCOLOR" name="PRIOR_OSCOLOR" value="<?php if ($OSCOLOR) { echo  attr($OSCOLOR); } else { echo "   /   "; } ?>"/>
                        </td>
                        <td style="text-align:bottom;"><!-- //Normals may be 11/11 or 15/15.  Need to make a preference here for the user.
                                                //or just take the normal they use and incorporate that ongoing?
                                            -->
                                               &nbsp;<span title="Insert normals - 11/11" class="fa fa-share-square-o fa-flip-horizontal" id="NEURO_COLOR" name="NEURO_COLOR"></span>
                                            </td>
                                        </tr>
                    <tr>
                        <td class="right" style="white-space: nowrap;font-size:0.9em;">
                            <span title="<?php echo xla('Variation in red color discrimination between the eyes (eg. OD=100, OS=75)'); ?>"><?php echo xlt('Red Desat'); ?>:</span>
                        </td>
                        <td>
                            <input disabled type="text" size="6" name="PRIOR_ODREDDESAT" id="PRIOR_ODREDDESAT" value="<?php echo attr($ODREDDESAT); ?>"/> 
                        </td>
                        <td>
                            <input disabled type="text" size="6" name="PRIOR_OSREDDESAT" id="PRIOR_OSREDDESAT" value="<?php echo attr($OSREDDESAT); ?>"/>
                        </td>
                        <td>&nbsp;
                            <span id="" class="fa fa-share-square-o fa-flip-horizontal" name="" title="Insert normals - 11/11"></span>
                        </td>  
                    </tr>
                    <tr>
                        <td class="right" style="white-space: nowrap;font-size:0.9em;">
                            <span title="<?php echo xla('Variation in white (muscle) light brightness discrimination between the eyes (eg. OD=$1.00, OS=$0.75)'); ?>"><?php echo xlt('Coins'); ?>:</span>
                        </td>
                        <td>
                            <input disabled type="text" size="6" name="PRIOR_ODCOINS" id="PRIOR_ODCOINS" value="<?php echo attr($ODCOINS); ?>"/> 
                        </td>
                        <td>
                            <input disabled type="text" size="6" name="PRIOR_OSCOINS" id="PRIOR_OSCOINS" value="<?php echo attr($OSCOINS); ?>"/>
                        </td>
                        <td>&nbsp;
                            <span id="" class="fa fa-share-square-o fa-flip-horizontal" name="" title="Insert normals - 11/11"></span>
                         </td>  
                    </tr>                  
                </table>
            </div>          
            <div class="borderShadow" style="position:relative;float:right;text-align:center;width:238px;height:242px;z-index:1;margin:2 0 2 2;">
                <span class="closeButton fa fa-th" id="PRIOR_Close_ACTMAIN" name="PRIOR_Close_ACTMAIN"></span>
                <table style="position:relative;float:left;font-size:1.1em;width:210px;font-weight:600;"> 
                    <tr style="text-align:left;height:26px;vertical-align:middle;width:180px;">
                        <td >
                            <span id="PRIOR_ACTTRIGGER" name="PRIOR_ACTTRIGGER" style="text-decoration:underline;"><?php echo ('Alternate Cover Test'); ?>:</span>
                        </td>
                        <td>
                            <span id="PRIOR_ACTNORMAL_CHECK" name="PRIOR_ACTNORMAL_CHECK">
                            <label for="PRIOR_ACT" class="input-helper input-helper--checkbox"><?php echo xlt('Ortho'); ?></label>
                            <input disabled type="checkbox" name="PRIOR_ACT" id="PRIOR_ACT" checked="<?php if ($ACT =='1') echo "checked"; ?>"></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align:center;"> 
                            <div id="PRIOR_ACTMAIN" name="PRIOR_ACTMAIN" class="ACT_TEXT nodisplay" style="position:relative;z-index:1;margin 10 auto;">
                               <table cellpadding="0" style="position:relative;text-align:center;font-size:0.9em;margin: 7 5 19 5;border-collapse: separate;">
                                    <tr>
                                        <td id="PRIOR_ACT_tab_SCDIST" name="PRIOR_ACT_tab_SCDIST" class="ACT_selected"> <?php echo xlt('scDist{{ACT without Correction Distance}}'); ?> </td>
                                        <td id="PRIOR_ACT_tab_CCDIST" name="PRIOR_ACT_tab_CCDIST" class="ACT_deselected"> <?php echo xlt('ccDist{{ACT with Correction Distance}}'); ?> </td>
                                        <td id="PRIOR_ACT_tab_SCNEAR" name="PRIOR_ACT_tab_SCNEAR" class="ACT_deselected"> <?php echo xlt('scNear{{ACT without Correction Near}}'); ?> </td>
                                        <td id="PRIOR_ACT_tab_CCNEAR" name="PRIOR_ACT_tab_CCNEAR" class="ACT_deselected"> <?php echo xlt('ccNear{{ACT with Correction Near}}'); ?> </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="text-align:center;font-size:0.8em;">
                                            <div id="PRIOR_ACT_SCDIST" name="PRIOR_ACT_SCDIST" class="ACT_box">
                                                <br />
                                                <table> 
                                                    <tr> 
                                                        <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>   
                                                        <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT1SCDIST" name="PRIOR_ACT1SCDIST" class="ACT"><?php echo text($ACT1SCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT2SCDIST"  name="PRIOR_ACT2SCDIST"class="ACT"><?php echo text($ACT2SCDIST); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT3SCDIST"  name="PRIOR_ACT3SCDIST" class="ACT"><?php echo text($ACT3SCDIST); ?></textarea></td>
                                                        <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td> 
                                                    </tr>
                                                    <tr>    
                                                        <td style="text-align:right;"><i class="fa fa-reply rotate-left right"></i></td> 
                                                        <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT4SCDIST" name="PRIOR_ACT4SCDIST" class="ACT"><?php echo text($ACT4SCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT5SCDIST" name="PRIOR_ACT5SCDIST" class="ACT"><?php echo text($ACT5SCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT6SCDIST" name="PRIOR_ACT6SCDIST" class="ACT"><?php echo text($ACT6SCDIST); ?></textarea></td>
                                                        <td><i class="fa fa-share rotate-right"></i></td> 
                                                    </tr> 
                                                    <tr> 
                                                        <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT10SCDIST" name="PRIOR_ACT10SCDIST" class="ACT"><?php echo text($ACT10SCDIST); ?></textarea></td>
                                                        <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT7SCDIST" name="PRIOR_ACT7SCDIST" class="ACT"><?php echo text($ACT7SCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                            <textarea disabled id="PRIOR_ACT8SCDIST" name="PRIOR_ACT8SCDIST" class="ACT"><?php echo text($ACT8SCDIST); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                            <textarea disabled id="PRIOR_ACT9SCDIST" name="PRIOR_ACT9SCDIST" class="ACT"><?php echo text($ACT9SCDIST); ?></textarea></td>
                                                        <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                            <textarea disabled id="PRIOR_ACT11SCDIST" name="PRIOR_ACT11SCDIST" class="ACT"><?php echo text($ACT11SCDIST); ?></textarea>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <br />
                                            </div>
                                            <div id="PRIOR_ACT_CCDIST" name="PRIOR_ACT_CCDIST" class="nodisplay ACT_box">
                                                <br />
                                                <table> 
                                                   <tr> 
                                                        <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>   
                                                        <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT1CCDIST" name="PRIOR_ACT1CCDIST" class="ACT"><?php echo text($ACT1CCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT2CCDIST"  name="PRIOR_ACT2CCDIST"class="ACT"><?php echo text($ACT2CCDIST); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT3CCDIST"  name="PRIOR_ACT3CCDIST" class="ACT"><?php echo text($ACT3CCDIST); ?></textarea></td>
                                                        <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td> 
                                                    </tr>
                                                    <tr>    
                                                        <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                                        <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT4CCDIST" name="PRIOR_ACT4CCDIST" class="ACT"><?php echo text($ACT4CCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT5CCDIST" name="PRIOR_ACT5CCDIST" class="ACT"><?php echo text($ACT5CCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT6CCDIST" name="PRIOR_ACT6CCDIST" class="ACT"><?php echo text($ACT6CCDIST); ?></textarea></td>
                                                        <td><i class="fa fa-share rotate-right"></i></td> 
                                                    </tr> 
                                                    <tr> 
                                                        <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT10CCDIST" name="PRIOR_ACT10CCDIST" class="ACT"><?php echo text($ACT10CCDIST); ?></textarea></td>
                                                        <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT7CCDIST" name="PRIOR_ACT7CCDIST" class="ACT"><?php echo text($ACT7CCDIST); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                            <textarea disabled id="PRIOR_ACT8CCDIST" name="PRIOR_ACT8CCDIST" class="ACT"><?php echo text($ACT8CCDIST); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                            <textarea disabled id="PRIOR_ACT9CCDIST" name="PRIOR_ACT9CCDIST" class="ACT"><?php echo text($ACT9CCDIST); ?></textarea></td>
                                                        <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                            <textarea disabled id="PRIOR_ACT11CCDIST" name="PRIOR_ACT11CCDIST" class="ACT"><?php echo text($ACT11CCDIST); ?></textarea>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <br />
                                            </div>
                                            <div id="PRIOR_ACT_SCNEAR" name="PRIOR_ACT_SCNEAR" class="nodisplay ACT_box">
                                                <br />
                                                <table> 
                                                    <tr> 
                                                        <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>    
                                                        <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT1SCNEAR" name="PRIOR_ACT1SCNEAR" class="ACT"><?php echo text($ACT1SCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT2SCNEAR"  name="PRIOR_ACT2SCNEAR"class="ACT"><?php echo text($ACT2SCNEAR); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT3SCNEAR"  name="PRIOR_ACT3SCNEAR" class="ACT"><?php echo text($ACT3SCNEAR); ?></textarea></td>
                                                        <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td> 
                                                    </tr>
                                                    <tr>    
                                                        <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                                        <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT4SCNEAR" name="PRIOR_ACT4SCNEAR" class="ACT"><?php echo text($ACT4SCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT5SCNEAR" name="PRIOR_ACT5SCNEAR" class="ACT"><?php echo text($ACT5SCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT6SCNEAR" name="PRIOR_ACT6SCNEAR" class="ACT"><?php echo text($ACT6SCNEAR); ?></textarea></td>
                                                        <td><i class="fa fa-share rotate-right"></i></td> 
                                                    </tr> 
                                                    <tr> 
                                                        <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT10SCNEAR" name="PRIOR_ACT10SCNEAR" class="ACT"><?php echo text($ACT10SCNEAR); ?></textarea></td>
                                                        <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT7SCNEAR" name="PRIOR_ACT7SCNEAR" class="ACT"><?php echo text($ACT7SCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                            <textarea disabled id="PRIOR_ACT8SCNEAR" name="PRIOR_ACT8SCNEAR" class="ACT"><?php echo text($ACT8SCNEAR); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                            <textarea disabled id="PRIOR_ACT9SCNEAR" name="PRIOR_ACT9SCNEAR" class="ACT"><?php echo text($ACT9SCNEAR); ?></textarea></td>
                                                        <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                            <textarea disabled id="PRIOR_ACT11SCNEAR" name="PRIOR_ACT11SCNEAR" class="ACT"><?php echo text($ACT11SCNEAR); ?></textarea>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <br />
                                            </div>
                                            <div id="PRIOR_ACT_CCNEAR" name="PRIOR_ACT_CCNEAR" class="nodisplay ACT_box">
                                                <br />
                                                <table> 
                                                    <tr> 
                                                        <td style="text-align:center;"><?php echo xlt('R{{Right}}'); ?></td>    
                                                        <td style="border-right:1pt solid black;border-bottom:1pt solid black;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT1CCNEAR" name="PRIOR_ACT1CCNEAR" class="ACT"><?php echo text($ACT1CCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-top:0pt;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT2CCNEAR"  name="PRIOR_ACT2CCNEAR"class="ACT"><?php echo text($ACT2CCNEAR); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-bottom:1pt solid black;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT3CCNEAR"  name="PRIOR_ACT3CCNEAR" class="ACT"><?php echo text($ACT3CCNEAR); ?></textarea></td>
                                                        <td style="text-align:center;"><?php echo xlt('L{{Left}}'); ?></td>
                                                    </tr>
                                                    <tr>    
                                                        <td style="text-align:right;"><i class="fa fa-reply rotate-left"></i></td> 
                                                        <td style="border:1pt solid black;border-left:0pt;text-align:right;">
                                                        <textarea disabled id="PRIOR_ACT4CCNEAR" name="PRIOR_ACT4CCNEAR" class="ACT"><?php echo text($ACT4CCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;text-align:center;">
                                                        <textarea disabled id="PRIOR_ACT5CCNEAR" name="PRIOR_ACT5CCNEAR" class="ACT"><?php echo text($ACT5CCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-right:0pt;text-align:left;">
                                                        <textarea disabled id="PRIOR_ACT6CCNEAR" name="PRIOR_ACT6CCNEAR" class="ACT"><?php echo text($ACT6CCNEAR); ?></textarea></td><td><i class="fa fa-share rotate-right"></i></td> 
                                                    </tr> 
                                                    <tr> 
                                                        <td style="border:0; border-top:2pt solid black;border-right:2pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT10CCNEAR" name="PRIOR_ACT10CCNEAR" class="ACT"><?php echo text($ACT10CCNEAR); ?></textarea></td>
                                                        <td style="border-right:1pt solid black;border-top:1pt solid black;text-align:right;">
                                                            <textarea disabled id="PRIOR_ACT7CCNEAR" name="PRIOR_ACT7CCNEAR" class="ACT"><?php echo text($ACT7CCNEAR); ?></textarea></td>
                                                        <td style="border:1pt solid black;border-bottom:0pt;text-align:center;">
                                                            <textarea disabled id="PRIOR_ACT8CCNEAR" name="PRIOR_ACT8CCNEAR" class="ACT"><?php echo text($ACT8CCNEAR); ?></textarea></td>
                                                        <td style="border-left:1pt solid black;border-top:1pt solid black;text-align:left;">
                                                            <textarea disabled id="PRIOR_ACT9CCNEAR" name="PRIOR_ACT9CCNEAR" class="ACT"><?php echo text($ACT9CCNEAR); ?></textarea></td>
                                                        <td style="border:0; border-top:2pt solid black;border-left:2pt solid black;text-align:left;vertical-align:middle;">
                                                            <textarea disabled id="PRIOR_ACT11CCNEAR" name="PRIOR_ACT11CCNEAR" class="ACT"><?php echo text($ACT11CCNEAR); ?></textarea>
                                                        </td>
                                                    </tr>
                                                </table>
                                               <br />
                                            </div>
                                        </td>
                                    </tr>
                               </table>
                            </div>
                        </td>
                    </tr>
                </table>
                <div id="PRIOR_NPCNPA" name="PRIOR_NPCNPA">
                    <table style="position:relative;float:left;text-align:center;margin: 4 2;width:100%;font-size:1.0em;padding:4px;">
                        <tr style=""><td style="width:50%;"></td><td style="font-weight:bold;"><?php echo xlt('OD'); ?></td><td style="font-weight:bold;"><?php echo xlt('OS'); ?></td></tr>
                        <tr>
                            <td class="right"><span title="<?php echo xla('Near Point of Accomodation'); ?>"><?php echo xlt('NPA'); ?>:</span></td>
                            <td><input disabled type="text" id="PRIOR_ODNPA" style="width:70%;" name="PRIOR_ODNPA" value="<?php echo attr($ODNPA); ?>"></td>
                            <td><input disabled type="text" id="PRIOR_OSNPA" style="width:70%;" name="PRIOR_OSNPA" value="<?php echo attr($OSNPA); ?>"></td>
                        </tr>
                        <tr>
                            <td class="right"><span title="<?php echo xla('Near Point of Convergence'); ?>"><?php echo xlt('NPC'); ?>:</span></td>
                            <td colspan="2" ><input disabled type="text" style="width:85%;" id="PRIOR_NPC" name="PRIOR_NPC" value="<?php echo attr($NPC); ?>">
                            </td>
                        </tr>
                         <tr>
                            <td class="right">
                                <?php echo xlt('Stereopsis'); ?>:
                            </td>
                            <td colspan="2">
                                <input disabled type="text" style="width:85%;" name="PRIOR_STEREOPSIS" id="PRIOR_STEREOPSIS" value="<?php echo attr($STEREOPSIS); ?>">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="font-weight:bold;"><br /><br /><u><?php echo xlt('Amplitudes'); ?></u><br />
                            </td>
                        </tr>
                        <tr><td ></td><td ><?php echo xlt('Distance'); ?></td><td><?php echo xlt('Near'); ?></td></tr>
                        <tr>
                            <td style="text-align:right;"><?php echo xlt('Divergence'); ?>:</td>
                            <td><input disabled type="text" id="PRIOR_DACCDIST" name="PRIOR_DACCDIST" value="<?php echo attr($DACCDIST); ?>"></td>
                            <td><input disabled type="text" id="PRIOR_DACCNEAR" name="PRIOR_DACCNEAR" value="<?php echo attr($DACCNEAR); ?>"></td></tr>
                        <tr>
                            <td style="text-align:right;"><?php echo xlt('Convergence'); ?>:</td>
                            <td><input disabled type="text" id="PRIOR_CACCDIST" name="PRIOR_CACCDIST" value="<?php echo attr($CACCDIST); ?>"></td>
                            <td><input disabled type="text" id="PRIOR_CACCNEAR" name="PRIOR_CACCNEAR" value="<?php echo attr($CACCNEAR); ?>"></td></tr>
                        </tr>
                         <tr>
                            <td class="right">
                                <?php echo xlt('Vertical Fusional'); ?>:
                            </td>
                            <td colspan="2">
                                <input disabled type="text" style="width:90%;" name="PRIOR_VERTFUSAMPS" id="PRIOR_VERTFUSAMPS" value="<?php echo attr($VERTFUSAMPS); ?>">
                                <br />
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
                <?php                 
                $hash_tag = '<i class="fa fa-minus"></i>';
                if ($MOTILITY_RS > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RS; ++$index) {
                        $here = "PRIOR_MOTILITY_RS_".$index;
                        $$here= $hash_tag;
                    }
                }
                if ($MOTILITY_RI > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RI; ++$index) {
                        $here ="PRIOR_MOTILITY_RI_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_LS > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LS; ++$index) {
                        $here ="PRIOR_MOTILITY_LS_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_LI > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LI; ++$index) {
                       $here ="PRIOR_MOTILITY_LI_".$index;
                        $$here = $hash_tag;
                    }
                }
                
                //hash_tags for obliques = "/" 
                //$hash_tag = '<span style="font-size:1.0em;font-weight:bold;">/</span>';
                if ($MOTILITY_RRSO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RRSO; ++$index) {
                       $here ="PRIOR_MOTILITY_RRSO_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_LRSO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LRSO; ++$index) {
                       $here ="PRIOR_MOTILITY_LRSO_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_RLIO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RLIO; ++$index) {
                       $here ="PRIOR_MOTILITY_RLIO_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_LLIO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LLIO; ++$index) {
                       $here ="PRIOR_MOTILITY_LLIO_".$index;
                        $$here = $hash_tag;
                    }
                }
                
                //hash_tags for obliques = "\" 
                //$hash_tag = '<span style="font-size:0.8em;font-weight:bold;">\</span>';
                if ($MOTILITY_RLSO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RLSO; ++$index) {
                       $here ="PRIOR_MOTILITY_RLSO_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_LLSO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LLSO; ++$index) {
                       $here ="PRIOR_MOTILITY_LLSO_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_RRIO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RRIO; ++$index) {
                       $here ="PRIOR_MOTILITY_RRIO_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_LRIO > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LRIO; ++$index) {
                       $here ="PRIOR_MOTILITY_LRIO_".$index;
                        $$here = $hash_tag;
                    }
                }

                
                $hash_tag = '<i class="fa fa-minus rotate-left"></i>';
                if ($MOTILITY_LR > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LR; ++$index) {
                       $here ="PRIOR_MOTILITY_LR_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_LL > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_LL; ++$index) {
                        $here ="PRIOR_MOTILITY_LL_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_RR > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RR; ++$index) {
                        $here ="PRIOR_MOTILITY_RR_".$index;
                        $$here = $hash_tag;
                    }
                }
                if ($MOTILITY_RL > '0') {
                    $PRIOR_MOTILITYNORMAL='';
                    for ($index =1; $index <= $MOTILITY_RL; ++$index) {
                        $here ="PRIOR_MOTILITY_RL_".$index;
                        $$here = $hash_tag;
                    }
                }
                ?>
            <div id="PRIOR_NEURO_MOTILITY" class="text_clinical borderShadow" 
                style="float:left;font-size:0.9em;margin:2 2;padding: 0 10;font-weight:bold;height:134px;width:175px;">
                <div>
                    <table style="width:100%;margin:0 0 1 0;">
                        <tr>
                            <td style="width:40%;font-size:0.9em;margin:0 auto;font-weight:bold;"><?php echo xlt('Motility'); ?>:</td>
                            <td style="font-size:0.9em;vertical-align:middle;text-align:right;top:0.0in;right:0.1in;height:30px;">
                                <label for="PRIOR_MOTILITYNORMAL" class="input-helper input-helper--checkbox"><?php echo xlt('Normal'); ?></label>
                                <input disabled id="PRIOR_MOTILITYNORMAL" name="PRIOR_MOTILITYNORMAL" type="checkbox" value="1" <?php if ($MOTILITYNORMAL >'0') echo "checked"; ?> disabled>
                            </td>
                        </tr>
                    </table>
                </div>
                <input disabled type="hidden" name="PRIOR_MOTILITY_RS"  id="PRIOR_MOTILITY_RS" value="<?php echo attr($MOTILITY_RS); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_RI"  id="PRIOR_MOTILITY_RI" value="<?php echo attr($MOTILITY_RI); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_RR"  id="PRIOR_MOTILITY_RR" value="<?php echo attr($MOTILITY_RR); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_RL"  id="PRIOR_MOTILITY_RL" value="<?php echo attr($MOTILITY_RL); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_LS"  id="PRIOR_MOTILITY_LS" value="<?php echo attr($MOTILITY_LS); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_LI"  id="PRIOR_MOTILITY_LI" value="<?php echo attr($MOTILITY_LI); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_LR"  id="PRIOR_MOTILITY_LR" value="<?php echo attr($MOTILITY_LR); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_LL"  id="PRIOR_MOTILITY_LL" value="<?php echo attr($MOTILITY_LL); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_RRSO"  id="PRIOR_MOTILITY_RRSO" value="<?php echo attr($MOTILITY_RRSO); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_RLSO"  id="PRIOR_MOTILITY_RLSO" value="<?php echo attr($MOTILITY_RLSO); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_RRIO"  id="PRIOR_MOTILITY_RRIO" value="<?php echo attr($MOTILITY_RRIO); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_RLIO"  id="PRIOR_MOTILITY_RLIO" value="<?php echo attr($MOTILITY_RLIO); ?>">

                <input disabled type="hidden" name="PRIOR_MOTILITY_LRSO"  id="PRIOR_MOTILITY_LRSO" value="<?php echo attr($MOTILITY_LRSO); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_LLSO"  id="PRIOR_MOTILITY_LLSO" value="<?php echo attr($MOTILITY_LLSO); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_LRIO"  id="PRIOR_MOTILITY_LRIO" value="<?php echo attr($MOTILITY_LRIO); ?>">
                <input disabled type="hidden" name="PRIOR_MOTILITY_LLIO"  id="PRIOR_MOTILITY_LLIO" value="<?php echo attr($MOTILITY_LLIO); ?>">
                
                <div style="float:left;left:0.4in;text-decoration:underline;"><?php echo xlt('OD'); ?></div>
                <div style="float:right;right:0.4in;text-decoration:underline;"><?php echo xlt('OS'); ?></div><br />
                <div class="divTable" style="background: url(../../forms/<?php echo $form_folder; ?>/images/eom.bmp) no-repeat center center;background-size: 90% 75%;height:0.77in;width:0.71in;padding:1px;margin:6 1 1 2;">
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRSO_4; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_4_3" id="PRIOR_MOTILITY_RS_4_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_4_1" id="PRIOR_MOTILITY_RS_4_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_4" id="PRIOR_MOTILITY_RS_4"><?php echo $PRIOR_MOTILITY_RS_4; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_4_2" id="PRIOR_MOTILITY_RS_4_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_4_4" id="PRIOR_MOTILITY_RS_4_4">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLSO_4; ?></div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRSO_3; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_3_1" id="PRIOR_MOTILITY_RS_3_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_3" id="PRIOR_MOTILITY_RS_3"><?php echo $PRIOR_MOTILITY_RS_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_3_2" id="PRIOR_MOTILITY_RS_3_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLSO_3; ?></div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRSO_2; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_2_1" id="PRIOR_MOTILITY_RS_2_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_2" id="PRIOR_MOTILITY_RS_2"><?php echo $PRIOR_MOTILITY_RS_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_2_2" id="PRIOR_MOTILITY_RS_2_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLSO_2; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRSO_1; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_1_1" id="PRIOR_MOTILITY_RS_1_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_1" id="PRIOR_MOTILITY_RS_1"><?php echo $PRIOR_MOTILITY_RS_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_1_2" id="PRIOR_MOTILITY_RS_1_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLSO_1; ?></div>
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
                        <div class="divCell" name="PRIOR_MOTILITY_RS_0_1" id="PRIOR_MOTILITY_RS_0_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_0" id="PRIOR_MOTILITY_RS_0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RS_0_1" id="PRIOR_MOTILITY_RS_0_1">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divMiddleRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RR_4" id="PRIOR_MOTILITY_RR_4"><?php echo $PRIOR_MOTILITY_RR_4; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RR_3" id="PRIOR_MOTILITY_RR_3"><?php echo $PRIOR_MOTILITY_RR_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RR_2" id="PRIOR_MOTILITY_RR_2"><?php echo $PRIOR_MOTILITY_RR_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RR_1" id="PRIOR_MOTILITY_RR_1"><?php echo $PRIOR_MOTILITY_RR_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RR_0" id="PRIOR_MOTILITY_RR_0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_R0" id="PRIOR_MOTILITY_R0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RL_0" id="PRIOR_MOTILITY_RL_0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RL_1" id="PRIOR_MOTILITY_RL_1"><?php echo $PRIOR_MOTILITY_RL_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RL_2" id="PRIOR_MOTILITY_RL_2"><?php echo $PRIOR_MOTILITY_RL_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RL_3" id="PRIOR_MOTILITY_RL_3"><?php echo $PRIOR_MOTILITY_RL_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RL_4" id="PRIOR_MOTILITY_RL_4"><?php echo $PRIOR_MOTILITY_RL_4; ?></div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_0_1" id="PRIOR_MOTILITY_RI_0_1">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_RI_0" name="PRIOR_MOTILITY_RI_0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_0_2" id="PRIOR_MOTILITY_RI_0_2">&nbsp;</div>
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
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRIO_1; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_1_1" id="PRIOR_MOTILITY_RI_1_1">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_RI_1" name="PRIOR_MOTILITY_RI_1"><?php echo $PRIOR_MOTILITY_RI_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_1_2" id="PRIOR_MOTILITY_RI_1_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLIO_1; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRIO_2; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_2_1" id="PRIOR_MOTILITY_RI_2_1">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_RI_2" name="PRIOR_MOTILITY_RI_2"><?php echo $PRIOR_MOTILITY_RI_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_2_2" id="PRIOR_MOTILITY_RI_2_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLIO_2; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRIO_3; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_3_5" id="PRIOR_MOTILITY_RI_3_5">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_3_3" id="PRIOR_MOTILITY_RI_3_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_3_1" id="PRIOR_MOTILITY_RI_3_1">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_RI_3" name="PRIOR_MOTILITY_RI_3"><?php echo $PRIOR_MOTILITY_RI_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_3_2" id="PRIOR_MOTILITY_RI_3_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_3_4" id="PRIOR_MOTILITY_RI_3_4">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_3_6" id="PRIOR_MOTILITY_RI_3_6">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLIO_3; ?></div>
                        <div class="divCell"></div>
                    </div>
                    <div class="divRow">
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RRIO_4; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_4_5" id="PRIOR_MOTILITY_RI_4_5">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_4_3" id="PRIOR_MOTILITY_RI_4_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_4_1" id="PRIOR_MOTILITY_RI_4_1">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_RI_4" name="PRIOR_MOTILITY_RI_4"><?php echo $PRIOR_MOTILITY_RI_4; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_4_2" id="PRIOR_MOTILITY_RI_4_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_4_4" id="PRIOR_MOTILITY_RI_4_4">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RI_4_6" id="PRIOR_MOTILITY_RI_4_6">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_RLIO_4; ?></div>
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
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LRSO_4; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_4_3" id="PRIOR_MOTILITY_LS_4_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_4_1" id="PRIOR_MOTILITY_LS_4_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_4" id="PRIOR_MOTILITY_LS_4"><?php echo $PRIOR_MOTILITY_LS_4; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_4_2" id="PRIOR_MOTILITY_LS_4_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_4_4" id="PRIOR_MOTILITY_LS_4_4">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LLSO_4; ?></div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LRSO_3; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_3_1" id="PRIOR_MOTILITY_LS_3_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_3" id="PRIOR_MOTILITY_LS_3"><?php echo $PRIOR_MOTILITY_LS_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_3_2" id="PRIOR_MOTILITY_LS_3_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LLSO_3; ?></div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LRSO_2; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_2_1" id="PRIOR_MOTILITY_LS_2_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_2" id="PRIOR_MOTILITY_LS_2"><?php echo $PRIOR_MOTILITY_LS_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_2_2" id="PRIOR_MOTILITY_LS_2_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LLSO_2; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LRSO_1; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_1_1" id="PRIOR_MOTILITY_LS_1_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_1" id="PRIOR_MOTILITY_LS_1"><?php echo $PRIOR_MOTILITY_LS_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_1_2" id="PRIOR_MOTILITY_LS_1_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LLSO_1; ?></div>
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
                        <div class="divCell" name="PRIOR_MOTILITY_LS_0_1" id="PRIOR_MOTILITY_LS_0_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_0" id="PRIOR_MOTILITY_LS_0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LS_0_1" id="PRIOR_MOTILITY_LS_0_1">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divMiddleRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_4" id="PRIOR_MOTILITY_LR_4"><?php echo $PRIOR_MOTILITY_LR_4; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_3" id="PRIOR_MOTILITY_LR_3"><?php echo $PRIOR_MOTILITY_LR_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_2" id="PRIOR_MOTILITY_LR_2"><?php echo $PRIOR_MOTILITY_LR_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_1" id="PRIOR_MOTILITY_LR_1"><?php echo $PRIOR_MOTILITY_LR_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_0" id="PRIOR_MOTILITY_LR_0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_L0" id="PRIOR_MOTILITY_L0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_0" id="PRIOR_MOTILITY_LL_0">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_1" id="PRIOR_MOTILITY_LL_1"><?php echo $PRIOR_MOTILITY_LL_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_2" id="PRIOR_MOTILITY_LL_2"><?php echo $PRIOR_MOTILITY_LL_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_3" id="PRIOR_MOTILITY_LL_3"><?php echo $PRIOR_MOTILITY_LL_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_4" id="PRIOR_MOTILITY_LL_4"><?php echo $PRIOR_MOTILITY_LL_4; ?></div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_4_1" id="PRIOR_MOTILITY_LR_4_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_3_1" id="PRIOR_MOTILITY_LR_3_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_2_1" id="PRIOR_MOTILITY_LR_2_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RO_I_1" id="PRIOR_MOTILITY_RO_I_1">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_LI_0" name="PRIOR_MOTILITY_LI_0">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LO_I_1" id="PRIOR_MOTILITY_LO_I_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_2_2" id="PRIOR_MOTILITY_LL_2_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_3_2" id="PRIOR_MOTILITY_LL_3_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_4_2" id="PRIOR_MOTILITY_LL_4_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                     <div class="divRow">
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_4_3" id="PRIOR_MOTILITY_LR_4_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LR_3_3" id="PRIOR_MOTILITY_LR_3_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RO_I_2" id="PRIOR_MOTILITY_RO_I_2"><?php echo $PRIOR_MOTILITY_LRIO_1; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_LI_1" name="PRIOR_MOTILITY_LI_1"><?php echo $PRIOR_MOTILITY_LI_1; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LO_I_2" id="PRIOR_MOTILITY_LO_I_2"><?php echo $PRIOR_MOTILITY_LLIO_1; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_3_4" id="PRIOR_MOTILITY_LL_3_4">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LL_4_4" id="PRIOR_MOTILITY_LL_4_4">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                    </div>
                    <div class="divRow">
                        <div class="divCell" name="PRIOR_MOTILITY_RO_I_3_1" id="PRIOR_MOTILITY_RO_I_3_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_RO_I_3" id="PRIOR_MOTILITY_RO_I_3">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LRIO_2; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_2_1" id="PRIOR_MOTILITY_LI_2_1">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_LI_2" name="PRIOR_MOTILITY_LI_2"><?php echo $PRIOR_MOTILITY_LI_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_2_2" id="PRIOR_MOTILITY_LI_2_2">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LLIO_2; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LO_I_2" id="PRIOR_MOTILITY_RO_I_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LO_I_3_1" id="PRIOR_MOTILITY_LO_I_3_1">&nbsp;</div>
                        </div>
                    <div class="divRow">
                        <div class="divCell" name="PRIOR_MOTILITY_LO_I_3" id="PRIOR_MOTILITY_RO_I_3">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LRIO_3; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_3_5" id="PRIOR_MOTILITY_LI_3_5">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_3_3" id="PRIOR_MOTILITY_LI_3_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_3_1" id="PRIOR_MOTILITY_LI_3_1">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_3"   id="PRIOR_MOTILITY_LI_3"><?php echo $PRIOR_MOTILITY_LI_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_3_2" id="PRIOR_MOTILITY_LI_3_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_3_4" id="PRIOR_MOTILITY_LI_3_4">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_3_6" id="PRIOR_MOTILITY_LI_3_6">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell"><?php echo $PRIOR_MOTILITY_LLIO_3; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LO_I_3" id="PRIOR_MOTILITY_LO_I_3">&nbsp;</div>
                        
                    </div>
                    <div class="divRow">
                        <div class="divCell" name="PRIOR_MOTILITY_RO_I_4" id="PRIOR_MOTILITY_RO_I_4"><?php echo $PRIOR_MOTILITY_LRIO_4; ?></div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_4_5" id="PRIOR_MOTILITY_LI_4_5">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_4_3" id="PRIOR_MOTILITY_LI_4_3">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_4_1" id="PRIOR_MOTILITY_LI_4_1">&nbsp;</div>
                        <div class="divCell" id="PRIOR_MOTILITY_LI_4" name="PRIOR_MOTILITY_LI_4"><?php echo $PRIOR_MOTILITY_LI_4; ?></div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_4_2" id="PRIOR_MOTILITY_LI_4_2">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_4_4" id="PRIOR_MOTILITY_LI_4_4">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LI_4_6" id="PRIOR_MOTILITY_LI_4_6">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell">&nbsp;</div>
                        <div class="divCell" name="PRIOR_MOTILITY_LO_I_4" id="PRIOR_MOTILITY_LO_I_4"><?php echo $PRIOR_MOTILITY_LLIO_4; ?></div>
                    </div>   
                    <div class="divRow"><div class="divCell">&nbsp;</div>
                    </div>
                </div> 
            </div>
        </div>
        <br />
        <div style="position: absolute;bottom:0.05in;clear:both;font-size:0.9em;text-align:left;padding-left:25px;"> 
            <b><?php echo xlt('Comments'); ?>:</b><br />
            <textarea disabled id="PRIOR_NEURO_COMMENTS" name="PRIOR_NEURO_COMMENTS" style="width:4.0in;height:3.0em;"><?php echo text($NEURO_COMMENTS); ?></textarea>
        </div>
        <input type="hidden" name="PRIOR_PREFS_ACT_SHOW"  id="PRIOR_PREFS_ACT_SHOW" value="<?php echo attr($ACT_SHOW); ?>">
            
        <script type="text/javascript">
            $("#PRIOR_ACTTRIGGER").mouseover(function() {
                                                   $("#PRIOR_ACTTRIGGER").toggleClass('buttonRefraction_selected').toggleClass('underline');
                                                   });
            $("#PRIOR_ACTTRIGGER").mouseout(function() {
                                                  $("#PRIOR_ACTTRIGGER").toggleClass('buttonRefraction_selected').toggleClass('underline');
                                                  });
            $("#PRIOR_ACTTRIGGER").click(function() {
                                               $("#PRIOR_ACTMAIN").toggleClass('nodisplay'); //.toggleClass('fullscreen');
                                               $("#PRIOR_NPCNPA").toggleClass('nodisplay');
                                               $("#PRIOR_ACTNORMAL_CHECK").toggleClass('nodisplay');
                                               $("#PRIOR_ACTTRIGGER").toggleClass('underline');
                                               $("#PRIOR_Close_ACTMAIN").toggleClass('fa-random').toggleClass('fa-eye');
                                               });
            $("[name^='PRIOR_ACT_tab_']").click(function()  {
                                                var section = this.id.match(/PRIOR_ACT_tab_(.*)/)[1];
                                                $("[name^='PRIOR_ACT_']").addClass('nodisplay');
                                                $("[name^='PRIOR_ACT_tab_']").removeClass('nodisplay').removeClass('ACT_selected').addClass('ACT_deselected');
                                                $("#PRIOR_ACT_tab_" + section).addClass('ACT_selected').removeClass('ACT_deselected');
                                                $("#PRIOR_ACT_" + section).removeClass('nodisplay');
                                                $("#PRIOR_PREFS_ACT_SHOW").val(section);
                                                });

            $("[name^='PRIOR_Close_']").click(function()  {
                                              var section = this.id.match(/PRIOR_Close_(.*)$/)[1];
                                              if (section =="ACTMAIN") {
                                                $("#PRIOR_ACTTRIGGER").trigger( "click" );
                                              } else {
                                                $("#LayerVision_"+section+"_lightswitch").click();
                                              }
                                              });
            if ($("#PREFS_ACT_VIEW").val() == '1') {
                $("#PRIOR_ACTMAIN").toggleClass('nodisplay'); //.toggleClass('fullscreen');
                $("#PRIOR_NPCNPA").toggleClass('nodisplay');
                $("#PRIOR_ACTNORMAL_CHECK").toggleClass('nodisplay');
                $("#PRIOR_ACTTRIGGER").toggleClass('underline');
                var show = $("#PREFS_ACT_SHOW").val();
                $("#PRIOR_ACT_tab_"+show).trigger('click');
            }
        </script>
        <?php 
    } elseif ($zone=="IMPPLAN") {
        if ($report =='0') $output =  priors_select($zone,$orig_id,$id_to_show,$pid);
        ?> 
        <input disabled type="hidden" id="PRIORS_<?php echo attr($zone); ?>_prefix" name="PRIORS_<?php echo attr($zone); ?>_prefix" value="">
        <span class="closeButton pull-right fa  fa-close" id="Close_PRIORS_<?php echo attr($zone); ?>" name="Close_PRIORS_<?php echo attr($zone); ?>"></span> 
        <div style="position:absolute;top:0.083in;right:0.241in;" class="PRIORS">
             <?php
             echo $output;
              ?>
        </div>
        <b> <?php echo xlt('Prior IMP/PLAN'); ?>:</b><br />
        <?php 
        $PRIOR_IMPPLAN_items = get_PRIOR_IMPPLAN($pid,$id_to_show);

        if ($PRIOR_IMPPLAN_items) { 
            echo "<br /><br /><div style='width:90%;'>";
            $i='0';$k='1';
            foreach ($PRIOR_IMPPLAN_items as $item) {
                echo "<div class='IMPPLAN_class' style='clear:both;margin:10px;'>";
                echo "  <span>$k. ".$item['title']."</span><span class='pull-right'>".$item['code']."</span><br />";
                echo '  <div class="fake-textarea-disabled-4">'.nl2br($item['plan']).'</div>';
                echo '</div>';
                $i++;$k++;
            }
            echo "</div>";
        } 
    } elseif ($zone =="ALL") {
        echo $selector = priors_select($zone,$orig_id,$id_to_show,$pid);
    } elseif ($zone =="PMSFH") {
        // Check authorization.        
        if (acl_check('patients','med')) {
            $tmp = getPatientData($pid);
        }
        // We are going to build the PMSFH panel.  
        // There are two rows in our panel.
        echo "<div style='height:auto;'>";
        echo $display_PMSFH = display_PMSFH('2');
        echo "</div>";
    }
    $output = ob_get_contents();

    ob_end_clean();
    return $output;
}

/* 
 * Function to prepare for sending the PMSFH_panel and PMSFH_right_panel
 * via display_PMSFH('2') and show_PMSFH_panel($PMSFH) respectively, 
 * to javascript to display changes to the user.
 */
function send_json_values($PMSFH="") {
  if (!$PMSFH) build_PMSFH();
  $send['PMSFH'] = $PMSFH[0]; //actual array
  $send['PMH_panel'] = display_PMSFH('2');//display PMSFH next to the PMSFH Builder
  $send['right_panel'] = show_PMSFH_panel($PMSFH);//display PMSFH in a slidable right-sided panel
  echo json_encode($send);
} 

/*
 *  This function builds the complete PMSFH array for a given patient, including the ROS for this encounter.  
 * 
 *  It returns the PMSFH array to be used to display it anyway you like.
 *  Currently it is used to display the expanded PMSFH 3 ways: 
 *      in the Quick Pick square; 
 *      as a persistent/hideable Right Panel; 
 *      and in the Printable Report form.  
 *  For other specialties, breaking out subtypes of surgeries, meds and 
 *  medical_problems should be done here by defining new ISSUE_TYPES which are subcategories of the current
 *  ISSUE_TYPES medical_problem, surgery and medication.  This way we do not change the base install ISSUE_TYPES,
 *  we merely extend them through subcategorization, allowing the reporting features built in for MU1/2/3/100?
 *  to function at their base level.  
 *
 * @param string $pid is the patient identifier 
 * Other variables will be pulled as need from the global parameters such as $id = encounter (needed for ROS)
 * @return $PMSFH array
 */ 
function build_PMSFH($pid) {
    global $form_folder;
    global $form_id;
    global $id;
    global $ISSUE_TYPES;
    global $ISSUE_TYPE_STYLES;

     //Define the PMSFH array elements as you need them:
    $PMSFH_labels = array("POH", "PMH", "Surgery", "Medication", "Allergy", "SOCH", "FH", "ROS");

    foreach ($PMSFH_labels as $panel_type) {
        $PMSFH[$panel_type] ='';
        $subtype = " and (subtype is NULL or subtype ='' )";
        $order = "ORDER BY title";
        if ($panel_type == "FH" || $panel_type == "SOCH" || $panel_type == "ROS") {
            /* 
             *  We are going to build SocHx, FH and ROS separately below since they don't feed off of 
             *  the pre-existing ISSUE_TYPE array - so for now do nothing
             */
            continue;
        } elseif ($panel_type =='POH') {
            $focusISSUE = "medical_problem"; //openEMR ISSUE_TYPE
            $subtype=" and subtype ='eye'";
            /* This is an "eye" form: providers would like ophthalmic medical problems listed separately.
             * Thus we split the ISSUE_TYPE 'medical_problem' using subtype "eye" 
             * but it could be "GYN", "ONC", "GU" etc - for whoever wants to 
             * extend this for their own specific "sub"-lists.
             * Similarly, consider Past Ocular Surgery, or Past GYN Surgery, etc for specialty-specific
             * surgery lists.  They would be subtypes of the ISSUE_TYPE 'surgery'...
             * eg.
             *   if ($focustype =='POS') { //Past Ocular Surgery
             *   $focusISSUE = "surgery";
             *   $subtype=" and subtype ='eye'";
             *   }
             * The concept is extensible to sub lists for Allergies & Medications too.
             * eg.
             *   if ($focustype =='OncMeds') {
             *      $focusISSUE = "medication";
             *      $subtype=" and subtype ='onc'";
             *   }
             */
        } elseif ($panel_type =='PMH') {
            $focusISSUE = "medical_problem"; //openEMR ISSUE_TYPE
            $subtype=" and subtype != 'eye'";
            $PMSFH['CHRONIC'] = '';
       } elseif ($panel_type =='Surgery') {
            $focusISSUE = "surgery"; //openEMR ISSUE_TYPE
            $subtype="";
            $order = "ORDER BY begdate DESC";
        } elseif ($panel_type =='Allergy') {
            $focusISSUE = "allergy"; //openEMR ISSUE_TYPE
            $subtype="";
        } elseif ($panel_type =='Medication') {
            $focusISSUE = "medication"; //openEMR ISSUE_TYPE
            $subtype="";
        } 
        // N.B. We are just building this patient's PMSFH array.
        // How you display is up to you.
        $pres = sqlStatement("SELECT * FROM lists WHERE pid = ? AND type = ? " .
            $subtype." ".$order, array($pid,$focusISSUE) );
        $row_counter='0';
        while ($row = sqlFetchArray($pres)) {
            $rowid = $row['id'];
            $disptitle = trim($row['title']) ? $row['title'] : "[".xlt("Missing Title")."]";
            //  I don't like this [Missing Title] business.  It is from the original "issue" code.
            //  It should not happen.  I will write something to prevent this from occurring
            //  on submission, but for now it needs to stay because it is also in the original
            //  /interface/patient_file/summary code.  Both areas need to prevent a blank submission,
            //  and when fixed, remove this note.
         
            //  look up the diag codes
            $codetext = "";
            $codedesc = "";
            $codetype = "";
            $code = "";
            if ($row['diagnosis'] != "") {
                $diags = explode(";", $row['diagnosis']);
                foreach ($diags as $diag) {
                    $codedesc = lookup_code_descriptions($diag);
                    list($codetype, $code) = explode(':', $diag);
                    $codetext .= text($diag) . " (" . text($codedesc) . ")";
                }
            }

            // calculate the status
            if ($row['outcome'] == "1" && $row['enddate'] != NULL) {
              // Resolved
              $statusCompute = generate_display_field(array('data_type'=>'1','list_id'=>'outcome'), $row['outcome']);
            } else if($row['enddate'] == NULL) {
                   $statusCompute = htmlspecialchars( xl("Active") ,ENT_NOQUOTES);
            } else {
                   $statusCompute = htmlspecialchars( xl("Inactive") ,ENT_NOQUOTES);
            }
            $counter_here = count($PMSFH[$panel_type]);
            $newdata =  array (
                'title' => $disptitle,
                'status' => $statusCompute,
                'begdate' => $row['begdate'],
                'enddate' => $row['enddate'],
                'returndate' => $row['returndate'],
                'occurrence' => $row['occurrence'],
                'classification' => $row['classification'],
                'referredby' => $row['referredby'],
                'extrainfo' => $row['extrainfo'],
                'diagnosis' => $row['diagnosis'],
                'activity' => $row['activity'],
                'code' => $code,
                'codedesc' => $codedesc,
                'codetext' => $codetext,
                'codetype' => $codetype,
                'comments' => $row['comments'],
                'issue' => $row['id'],
                'rowid' => $row['id'],
                'row_type' => $row['type'],
                'row_subtype' => $row['subtype'],
                'user' => $row['user'],
                'groupname' => $row['groupname'],
                'outcome' => $row['outcome'],
                'destination' => $row['destination'],
                'reinjury_id' => $row['reinjury_id'],
                'injury_part' => $row['injury_part'],
                'injury_type' => $row['injury_type'],
                'injury_grade' => $row['injury_grade'],
                'reaction' => $row['reaction'],
                'external_allergyid' => $row['external_allergyid'],  
                'erx_source' => $row['erx_source'],
                'erx_uploaded' => $row['erx_uploaded'],
                'modifydate' => $row['modifydate'],
                'PMSFH_link' => $panel_type."_".$row_counter
            );
            //could add in short names/display names here but then they'd be in each element of the array
            //let the end user decide on display elsewhere...  This is all about the array itself.
            $PMSFH[$panel_type][] = $newdata;
            if ($row['occurrence'] =='4') $PMSFH['CHRONIC'][]=$newdata;
            $row_counter++;
        }
    }
    //Build the SocHx portion of $PMSFH for this patient.
    //$given ="coffee,tobacco,alcohol,sleep_patterns,exercise_patterns,seatbelt_use,counseling,hazardous_activities,recreational_drugs";
    $result1 = sqlQuery("select * from history_data where pid=? order by date DESC limit 0,1", array($pid) );
     
    $group_fields_query = sqlStatement("SELECT * FROM layout_options " .
    "WHERE form_id = 'HIS' AND group_name = '4Lifestyle' AND uor > 0 " .
    "ORDER BY seq");
    while ($group_fields = sqlFetchArray($group_fields_query)) {
        $titlecols  = $group_fields['titlecols'];
        $datacols   = $group_fields['datacols'];
        $data_type  = $group_fields['data_type'];
        $field_id   = $group_fields['field_id'];
        $list_id    = $group_fields['list_id'];
        $currvalue  = '';
        if ((preg_match("/^\|?0\|?\|?/", $result1[$field_id]))|| ($result1[$field_id]=='')) {
            continue;
        } else {
            $currvalue = $result1[$field_id];
        }
        if ($data_type == 28 || $data_type == 32) {
            $tmp = explode('|', $currvalue);
            switch(count($tmp)) {
                case "4": {
                    $PMSFH['SOCH'][$field_id]['resnote'] = $tmp[0];
                    $PMSFH['SOCH'][$field_id]['restype'] = $tmp[1];
                    $PMSFH['SOCH'][$field_id]['resdate'] = $tmp[2];
                    $PMSFH['SOCH'][$field_id]['reslist'] = $tmp[3];
                } break;
                case "3": {
                    $PMSFH['SOCH'][$field_id]['resnote'] = $tmp[0];
                    $PMSFH['SOCH'][$field_id]['restype'] = $tmp[1];
                    $PMSFH['SOCH'][$field_id]['resdate'] = $tmp[2];
                } break;
                case "2": {
                    $PMSFH['SOCH'][$field_id]['resnote'] = $tmp[0];
                    $PMSFH['SOCH'][$field_id]['restype'] = $tmp[1];
                    $PMSFH['SOCH'][$field_id]['resdate'] = "";
                } break;
                case "1": {
                    $PMSFH['SOCH'][$field_id]['resnote'] = $tmp[0];
                    $PMSFH['SOCH'][$field_id]['resdate'] = $PMSFH['SOCH'][$field_id]['restype'] = "";
                } break;
                default: {
                    $PMSFH['SOCH'][$field_id]['restype'] = $PMSFH['SOCH'][$field_id]['resdate'] = $PMSFH['SOCH'][$field_id]['resnote'] = "";
                } break;
            }
            $PMSFH['SOCH'][$field_id]['resnote'] = htmlspecialchars( $PMSFH['SOCH'][$field_id]['resnote'], ENT_QUOTES);
            $PMSFH['SOCH'][$field_id]['resdate'] = htmlspecialchars( $PMSFH['SOCH'][$field_id]['resdate'], ENT_QUOTES);
                //  if ($group_fields['title']) echo htmlspecialchars(xl_layout_label($group_fields['title']).":",ENT_NOQUOTES)."</b>"; else echo "&nbsp;";
                //      echo generate_display_field($group_fields, $currvalue);
        } else if ($data_type == 2) {
             $PMSFH['SOCH'][$field_id]['resnote'] = nl2br(htmlspecialchars($currvalue,ENT_NOQUOTES));
        }
        if ($PMSFH['SOCH'][$field_id]['resnote'] > '') {
            $PMSFH['SOCH'][$field_id]['display'] = substr($PMSFH['SOCH'][$field_id]['resnote'],0,10);
        } elseif ($PMSFH['SOCH'][$field_id]['restype']) {
            $PMSFH['SOCH'][$field_id]['display'] = str_replace($field_id,'',$PMSFH['SOCH'][$field_id]['restype']);
        }
        //coffee,tobacco,alcohol,sleep_patterns,exercise_patterns,seatbelt_use,counseling,hazardous_activities,recreational_drugs
        if ($field_id =="coffee") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Caffeine");
        if ($field_id =="tobacco") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Cigs");
        if ($field_id =="alcohol") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("ETOH");
        if ($field_id =="sleep_patterns") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Sleep");
        if ($field_id =="exercise_patterns") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Exercise");
        if ($field_id =="seatbelt_use") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Seatbelt");
        if ($field_id =="counseling") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Therapy");
        if ($field_id =="hazardous_activities") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Thrills");
        if ($field_id =="recreational_drugs") $PMSFH['SOCH'][$field_id]['short_title'] = xlt("Drug Use");
    }
    
    //  Drag in Marital status and Employment history to this Social Hx area, where I believe it belongs.
    $patient = getPatientData($pid, "*");
    $PMSFH['SOCH']['marital_status']['short_title']=xlt("Marital");
    $PMSFH['SOCH']['marital_status']['display']=text($patient['status']);
    $PMSFH['SOCH']['occupation']['short_title']=xlt("Occupation");
    $PMSFH['SOCH']['occupation']['display']=text($patient['occupation']);


    // Build the FH portion of $PMSFH,$PMSFH['FH']
    // history_mother  history_father  history_siblings    history_offspring   history_spouse  
    // relatives_cancer    relatives_tuberculosis  relatives_diabetes  relatives_high_blood_pressure   relatives_heart_problems    relatives_stroke    relatives_epilepsy  relatives_mental_illness    relatives_suicide
    //  There are two ways FH is stored in the history area, one on a specific relationship basis
    // ie. parent,sibling, offspring has X, or the other by "relatives_disease" basis.  
    // Hmmm, neither really meets our needs.  This is an eye form,
    // and we don't really care about most non-eye FH diseases - we do a focused family history.
    // Cataracts, glaucoma, AMD, RD, cancer, heart disease etc.  
    // The openEMR people who want to adapt this for another specialty will no doubt
    // have different diseases they want listed in the FH specifically.  We all need to be able to 
    // adjust the form.  Perhaps we should use the UserDefined fields at the end of this history_data table?
    // Question 1. is, does anything use this family history data - any higher function like reporting? 
    // Also 2., if there is an engine to validate level of exam, how do we tell it that this was completed?
    // First we would need to know the criteria this engine looks for and I don't think in reality there is anything 
    // written yet that does validate exams for coding level, so maybe we should create a flag in the user defined area of the history_data 
    // table to notate that the FH portion of the exam was completed? TBD.
    /*
    Cancer:     Tuberculosis:   
    Diabetes:       High Blood Pressure:    
    Heart Problems:     Stroke: 
    Epilepsy:       Mental Illness: 
    Suicide:    
    */
    $group_fields_query = sqlStatement("SELECT * FROM layout_options " .
    "WHERE form_id = 'HIS' AND group_name = '3Relatives' AND uor > 0 " .
    "ORDER BY seq");
    while ($group_fields = sqlFetchArray($group_fields_query)) {
        $titlecols  = $group_fields['titlecols'];
        $datacols   = $group_fields['datacols'];
        $data_type  = $group_fields['data_type'];
        $field_id   = $group_fields['field_id'];
        $list_id    = $group_fields['list_id'];
        $currvalue  = '';
        if ((preg_match("/^\|?0\|?\|?/", $result1[$field_id]))|| ($result1[$field_id]=='')) {
            continue;
        } else {
            $currvalue = $result1[$field_id];
        }
        $PMSFH['FH'][$field_id]['resnote'] = nl2br(htmlspecialchars($currvalue,ENT_NOQUOTES));
        if ($PMSFH['FH'][$field_id]['resnote'] > '') {
            $PMSFH['FH'][$field_id]['display'] = substr($PMSFH['FH'][$field_id]['resnote'],0,100);
        } elseif ($PMSFH['FH'][$field_id]['restype']) {
            $PMSFH['FH'][$field_id]['display'] = str_replace($field_id,'',$PMSFH['FH'][$field_id]['restype']);
        } else {
            $PMSFH['FH'][$field_id]['display'] = xlt("denies");
        }
        //coffee,tobacco,alcohol,sleep_patterns,exercise_patterns,seatbelt_use,counseling,hazardous_activities,recreational_drugs
        if ($field_id =="relatives_cancer") $PMSFH['FH'][$field_id]['short_title'] = xlt("Cancer");
        if ($field_id =="relatives_diabetes") $PMSFH['FH'][$field_id]['short_title'] = xlt("Diabetes");
        if ($field_id =="relatives_high_blood_pressure") $PMSFH['FH'][$field_id]['short_title'] = xlt("HTN");
        if ($field_id =="relatives_heart_problems") $PMSFH['FH'][$field_id]['short_title'] = xlt("Cor Disease");
        if ($field_id =="relatives_epilepsy") $PMSFH['FH'][$field_id]['short_title'] = xlt("Epilepsy");
        if ($field_id =="relatives_mental_illness") $PMSFH['FH'][$field_id]['short_title'] = xlt("Psych");
        if ($field_id =="relatives_suicide") $PMSFH['FH'][$field_id]['short_title'] = xlt("Suicide");
        if ($field_id =="relatives_stroke") $PMSFH['FH'][$field_id]['short_title'] = xlt("Stroke");
        if ($field_id =="relatives_tuberculosis") $PMSFH['FH'][$field_id]['short_title'] = xlt("TB");
    }
    // Now make some of our own using the usertext11-30 fields
    // These can be customized for specialties but remember this is just an array,
    // you will need to check the code re: how it is displayed elsewhere...
    // For now, just changing the short_titles will display intelligently
    // but it is best to change both in the long run.
    // $PMSFH['FH']['my_term']['display'] = (substr($result1['usertext11'],0,10));
    // $PMSFH['FH']['my_term']['short_title'] = xlt("My Term");

    $PMSFH['FH']['glaucoma']['display'] = (substr($result1['usertext11'],0,100));
    $PMSFH['FH']['glaucoma']['short_title'] = xlt("Glaucoma");
    $PMSFH['FH']['cataract']['display'] = (substr($result1['usertext12'],0,100));
    $PMSFH['FH']['cataract']['short_title'] = xlt("Cataract");
    $PMSFH['FH']['amd']['display'] = (substr($result1['usertext13'],0,100));
    $PMSFH['FH']['amd']['short_title'] = xlt("AMD");
    $PMSFH['FH']['RD']['display'] = (substr($result1['usertext14'],0,100));
    $PMSFH['FH']['RD']['short_title'] = xlt("RD");
    $PMSFH['FH']['blindness']['display'] = (substr($result1['usertext15'],0,100));
    $PMSFH['FH']['blindness']['short_title'] = xlt("Blindness");
    $PMSFH['FH']['amblyopia']['display'] = (substr($result1['usertext16'],0,100));
    $PMSFH['FH']['amblyopia']['short_title'] = xlt("Amblyopia");
    $PMSFH['FH']['strabismus']['display'] = (substr($result1['usertext17'],0,100));
    $PMSFH['FH']['strabismus']['short_title'] = xlt("Strabismus");
    $PMSFH['FH']['other']['display'] = (substr($result1['usertext18'],0,100));
    $PMSFH['FH']['other']['short_title'] = xlt("Other");
    
    // Thinking this might be a good place to put in last_retinal exam and last_HbA1C?
    // I don't know enough about the reporting parameters and each risk contract will
    // have its own unique targets so hold off for now.     
    // $PMSFH['SOCH'][$field_id]['resnote'] = nl2br(htmlspecialchars($currvalue,ENT_NOQUOTES));

    // Build ROS into $PMSFH['ROS'] also for this patient.
    // ROS is not static and is directly linked to each encounter
    // True it could be a separate table, but it is currently in form_eye_mag for each visit
    // To use this for any other forms, we should consider making this its own separate table with id,pid and encounter link,
    // just like we are doing for Impression Plan.

    //define the ROS aras to include = $given
    $given="ROSGENERAL,ROSHEENT,ROSCV,ROSPULM,ROSGI,ROSGU,ROSDERM,ROSNEURO,ROSPSYCH,ROSMUSCULO,ROSIMMUNO,ROSENDOCRINE";
    $ROS_table = "form_eye_mag";
    $query="SELECT $given from ". $ROS_table ." where id=? and pid=?";

    $ROS = sqlStatement($query,array($form_id,$pid));
    while ($row = sqlFetchArray($ROS)) {
        foreach (split(',',$given) as $item) {
            $PMSFH['ROS'][$item]['display']= $row[$item];
        }
    }
    // translator will need to translate each item in $given
    $PMSFH['ROS']['ROSGENERAL']['short_title']=xlt("GEN{{General}}");
    $PMSFH['ROS']['ROSHEENT']['short_title']=xlt("HEENT");
    $PMSFH['ROS']['ROSCV']['short_title']=xlt("CV{{Cardiovascular}}");
    $PMSFH['ROS']['ROSPULM']['short_title']=xlt("PULM{{Pulmonary}}");
    $PMSFH['ROS']['ROSGI']['short_title']=xlt("GI{{Gastrointestinal}}");
    $PMSFH['ROS']['ROSGU']['short_title']=xlt("GU{{Genitourinary}}");
    $PMSFH['ROS']['ROSDERM']['short_title']=xlt("DERM{{Dermatology}}");
    $PMSFH['ROS']['ROSNEURO']['short_title']=xlt("NEURO{{Neurology}}");
    $PMSFH['ROS']['ROSPSYCH']['short_title']=xlt("PSYCH{{Psychiatry}}");
    $PMSFH['ROS']['ROSMUSCULO']['short_title']=xlt("ORTHO{{Orthopedics}}");
    $PMSFH['ROS']['ROSIMMUNO']['short_title']=xlt("IMMUNO{{Immunology/Rheumatology}}");
    $PMSFH['ROS']['ROSENDOCRINE']['short_title']=xlt("ENDO{{Endocrine}}");

    $PMSFH['ROS']['ROSGENERAL']['title']=xlt("General");
    $PMSFH['ROS']['ROSHEENT']['title']=xlt("HEENT");
    $PMSFH['ROS']['ROSCV']['title']=xlt("Cardiovascular");
    $PMSFH['ROS']['ROSPULM']['title']=xlt("Pulmonary");
    $PMSFH['ROS']['ROSGI']['title']=xlt("GI");
    $PMSFH['ROS']['ROSGU']['title']=xlt("GU");
    $PMSFH['ROS']['ROSDERM']['title']=xlt("Dermatology");
    $PMSFH['ROS']['ROSNEURO']['title']=xlt("Neurology");
    $PMSFH['ROS']['ROSPSYCH']['title']=xlt("Pyschiatry");
    $PMSFH['ROS']['ROSMUSCULO']['title']=xlt("Musculoskeletal");
    $PMSFH['ROS']['ROSIMMUNO']['title']=xlt("Immune System");
    $PMSFH['ROS']['ROSENDOCRINE']['title']=xlt("Endocrine");

    return array($PMSFH); //yowsah!
}
/*
 *  This function uses the complete PMSFH array for a given patient, including the ROS for this encounter  
 *  and returns the PMSFH display square.
 *  @param integer rows is the number of rows you want to display
 *  @param option string view defaults to white on beige, versus original right panel of text on beige only...
 *  @param option string min_height to set min height for the row
 *  @return $display_PMSFH HTML
 */ 
function display_PMSFH($rows,$view="pending",$min_height="min-height:344px;") {
    global $PMSFH;
    global $pid;
    global $PMSFH_titles;
    if (!$PMFSH) $PMSFH = build_PMSFH($pid);
    ob_start();
    // There are two rows in our PMH section, only one in the side panel.
    // If you want it across the bottom in a panel with 8 rows?  Or other wise?
    // This should handle that too.
  
    // We are building the PMSFH panel.  
    // Let's put half in each... or try to at least.
    // Find out the number of items present now and put half in each column.
    foreach ($PMSFH[0] as $key => $value) {
        $total_PMSFH += count($PMSFH[0][$key]);
        $total_PMSFH += 2; //add two for the title and a space
        $count[$key] = count($PMSFH[0][$key]) + 1;
    }
    //SOCH, FH and ROS are listed in $PMSFH even if negative, only count positives
    foreach($PMSFH[0]['ROS'] as $key => $value) {
        if ($value['display'] =='') { 
            $total_PMSFH--;
            $count['ROS']--;
        }
    }
    foreach($PMSFH[0]['FH'] as $key => $value) {
        if ($value['display'] =='') { 
            $total_PMSFH--;
            $count['FH']--;
        }
    }
    foreach($PMSFH[0]['SOCH'] as $key => $value) {
        if ($value['display'] =='') { 
            $total_PMSFH--;
            $count['SOCH']--;
        }
    }
    $counter = "0";
    $column_max = round($total_PMSFH/$rows);
    if ($column_max < "18") $column_max ='18';
    // for testing symmetry
    // echo $column_max." - ".$total_PMSFH;
    $open_table = "<table style='margin-bottom:10px;border:1pt solid black;
    background-color: rgb(255, 248, 220); font-size:0.8em;overflow:auto;'><tr><td style='min-height:24px;min-width:1.5in;padding:5px;'>";
    $close_table = "</td></tr></table>";
    
    // $div is used when $counter reaches $column_max and a new row is needed.
    // It is used only if $row_count <= $rows, ie. $rows -1 times.
    $div = '</div>
    <div id="PMSFH_block_2" name="PMSFH_block_2" class="QP_block_outer borderShadow text_clinical" style="'.$min_height.'">';
  
    echo $header = '  
            <div id="PMSFH_block_1" name="PMSFH_block_1" class="QP_block borderShadow text_clinical" style="'.$min_height.';">
             ';
             $row_count=1;
    foreach ($PMSFH[0] as $key => $value) {     
        if ($key == "FH" || $key == "SOCH" || $key == "ROS") {
            // We are going to build SocHx, FH and ROS separately below since they are different..
            continue;
        }
        $table='';
        $header='';
        $header .='    <table style="width:1.6in;">
                <tr>
                    <td width="90%">
                        <span class="left" style="font-weight:800;font-size:0.9em;">'.$key.'</span>
                    </td>
                    <td>
                        <span class="right btn-sm" href="#PMH_anchor" onclick="alter_issue2(\'0\',\''.$key.'\',\'0\');" style="text-align:right;font-size:8px;">'. xlt("New") .'</span>
                    </td>
                </tr>
                </table>  
        ';       
        
        if ($PMSFH[0][$key] > "") {
            $index=0;
            foreach ($PMSFH[0][$key] as $item) {
                if ($key == "Allergy") {
                    if ($item['reaction']) { $reaction = " (".text($item['reaction']).")";} else { $reaction =""; }
                    $red = "style='color:red;'";
                } else { $red =''; }
                $table .= "<span $red name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
                onclick=\"alter_issue2('".$item['rowid']."','".$key."','".$index."');\">".text($item['title']).$reaction."</span><br />";
                $index++;
            }
        } else {
            if ($key == "Allergy") {
                $table .= xlt("NKDA");
            } else {
                $table .= xlt("None");
            }
            $counter++;
        }
        $display_PMSFH[$key] = $header.$open_table.$table.$close_table;
    }
    echo $display_PMSFH['POH'];
    $count = $count['POH'] + $count['PMH'] + 4;
    if ($count > $column_max) echo $div.$header1;
    echo $display_PMSFH['PMH'];
    $count = $count + $count['Surgery'] +  4;
    if (($count > $column_max) && ($row_count < $rows)) { echo $div; $count=0; $row_count =2;}
    echo $display_PMSFH['Surgery'];
    
    $count = $count + $count['Medication'] + 4;
    if (($count > $column_max) && ($row_count < $rows)) { echo $div; $count=0; $row_count =2;}
    echo $display_PMSFH['Medication'];
    
    $count = $count + $count['Allergy'] + 4;
    if (($count > $column_max) && ($row_count < $rows)) { echo $div; $count=0; $row_count =2;}
    echo $display_PMSFH['Allergy'];

    $count = $count + $count['FH'] + 4;
    if (($count > $column_max) && ($row_count < $rows)) { echo $div; $count=0; $row_count =2;}
     ?>
        <table style="width:1.6in;">
                <tr>
                    <td width="90%">
                        <span class="left" style="font-weight:800;font-size:0.9em;"><?php echo xlt("FH{{Family History}}"); ?></span>
                    </td>
                    <td >
                        <span class="right btn-sm" href="#PMH_anchor" onclick="alter_issue2('0','FH','');" style="text-align:right;font-size:8px;"><?php echo xlt("New"); ?></span>
                    </td>
                </tr>
        </table>
        <?php
                echo $open_table;
                $mentions_FH='';
                if (count($PMSFH[0]['FH']) > 0) {
                
                    foreach ($PMSFH[0]['FH'] as $item) {
                        if (($counter > $column_max) && ($row_count < $rows)) {echo $close_table.$div.$open_table; $counter="0";$row_count++;} 
                        if ($item['display'] > '') {
                            $counter++;
                            echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
                            onclick=\"alter_issue2('0','FH','');\">".xlt($item['short_title']).": ".text($item['display'])."</span><br />";
                            $mentions_FH++;
                        }
                    }
                } 
                if ($mentions_FH < '1') {
                        ?>
                        <span href="#PMH_anchor" 
                        onclick="alter_issue2('0','FH','');" style="text-align:right;"><?php echo xlt("Negative"); ?></span><br />
                        <?php                    
                        $counter = $counter+3;
                }
                echo $close_table;
         
                $count = $count + $count['SOCH'] + 4;
                if (($count > $column_max) && ($row_count < $rows)) { echo $div; $count=0; $row_count =2;}
                  
                    ?>
                <table style="width:1.6in;">
                <tr>
                    <td width="90%">
                        <span class="left" style="font-weight:800;font-size:0.9em;"><?php echo xlt("Social"); ?></span>
                    </td>
                    <td >
                        <span class="right btn-sm" href="#PMH_anchor" onclick="alter_issue2('0','SOCH','');" style="text-align:right;font-size:8px;"><?php echo xlt("New"); ?></span>
                    </td>
                </tr>
                </table>
                <?php
                    echo $open_table;
                    foreach ($PMSFH[0]['SOCH'] as $item) {
                            if (($counter > $column_max) && ($row_count < $rows)) {echo $close_table.$div.$open_table; $counter="0";$row_count++;} 
                            
                            if ($item['short_title'] > '') {
                                echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
                                onclick=\"alter_issue2('0','SOCH','');\">".xlt($item['short_title']).": ".text($item['display'])."</span><br />";
                                $counter++;
                                $mentions_SOCH++;
                            }
                    }
                    if (!$mentions_SOCH)  {
                        ?>
                        <span href="#PMH_anchor" 
                        onclick="alter_issue2('0','SOCH','');" style="text-align:right;"><?php echo xlt("Not documented"); ?></span><br />
                        <?php      
                        $counter=$counter+2;
                        
                    }
                    echo $close_table;
                    
                    $count = $count + $count['ROS'] + 4;
                    if (($count > $column_max) && ($row_count < $rows)) { echo $div; $count=0; $row_count =2;}
   
                           ?>
            
            <table style="width:1.6in;">
                <tr>
                    <td width="90%">
                        <span class="left" style="font-weight:800;font-size:0.9em;"><?php echo xlt("ROS{{Review of Systems}}"); ?></span>
                    </td>
                    <td >
                        <span class="right btn-sm" href="#PMH_anchor" onclick="alter_issue2('0','ROS','');" style="text-align:right;font-size:8px;"><?php echo xlt("New"); ?></span>
                    </td>
                </tr>
            </table>               
            <?php
                    echo $open_table;
                    foreach ($PMSFH[0]['ROS'] as $item) {
                        if ($item['display'] > '') {
                            if (($counter > $column_max)&& ($row_count < $rows)) {echo $close_table.$div.$open_table; $counter="0";$row_count++;} 
                          
                            //xlt($item['short_title']) - for a list of short_titles, see the predefined ROS categories
                            echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
                             onclick=\"alter_issue2('0','ROS','');\">".xlt($item['short_title']).": ".text($item['display'])."</span><br />";
                            $mention++;
                            $counter++;
                        }
                    }
                    if ($mention < 1) {
                        echo  xlt("Negative") ."<br />";
                        $counter=$counter++;
                    }
                    echo $close_table;
                
                    ?>
        </div>
            <?php
    $PMH_panel = ob_get_contents();
    ob_end_clean();
    return $PMH_panel;
}

function PMSFH_json($PMSFH) {
    echo json_encode($PMSFH[0]);
    return;
}

/**
 *  This function uses the complete PMSFH array for a given patient, including the ROS for this encounter  
 *  and returns the PMSFH/ROS slideable Right Panel
 *
 * @param array $PMSFH 
 * @return $right_panel html
 */ 
function show_PMSFH_panel($PMSFH,$columns='1') {
    ob_start();
    echo '<div style="font-size:1.2em;padding:25 2 2 5;z-index:1;">
    <div>';

    //<!-- POH -->
    echo "<br /><span class='panel_title'>".xlt("POH").":</span>";
    ?>
    <span class="top-right btn-sm" href="#PMH_anchor" 
        onclick="alter_issue2('0','POH','');" 
        style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?></span>
    <br />
    <?php
    if ($PMSFH[0]['POH'] > "") {
        $i=0;
        foreach ($PMSFH[0]['POH'] as $item) {
            echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
            onclick=\"alter_issue2('".$item['rowid']."','POH','$i');\">".text($item['title'])."</span><br />";
            $i++;
        }
    } else { ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','POH','');" style="text-align:right;"><?php echo xlt("None"); ?><br /></span>
        <?php       
    }
    //<!-- PMH -->
    echo "<br /> <span class='panel_title'>".xlt("PMH").":</span>";
    ?><span class="top-right btn-sm" href="#PMH_anchor" 
    onclick="alter_issue2('0','PMH','');" style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?></span>
    <br />
    <?php
    if ($PMSFH[0]['PMH'] > "") {
        $i=0;
        foreach ($PMSFH[0]['PMH'] as $item) {
            echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
            onclick=\"alter_issue2('".$item['rowid']."','PMH','$i');\">".text($item['title'])."</span><br />";
            $i++;
        }
    } else { ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','PMH','');" style="text-align:right;"><?php echo xlt("None"); ?></br></span>
        <?php       
    }
      
    //<!-- Surgeries -->
    echo "<br /><span class='panel_title'>".xlt("Surgery").":</span>";
    ?><span class="top-right btn-sm" href="#PMH_anchor" 
    onclick="alter_issue2('0','Surgery','');" style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?></span>
    <br />
    <?php
    if ($PMSFH[0]['Surgery'] > "") {
        $i=0;
        foreach ($PMSFH[0]['Surgery'] as $item) {
        echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
            onclick=\"alter_issue2('".$item['rowid']."','Surgery','$i');\">".text($item['title'])."<br /></span>";
        $i++;
        }
    } else { ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','Surgery','');" style="text-align:right;"><?php echo xlt("None"); ?><br /></span>
        <?php       
    }

    //<!-- Meds -->
    echo "<br /><span class='panel_title'>".xlt("Medication").":</span>";
    ?><span class="top-right btn-sm" href="#PMH_anchor" 
    onclick="alter_issue2('0','Medication','');" style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?></span>
    <br />
    <?php
    if ($PMSFH[0]['Medication'] > "") {
        $i=0;
        foreach ($PMSFH[0]['Medication'] as $item) {
            echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
            onclick=\"alter_issue2('".$item['rowid']."','Medication','$i');\">".text($item['title'])."</span><br />";
            $i++;
        }
    } else { ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','Medication','');" style="text-align:right;"><?php echo xlt("None"); ?><br /></span>
        <?php       
    }


    //<!-- Allergies -->
    echo "<br /><span class='panel_title'>".xlt("Allergy").":</span>";
    ?><span class="top-right btn-sm" href="#PMH_anchor" 
    onclick="alter_issue2('0','Allergy','');" style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?></span>
    <br />
    <?php
    if ($PMSFH[0]['Allergy'] > "") {
        $i=0;
        foreach ($PMSFH[0]['Allergy'] as $item) {
            if ($item['reaction']) {
                $reaction = "(".text($item['reaction']).")";
            } else { $reaction =""; }
      echo "<span ok style='color:red;' name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
      onclick=\"alter_issue2('".$item['rowid']."','Allergy','$i');\">".text($item['title'])." ".$reaction."</span><br />";
      $i++;
        } 
    } else { ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','Allergy','');" style="text-align:right;"><?php echo xlt("NKDA{{No Known Drug Allergies}}"); ?><br /></span>
        <?php       
    }
      
       //<!-- Social History -->
    echo "<br /><span class='panel_title'>Soc Hx:</span>";
    ?><span class="top-right btn-sm" href="#PMH_anchor" 
    onclick="alter_issue2('0','SOCH','');" style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?>
    </span><br />
    <?php
    foreach ($PMSFH[0]['SOCH'] as $k => $item) {
        if ($item['display']) {
        echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
        onclick=\"alter_issue2('0','SOCH','');\">".xlt($item['short_title']).": ".text($item['display'])."<br /></span>";
        
        $mention_SOCH++;
        }
    }
    if (!$mention_SOCH) {
        ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','SOCH','');" style="text-align:right;"><?php echo xlt("Negative"); ?><br /></span>
        <?php         }

    //<!-- Family History -->
    echo "<br /><span class='panel_title'>".xlt("FH{{Family History}}").":</span>";
    ?><span class="top-right btn-sm" href="#PMH_anchor" 
    onclick="alter_issue2('0','FH','');" style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?></span><br />

    <?php
    if (count($PMSFH[0]['FH']) > 0) {
        foreach ($PMSFH[0]['FH'] as $item) {
            if ($item['display'] > '') {
                echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
                onclick=\"alter_issue2('0','FH','');\">".xlt($item['short_title']).": ".text($item['display'])."<br /></span>";
                $mention_FH++;
            }
        }
    }
    if (!$mention_FH) {
        ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','FH','');" style="text-align:right;"><?php echo xlt("Negative"); ?><br /></span>
        <?php         
    }
    echo "<br /><span class='panel_title'>".xlt("ROS").":</span>";
    ?><span class="top-right btn-sm" href="#PMH_anchor" 
    onclick="alter_issue('0','ROS','');" style="text-align:right;font-size:8px;"><?php echo xlt("Add"); ?></span>
    <br />
    <?php
    foreach ($PMSFH[0]['ROS'] as $item) {
        if ($item['display']) {
            echo "<span name='QP_PMH_".$item['rowid']."' href='#PMH_anchor' id='QP_PMH_".$item['rowid']."' 
            onclick=\"alter_issue2('0','ROS','');\">".text($item['short_title']).": ".text($item['display'])."</span><br />";
            $mention_ROS++;
        }
    }
   
    if (!$mention_ROS) { ?>
        <span href="#PMH_anchor" 
        onclick="alter_issue2('0','ROS','');" style="text-align:right;"><?php echo xlt('Negative'); ?><br /></span>
        <?php         
    }
        $right_panel = ob_get_contents();

    ob_end_clean();
    return $right_panel;
}

/**
 *  This function displays via echo the PMSFH/ROS in the report
 *
 * @param array $PMSFH 
 * 
 */ 
function show_PMSFH_report($PMSFH) {
    global $pid;
    global $ISSUE_TYPES;

    //4 panels
    $rows = '4';
    if (!$PMFSH) $PMSFH = build_PMSFH($pid);
    // Find out the number of items present now and put 1/4 in each column.
    foreach ($PMSFH[0] as $key => $value) {
        $total_PMSFH += count($PMSFH[0][$key]);
        $total_PMSFH += 2; //add two for the title and a space
        $count[$key] = count($PMSFH[0][$key]) + 1;
    }
    //SOCH, FH and ROS are listed in $PMSFH even if negative, only count positives
    foreach($PMSFH[0]['ROS'] as $key => $value) {
        if ($value['display'] =='') { 
            $total_PMSFH--;
            $count['ROS']--;
        }
    }
    foreach($PMSFH[0]['FH'] as $key => $value) {
        if ($value['display'] =='') { 
            $total_PMSFH--;
            $count['FH']--;
        }
    }
    foreach($PMSFH[0]['SOCH'] as $key => $value) {
        if ($value['display'] =='') { 
            $total_PMSFH--;
            $count['SOCH']--;
        }
    }
    $counter = "0";
    $column_max = round($total_PMSFH/$rows);
    $panel_size = round($total_PMSFH/$rows) -1;
    ?>        

    <?php
    //<!-- POH -->
    $counter++;
    echo "<table style='width:700px;'><tr><td style='vertical-align:top;width:150px;' class='show_report'><br /><b>".xlt("POH").":</b>";
    //note the HTML2PDF does not like <span style="font-weight:bold;"></span> so we are using the deprecated <b></b>
    ?>
    <br />
    <?php
    if ($PMSFH[0]['POH'] > "") {
    foreach ($PMSFH[0]['POH'] as $item) {
        echo text($item['title'])." ".text($item['diagnosis'])."<br />";
        $counter++;
    }
    } else {
    echo xlt("None")."<br />";
    }

    if (($counter + $count['PMH']) > $panel_size) { echo "</td><td class='show_report' style='vertical-align:top;width:150px;'>"; $counter ="0"; } 
    $counter++;
    //<!-- PMH -->
    echo "<br /><b>".xlt("PMH").":</b>";
    ?>
    <br />
    <?php
    if ($PMSFH[0]['PMH'] > "") {
      foreach ($PMSFH[0]['PMH'] as $item) {
        echo text($item['title'])." ".text($item['diagnosis'])."<br />";
        $counter++;
      }
    } else {
    echo xlt("None")."<br />";
    }
    
    if ($counter + $count['Medication'] > $panel_size) { echo "</td><td class='show_report' style='vertical-align:top;width:150px;'>"; $counter ="0"; } 
     $counter++;
    //<!-- Meds -->
    echo "<br /><b>".xlt("Medication").":</b>";
    ?>
    <br />
    <?php
    if ($PMSFH[0]['Medication'] > "") {
            foreach ($PMSFH[0]['Medication'] as $item) {
            echo text($item['title'])." ".text($item['diagnosis'])."<br />";
            $counter++;
        }
    } else {
        echo xlt("None")."<br />";
    }

    if ($counter + $count['Surgery'] > $panel_size) { echo "</td><td class='show_report' style='vertical-align:top;width:150px;'>"; $counter ="0"; } 
    //<!-- Surgeries -->
    $counter++;
    echo "<br /><b>".xlt("Surgery").":</b>";
    ?><br />
    <?php
    if ($PMSFH[0]['Surgery'] > "") {
      foreach ($PMSFH[0]['Surgery'] as $item) {
            echo text($item['title'])." ".text($item['diagnosis'])."<br />";
            $counter++;
        }
    } else { 
    echo xlt("None")."<br />";
    }

    if ($counter + $count['Allergy'] > $panel_size) { echo "</td><td class='show_report' style='vertical-align:top;width:150px;'>"; $counter ="0"; } 
    $counter++;
    //<!-- Allergies -->
    echo "<br /><b>".xlt("Allergy").":</b>";
    ?>
    <br />
    <?php
    if ($PMSFH[0]['Allergy'] > "") {
      foreach ($PMSFH[0]['Allergy'] as $item) {
            echo text($item['title'])."<br />";
            $counter++;
        } 
    } else { 
    echo xlt("NKDA")."<br />";
    }

    if ($counter + $count['SOCH'] > $panel_size) { echo "</td><td class='show_report' style='vertical-align:top;width:150px;'>"; $counter ="0"; } 
    $counter++;
    //<!-- SocHx -->
    echo "<br /><b>".xlt("Soc Hx{{Social History}}").":</b>";
    ?>
    <br />
    <?php
    foreach ($PMSFH[0]['SOCH'] as $k => $item) {
        if ($item['display']) {
            echo xlt($item['short_title']).": ".text($item['display'])."<br />";
            $mention_PSOCH++;
            $counter++;
        }
    }
    if (!$mention_PSOCH) {
    echo xlt("Negative")."<br />";
    }

    if (($counter + $count['FH']) > $panel_size) { echo "</td><td class='show_report' style='vertical-align:top;width:150px;'>"; $counter ="0"; } 
    $counter++;
    //<!-- FH -->
    echo "<br /><b>".xlt("FH{{Family History}}").":</b>";
    ?>
    <br />
    <?php
    foreach ($PMSFH[0]['FH'] as $item) {
        if ($item['display']) {
            echo xlt($item['short_title']).": ".text($item['display'])."<br />";
            $mention_FH++;
            $counter++;
        }
    }
    if (!$mention_FH) {
        echo xlt("Negative")."<br />";
    }

    if (($counter!=="0") && (($counter + $count['ROS']) > $panel_size)) { echo "</td><td class='show_report' style='vertical-align:top;width:150px;'>"; $counter ="0"; } 
    $counter++;
    //<!-- ROS -->
    echo "<br /><b>".xlt("ROS{{Review of Systems}}").":</b>";
    ?><br />
    <?php
    foreach ($PMSFH[0]['ROS'] as $item) {
        if ($item['display']) {
            echo $item['short_title'].": ".$item['display']."<br />";
            $mention_ROS++;
            $counter++;
        }
    }
    if ($mention_ROS < '1') {
        echo xlt("Negative");
    }    
    echo "</td></tr></table>";
}

/**
 *  This function returns display the draw/sketch diagram for a zone (4 input values)
 * 
 *  If there is already a drawing for this zone in this encounter, it is pulled from
 *  from its stored location:
 *  $GLOBALS['web_root']."/sites/".$_SESSION['site_id']."/".$form_folder."/".$pid."/".$encounter."/".$side."_".$zone."_VIEW.png?".rand();
 *  
 *  Otherwise a "BASE" image is pulled from the images directory of the form...  Customizable.
 *
 * @param string $zone options ALL,EXT,ANTSEG,RETINA,NEURO 
 * @param string $visit_date Future functionality to limit result set. UTC DATE Formatted 
 * @param string $pid value = patient id
 * @param string OU by default.  Future functionality will allow OD and OS values- not implemented yet.
 * @return true : when called directly outputs the ZONE specific HTML5 CANVAS widget 
 */ 
function display_draw_section($zone,$encounter,$pid,$side ='OU',$counter='') {
    global $form_folder;
    $filepath = $GLOBALS['oer_config']['documents']['repository'] . $pid ."/";
    $base_name = $pid."_".$encounter."_".$side."_".$zone."_VIEW";
    $file_history =  $filepath.$base_name;
    $file_store= $file_history.".jpg";
    ?>
    <div id="Draw_<?php echo attr($zone); ?>" name="Draw_<?php echo attr($zone); ?>" style="text-align:center;height: 2.5in;" class="Draw_class canvas">
        <span class="fa fa-file-text-o closeButton" id="BUTTON_TEXT_<?php echo attr($zone); ?>" name="BUTTON_TEXT_<?php echo attr($zone); ?>"></span>
        <i class="closeButton_2 fa fa-database" id="BUTTON_QP_<?php echo attr($zone); ?>_2" name="BUTTON_QP_<?php echo attr($zone); ?>"></i>
        <i class="closeButton_3 fa fa-user-md fa-sm fa-2" name="Shorthand_kb" title="<?php echo xla("Open the Shorthand Window and display Shorthand Codes"); ?>"></i>
                    
        <?php  
            /* This will provide a way to scroll back through prior VISIT images, to copy forward to today's visit,
             * just like we do in the text fields.
             * Will need to do a lot of thinking to create this.  Jist is ajax call to server for image retrieval.
             * To get this to work we need a way to select an old image to work from, use current or return to baseline.
             * This will require a global BACK button like above (BUTTON_BACK_<?php echo attr($zone); ?>). 
             * The Undo Redo buttons are currently javascript client side.
             * The Undo Redo features will only work for changes made since form was loaded locally.
             
             * If we want to look back at a prior VISITs saved final images, 
             * we will need to create this logic.
             * Need to think about how to display this visually so it's intuitive, without cluttering the page...
             * At first glance, using the text PRIORS selection method should work...  Not yet.
             */
        //$output = priors_select($zone,$orig_id,$id_to_show,$pid); echo $output; 
        ?>  
        <div class="tools" style="text-align:center;width:100%;">
            <img id="sketch_tools_<?php echo attr($zone); ?>" onclick='$("#selColor_<?php echo $zone; ?>").val("blue");' src="../../forms/<?php echo $form_folder; ?>/images/pencil_blue.png" style="height:30px;width:15px;">
            <img id="sketch_tools_<?php echo attr($zone); ?>" onclick='$("#selColor_<?php echo $zone; ?>").val("#ff0");'  src="../../forms/<?php echo $form_folder; ?>/images/pencil_yellow.png" style="height:30px;width:15px;">
            <img id="sketch_tools_<?php echo attr($zone); ?>" onclick='$("#selColor_<?php echo $zone; ?>").val("#ffad00");' src="../../forms/<?php echo $form_folder; ?>/images/pencil_orange.png" style="height:30px;width:15px;">
            <img id="sketch_tools_<?php echo attr($zone); ?>" onclick='$("#selColor_<?php echo $zone; ?>").val("#AC8359");' src="../../forms/<?php echo $form_folder; ?>/images/pencil_brown.png" style="height:30px;width:15px;">
            <img id="sketch_tools_<?php echo attr($zone); ?>" onclick='$("#selColor_<?php echo $zone; ?>").val("red");' src="../../forms/<?php echo $form_folder; ?>/images/pencil_red.png" style="height:30px;width:15px;">
            <img id="sketch_tools_<?php echo attr($zone); ?>" onclick='$("#selColor_<?php echo $zone; ?>").val("#000");' src="../../forms/<?php echo $form_folder; ?>/images/pencil_black.png" style="height:50px;width:15px;">
            <img id="sketch_tools_<?php echo attr($zone); ?>" onclick='$("#selColor_<?php echo $zone; ?>").val("#fff");' src="../../forms/<?php echo $form_folder; ?>/images/pencil_white.png" style="height:30px;width:15px;">
             
            <span style="min-width:1in;">&nbsp;</span>
            <!-- now to pencil size -->
            <img id="sketch_sizes_<?php echo attr($zone); ?>" onclick='$("#selWidth_<?php echo $zone; ?>").val("1");' src="../../forms/<?php echo $form_folder; ?>/images/brush_1.png" style="height:20px;width:20px; border-bottom: 2pt solid black;">
            <img id="sketch_sizes_<?php echo attr($zone); ?>" onclick='$("#selWidth_<?php echo $zone; ?>").val("3");' src="../../forms/<?php echo $form_folder; ?>/images/brush_3.png" style="height:20px;width:20px;">
            <img id="sketch_sizes_<?php echo attr($zone); ?>" onclick='$("#selWidth_<?php echo $zone; ?>").val("5");' src="../../forms/<?php echo $form_folder; ?>/images/brush_5.png" style="height:20px;width:20px;">
            <img id="sketch_sizes_<?php echo attr($zone); ?>" onclick='$("#selWidth_<?php echo $zone; ?>").val("10");' src="../../forms/<?php echo $form_folder; ?>/images/brush_10.png" style="height:20px;width:20px;">
            <img id="sketch_sizes_<?php echo attr($zone); ?>" onclick='$("#selWidth_<?php echo $zone; ?>").val("15");' src="../../forms/<?php echo $form_folder; ?>/images/brush_15.png" style="height:20px;width:20px;">
        </div>
        
        <?php 
        $sql = "SELECT * from documents where url like '%".$base_name."%'";
            $doc = sqlQuery($sql);
            // random to not pull from cache.
            if (file_exists($file_store) && ($doc['id'] > '0')) {
                $filetoshow = $GLOBALS['web_root']."/controller.php?document&retrieve&patient_id=$pid&document_id=".$doc['id']."&as_file=false&blahblah=".rand();
            } else {
                //base image. 
                $filetoshow = $GLOBALS['web_root']."/interface/forms/".$form_folder."/images/".$side."_".$zone."_BASE.jpg"; 
            }
        ?>
        <input type="hidden" id="url_<?php echo attr($zone); ?>" name="url_<?php echo attr($zone); ?>" value="<?php echo $filetoshow; ?>">
       
        <div align="center" class="borderShadow">
            <canvas id="myCanvas_<?php echo $zone; ?>" name="myCanvas_<?php echo $zone; ?>" width="400" height="225"></canvas>
        </div>
        <input type="hidden" id="selWidth_<?php echo $zone; ?>" value="1">
        <input type="hidden" id="selColor_<?php echo $zone; ?>" value="#000">
        <div style="margin-top: 7px;">
            <button onclick="javascript:cUndo('<?php echo $zone; ?>');return false;" id="Undo_Canvas_<?php echo $zone; ?>"><?php echo xlt("Undo"); ?></button>
            <button onclick="javascript:cRedo('<?php echo $zone; ?>');return false;" id="Redo_Canvas_<?php echo $zone; ?>"><?php echo xlt("Redo"); ?></button>
            <button onclick="javascript:drawImage('<?php echo $zone; ?>');return false;" id="Clear_Canvas_<?php echo $zone; ?>"><?php echo xlt("Clear"); ?></button>
            <!-- <button onclick="return false;" id="Base_Canvas_<?php echo $zone; ?>">Change Base</button> -->       
        </div>
        <br />
    </div>
    <?php
}

/**
 *  This function returns HTML to replace a requested section with copy_forward values (3 input values)
 *  It will also replace the drawings if ALL is selected
 *  
 * @param string $zone options ALL,EXT,ANTSEG,RETINA,NEURO, EXT_DRAW, ANTSEG_DRAW, RETINA_DRAW, NEURO_DRAW 
 * @param string $form_id is the form_eye_mag.id where the data to carry forward is located
 * @param string $pid value = patient id
 * @return true : when called directly outputs the ZONE specific HTML for a prior record + widget for the desired zone 
 */ 
function copy_forward($zone,$copy_from,$copy_to,$pid) {
    $query="select form_encounter.date as encounter_date,form_eye_mag.* from form_eye_mag ,forms,form_encounter 
                where 
                form_encounter.encounter = forms.encounter and 
                form_eye_mag.id=forms.form_id and
                forms.pid =form_eye_mag.pid and 
                form_eye_mag.pid=? 
                and form_eye_mag.id =? ";        

    $objQuery =sqlQuery($query,array($pid,$copy_from));
    if ($zone =="EXT") {
        $result['RUL']=$objQuery['RUL'];
        $result['LUL']=$objQuery['LUL'];
        $result['RLL']=$objQuery['RLL'];
        $result['LLL']=$objQuery['LLL'];
        $result['RBROW']=$objQuery['RBROW'];
        $result['LBROW']=$objQuery['LBROW'];
        $result['RMCT']=$objQuery['RMCT'];
        $result['LMCT']=$objQuery['LMCT'];
        $result['RADNEXA']=$objQuery['RADNEXA'];
        $result['LADNEXA']=$objQuery['LADNEXA'];
        $result['RMRD']=$objQuery['RMRD'];
        $result['LMRD']=$objQuery['LMRD'];
        $result['RLF']=$objQuery['RLF'];
        $result['LLF']=$objQuery['LLF'];
        $result['RVFISSURE']=$objQuery['RVFISSURE'];
        $result['LVFISSURE']=$objQuery['LVFISSURE'];
        $result['RCAROTID']=$objQuery['RCAROTID'];
        $result['LCAROTID']=$objQuery['LCAROTID'];
        $result['RTEMPART']=$objQuery['RTEMPART'];
        $result['LTEMPART']=$objQuery['LTEMPART'];
        $result['RCNV']=$objQuery['RCNV'];
        $result['LCNV']=$objQuery['LCNV'];
        $result['RCNVII']=$objQuery['RCNVII'];
        $result['LCNVII']=$objQuery['LCNVII'];
        $result['ODSCHIRMER1']=$objQuery['ODSCHIRMER1'];
        $result['OSSCHIRMER1']=$objQuery['OSSCHIRMER1'];
        $result['ODSCHIRMER2']=$objQuery['ODSCHIRMER2'];
        $result['OSSCHIRMER2']=$objQuery['OSSCHIRMER2'];
        $result['ODTBUT']=$objQuery['ODTBUT'];
        $result['OSTBUT']=$objQuery['OSTBUT'];
        $result['OSHERTEL']=$objQuery['OSHERTEL'];
        $result['HERTELBASE']=$objQuery['HERTELBASE'];
        $result['ODPIC']=$objQuery['ODPIC'];
        $result['OSPIC']=$objQuery['OSPIC'];
        $result['EXT_COMMENTS']=$objQuery['EXT_COMMENTS'];
        $result["json"] = json_encode($result);
        echo json_encode($result); 
    } elseif ($zone =="ANTSEG") {
        $result['OSCONJ']=$objQuery['OSCONJ'];
        $result['ODCONJ']=$objQuery['ODCONJ'];
        $result['ODCORNEA']=$objQuery['ODCORNEA'];
        $result['OSCORNEA']=$objQuery['OSCORNEA'];
        $result['ODAC']=$objQuery['ODAC'];
        $result['OSAC']=$objQuery['OSAC'];
        $result['ODLENS']=$objQuery['ODLENS'];
        $result['OSLENS']=$objQuery['OSLENS'];
        $result['ODIRIS']=$objQuery['ODIRIS'];
        $result['OSIRIS']=$objQuery['OSIRIS'];
        $result['ODKTHICKNESS']=$objQuery['ODKTHICKNESS'];
        $result['OSKTHICKNESS']=$objQuery['OSKTHICKNESS'];
        $result['ODGONIO']=$objQuery['ODGONIO'];
        $result['OSGONIO']=$objQuery['OSGONIO'];
        $result['ODSHRIMER1']=$objQuery['ODSHIRMER1'];
        $result['OSSHRIMER1']=$objQuery['OSSHIRMER1'];
        $result['ODSHRIMER2']=$objQuery['ODSHIRMER2'];
        $result['OSSHRIMER2']=$objQuery['OSSHIRMER2'];
        $result['ODTBUT']=$objQuery['ODTBUT'];
        $result['OSTBUT']=$objQuery['OSTBUT'];
        $result['ANTSEG_COMMENTS']=$objQuery['ANTSEG_COMMENTS'];
        $result["json"] = json_encode($result);
        echo json_encode($result); 
    } elseif ($zone =="RETINA") {
        $result['ODDISC']=$objQuery['ODDISC'];
        $result['OSDISC']=$objQuery['OSDISC'];
        $result['ODCUP']=$objQuery['ODCUP'];
        $result['OSCUP']=$objQuery['OSCUP'];
        $result['ODMACULA']=$objQuery['ODMACULA'];
        $result['OSMACULA']=$objQuery['OSMACULA'];
        $result['ODVESSELS']=$objQuery['ODVESSELS'];
        $result['OSVESSELS']=$objQuery['OSVESSELS'];
        $result['ODPERIPH']=$objQuery['ODPERIPH'];
        $result['OSPERIPH']=$objQuery['OSPERIPH'];
        $result['ODDRAWING']=$objQuery['ODDRAWING'];
        $result['OSDRAWING']=$objQuery['OSDRAWING'];
        $result['ODCMT']=$objQuery['ODCMT'];
        $result['OSCMT']=$objQuery['OSCMT'];
        $result['RETINA_COMMENTS']=$objQuery['RETINA_COMMENTS'];
        $result["json"] = json_encode($result);
        echo json_encode($result); 
    } elseif ($zone =="NEURO") {
        $result['ACT']=$objQuery['ACT'];
        $result['ACT5CCDIST']=$objQuery['ACT5CCDIST'];
        $result['ACT1CCDIST']=$objQuery['ACT1CCDIST'];
        $result['ACT2CCDIST']=$objQuery['ACT2CCDIST'];
        $result['ACT3CCDIST']=$objQuery['ACT3CCDIST'];
        $result['ACT4CCDIST']=$objQuery['ACT4CCDIST'];
        $result['ACT6CCDIST']=$objQuery['ACT6CCDIST'];
        $result['ACT7CCDIST']=$objQuery['ACT7CCDIST'];
        $result['ACT8CCDIST']=$objQuery['ACT8CCDIST'];
        $result['ACT9CCDIST']=$objQuery['ACT9CCDIST'];
        $result['ACT10CCDIST']=$objQuery['ACT10CCDIST'];
        $result['ACT11CCDIST']=$objQuery['ACT11CCDIST'];
        $result['ACT1SCDIST']=$objQuery['ACT1SCDIST'];
        $result['ACT2SCDIST']=$objQuery['ACT2SCDIST'];
        $result['ACT3SCDIST']=$objQuery['ACT3SCDIST'];
        $result['ACT4SCDIST']=$objQuery['ACT4SCDIST'];
        $result['ACT5SCDIST']=$objQuery['ACT5SCDIST'];
        $result['ACT6SCDIST']=$objQuery['ACT6SCDIST'];
        $result['ACT7SCDIST']=$objQuery['ACT7SCDIST'];
        $result['ACT8SCDIST']=$objQuery['ACT8SCDIST'];
        $result['ACT9SCDIST']=$objQuery['ACT9SCDIST'];
        $result['ACT10SCDIST']=$objQuery['ACT10SCDIST'];
        $result['ACT11SCDIST']=$objQuery['ACT11SCDIST'];
        $result['ACT1SCNEAR']=$objQuery['ACT1SCNEAR'];
        $result['ACT2SCNEAR']=$objQuery['ACT2SCNEAR'];
        $result['ACT3SCNEAR']=$objQuery['ACT3SCNEAR'];
        $result['ACT4SCNEAR']=$objQuery['ACT4SCNEAR'];
        $result['ACT5CCNEAR']=$objQuery['ACT5CCNEAR'];
        $result['ACT6CCNEAR']=$objQuery['ACT6CCNEAR'];
        $result['ACT7CCNEAR']=$objQuery['ACT7CCNEAR'];
        $result['ACT8CCNEAR']=$objQuery['ACT8CCNEAR'];
        $result['ACT9CCNEAR']=$objQuery['ACT9CCNEAR'];
        $result['ACT10CCNEAR']=$objQuery['ACT10CCNEAR'];
        $result['ACT11CCNEAR']=$objQuery['ACT11CCNEAR'];
        $result['ACT5SCNEAR']=$objQuery['ACT5SCNEAR'];
        $result['ACT6SCNEAR']=$objQuery['ACT6SCNEAR'];
        $result['ACT7SCNEAR']=$objQuery['ACT7SCNEAR'];
        $result['ACT8SCNEAR']=$objQuery['ACT8SCNEAR'];
        $result['ACT9SCNEAR']=$objQuery['ACT9SCNEAR'];
        $result['ACT10SCNEAR']=$objQuery['ACT10SCNEAR'];
        $result['ACT11SCNEAR']=$objQuery['ACT11SCNEAR'];
        $result['ACT1CCNEAR']=$objQuery['ACT1CCNEAR'];
        $result['ACT2CCNEAR']=$objQuery['ACT2CCNEAR'];
        $result['ACT3CCNEAR']=$objQuery['ACT3CCNEAR'];
        $result['ACT4CCNEAR']=$objQuery['ACT4CCNEAR'];
        $result['ODVF1']=$objQuery['ODVF1'];
        $result['ODVF2']=$objQuery['ODVF2'];
        $result['ODVF3']=$objQuery['ODVF3'];
        $result['ODVF4']=$objQuery['ODVF4'];
        $result['OSVF1']=$objQuery['OSVF1'];
        $result['OSVF2']=$objQuery['OSVF2'];
        $result['OSVF3']=$objQuery['OSVF3'];
        $result['OSVF4']=$objQuery['OSVF4'];
        $result['MOTILITY_RS']=$objQuery['MOTILITY_RS'];
        $result['MOTILITY_RI']=$objQuery['MOTILITY_RI'];
        $result['MOTILITY_RR']=$objQuery['MOTILITY_RR'];
        $result['MOTILITY_RL']=$objQuery['MOTILITY_RL'];
        $result['MOTILITY_LS']=$objQuery['MOTILITY_LS'];
        $result['MOTILITY_LI']=$objQuery['MOTILITY_LI'];
        $result['MOTILITY_LR']=$objQuery['MOTILITY_LR'];
        $result['MOTILITY_LL']=$objQuery['MOTILITY_LL'];
        $result['NEURO_COMMENTS']=$objQuery['NEURO_COMMENTS'];
        $result['STEREOPSIS']=$objQuery['STEREOPSIS'];
        $result['ODNPA']=$objQuery['ODNPA'];
        $result['OSNPA']=$objQuery['OSNPA'];
        $result['VERTFUSAMPS']=$objQuery['VERTFUSAMPS'];
        $result['DIVERGENCEAMPS']=$objQuery['DIVERGENCEAMPS'];
        $result['NPC']=$objQuery['NPC'];
        $result['DACCDIST']=$objQuery['DACCDIST'];
        $result['DACCNEAR']=$objQuery['DACCNEAR'];
        $result['CACCDIST']=$objQuery['CACCDIST'];
        $result['CACCNEAR']=$objQuery['CACCNEAR'];
        $result['ODCOLOR']=$objQuery['ODCOLOR'];
        $result['OSCOLOR']=$objQuery['OSCOLOR'];
        $result['ODCOINS']=$objQuery['ODCOINS'];
        $result['OSCOINS']=$objQuery['OSCOINS'];
        $result['ODREDDESAT']=$objQuery['ODREDDESAT'];
        $result['OSREDDESAT']=$objQuery['OSREDDESAT'];
        $result['ODPUPILSIZE1']=$objQuery['ODPUPILSIZE1'];
        $result['ODPUPILSIZE2']=$objQuery['ODPUPILSIZE2'];
        $result['ODPUPILREACTIVITY']=$objQuery['ODPUPILREACTIVITY'];
        $result['ODAPD']=$objQuery['ODAPD'];
        $result['OSPUPILSIZE1']=$objQuery['OSPUPILSIZE1'];
        $result['OSPUPILSIZE2']=$objQuery['OSPUPILSIZE2'];
        $result['OSPUPILREACTIVITY']=$objQuery['OSPUPILREACTIVITY'];
        $result['OSAPD']=$objQuery['OSAPD'];
        $result['DIMODPUPILSIZE1']=$objQuery['DIMODPUPILSIZE1'];
        $result['DIMODPUPILSIZE2']=$objQuery['DIMODPUPILSIZE2'];
        $result['DIMODPUPILREACTIVITY']=$objQuery['DIMODPUPILREACTIVITY'];
        $result['DIMOSPUPILSIZE1']=$objQuery['DIMOSPUPILSIZE1'];
        $result['DIMOSPUPILSIZE2']=$objQuery['DIMOSPUPILSIZE2'];
        $result['DIMOSPUPILREACTIVITY']=$objQuery['DIMOSPUPILREACTIVITY'];
        $result['PUPIL_COMMENTS']=$objQuery['PUPIL_COMMENTS'];
        $result['ODVFCONFRONTATION1']=$objQuery['ODVFCONFRONTATION1'];
        $result['ODVFCONFRONTATION2']=$objQuery['ODVFCONFRONTATION2'];
        $result['ODVFCONFRONTATION3']=$objQuery['ODVFCONFRONTATION3'];
        $result['ODVFCONFRONTATION4']=$objQuery['ODVFCONFRONTATION4'];
        $result['ODVFCONFRONTATION5']=$objQuery['ODVFCONFRONTATION5'];
        $result['OSVFCONFRONTATION1']=$objQuery['OSVFCONFRONTATION1'];
        $result['OSVFCONFRONTATION2']=$objQuery['OSVFCONFRONTATION2'];
        $result['OSVFCONFRONTATION3']=$objQuery['OSVFCONFRONTATION3'];
        $result['OSVFCONFRONTATION4']=$objQuery['OSVFCONFRONTATION4'];
        $result['OSVFCONFRONTATION5']=$objQuery['OSVFCONFRONTATION5'];
        $result["json"] = json_encode($result);
        echo json_encode($result); 
    } elseif ($zone =="IMPPLAN") {
        $result['IMPPLAN'] = get_PRIOR_IMPPLAN($pid,$copy_from);
   //     $result["json"] = json_encode($result);
        echo json_encode($result); 
    } elseif ($zone =="ALL") {
        $result['RUL']=$objQuery['RUL'];
        $result['LUL']=$objQuery['LUL'];
        $result['RLL']=$objQuery['RLL'];
        $result['LLL']=$objQuery['LLL'];
        $result['RBROW']=$objQuery['RBROW'];
        $result['LBROW']=$objQuery['LBROW'];
        $result['RMCT']=$objQuery['RMCT'];
        $result['LMCT']=$objQuery['LMCT'];
        $result['RADNEXA']=$objQuery['RADNEXA'];
        $result['LADNEXA']=$objQuery['LADNEXA'];
        $result['RMRD']=$objQuery['RMRD'];
        $result['LMRD']=$objQuery['LMRD'];
        $result['RLF']=$objQuery['RLF'];
        $result['LLF']=$objQuery['LLF'];
        $result['RVFISSURE']=$objQuery['RVFISSURE'];
        $result['LVFISSURE']=$objQuery['LVFISSURE'];
        $result['ODHERTEL']=$objQuery['ODHERTEL'];
        $result['OSHERTEL']=$objQuery['OSHERTEL'];
        $result['HERTELBASE']=$objQuery['HERTELBASE'];
        $result['ODPIC']=$objQuery['ODPIC'];
        $result['OSPIC']=$objQuery['OSPIC'];
        $result['EXT_COMMENTS']=$objQuery['EXT_COMMENTS'];
        
        $result['OSCONJ']=$objQuery['OSCONJ'];
        $result['ODCONJ']=$objQuery['ODCONJ'];
        $result['ODCORNEA']=$objQuery['ODCORNEA'];
        $result['OSCORNEA']=$objQuery['OSCORNEA'];
        $result['ODAC']=$objQuery['ODAC'];
        $result['OSAC']=$objQuery['OSAC'];
        $result['ODLENS']=$objQuery['ODLENS'];
        $result['OSLENS']=$objQuery['OSLENS'];
        $result['ODIRIS']=$objQuery['ODIRIS'];
        $result['OSIRIS']=$objQuery['OSIRIS'];
        $result['ODKTHICKNESS']=$objQuery['ODKTHICKNESS'];
        $result['OSKTHICKNESS']=$objQuery['OSKTHICKNESS'];
        $result['ODGONIO']=$objQuery['ODGONIO'];
        $result['OSGONIO']=$objQuery['OSGONIO'];
        $result['ANTSEG_COMMENTS']=$objQuery['ANTSEG_COMMENTS'];
        
        $result['ODDISC']=$objQuery['ODDISC'];
        $result['OSDISC']=$objQuery['OSDISC'];
        $result['ODCUP']=$objQuery['ODCUP'];
        $result['OSCUP']=$objQuery['OSCUP'];
        $result['ODMACULA']=$objQuery['ODMACULA'];
        $result['OSMACULA']=$objQuery['OSMACULA'];
        $result['ODVESSELS']=$objQuery['ODVESSELS'];
        $result['OSVESSELS']=$objQuery['OSVESSELS'];
        $result['ODPERIPH']=$objQuery['ODPERIPH'];
        $result['OSPERIPH']=$objQuery['OSPERIPH'];
        $result['ODDRAWING']=$objQuery['ODDRAWING'];
        $result['OSDRAWING']=$objQuery['OSDRAWING'];
        $result['ODCMT']=$objQuery['ODCMT'];
        $result['OSCMT']=$objQuery['OSCMT'];
        $result['RETINA_COMMENTS']=$objQuery['RETINA_COMMENTS'];

        $result['ACT']=$objQuery['ACT'];
        $result['ACT5CCDIST']=$objQuery['ACT5CCDIST'];
        $result['ACT1CCDIST']=$objQuery['ACT1CCDIST'];
        $result['ACT2CCDIST']=$objQuery['ACT2CCDIST'];
        $result['ACT3CCDIST']=$objQuery['ACT3CCDIST'];
        $result['ACT4CCDIST']=$objQuery['ACT4CCDIST'];
        $result['ACT6CCDIST']=$objQuery['ACT6CCDIST'];
        $result['ACT7CCDIST']=$objQuery['ACT7CCDIST'];
        $result['ACT8CCDIST']=$objQuery['ACT8CCDIST'];
        $result['ACT9CCDIST']=$objQuery['ACT9CCDIST'];
        $result['ACT10CCDIST']=$objQuery['ACT10CCDIST'];
        $result['ACT11CCDIST']=$objQuery['ACT11CCDIST'];
        $result['ACT1SCDIST']=$objQuery['ACT1SCDIST'];
        $result['ACT2SCDIST']=$objQuery['ACT2SCDIST'];
        $result['ACT3SCDIST']=$objQuery['ACT3SCDIST'];
        $result['ACT4SCDIST']=$objQuery['ACT4SCDIST'];
        $result['ACT5SCDIST']=$objQuery['ACT5SCDIST'];
        $result['ACT6SCDIST']=$objQuery['ACT6SCDIST'];
        $result['ACT7SCDIST']=$objQuery['ACT7SCDIST'];
        $result['ACT8SCDIST']=$objQuery['ACT8SCDIST'];
        $result['ACT9SCDIST']=$objQuery['ACT9SCDIST'];
        $result['ACT10SCDIST']=$objQuery['ACT10SCDIST'];
        $result['ACT11SCDIST']=$objQuery['ACT11SCDIST'];
        $result['ACT1SCNEAR']=$objQuery['ACT1SCNEAR'];
        $result['ACT2SCNEAR']=$objQuery['ACT2SCNEAR'];
        $result['ACT3SCNEAR']=$objQuery['ACT3SCNEAR'];
        $result['ACT4SCNEAR']=$objQuery['ACT4SCNEAR'];
        $result['ACT5CCNEAR']=$objQuery['ACT5CCNEAR'];
        $result['ACT6CCNEAR']=$objQuery['ACT6CCNEAR'];
        $result['ACT7CCNEAR']=$objQuery['ACT7CCNEAR'];
        $result['ACT8CCNEAR']=$objQuery['ACT8CCNEAR'];
        $result['ACT9CCNEAR']=$objQuery['ACT9CCNEAR'];
        $result['ACT10CCNEAR']=$objQuery['ACT10CCNEAR'];
        $result['ACT11CCNEAR']=$objQuery['ACT11CCNEAR'];
        $result['ACT5SCNEAR']=$objQuery['ACT5SCNEAR'];
        $result['ACT6SCNEAR']=$objQuery['ACT6SCNEAR'];
        $result['ACT7SCNEAR']=$objQuery['ACT7SCNEAR'];
        $result['ACT8SCNEAR']=$objQuery['ACT8SCNEAR'];
        $result['ACT9SCNEAR']=$objQuery['ACT9SCNEAR'];
        $result['ACT10SCNEAR']=$objQuery['ACT10SCNEAR'];
        $result['ACT11SCNEAR']=$objQuery['ACT11SCNEAR'];
        $result['ACT1CCNEAR']=$objQuery['ACT1CCNEAR'];
        $result['ACT2CCNEAR']=$objQuery['ACT2CCNEAR'];
        $result['ACT3CCNEAR']=$objQuery['ACT3CCNEAR'];
        $result['ACT4CCNEAR']=$objQuery['ACT4CCNEAR'];
        $result['ODVF1']=$objQuery['ODVF1'];
        $result['ODVF2']=$objQuery['ODVF2'];
        $result['ODVF3']=$objQuery['ODVF3'];
        $result['ODVF4']=$objQuery['ODVF4'];
        $result['OSVF1']=$objQuery['OSVF1'];
        $result['OSVF2']=$objQuery['OSVF2'];
        $result['OSVF3']=$objQuery['OSVF3'];
        $result['OSVF4']=$objQuery['OSVF4'];
        $result['MOTILITY_RS']=$objQuery['MOTILITY_RS'];
        $result['MOTILITY_RI']=$objQuery['MOTILITY_RI'];
        $result['MOTILITY_RR']=$objQuery['MOTILITY_RR'];
        $result['MOTILITY_RL']=$objQuery['MOTILITY_RL'];
        $result['MOTILITY_LS']=$objQuery['MOTILITY_LS'];
        $result['MOTILITY_LI']=$objQuery['MOTILITY_LI'];
        $result['MOTILITY_LR']=$objQuery['MOTILITY_LR'];
        $result['MOTILITY_LL']=$objQuery['MOTILITY_LL'];
        $result['NEURO_COMMENTS']=$objQuery['NEURO_COMMENTS'];
        $result['STEREOPSIS']=$objQuery['STEREOPSIS'];
        $result['ODNPA']=$objQuery['ODNPA'];
        $result['OSNPA']=$objQuery['OSNPA'];
        $result['VERTFUSAMPS']=$objQuery['VERTFUSAMPS'];
        $result['DIVERGENCEAMPS']=$objQuery['DIVERGENCEAMPS'];
        $result['NPC']=$objQuery['NPC'];
        $result['DACCDIST']=$objQuery['DACCDIST'];
        $result['DACCNEAR']=$objQuery['DACCNEAR'];
        $result['CACCDIST']=$objQuery['CACCDIST'];
        $result['CACCNEAR']=$objQuery['CACCNEAR'];
        $result['ODCOLOR']=$objQuery['ODCOLOR'];
        $result['OSCOLOR']=$objQuery['OSCOLOR'];
        $result['ODCOINS']=$objQuery['ODCOINS'];
        $result['OSCOINS']=$objQuery['OSCOINS'];
        $result['ODREDDESAT']=$objQuery['ODREDDESAT'];
        $result['OSREDDESAT']=$objQuery['OSREDDESAT'];
        $result['ODPUPILSIZE1']=$objQuery['ODPUPILSIZE1'];
        $result['ODPUPILSIZE2']=$objQuery['ODPUPILSIZE2'];
        $result['ODPUPILREACTIVITY']=$objQuery['ODPUPILREACTIVITY'];
        $result['ODAPD']=$objQuery['ODAPD'];
        $result['OSPUPILSIZE1']=$objQuery['OSPUPILSIZE1'];
        $result['OSPUPILSIZE2']=$objQuery['OSPUPILSIZE2'];
        $result['OSPUPILREACTIVITY']=$objQuery['OSPUPILREACTIVITY'];
        $result['OSAPD']=$objQuery['OSAPD'];
        $result['DIMODPUPILSIZE1']=$objQuery['DIMODPUPILSIZE1'];
        $result['DIMODPUPILSIZE2']=$objQuery['DIMODPUPILSIZE2'];
        $result['DIMODPUPILREACTIVITY']=$objQuery['DIMODPUPILREACTIVITY'];
        $result['DIMOSPUPILSIZE1']=$objQuery['DIMOSPUPILSIZE1'];
        $result['DIMOSPUPILSIZE2']=$objQuery['DIMOSPUPILSIZE2'];
        $result['DIMOSPUPILREACTIVITY']=$objQuery['DIMOSPUPILREACTIVITY'];
        $result['PUPIL_COMMENTS']=$objQuery['PUPIL_COMMENTS'];
        $result['ODVFCONFRONTATION1']=$objQuery['ODVFCONFRONTATION1'];
        $result['ODVFCONFRONTATION2']=$objQuery['ODVFCONFRONTATION2'];
        $result['ODVFCONFRONTATION3']=$objQuery['ODVFCONFRONTATION3'];
        $result['ODVFCONFRONTATION4']=$objQuery['ODVFCONFRONTATION4'];
        $result['ODVFCONFRONTATION5']=$objQuery['ODVFCONFRONTATION5'];
        $result['OSVFCONFRONTATION1']=$objQuery['OSVFCONFRONTATION1'];
        $result['OSVFCONFRONTATION2']=$objQuery['OSVFCONFRONTATION2'];
        $result['OSVFCONFRONTATION3']=$objQuery['OSVFCONFRONTATION3'];
        $result['OSVFCONFRONTATION4']=$objQuery['OSVFCONFRONTATION4'];
        $result['OSVFCONFRONTATION5']=$objQuery['OSVFCONFRONTATION5'];
        $result['IMP']=$objQuery['IMP'];
        $result["json"] = json_encode($result);
        echo json_encode($result); 
    } elseif ($zone =="READONLY") {
        $result=$objQuery;
        //$result['CC1'] = $objQuery['CC1'];
        $result["json"] = json_encode($result);
        echo json_encode($result); 
    }
}

/*  
 *  This builds the IMPPLAN_items variable for a given pid and form_id.
 */
function get_PRIOR_IMPPLAN($pid,$form_id) {
    global $form_folder;

    //we could build the whole arrray for all form_id/visits for a given pt but just do one at a time for now
    $query = "select * from form_".$form_folder."_impplan where form_id=? and pid=? order by IMPPLAN_order ASC";
    $result =  sqlStatement($query,array($form_id,$pid));
    while ($ip_list = sqlFetchArray($result))   {
        $newdata =  array (
          'form_id' => $ip_list['form_id'],
          'pid' => $ip_list['pid'],
          'title' => $ip_list['title'],
          'code' => $ip_list['code'],
          'codetype' => $ip_list['codetype'],
          'codetext' => $ip_list['codetext'],
          'plan' => $ip_list['plan'],
          'IMPPLAN_order' => $ip_list['IMPPLAN_order']
          );
        $PRIOR_IMPPLAN_items[] =$newdata;
    }
    return $PRIOR_IMPPLAN_items;
}
/**
  *  This function builds an array of documents for this patient ($pid).
  *  We first list all the categories this practice has created by name and by category_id  
  *  
  *  Each document info from documents table is added to these as arrays
  *  
  */
function document_engine($pid) {
    $sql1 =  sqlStatement("Select * from categories");
    while ($row1 = sqlFetchArray($sql1)) {
        $categories[] = $row1;
        $my_name[$row1['id']] = $row1['name'];
        $children_names[$row1['parent']][]=$row1['name'];
        $parent_name[$row1['name']] = $my_name[$row1['parent']];
        if ($row1['value'] >'') {
            //if there is a value, tells us what segment of exam ($zone) this belongs in...
            $zones[$row1['value']][] = $row1;
        } else {
            if ($row1['name'] != "Categories") {
                $zones['OTHER'][] = $row1;
            }
        }
    }
    $query = "Select *
                from 
                categories, documents,categories_to_documents
                where documents.foreign_id=? and documents.id=categories_to_documents.document_id and
                categories_to_documents.category_id=categories.id ORDER BY categories.name";
    $sql2 =  sqlStatement($query,array($pid));
    while ($row2 = sqlFetchArray($sql2)) {
        $documents[]= $row2;
        $docs_in_cat_id[$row2['category_id']][] = $row2;
        if ($row2['value'] > '') {
            $docs_in_zone[$row2['value']][] = $row2;
        } else {
                $docs_in_zone['OTHER'][]=$row2;
        }
        $docs_in_name[$row2['name']][] = $row2;
    }
    $documents['categories']=$categories;
    $documents['my_name']=$my_name;
    $documents['children_names']=$children_names;
    $documents['parent_name'] = $parent_name;
    $documents['zones'] = $zones;
    $documents['docs_in_zone'] = $docs_in_zone;
    $documents['docs_in_cat_id'] = $docs_in_cat_id;
    $documents['docs_in_name'] = $docs_in_name;
    
    return array($documents);
}
/**
 *  This function returns hooks/links for the Document Library, 
 *      Document Reports(to do), upload(done) and image DB(done)
 *      based on the category/zone
 *
 *  @param string $pid value = patient id
 *  @param string $encounter is the encounter_id 
 *  @param string $category_value options EXT,ANTSEG,POSTSEG,NEURO,OTHER
 *                These values are taken from the "value" field in the category table
 *                They allow us to regroup the categories how we like them.
 *  @return array($imaging,$episode)
 */ 
function display($pid,$encounter,$category_value) {
    global $form_folder;
    global $id;
    global $documents;
       /**
        *   Each section will need a designator as to the section it belongs in.
        *   The categories table does not have that but it has an unused value field.
        *   This is where we link it to the image database.  We add this link value  
        *   on install but end user can change or add others as the devices evolve.
        *   New names new categories.  OCT would not have been a category 5 years ago.
        *   Who knows what is next?  Gene-lab construction?  Sure will.  
        *   So the name is user assigned as is the location.  
        *   Thus we need to build out the Documents section by adding another layer "zones"
        *   to the treemenu backbone.  
        */
    if (!$documents) {
        list($documents) = document_engine($pid);
    }
    for ($j=0; $j < count($documents['zones'][$category_value]); $j++) {
        $episode .= "<tr>
        <td class='right' style='font-size:1.3em;'><b>".xlt($documents['zones'][$category_value][$j]['name'])."</b>:&nbsp;</td>
        <td>
            <a href='../../../controller.php?document&upload&patient_id=".$pid."&parent_id=".$documents['zones'][$category_value][$j]['id']."&'>
            <img src='../../forms/".$form_folder."/images/upload_file.png' class='little_image'>
            </a>
        </td>
        <td>
            <img src='../../forms/".$form_folder."/images/upload_multi.png' class='little_image'>
        </td>
        <td>";
        // theorectically above leads to a document management engine.  Gotta build that...
        // we only need to know if there is one as this link will open the image management engine/display
        //AnythingSlider
         if (count($documents['docs_in_cat_id'][$documents['zones'][$category_value][$j]['id']]) > '0') {
            $episode .= '<a href="../../forms/'.$form_folder.'/css/AnythingSlider/simple.php?display=i&category_id='.$documents['zones'][$category_value][$j]['id'].'&encounter='.$encounter.'&category_name='.urlencode(xla($category_value)).'"
                    onclick="return dopopup(\'../../forms/'.$form_folder.'/css/AnythingSlider/simple.php?display=i&category_id='.$documents['zones'][$category_value][$j]['id'].'&encounter='.$encounter.'&category_name='.urlencode(xla($category_value)).'\')">
                    <img src="../../forms/'.$form_folder.'/images/jpg.png" class="little_image" /></a>';     
        //OpenEMR
        /*if (count($documents['docs_in_cat_id'][$documents['zones'][$category_value][$j]['id']]) > '0') {
            $episode .= '<a href="../../../controller.php?document&view&patient_id='.$pid.'&parent_idX='.$documents['zones'][$category_value][$j]['id'].'&" 
                    onclick="return dopopup(\'../../../controller.php?document&view&patient_id='.$pid.'&parent_idX='.$documents['zones'][$category_value][$j]['id'].'&document_id='.$doc[id].'&as_file=false\')">
                    <img src="../../forms/'.$form_folder.'/images/jpg.png" class="little_image" /></a>';
        */
                }
    //http://www.oculoplasticsllc.com/openemr/controller.php?document&view&patient_id=1&doc_id=411&
        $episode .= '</td></tr>';
        $i++;
    }  
    return array($documents,$episode);
}
/**
 *   This is an attempt to redirect new menu items which point to old OpenEMR forms, to display
 *   inside of the menu page.  Not working yet - I am missing something that is obvious but I am not
 *   sure what....
 */
function redirector($url) {
    global $form_folder;
    
     ?>
    <html>
    <head>
    <!-- jQuery library -->
    <script src="<?php echo $GLOBALS['webroot']; ?>/library/js/jquery.min.js"></script>

    <!-- Latest compiled JavaScript -->
    <script src="<?php echo $GLOBALS['webroot']; ?>/library/js/bootstrap.min.js"></script>  
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
        <!-- Add Font stuff for the look and feel.  -->
    <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot']; ?>/library/css/pure-min.css">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot']; ?>/interface/forms/<?php echo $form_folder; ?>/style.css" type="text/css">    
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot']; ?>/library/css/font-awesome-4.2.0/css/font-awesome.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
    <body>
    <?php
    $input_echo = menu_overhaul_top($pid,$encounter);
    //url
    ?>
    <object data="<?php echo $GLOBALS['webroot'].$url; ?>" width="600" height="400"> 
    <embed src="<?php echo $GLOBALS['webroot'].$url; ?>" width="600" height="400"> </embed> 
    Error: Embedded data could not be displayed. </object>
    <?php 
    
    $output = menu_overhaul_bottom($pid,$encounter);
    exit(0);}
/**
 *  This is an experiment to start shifting clinical functions into a single page with an application style menu.
 */
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
/**
 *  This is currently a floating div top with patient demographics and such only in fullscreen mode.
 */
function menu_overhaul_left($pid,$encounter) {
    global $form_folder;
    global $pat_data;
    global $visit_date;
   // @extract($pat_data);
    /*
     * We need to find out if the patient has a photo right? 
     */
    list($documents) = document_engine($pid);
        ?>    
    <div class="borderShadow" style="font-size:1.2em;width:70%;display:inline-block;">
        <div id="left_menu" name="left_menu" class="col-md-3" style="font-size:1.0em;">
            <div style="padding-left: 18px;">
                <table style="font-size:1.0em;text-align:left;">
                    <tr>
                        <td class="right" >
                                <?php 
                                $age = getPatientAgeDisplay($pat_data['DOB'], $encounter_date);
                                $DOB = oeFormatShortDate($pat_data['DOB']);
                                echo "<b>".xlt('Name').":</b> </td><td nowrap> &nbsp;".text($pat_data['fname'])."  ".text($pat_data['lname'])." (".text($pid).")</td></tr>
                                        <tr><td class='right'><b>".xlt('DOB').":</b></td><td  nowrap> &nbsp;".text($pat_data['DOB']). "&nbsp;&nbsp;(".text($age).")</td></tr>
                                        "; 
                                ?>
                                        <?php 
                                            echo "<tr><td class='right' nowrap><b>".xlt('Visit Date').":</b></td><td>&nbsp;".$visit_date."</td></tr>";
                                        ?>
                        </td>
                        <td class="right" style="vertical-align:top;" nowrap><b><?php echo xlt("Today's Plan"); ?>:</b>&nbsp;</td>
                        <td style="vertical-align:top;" nowrap>
                        <?php
                        // Start with Appt reason from calendar
                        // Consider using last visit's PLAN field?
                        //think about this space and how to use it...
                        $query = "select * from  openemr_postcalendar_events where pc_pid=? and pc_eventDate=?";
                        $res = sqlStatement($query,array($pid,$_SESSION['lastcaldate']));
                        $reason = sqlFetchArray($res);
                        ?>&nbsp;<?php echo text($reason['pc_hometext']); 
                        global $priors;
                        $PLAN_today = preg_replace("/\|/","<br />",$earlier['PLAN']);
                        if ($PLAN_today) echo "<br />".$PLAN_today;

                        ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div id="left_menu3" name="left_menu3" class="col-md-1" style="font-size:1.0em;">
            <?php             //if the patient has a photograph, use it else use generic avitar thing.
            if ($documents['docs_in_name']['Patient Photograph'][0]['id']) {
                ?>
                <object><embed src="/openemr/controller.php?document&amp;retrieve&amp;patient_id=<?php echo $pid; ?>&amp;document_id=<?php echo $documents['docs_in_name']['Patient Photograph'][0]['id']; ?>&amp;as_file=false" frameborder="0"
                     type="<?php echo $documents['docs_in_name']['Patient Photograph'][0]['mimetype']; ?>" allowscriptaccess="always" allowfullscreen="false" height="50"></embed></object>
            <?php 
            } else {
            ?>
            <object><embed src="<?php echo $GLOBALS['web_root']; ?>/interface/forms/<?php echo $form_folder; ?>/images/anon.gif" frameborder="0"
                 type="image/gif" height="50"></embed></object>
                <?php
            }
            ?>
        </div>
        
        <div id="left_menu2" name="left_menu2" class="col-md-4" style="font-size:1.0em;">
            <?php 
            $query = "Select * from users where id =?";
            $prov = sqlQuery($query,array($pat_data['ref_providerID']));
            $Ref_provider = $prov['fname']." ".$prov['lname'];
            $prov = sqlQuery($query,array($pat_data['providerID']));
           // $PCP = $prov['fname']." ".$prov['lname'];

            $query = "Select * from insurance_companies where id in (select provider from insurance_data where pid =? and type='primary')";
            $ins = sqlQuery($query,array($pid));
            $ins_co1 = $ins['name'];
            $query = "Select * from insurance_companies where id in (select provider from insurance_data where pid =? and type='secondary')";
            $ins = sqlQuery($query,array($pid));
            $ins_co2 = $ins['name'];
            ?>

            <div style="position:relative;float:left;padding-left:18px;top:0px;">
            <table style="border:1pt;font-size:1.0em;">
                <tr>
                    <td class="right"><b><?php echo xlt("PCP"); ?>:</b>&nbsp;</td><td style="font-size:0.8em;">&nbsp;
                    <?php $query="SELECT * FROM layout_options WHERE form_id = 'DEM' AND uor > 0 AND field_id = 'providerID' ORDER BY seq";
                        $group_fields_query = sqlStatement($query);
                        while ($group_fields = sqlFetchArray($group_fields_query)) {
                            $group_fields['edit_options']='0'; //disable the select field here
                            $PCP = generate_form_field($group_fields, $pat_data['providerID']);
                        }
                        ?>
                    </td>
                </tr>
                <tr><td class="right" nowrap><b><?php echo xlt("Referred By"); ?>:</b>&nbsp;</td><td style="font-size:0.8em;">&nbsp;<?php $query="SELECT  *,field_id as ref_providerID FROM layout_options WHERE form_id = 'DEM' AND uor > 0 AND field_id = 'providerID' ORDER BY seq";
                        $group_fields_query = sqlStatement($query);
                        while ($group_fields = sqlFetchArray($group_fields_query)) {
                            $group_fields['edit_options']='0'; //disable the select field here
                            $ref_providerID = generate_form_field($group_fields, $pat_data['ref_providerID']);
                        }
                        ?></td></tr>
                <tr><td class="right"><b><?php echo xlt("Insurance"); ?>:</b>&nbsp;</td><td>&nbsp;<?php echo text($ins_co1); ?></td></tr>
                <tr><td class="right"><b><?php echo xlt("Secondary"); ?>:</b>&nbsp;</td><td>&nbsp;<?php echo text($ins_co2); ?></td></tr>
            </table>
            </div>
        </div>
        
    </div>
    <?php
}
/**
 *  This is currently just closing up the divs.  It can easily be a footer with the practice info
 *  or whatever you like.  Maybe a placeholder for user groups or link outs to data repositories 
 *  such as Medfetch.com/PubMed/UpToDate/DynaMed????
 *  It could provide information as to available data imports from connected machines - yes we have 
 *  data from an autorefractor needed to be imported.  The footer can be fixed or floating.
 *  It could have balance info, notes, or an upside down menu mirroring the header menu, maybe allowing
 *  the user to decide which is fixed and which is not?  Oh the possibilities.
 */
function menu_overhaul_bottom($pid,$encounter) {

 /*   </div>
    <!-- /#wrapper -->
 <?php
 */
    // if ($display="fullscreen") {
    //}
}
/*
    *  To make this all work, DO we need to delete every record for this form and encounter in the undo folder?
    *  The act of finalizing and "esigning" a document to me means the document is locked.  There should be some sort of
    *  encryption key here with a checksum and/or digital time mark to say this is locked and if the key fails, the values do
    *  NOT natch the esigned document.  Indeed all knock-on changes should be added as addendums or notes or whatever exists in the main
    *  openEMR.  The file needs to be locked and unless someone goes into the DB to change a field's value, the program should not allow
    *  any update.  If they do that, the keys will not match.  An immediate chart integrity issue is raised.  I don't know how to do this
    *  but someone does...  Can a DB field be made permanent?  Can a DB record of all fields have an encryption protocol attached to it 
    *  so if it is changed, the stored key no longer matches and the record is forever tainted? We should make openEMR records
    *  untaintable, if that is a word.
*/
function  finalize() {
    global $form_folder;
    global $pid;
    global $encounter;
    if (($_REQUEST['action'] =='finalize') or ($_REQUEST['final'] == '1')) {
        //logic to finalize according to openEMR protocol
    }
    return;
}
/*
 * This was taken from new_form.php and is helping to integrate new menu with openEMR
 * menu seen on encounter page.
 */
function Menu_myGetRegistered($state="1", $limit="unlimited", $offset="0") {
    $sql = "SELECT category, nickname, name, state, directory, id, sql_run, " .
      "unpackaged, date FROM registry WHERE " .
      "state LIKE ? ORDER BY category, priority, name";
    if ($limit != "unlimited") $sql .= " limit " . escape_limit($limit) . ", " . escape_limit($offset);
    $res = sqlStatement($sql,array($state));
    if ($res) {
        for($iter=0; $row=sqlFetchArray($res); $iter++) {
            $all[$iter] = $row;
        }
    } else {
        return false;
    }
    return $all;
}
/*
 * This prints a header for documents.  Keeps the brand uniform...
 */
function report_header($pid,$direction='shell') {
    global $form_name;
    global $encounter;
    /*******************************************************************
    $titleres = getPatientData($pid, "fname,lname,providerID");
    $sql = "SELECT * FROM facility ORDER BY billing_location DESC LIMIT 1";
    *******************************************************************/
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

    // Use logo if it exists as 'practice_logo.gif' in the site dir
    // old code used the global custom dir which is no longer a valid
    if ($direction == "web") {
        global $OE_SITE_DIR;
        $practice_logo = $GLOBALS['webroot']."/sites/default/images/practice_logo.gif";
        if (file_exists($OE_SITE_DIR."/images/practice_logo.gif")) {
            echo "<img src='$practice_logo' align='left' style='width:150px;margin:10px;'><br />\n";
        } 
    } else {
        global $OE_SITE_DIR;
        $practice_logo = "$OE_SITE_DIR/images/practice_logo.gif";
        if (file_exists($practice_logo)) {
            echo "<img src='$practice_logo' align='left' style='width:100px;margin:10px;'><br />\n";
        } 
    }
    ?>
    <span style="font-weight:bold;font-size:1.4em;"><?php echo $facility['name'] ?></span><br />
    <?php echo $facility['street'] ?><br />
    <?php echo $facility['city'] ?>, <?php echo $facility['state'] ?> <?php echo $facility['postal_code'] ?><br />
    <?php echo $facility['phone'] ?><br clear='all' />
    <?php 
        $visit= getEncounterDateByEncounter($encounter); 
        $visit_date = $visit['date']; 
        ?>
     <span class='title' style="position:absolute;top:25px;right:25px;">
        <a href="javascript:window.close();"><?php echo $titleres['fname'] . " " . $titleres['lname']; ?></a><br />
        <span class='text'><?php echo xlt('Generated on'); ?>: <?php echo oeFormatShortDate(); ?></span><br />
        <span class='text'><?php echo xlt('Visit Date'); ?>: <?php echo oeFormatSDFT(strtotime($visit_date)); ?></span><br />
        <span class='text'><?php echo xlt('Provider') . ': ' . text(getProviderName(getProviderIdOfEncounter($encounter))).'<br />'; ?></span>
      </span>
      <?php
}

function first_run() {
    // Do we need to set up the system?
    // Check to see if the lists are present.
    // They are autocreated in the base install database.sql, but not if form was just added
    // to an old install.  Do we need to worry about these folks?

    // Check to see if the Categories are setup. Build them if not.
    // We'd like to create two subcategories under 'Medical Record': Imaging and Encounters
    // if they are not present.  Check for Imaging and if not present create them all?
    // That is what we will do now but in the future:
    // I think a good way to go here is to create a list of categories, using subtype to put them
    // in a particular clinical area.  That way people can change whatever NAME they want.
    // This will also allow easier duplication of the form to other specialties, allowing
    // easy integration of document categories into the form.
    $query = "select id from categories where name = 'Imaging'";
    $result = sqlStatement($query);
    $ID = sqlFetchArray($result);
    $Imaging_ID = $ID['id'];
    // Build it all if Imaging_ID < 1, otherwise , move along
    //the categories under Medical Record
        // In the base install as of today (10/17/15) this category = 3, but it may not be true later...
        // So get it for this installation...
    if ($Imaging_ID < '1') {
        $query = "select id from categories where name = 'Medical Record'";
        $result = sqlStatement($query);
        $ID = sqlFetchArray($result);
        $medical_record = $ID['id'];
       
        $queries = "INSERT INTO categories (select (select MAX(id) from categories) + 1, 'Imaging', '', ". $medical_record ." , rght, rght + 1 from categories where name = 'Categories');
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            INSERT INTO categories (select (select MAX(id) from categories) + 1, 'Communication', '', '".$medical_record."', rght, rght + 1 from categories where name = 'Categories');
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            INSERT INTO categories (select (select MAX(id) from categories) + 1, 'Encounters', '', '".$medical_record."', rght, rght + 1 from categories where name = 'Categories');
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';";
        mysql_multiquery($queries);

        // Now find out what Document->Medical Record->Imaging's 'id' is,
        // So we can add the categories which fall under Imaging.
        $query = "select id from categories where name = 'Imaging' and parent=?";
        $result = sqlStatement($query,array($medical_record));
        $ID = sqlFetchArray($result);
        $imaging = $ID['id'];

        $queries = "INSERT INTO categories select (select MAX(id) from categories) + 1,'External Photos','EXT','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'AntSeg Photos','ANTSEG','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'US/Biometry','POSTSEG','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'Drawings','DRAW','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'VF','NEURO','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'Radiology','NEURO','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'FA/ICG','POSTSEG','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'OCT','POSTSEG','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'Optic Disk','POSTSEG','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            
            INSERT INTO categories select (select MAX(id) from categories) + 1,'Fundus','POSTSEG','".$imaging."',rght, rght + 1 from categories where name = 'Imaging' and parent='".$medical_record."';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Categories';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Medical Record';
            UPDATE categories SET rght = rght + 2 WHERE name = 'Imaging';
            UPDATE categories_seq SET id = (select MAX(id) from categories);";
        mysql_multiquery($queries);
    
        //check to see if subtype column is added to list_options, if not add it.
        $query = "SELECT COUNT(*) as count
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE   TABLE_NAME = 'list_options' AND 
                                COLUMN_NAME = 'subtype'";
        $result = sqlStatement($query);
        $ID = sqlFetchArray($result);
        if ($ID['count'] != '1') {
            $query ="ALTER TABLE `list_options`  ADD `subtype` VARCHAR(31) DEFAULT ''";
            $result = sqlStatement($query);    
        }

        // Check for the Contact Lens lists.  
        // If present this form was previously installed - otherwise add create these lists.
        $query="select list_id from list_options where list_id ='lists' and option_id ='CTLManufacturer'";
        $result = sqlStatement($query);
        $count = sqlNumRows($result);

        if ($count < '1') {
            //create the base lists for CTLManufacturer, CTLSupplier, CTLBrand, and add in POH base list
            $query = "SELECT max(seq) as maxseq FROM list_options WHERE list_id= 'lists'";
            $pres = sqlStatement($query);
            $maxseq = sqlFetchArray($pres);
        
            $seq=$maxseq['maxseq'];
            $seq1 = $seg +1;
            $seq2 = $seg +2;
            $queries = "INSERT INTO `openemr`.`list_options` 
                    (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES 
                    ('lists', 'CTLManufacturer', 'Contact Lens Manufacturer list', '".$seq."', '1', '0', '', '', ''),
                    ('lists', 'CTLSupplier', 'Contact Lens Supplier list', '".$seq1."', '1', '0', '', '', ''),
                    ('lists', 'CTLBrand', 'Contact Lens Brand list','".$seq2."', '1', '0', '', '', '');
                    ('CTLManufacturer', 'BNL', 'Bausch&Lomb', '1', '0', '0', '', '', ''),
                    ('CTLManufacturer', 'CibaVision', 'Ciba Vision', '2', '0', '0', '', '', ''),
                    ('CTLManufacturer', 'Cooper', 'CooperVision', '3', '0', '0', '', '', ''),
                    ('CTLManufacturer', 'JNJ', 'Johnson&Johnson', '4', '0', '0', '', '', ''),
                    ('CTLSupplier', 'ABB', 'ABB Optical', '1', '0', '0', '', '', ''),
                    ('CTLSupplier', 'JNJ', 'Johnson&Johnson', '2', '0', '0', '', '', ''),
                    ('CTLSupplier', 'LF', 'Lens Ferry', '3', '0', '0', '', '', ''),
                    ('CTLBrand', 'Acuvue', 'Acuvue', '1', '0', '0', '', '', 'JNJ'),
                    ('CTLBrand', 'Acuvue2', 'Acuvue 2', '2', '0', '0', '', '', 'JNJ'),
                    ('CTLBrand', 'AcuvueOa', 'Acuvue Oasys', '3', '0', '0', '', '', 'JNJ'),
                    ('CTLBrand', 'SF66', 'SofLens Toric', '4', '0', '0', '', '', ''),
                    ('CTLBrand', 'PVMF', 'PureVision MultiFocal', '5', '0', '0', '', '', '');
                INSERT INTO list_options
                    (`list_id`, `option_id`, `title`, `seq`, `subtype`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES 
                    ('medical_problem_issue_list', 'POAG', 'poag', 10,'eye', '0', '0', '', '', 'ICD10:H40.11X2'),
                    ('medical_problem_issue_list', 'POAG Suspect', 'poag_susp', 15,'eye', '0', '0', '', '', 'ICD10:H40.003'),
                    ('medical_problem_issue_list', 'Dermatochalasis', 'dermatochalsis', 20,'eye', '0', '0', '', '', 'ICD10:H02.839'),
                    ('medical_problem_issue_list', 'NIDDM w/ BDR', 'niddm_bdr', 31,'eye', '0', '0', '', '', 'ICD10:E10.319'),
                    ('medical_problem_issue_list', 'NIDDM w/o BDR', 'niddm_no_bdr', 30,'eye', '0', '0', '', '', 'ICD10:E11.9'),
                    ('medical_problem_issue_list', 'IDDM w/o BDR', 'iddm_no_bdr', 30,'eye', '0', '0', '', '', 'ICD10:E10.9'),
                    ('medical_problem_issue_list', 'NS Cataract', 'ns_cataract', 40,'eye', '0', '0', '', '', 'ICD10:H25.10'),
                    ('medical_problem_issue_list', 'BCC', 'BCC', 50,'eye', '0', '0', '', 'Basal cell carcinoma of skin of other parts of face', 'ICD10:C44.319'),
                    ('medical_problem_issue_list', 'IDDM w/ BDR', 'iddm_bdr', 60,'eye', '0', '0', '', '', 'ICD10:E10.319'),
                    ('medical_problem_issue_list', 'Keratoconus', 'keratoconus', 70,'eye', '0', '0', '', 'Keratoconus, unspecified, bilateral', 'ICD10:H18.603'),
                    ('medical_problem_issue_list', 'Dry Eye Syndrome', 'dry eye', 80,'eye', '0', '0', '', 'Keratoconjunctivitis sicca, not specified as Sjogren\'s, bilateral', 'ICD10:H16.223'),
                    ('medical_problem_issue_list', 'SCC', 'SCC', 90,'eye', '0', '0', '', 'Squamous cell carcinoma of skin, unspecified', 'ICD10:C44.92'),
                    ('medical_problem_issue_list', 'stye', 'stye', 100,'eye', '0', '0', '', 'Hordeolum internum unspecified eye, unspecified eyelid', 'ICD10:H00.029');";
            mysql_multiquery($queries);
        } 
    }
}

function mysql_multiquery($queries) {
    $queries = explode(";", $queries);
    foreach ($queries as $query) {
        // if it has mass, try to execute it.  
        if (preg_match('#\w#', $query)) $query = sqlStatement(trim($query));
    }
    return $query;
}

/*
* This is a debug function with timing involved to see if and where server bottlenecks are occurring
* Based on $_SESSION["authId"]- change it to the debugging user...
*/
function timing($marker='',$action='') {
    global $count_time;
    global $verbose;
    global $start;

    if ($_SESSION["authId"] !="1") {
        return; 
    } else {
      $verbose='1';
    }
    if (!$count_time) $start = time();
    $time_now = time();
    $count_time++;
    echo "<br />----------------------------------->".($time_now - $start) . " seconds 5.".$count_time.".  ".$marker."<BR>";
    if ($action == 'exit') exit;
}

return ;
?>
