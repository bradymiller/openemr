<?php
    /**
     * interface/super/rules/controllers/detail/view/view.php
     *
     * @package   OpenEMR
     * @link      https://www.open-emr.org
     * @author    Aron Racho <aron@mi-squared.com>
     * @author    Brady Miller <brady.g.miller@gmail.com>
     * @copyright Copyright (c) 2010-2011 Aron Racho <aron@mi-squared.com>
     * @copyright Copyright (c) 2019 Brady Miller <brady.g.miller@gmail.com>
     * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
     */
    
    require_once($GLOBALS["srcdir"] . "/options.inc.php");
?>
<div class="title header" style="display:none">
            <?php // this will display the TAB title
            echo xlt('CR{{Clinical Reminder abbreviation}}'); ?>:
    <?php
    if ($rule->title) {
        $in = text($rule->title);
        echo strlen($in) > 10 ? substr($in, 0, 10) . "..." : $in;
    } else {
        echo xlt('Manager');
    }
    ?>
</div>

<input type="hidden" id="ruleId" name="ruleId" value="<?php echo attr($rule->id); ?>">
<script language="javascript" src="<?php js_src('detail.js') ?>" xmlns="http://www.w3.org/1999/html"></script>
<script type="text/javascript">
    var detail = new rule_detail( {editable: <?php echo $rule->isEditable() ? "true":"false"; ?>});
    detail.init();
</script>

<div class="row">

        <div class="col-12">
            <div class="header">
                <div class="title"><?php echo xlt('Clinical Reminder'); ?>: <i class="bolder"><?php echo xlt($rule->title); ?></i>  </div>
                <div id="show_summary_report" class="red">&nbsp;</div>
            </div>
        </div>
        <div class="col-6" id="show_summary">
            <!-- summary -->
            <div class="section text-center row" >
                <button class="btn-sm btn-primary icon_2"
                        id="edit_summary"
                        title="<?php echo xla('Edit this Rule'); ?>"><i class="fa fa-pencil"></i>
                </button>
                <button class="btn-sm btn-primary icon_1"
                        data-toggle="modal"
                        data-backdrop="false"
                        data-target="#help_summary"
                        id="show_summary_help"
                        title="<?php echo xla('Open the Help:: Summary Modal'); ?>"><i class="fa fa-question"></i>
                </button>
                <div class="col-12 text-left">
                    <span class="title "><?php echo xlt('Summary'); ?> </span>
                </div>
                <div id="show_summary_1" class="col-12">
                    <table class="table table-sm table-condensed text-left">
                        <tr>
                            <td class="text-right">
                                <span class="underline"><?php echo xlt('Name'); ?>:</span>
                            </td>

                            <td colspan="3" class="table-100"><?php echo xlt($rule->title); ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">
                                <?php
                                    $intervals = $rule->reminderIntervals;
                                    $provider = $intervals->getDetailFor('provider');
                                foreach (ReminderIntervalType::values() as $type) {
                                    foreach (ReminderIntervalRange::values() as $range) {
                                        $first = true;
                                        $detail = $intervals->getDetailFor($type, $range);
                                        $detail->timeUnit;
                                        $timings[$type->code][$range->code]['timeUnit'] =  $detail->timeUnit->code;
                                        $timings[$type->code][$range->code]['amount'] = $detail->amount;
                                        if ($timings[$type->code][$range->code]['amount'] >'1') {
                                            $timings[$type->code][$range->code]['timeUnit2'] =$timings[$type->code][$range->code]['timeUnit']."s";
                                        } else {
                                            $timings[$type->code][$range->code]['timeUnit2']= $timings[$type->code][$range->code]['timeUnit'];
                                        }
                                    }
                                }
                                    
                                    $more='';
                                    $something=0;
                                    
                                foreach (RuleType::values() as $type) {
                                    if ($rule->hasRuleType(RuleType::from($type))) {
                                        $something++;
                                    }
                                }
                                ?>
                                <span class="underline"><?php
                                if ($something > '1') {
                                    echo xlt('Alert Types');
                                } else {
                                    echo xlt('Alert Type');
                                }
                                ?>:</span></td>
                            <td colspan="3">
                                <?php
                                    
                                    //of course move this somewhere central??
                                    function xlts($data)
                                    {
                                        if ($GLOBALS['language_default'] !='English (Standard)') {
                                            $data = preg_replace('/<[^>]*>/', '', $data);
                                            return xlt($data);
                                        }
                                        return $data;
                                    }
                                    function xlas($data)
                                    {
                                        if ($GLOBALS['language_default'] !='English (Standard)') {
                                            $data = preg_replace('/<[^>]*>/', '', $data);
                                            return xla($data);
                                        }
                                        return $data;
                                    }
                                    if ($something) {
                                        if ($rule->hasRuleType(RuleType::from('activealert')) || $rule->hasRuleType(RuleType::from('passivealert'))) {
                                            $clinical = '1';
                                        }
                                        if ($rule->hasRuleType(RuleType::from('activealert')) && $rule->hasRuleType(RuleType::from('passivealert'))) {
                                            $timing .= "This CR has both an
                                                            <span class='bold'
                                                                  data-toggle='popover'
                                                                  data-trigger='hover'
                                                                  data-placement='auto'
                                                                  title='Active Alerts'
                                                                  data-content='A Pop-up will occur daily when the demographics page is opened listing any Treatment Goals needing attention.'>Active Alert
                                                             </span>
                                                             and a
                                                            <span class='bold'
                                                                  data-toggle='popover'
                                                                  data-toggle='popover'
                                                                  data-trigger='hover'
                                                                  data-placement='auto'
                                                                  title='Passive Alerts'
                                                                  data-content='These alerts appear on the Dashboard page inside the CR widget'>Passive Alert</span>.
                                                            <br /> ".text($timings['clinical']['pre']['amount']). " " .text($timings['clinical']['pre']['timeUnit2']). "
                                                            before its Due date, this CR is marked <span class='due_soon bolder'>Due Soon</span>. <br />";
                                            $timing .= "Then for ".text($timings['clinical']['post']['amount'])." ".$timings['clinical']['post']['timeUnit2']." it is <span class='due_now'>Due</span>. ";
                                            $timing .= "After this, it is marked as <span class='past_due'>Past due</span>.";
                                            $timing = xlts($timing);
                                            $timing = "<div>".$timing."</div><br />";
                                        } elseif ($rule->hasRuleType(RuleType::from('activealert'))) {
                                            $timing = "<div>".xlts("An <span class='bold'>Active Alert</span> will pop-up daily listing any Treatment Goals needing attention.")."</div>";
                                        } elseif ($rule->hasRuleType(RuleType::from('passivealert'))) {
                                            $timing = "A <span class='bold'>Passive Alert</span> will appear in the
                                                <a href='#' data-toggle='popover'
                                                            data-trigger='hover'
                                                            data-placement='auto'
                                                            title='Clinical Reminders Widget(CR)'
                                                            data-content='The CR Widget is located on the demographics page.'>CR Widget</a> ";
                                            $timing .= text($timings['clinical']['pre']['amount'])." ".$timings['clinical']['pre']['timeUnit2']." before its Due date, this CR is marked <span class='due_soon bolder'>Due Soon</span>.";
                                            $timing .= text($timings['clinical']['post']['amount'])." ".$timings['clinical']['post']['timeUnit2']." after the Due Date, it is marked <span class='past_due'>Past Due</span>. <br />";
                                            $timing .= "Alerts stop when their Treatment Goals are completed.";
                                            $timing = xlts($timing);
                                            $timing = "<div>".$timing."</div><br />";
                                        }
                                        if ($rule->hasRuleType(RuleType::from('patientreminder'))) {
                                            $timing_pt = "This CR ";
                                            if ($clinical=='1') {
                                                $timing_pt .= "also ";
                                            }
                                            $timing_pt .= "triggers a <span class='bold'>Patient Reminder</span>.";
                                            $timing_pt = xlts($timing_pt);
                                            $timing_pt = "<div>".$timing_pt."<br /></div><div class='indent10'>";
            
                                            $timing_pt .= xlts("A message will be sent to the patient.");
                                            if ($GLOBALS['medex_enable'] == '1') {
                                                $timing_pt .= " ".xlts("<br /><a href='https://medexbank.com/'>MedEx</a> will send an e-mail, SMS text and/or a voice message as requested.");
                                            }
                                            $timing_pt .= "</div>";
            
                                        }
                                        if ( ($GLOBALS['medex_enable'] == '1') && ($rule->hasRuleType(RuleType::from('provideralert'))) ) {
                                            $timing_prov = "<div>".xlts("<span class='bolder red'>This CR has a Provider Alert!</span>")."</div>";
                                            $timing_prov .= "<div class='indent10'>".xlts("<span class='bold'>Provider Alert</span>: A message will be sent to the provider.");
                                            $timing_prov .="<br />".xlts("<a href='https://medexbank.com/'>MedEx</a> will send an e-mail, SMS text and/or a voice message as requested.");
                                            $timing_prov .= "</div>";
                                        }
                                    } else {
                                        $timing = "<span class='bold'>".xlt('None. Edit this CR to create an Alert!')."</span><br />";
                                    }
    
                                    echo $timing;
                                    echo $timing_pt;
                                    echo $timing_prov;
                                ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right">
                                <span class="underline"><?php echo xlt('Developer'); ?>:</span></td>
                            <td><?php echo text($rule->developer); ?></td>
                            <td class="text-right">
                                <span class="underline"><?php echo xlt('Funding Source'); ?>:</span></td>
                            <td><?php echo text($rule->funding_source)?:xlt("None"); ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">
                                <span class="underline"><?php echo xlt('Release'); ?>:</span></td>
                            <td><?php echo text($rule->release); ?></td>
                            <td class="text-right underline">
                                <span data-toggle='popover'
                                      title='Reference'
                                      data-html="true"
                                      data-trigger='hover'
                                      data-placement='auto'
                                      data-content='<?php echo xla('When present, References appear in the Dashboard CR widget as'); ?> <i class="fa fa-link text-primary"></i>.
                                      <hr>
                                      <img width="250px" class="table-bordered" src="<?php echo $GLOBALS['webroot'];?>/interface/super/rules/www/CR_widget.png">
                                      <hr><?php echo xla('This clickable link leads to the url specified here. It is suggested to link out to relevant clinical information, perhaps a government publication explaining why this CR exists. However, you can link to anything desired.');?>
                                      <?php
                                        if ($rule->web_ref) {
                                            echo xla('Currently this reference links to').' '. attr($rule->web_ref);
                                        } else {
                                            echo xla('Currently this reference does not link to anything.');
                                        } ?>
                                      '>
                                    <i class="fa fa-link"></i> <?php echo xlt('Reference'); ?>:</span>
                                </span>
                            </td>
                            <td><a href="<?php echo attr($rule->web_ref); ?>"><?php
                            if ($rule->web_ref) {
                                $in = attr($rule->web_ref);
                                echo mb_strlen($in) > 30 ? mb_substr($in, 0, 25) . "..." : $in;
                            } else {
                                echo xlt("None");
                            }
                            ?></a></td>
                        </tr>
                        <tr>
                            <td class="text-right">
                                <span data-toggle='popover'
                                      title='<?php echo xla('Public Description'); ?>'
                                      data-html="true"
                                      data-trigger='hover'
                                      data-placement='auto'
                                      data-content='<?php echo xla('The text here will be displayed in the CR widget via a tooltip. Use it to describe to your staff what this CR means.'); ?>
                                            <hr>
                                            <img width="250px" src="<?php echo $GLOBALs['webroot'];?>/interface/super/rules/www/CR_tooltip.png">
                                            <hr>
                                        <?php echo xla('In the CR widget, each Treatment Goal in this CR carries this description as a tooltip. It is also a clickable link. This link leads to either a pop-up (add a note and/or mark the task completed), or to an external link. This link is set separately from the Reference link. Each Treatment Goal can have a unique link that is defined in the last step of this process (see PROMPTING YOU TO DO THIS below).'); ?> '>
                                    <span class="underline"><?php echo xlt('Description'); ?></span>:
                                </span>
                            </td>
                            <td colspan="3">
                                <?php echo attr($rule->public_description); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-6" id="show_summary_edit" style="display: none;">
            <div class="section row">
                <button class="btn-sm btn-primary icon_1"
                        id="save_summary"
                        title="<?php echo xla('Refresh'); ?>"><i class="fa fa-refresh"></i>
                </button>
                <div class="col-12 text-left">
                    <span class="title "><?php echo xlt('Summary'); ?> </span>
                    <table class="table table-sm table-condensed text-left">
                        <tr>
                            <td class="text-right align-baseline">
                                *<span class="underline"><?php echo xlt('Name'); ?>:</span>
                            </td>
                            <td>
                                <input type="text" name="summary_title" class="field" id="fld_title" value="<?php echo attr($rule->title); ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right align-baseline">
                                <span class="underline align-text-top"><?php echo xlt('Alert Type'); ?>:</span>
                            </td>
                            <td>
                                <?php
                                foreach (RuleType::values() as $type) {
                                    if (($GLOBALS['medex_enable'] !='1') && ($type =="provideralert")) {
                                        continue;
                                    }?>
                                        <label><input name="fld_ruleTypes[]"
                                                      value="<?php echo attr($type); ?>"
                                                      type="checkbox" <?php echo $rule->hasRuleType(RuleType::from($type)) ? "CHECKED": "" ?>>
                                            <?php echo text(RuleType::from($type)->lbl); ?>
                                        </label>
                                    <?php }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right align-baseline">
                                <span class="text-right underline"><?php echo xlt('Developer'); ?>:</span>
                            </td>
                            <td class="text-left">
                                <input type="text" name="summary_developer" class="field" id="fld_developer" value="<?php echo attr($rule->developer); ?>" maxlength="255">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right">
                                <span class="underline align-middle"><?php echo xlt('Funding Source'); ?>:</span>
                            </td>
                            <td class="text-left">
                                <input type="text" name="summary_funding_source" class="FORM-CONTROL field" id="fld_funding_source" value="<?php echo attr($rule->funding_source); ?>" maxlength="255">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right">
                                <span class="underline align-middle"><?php echo xlt('Release'); ?>:</span>
                            </td>
                            <td class="text-left">
                                <input type="text" name="summary_release" class="field" id="fld_release" value="<?php echo attr($rule->release); ?>" maxlength="255">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right"><span
                                        data-toggle='popover'
                                        title='Reference'
                                        data-html="true"
                                        data-trigger='hover'
                                        data-placement='auto'
                                        data-content='<?php echo xlas('References appear in the Dashboard CR widget as <i class="fa fa-link"></i> and can link to anything desired.'); ?>
                                                    <img width="250px" src="<?php echo $GLOBALS['webroot'];?>/interface/super/rules/www/CR_widget.png">'
                                        class="underline"><?php echo xlt('Reference'); ?><i class="fa fa-link"></i>:
                            </td>
                            <td class="text-left">
                                <input type="text" name="summary_web_reference" class="field" id="fld_web_reference" value="<?php echo attr($rule->web_ref); ?>" maxlength="255">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right">
                                <span data-toggle='popover'
                                      title='<?php echo xla('Public Description'); ?>'
                                      data-html="true"
                                      data-trigger='hover'
                                      data-placement='auto'
                                      data-content='<?php echo xla('The text here will be displayed in the Dashboard::CR widget via a tooltip. Use it to describe to your staff what this CR means.'); ?>
                                        <hr>
                                        <img width="250px" src="<?php echo $GLOBALS['webroot'];?>/interface/super/rules/www/CR_tooltip.png">
                                        <hr>
                                        <?php echo xla('Every Treatment Goal (created in Step 2 below) added to this CR is listed in the Dashboard::CR widget. Each Treatment Goal in this CR carries this same description as a tooltip, but they can link to different things. Clicking a link leads either to a pop-up (add a note and/or mark the task completed), or to an external link. This link is set separately from the Reference link above - it is defined in Step 2.'); ?> '>
                                    <span class="underline"><?php echo xlt('Description'); ?></span>:
                                </span>
                            </td>
                            <td>
                                <textarea class="form-control"
                                          id="fld_public_description"
                                          name="summary_public_description"
                                          placeholder="<?php echo xla('Add text here to describe what this CR does. It appears as a Tooltip in the CR widget.'); ?>"><?php echo attr($rule->public_description); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>
                <div id="summary_report"></div>
                <div id="required_msg" class="col-11 offset-1">
                    * <?php echo xlt('Developer, Funding Source, Release and Reference are MU2 requirements.'); ?>

                </div>
            </div>
        </div>
        <div class="col-6">
            <!-- rule filter criteria -->
            <?php $filters = $rule->filters; if ($filters) { ?>
                <div class="section row" id="show_filters">
                    <button id="add_filters"
                            onclick="top.restoreSession()"
                            class="btn-sm btn-primary icon_2"
                            title='<?php echo xla('Refine the Target'); ?>'><i class="fa fa-plus"></i>
                    </button>
                    <button class="btn-sm btn-primary icon_1"
                            id="show_filters_help"
                            data-toggle="modal" data-target="#help_filters"
                            title="<?php echo xla('Open the Help:: Who will this CR affect?'); ?>"><i class="fa fa-question"></i>
                    </button>

                    <div class="col-sm-12">
                        <span class="title text-left"><?php echo xlt('Step 1: Who are we targeting?'); ?> </span>

                        <table class="table table-hover table-sm table-condensed">
                            <thead>
                            <tr>
                                <th scope="col" class="text-center underline">
                                    <span class="underline"><?php echo xlt('Edit'); ?></span>
                                </th>
                                <th scope="col" class="text-center underline">
                                    <?php echo xlt('Delete'); ?></th>
                                <th scope="col" class="text-center underline">
                                    <?php echo xlt('Look at'); ?>:</th>
                                <th scope="col" class="text-center underline">
                                    <?php echo xlt('Look For'); ?>:</th>
                                <th scope="col" class="text-center underline">
                                    <?php echo xlt('Possible Targets'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($filters->criteria) {
                                foreach ($filters->criteria as $criteria) { ?>
                                        <tr>
                                            <td scope="row">
                                                <button id="edit_filter_<?php echo attr($criteria->uid); ?>"
                                                        title="<?php echo xla('Edit this Criteria'); ?>"
                                                        class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <button onclick="top.restoreSession();location.href='index.php?action=edit!delete_filter&id=<?php echo attr_url($rule->id); ?>&rf_uid=<?php echo attr_url($criteria->uid); ?>'"
                                                        class="btn btn-sm btn-danger"
                                                        title="<?php echo xla('Remove this criterion'); ?>'"><i class="fa fa-trash-o"></i>
                                                </button>
                                            </td>
                                            <td class="text-center"><?php echo( text($criteria->getTitle()) ); ?></td>
                                            <td class="text-center"><?php echo $criteria->getRequirements(); ?></td>
                                            <td class="text-center"><?php echo( text($criteria->getCharacteristics()) ); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td class="text-center text-middle" colspan="5"><?php echo xlt('All patients are targeted. Please refine your selection criteria.'); ?></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>
            <div class="section row" id="show_filters_edit">
            </div>
        </div>
        <div class="section row col-12">
                <?php
                    $nextGroupId = 0;
                    //we cannot use count.  If two new groups are created, group 1&2, and group 1 is deleted
                    //newgroup will still be 3, not count(groups) + 1
                foreach ($rule->groups as $group) {
                    $nextGroupId = $group->groupId + 1;
                }
                ?>
                <div class="col-12">
                    <button type="button"
                            class="btn-sm btn-primary icon_3"
                            id="show_intervals_help"
                            data-toggle="modal" data-target="#help_intervals"
                            title="<?php echo xla('Open the Help:: Actions Modal'); ?>"><i class="fa fa-clock-o"></i>
                    </button>
                    <button id="new_group_<?php echo (int)($nextGroupId);?>"
                          type="button"
                          class="btn-sm btn-primary icon_2"
                          data-toggle='popover'
                          data-trigger="hover"
                          data-placement="auto left"
                          data-html="true"
                          data-title='Step 2: Add A New Group'
                          data-content="<?php echo xlas("<span class='text-justify'>Having narrowed your target group of patients in <span class='bold'>Step 1</span>, now in <span class='bold'>Step 2</span> you need to look for an item. If present, an alert fires prompting you to do something, usually a Treatment Goal. Most CRs only need to reference one Treatment Goal. You can create multiple <span class='bold'>Step 2</span> criteria for a given group of patients identified in <span class='bold'>Step 1</span>. Remember each Treatment Goal is displayed separately in the Dashboard's CR widget and each can trigger a separate Active Alert.  Be wary of Alert Fatigue! If you wish to fire multiple Alerts for a Targeted group, consider using Care Plans to combine Alerts. Expert use only...</span>"); ?>"><i class="fa fa-plus"></i>
                    </button>
                    <button type="button"
                            class="btn-sm btn-primary icon_1"
                            data-toggle="modal" data-target="#help_targets"
                            title="<?php echo xla('Open the Help Page').":: ".xla('When will this CR fire?'); ?>"><i class="fa fa-question"></i>
                    </button>
                    
                    <span class="title text-left"><?php echo xlt('Step 2').": ".xlt('When will this CR fire?'); ?></span>
                </div>
                <?php
                foreach ($rule->groups as $group) {
                    ?>
                        <div class="row" id="show_group_<?php echo attr($group->groupId); ?>">
                            <div class="col-6 inline">
                                <div class="col-12 title2"> <?php echo xlt('If we need this to happen'); ?>:</div>
                                <div class="col-12" id="show_targets_<?php echo attr($group->groupId); ?>">
                                    <button type="button"
                                            id="add_criteria_target_<?php echo attr($group->groupId);?>"
                                            class="btn-sm btn-primary icon_1"
                                            title='<?php echo xla('Add New Target'); ?>'><i class="fa fa-plus"></i>
                                    </button>
                                    <table class="table table-sm table-condensed bgcolor2 section2">
                                        <thead>
                                        <tr>
                                            <td class="text-center underline">
                                            <?php echo xlt('Edit'); ?>
                                            </td>
                                            <td class="text-center underline">
                                            <?php echo xlt('Delete'); ?>
                                            </td>
                                            <td class="text-center underline" colspan="3">
                                            <?php echo xlt('Look at'); ?>:
                                            </td>
                                            <td class="text-center underline" colspan="3">
                                            <?php echo xlt('Look for'); ?>:
                                            </td>
                                            <td class="text-center underline" colspan="3">
                                            <?php echo xlt('Cohort'); ?>:
                                            </td>
                                        </tr>
                                        </thead>
                                        <!-- rule target criteria -->
                                    <?php
                                        //$groupId = $group->groupId;
                                        $targets = $group->ruleTargets;
                                    if ($targets) {
                                        if ($targets->criteria) {
                                            foreach ($targets->criteria as $criteria) { ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <button id="edit_target_<?php echo attr($group->groupId); ?>_<?php echo attr($criteria->uid); ?>"
                                                                        class="btn btn-sm btn-primary indent10"
                                                                        title="<?php echo xla('Edit this Clinical Target'); ?>."><i class="fa fa-pencil"></i>
                                                                </button>
                                                            </td>

                                                            <td class="text-center">
                                                                <button onclick="top.restoreSession();location.href='index.php?action=edit!delete_target&id=<?php echo attr_url($rule->id); ?>&group_id=<?php echo attr_url($group->groupId); ?>&rt_uid=<?php echo attr_url($criteria->uid); ?>';"
                                                                        class="btn btn-sm btn-danger indent10"
                                                                        title="<?php echo xla('Delete this Clinical Target'); ?>."><i class="fa fa-trash-o"></i>
                                                                </button>
                                                            </td>
                                                            <td colspan="3" class="text-center"><?php echo(text($criteria->getTitle())); ?></td>
                                                            <td colspan="3" class="nowrap"><?php
                                                            echo $criteria->getRequirements(); //escaped in interface/super/rules/library/RuleCriteriaDatabaseBucket.php
                                                            ?>
                                                            <?php echo is_null($criteria->getInterval()) ? "" : " <br /> " . xlt('every') . " " . text($criteria->getInterval()); ?>
                                                            </td>
                                                            <td colspan="3" class="text-center"><?php echo(text($criteria->getCharacteristics())); ?></td>

                                                        </tr>
                                                    <?php }
                                        } else { ?>
                                                    <tr><td><?php echo xlt('None defined'); ?></td></tr>
                                                    <?php
                                        }
                                    } ?>
                                    </table>
                                </div>
                                <div id="show_targets_edit_<?php echo attr($group->groupId); ?>"></div>
                            </div>
                            <div class="col-2" class="display intervals">
                                <span class="title2 text-center"><?php echo xlt('This happens:'); ?></span>
                                <span class="title2 bold text-center">
                                <?php
    
                                if (!$something) {
                                    echo "<br /><span class='bold'>".xlt('There are no Alerts selected!')."</span><br />";
                                } else {
                                    if ($rule->hasRuleType(RuleType::from('activealert'))) {
                                        echo xlt("Active alert")."<br />";
                                    }
                                    if ($rule->hasRuleType(RuleType::from('passivealert'))) {
                                        echo "<br />".xlt("Passive alert")."<br />";
                                    }
                                    if ($rule->hasRuleType(RuleType::from('patientreminder'))) {
                                        echo "<br />".xlt("Patient Reminder")."<br />";
                                    }
                                    if ($rule->hasRuleType(RuleType::from('provideralert'))) {
                                        echo "<br />".xlt("Provider alert")."<br />";
                                    }
                                }
                                //echo $timing;
                                ?><br />
                                </span>
                            </div>
                            <div class="col-4 inline">
                                <div class="col-12 title2"><?php echo xlt('Prompting you to do this'); ?>:</div>
                                <div class="col-12"
                                     id="show_actions_<?php echo xla($group->groupId); ?>"
                                     name="show_actions">
                                    <button type="button"
                                            class="btn-sm btn-primary icon_1"
                                            id="add_action_<?php echo (int)($group->groupId); ?>"
                                            title='<?php echo xla('Add New Treatment Goal'); ?>'><i class="fa fa-plus"></i>
                                    </button>


                                    <table class="table table-sm bgcolor2 section2 text-center">
                                        <thead>
                                        <tr>
                                            <td class="underline">
                                            <?php echo xlt('Edit'); ?>
                                            </td>
                                            <td class="underline">
                                            <?php echo xlt('Delete'); ?>
                                            </td>
                                            <td class="underline" colspan="4">
                                            <?php echo xlt('Treatment Goal'); ?>
                                            </td>
                                            <td class="underline">
                                                <?php echo xlt('Confirm pop-up'); ?>?
                                            </td>
                                            <td class="underline"><?php echo xlt('Link Out'); ?></td>
                                        </tr>
                                        </thead>
                                    <?php
                                        $actions = $group->ruleActions;
                                            
                                    if ($actions->actions) {
                                        foreach ($actions->actions as $action) {   ?>
                                                    <tr class="baseboard">
                                                        <td>
                                                            <button id="edit_action_<?php echo attr($group->groupId); ?>_<?php echo attr($action->ra_uid); ?>"
                                                                    class="btn btn-sm btn-primary"
                                                                    title="Edit this Action."><i class="fa fa-pencil"></i>
                                                            </button>
                                                        </td>

                                                        <td>
                                                            <button onclick="top.restoreSession();location.href='index.php?action=edit!delete_action&id=<?php echo attr_url($rule->id); ?>&group_id=<?php echo attr_url($group->groupId); ?>&ra_uid=<?php echo attr_url($action->ra_uid); ?>';"
                                                                    class="btn btn-sm btn-danger"
                                                                    title="<?php echo xla('Delete this Clinical Target'); ?>."><i class="fa fa-trash-o"></i>
                                                            </button>
                                                        </td>
                                                        <td colspan="4">
                                                            <?php echo text($action->getTitle()); ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            if ($action->customRulesInput==1) {
                                                                echo xlt('Yes');
                                                            } else {
                                                                echo xlt('No');
                                                            } ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            if (!empty($action->reminderLink)) {
                                                                echo "<a href='".attr_url('$action->reminderLink')."' target='_blank'>".xlt('Yes')."</a>";
                                                            } else {
                                                                echo xlt('No');
                                                            } ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                        }
                                    } else { ?>
                                                <tr>
                                                    <td class="text-center" colspan="6">
                                                    <?php echo xlt('None defined'); ?>
                                                    </td>
                                                </tr>
                                                <?php
                                    }
                                    ?>
                                    </table>
                                </div>
                                <div class="col-12"
                                     id="show_actions_edit_<?php echo attr($group->groupId); ?>"
                                     name="show_actions_edit"></div>
                            </div>
                        </div>
                        <?php
                } // iteration over groups
                ?>
                <div class="row col-12" id="show_group_<?php echo attr($nextGroupId); ?>">
                    
                    <div class="col-6 inline row">
                        <button type="button"
                                id="add_criteria_target_<?php echo attr($nextGroupId);?>"
                                class="btn-sm btn-primary icon_2"
                                title='<?php echo xla('Add New Target'); ?>'><i class="fa fa-plus"></i>
                        </button>
                        

                        <button
                                class="btn-sm btn-primary icon_1"
                                id="show_actions_help"
                                data-toggle="modal" data-target="#help_alerts"
                                title="<?php echo xla('Open the Help:: Actions Modal'); ?>"><i class="fa fa-question"></i>
                        </button>
                        <span class="title2 text-left"><?php echo xlt('If we need this to happen'); ?>:</span>
                        <div id="show_targets_<?php echo attr($nextGroupId); ?>"></div>
                        <div id="show_targets_edit_<?php echo attr($nextGroupId); ?>"></div>
                    </div>
                    <div class="col-2" class="display intervals">
                        <span class="title2 text-center"><?php echo xlt('This happens'); ?>:</span>
                        <span class="title2 bold text-center">
                                <?php
    
                                if (!$something) {
                                    echo "<br /><span class='bold'>".xlt('There are no Alerts selected!')."</span><br />";
                                } else {
                                    if ($rule->hasRuleType(RuleType::from('activealert'))) {
                                        echo xlt("Active alert")."<br />";
                                    }
                                    if ($rule->hasRuleType(RuleType::from('passivealert'))) {
                                        echo "<br />".xlt("Passive alert")."<br />";
                                    }
                                    if ($rule->hasRuleType(RuleType::from('patientreminder'))) {
                                        echo "<br />".xlt("Patient Reminder")."<br />";
                                    }
                                    if ($rule->hasRuleType(RuleType::from('provideralert'))) {
                                        echo "<br />".xlt("Provider alert")."<br />";
                                    }
                                }
                                    //echo $timing;
                                ?><br />
                            </span>
                    </div>

                    <div class="col-4 row">
                        <button type="button"
                                id="add_action_<?php echo attr($nextGroupId); ?>"
                                class="btn-sm btn-primary icon_A2"
                                title='<?php echo xla('Add New Action'); ?>'><i class="fa fa-plus"></i>
                        </button>
                        <button
                                class="btn-sm btn-primary icon_A1"
                                id="show_actions_help"
                                data-toggle="modal" data-target="#help_intervals2"
                                title="<?php echo xla('Open the Help:: Actions Modal'); ?>"><i class="fa fa-question"></i>
                        </button>
                        <span class="title2 text-left"><?php echo xlt('Prompting you to do this'); ?>:</span>

                        <div id="show_actions_<?php echo attr($nextGroupId);?>" class="col-12" ></div>
                        <div id="show_actions_edit_<?php echo attr($nextGroupId);?>" class="col-12" ></div>
                    </div>
                </div>
            </div>

    <!-- Help Modals -->
    <div id="help_filters" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title title"><?php echo xlt('Who are we Targeting'); ?>?</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body container">
                    <div class="row">
                        <div class="col-10 offset-1">
                            <div class="title2"><?php echo xlt('Define the cohort of patients that this CR affects'); ?></div>
                        </div>
                        <div class="col-10 offset-1">
                            <?php echo xlts('Target patients by the value of any criterion eg. <span class="bolder">age, sex, demographics, diagnosis, etc.</span>'); ?>
                                    <br />
                            <?php echo xlt("Patients matching these criteria"); ?>:
                            <ul>
                                <li><span class="bold"><?php echo xlt('may be included'); ?></span>: <?php echo xlt('include patients matching this criterion (among others)'); ?>.  </li>
                                <ul>
                                    <li><?php echo xlt('If there is more than one criteria, patients matching any of the criteria are included.'); ?></li>
                                    <li><?php echo xlts('Used when there are <span class="bolder">multiple optional inclusion</span> criteria to allow the inclusion of multiple sub groups of patients.'); ?></li>
                                </ul>
                                <li><span class="bold"><?php echo xlt('must be included'); ?></span>: <?php echo xlt('targeted patients must meet this criterion'); ?></li>
                                <li><span class="bold"><?php echo xlt('may be excluded'); ?>'</span>: <?php echo xlt('If there is more than one criteria, patients matching any of the criteria are excluded.'); ?></li>
                                <ul>
                                    <li><?php echo xlt('If there is more than one exclusion criteria, patients matching any of these exclusion criteria are excluded.'); ?></li>
                                </ul></li>
                                <li><span class="bold"><?php echo xlt('must be excluded'); ?></span>: <?php echo xlt('If this matches, exclude these patients no matter if any other criteria match.');?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-dismiss="modal"><?php echo xlt('Close');?></button>
                </div>
            </div>
        </div>
    </div>
   
    <div id="help_targets" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-body row">
                    <div class="col-6">
                        <span class="title2"><?php echo xlt('If this is true'); ?>:</span>
                    </div>
                    <div class="col-6">
                        <span class=" title2"><?php echo xlt('Prompting you to do this'); ?>:</span>
                    </div>
                    
                    <div class="col-6">
                          <span class="text"><?php echo xlt('Define the items to trigger an alert'); ?>
                        <ul>
                            <li> <?php echo xlt('Social History: choose a "lifestyle" value'); ?> </li>
                            <li> <?php echo xlt('Did a specific Assessment occur?'); ?></li>
                            <li> <?php echo xlt('Is an Education event needed?'); ?></li>
                            <li> <?php echo xlt('Should a specific Examination occur?'); ?></li>
                            <li> <?php echo xlt('Do we need an Intervention?'); ?></li>
                            <li> <?php echo xlt('Was a specific Measurement noted?'); ?></li>
                            <li> <?php echo xlt('Did a Reminder occur?'); ?></li>
                            <li> <?php echo xlt('Did a specific Treatment happen?'); ?></li></select>
                            <li> <?php echo xlt('Custom Input:  link to any field in any table in the database'); ?></li>
                        </ul>
                          </span>
                    </div>
                    
                    <div class="col-6">
                        <span class="text"><?php echo xlt('Define what needs to happen to satisfy this alert'); ?></span>

                        <ul>
                            <li> <?php echo xlt('Social History: enter a "lifestyle" value'); ?> </li>
                            <li> <?php echo xlt('Perform a specific Assessment'); ?></li>
                            <li> <?php echo xlt('Provide an Education event and mark "Completed"'); ?></li>
                            <li> <?php echo xlt('Perform a specific Examination'); ?></li>
                            <li> <?php echo xlt('Provide an Intervention and mark "Completed"'); ?></li>
                            <li> <?php echo xlt('Document a specific Measurement'); ?></li>
                            <li> <?php echo xlt('Update a specific database field'); ?></li>
                            <li> <?php echo xlt('Perform a specific Treatment'); ?></li></select>
                            <li> <?php echo xlt('Custom answer:  advanced users only...'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-dismiss="modal"><?php echo xlt('Close'); ?></button>
                </div>
            </div>

        </div>
    </div>
    <div id="help_intervals" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title title"><?php echo xlt('When is this Clinical Reminder triggered'); ?>?</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body container">
                    <div class="row">
                        <div class="col-12 text-center"><h6><?php echo xlt('In Step 2, we define "What needs to happen" and "How often should it recur"'); ?>.</h6></div>
                    </div>
                    <div class="row">
                        <div class="col-11 offset-1 text-center title"> <?php echo xlt('TIME'); ?> ----------&gt;</div>
                        
                        <div class="col-1 offset-1 text-right bold"><?php echo xlt('Last event'); ?> </div>
                        <div class="col-5 alert-success text-center text-nowrap bold"><?php echo xlt('This should occur once every day/week/month/year'); ?>:</div>
                        <div class="col-5 bold text-left"><h6 style="position:relative;left:-20px;">X &lt;-- <?php echo xlt('Due Date'); ?></h6></div>

                        <div class="col-3 offset-2"></div>
                        <div class="col-2 alert-warning text-center"><span class="underline"><?php echo xlt('Warning Period'); ?></span><br />
                            <?php echo xlt('an interval of time').' <span class="bold">'.xlt('before').'</span> '.xlt('the due date'); ?>
                        </div>
                        <div class="col-2 text-center alert-primary"><span class="underline"><?php echo xlt('Due Period'); ?></span><br />
                            <?php echo xlt('an interval of time');?>
                            <span class="bold"><?php echo xlt('from');?></span> <?php echo xlt(' the due date onward'); ?>
                        </div>
                        <div class="col-2 text-center alert-danger text-nowrap"><span class="underline"><?php echo xlt('Over Due Period'); ?></span> -----&gt;</div>
                        <div class="col-1"></div>

                        <div class="col-11 offset-1 text-center">&nbsp;</div>

                        <div class="col-2 offset-5 alert-warning text-center bold tight">
                            <input data-grp-tgt="clinical"
                                   type="text"
                                   id="clinical-pre"
                                   value="<?php echo attr($timings['clinical']['pre']['amount']); ?>">
                            <?php
                                echo  generate_select_list(
                                    "clinical",
                                    "rule_reminder_intervals",
                                    $timings['clinical']['pre']['timeUnit']."",
                                    "clinical-pre-timeunit",
                                    '',
                                    'small',
                                    '',
                                    "clinical-pre-timeunit",
                                    array( "data-grp-tgt" => "clinical" )
                                );
                                ?>
                        </div>
                        <div class="col-2 text-center alert-primary bold tight"><input data-grp-tgt="clinical" type="text" id="clinical-post" value="<?php echo attr($timings['clinical']['pre']['amount']); ?>">
                            <?php
                                echo  generate_select_list(
                                    "clinical",
                                    "rule_reminder_intervals",
                                    $timings['clinical']['post']['timeUnit']."",
                                    'clinical-post-timeunit',
                                    '',
                                    'small',
                                    "",
                                    "clinical-post-timeunit",
                                    array( "data-grp-tgt" => "clinical" )
                                );
                                ?></div>
                        <div class="col-2 text-center alert-danger text-nowrap"><span style="position:relative;left:-20px;"> </div>
                        <div class="col-1"></div>

                        
                        <div class="col-2 text-center offset-3 text-right title3"><?php echo xlt('Passive Alerts'); ?>: </div>
                        <div class="col-2 alert-warning text-center"><?php echo xlt('Marked as'); ?> <span class="due_soon"><?php echo xlt('Due soon'); ?></span><br /> <?php echo xlt('in CR widget'); ?></div>
                        <div class="col-2 text-center alert-primary"><?php echo xlt('Marked as'); ?> <span class="due_now"><?php echo xlt('Due'); ?></span><br /> <?php echo xlt('in CR widget'); ?></div>
                        <div class="col-2 text-center alert-danger text-nowrap"><?php echo xlt('Marked as'); ?> <span class="past_due"><?php echo xlt('Past Due'); ?></span><br /><?php echo xlt('in CR widget'); ?></div>
                        <div class="col-1"></div>

                        
                        <div class="col-2 text-center offset-3 text-right title3"><?php echo xlt('Active Alerts'); ?>: </div>
                        <div class="col-2 alert-warning text-center align-text-bottom"><br /><?php echo xlt('Fire/Pop-up'); ?></div>
                        <div class="col-2 text-center alert-primary align-text-bottom"><br /><?php echo xlt('until'); ?> </div>
                        <div class="col-2 text-center alert-danger align-text-bottom"><br /><?php echo xlt('satisfied'); ?></div>
                        <div class="col-1"></div>

                        <div class="col-11 offset-1 text-center">&nbsp;</div>


                        <div class="col-2 text-center offset-3 text-right title3"><?php echo xlt('Patient Reminders'); ?>: </div>
                        <div class="col-2 alert-warning text-center">
                            <?php
                                /*
                                 * Currently OpenEMR only sends one reminder for an action and its timing is set by
                                 * GLOBALS::Notifications::Email/SMS Notification Hours
                                 * So it apears that the reminder pre and post values are not used?
                                <span class="tight"><input data-grp-tgt="patient" type="text" id="patient-pre" value="<?php echo attr($timings['patient']['pre']['amount']); ?>">
                                <?php

                               echo  generate_select_list(
                                    "patient",
                                    "rule_reminder_intervals",
                                    $timings['patient']['pre']['timeUnit']."",
                                    'patient-pre-timeunit',
                                    '',
                                    'small',
                                    "",
                                    "patient-pre-timeunit",
                                    array( "data-grp-tgt" => "patient" ));
                            ?></span><br />
                                        <?php echo xlt('If configured, a Message is sent informing the patient there is a Treatment Goal coming due soon.'); ?>*
                                */
                            ?>
                        </div>
                        <div class="col-2 text-center alert-primary">
                            <?php
                            if (!$GLOBALS['medex_enable']) {
                                echo xlt('Patient Reminders are not delivered until you actively initiate the "Process Reminders" task');
                            } else {
                                echo xlt("MedEx sends out email, SMS and/or Voice messages as configured");
                            }
                            ?>
                        </div>
                        <div class="col-2 alert-danger"><span class="tight">
                              <?php  /*
                                        <input data-grp-tgt="patient" type="text" id="patient-post" value="<?php echo attr($timings['patient']['post']['amount']); ?>">
                                            <?php echo $timings['patient']['post']['timeunit'];
                                                echo  generate_select_list(
                                                    "patient",
                                                    "rule_reminder_intervals",
                                                    $timings['patient']['post']['timeUnit']."",
                                                    "patient-post-timeunit",
                                                    '',
                                                    'small',
                                                    '',
                                                    "patient-post-timeunit",
                                                    array( "data-grp-tgt" => "patient" ));
                                            ?></span><br />
                                        <?php echo xlt('If you have an over-due message configured for this CR, it will be sent this long after the due date'); ?>*</span>
                                   */
                                ?>
                        </div>

                        <?php
                        if ($GLOBALS['medex_enable']) { ?>
                                <div class="col-11 offset-1 text-center">&nbsp;</div>

                                <div class="col-2 text-center offset-3 text-right title3"><?php echo xlt('Provider Reminders'); ?>: </div>
                        <div class="col-2 alert-warning text-center">
                            <?php
                            /*
                             * Currently OpenEMR only sends one reminder for an action and its timing is set by
                             * GLOBALS::Notifications::Email/SMS Notification Hours
                             * So it apears that the reminder pre and post values are not used?
                            <span class="tight"><input data-grp-tgt="provider" type="text" id="provider-pre" value="<?php echo attr($timings['provider']['pre']['amount']); ?>">
                            <?php

                           echo  generate_select_list(
                                "provider",
                                "rule_reminder_intervals",
                                $timings['provider']['pre']['timeUnit']."",
                                'provider-pre-timeunit',
                                '',
                                'small',
                                "",
                                "provider-pre-timeunit",
                                array( "data-grp-tgt" => "provider" ));
                            ?></span><br />
                                    <?php echo xlt('If configured, a Message is sent informing the patient there is a Treatment Goal coming due soon.'); ?>*
                            */
                            ?>
                        </div>
                        <div class="col-2 text-center alert-primary">
                            <?php echo xlt("MedEx sends out email, SMS and/or Voice messages as configured"); ?>
                        </div>
                        <?php } ?>
                        <div class="col-2 alert-danger"><span class="tight">
                              <?php  /*
                                        <input data-grp-tgt="provider" type="text" id="patient-post" value="<?php echo attr($timings['provider']['post']['amount']); ?>">
                                            <?php echo $timings['provider']['post']['timeunit'];
                                                echo  generate_select_list(
                                                    "provider",
                                                    "rule_reminder_intervals",
                                                    $timings['provider']['post']['timeUnit']."",
                                                    "provider-post-timeunit",
                                                    '',
                                                    'small',
                                                    '',
                                                    "provider-post-timeunit",
                                                    array( "data-grp-tgt" => "provider" ));
                                            ?></span><br />
                                        <?php echo xlt('If you have an over-due message configured for this CR, it will be sent this long after the due date'); ?>*</span>
                                   */
                                ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-dismiss="modal"><?php echo xlt('Close'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <div id="help_complete" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title"><?php echo xlt('Once this CR becomes Active'); ?>:</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body row">
                    <div class="col-12">

                        <table class="table-100 tight">
                            <thead>
                            <tr>
                                <td class="text-center">
                                    <span class="bold"><?php echo xlt('After this time has passed'); ?>: <i class="fa fa-plus"></i></span>
                                </td>
                                <td class="text-center">
                                    <span class='bold'
                                          data-toggle='popover'
                                          data-trigger="hover"
                                          data-placement="auto"
                                          title='<?php echo xla('Alert Begins'); ?>'
                                          data-content='<?php echo xla('Given a specific due date, an alert will fire this early before the due date.'); ?>'><?php echo xlt('Active Alerts'); ?></span>
                                </td>
                                <td class="text-center">
                                            <span class='bold'
                                                  data-toggle='popover'
                                                  data-trigger="hover"
                                                  data-placement="auto"
                                                  title='<?php echo xla('Alert is Past Due'); ?>'
                                                  data-content='<?php echo xla('Each Clinical Reminder has a specific "due date". After a certain period of time has passed, the Clinical Reminder is considered late. Active alerts stop showing up altogether but Passive alerts (in the CR widget on the demographics page) are labelled "Past Due" after this time period. Patient Reminders that are past due can trigger a second follow-up e-mail if desired. This setting has no effect on Provider Alerts.'); ?>'><?php echo xlt('Passive Alerts'); ?></span>
                                </td>
                            </tr>
                            </thead>
                            <tbody class="text-center tight">
                            <tr>
                                <td>
                                    <input data-grp-tgt="clinical"
                                           type="text"
                                           id="clinical-pre"
                                           value="<?php echo attr($timings['clinical']['pre']['amount']); ?>">
                                    <?php
                                        echo  generate_select_list(
                                            "clinical",
                                            "rule_reminder_intervals",
                                            $timings['clinical']['pre']['timeUnit']."",
                                            "clinical-pre-timeunit",
                                            '',
                                            'small',
                                            '',
                                            "clinical-pre-timeunit",
                                            array( "data-grp-tgt" => "clinical" )
                                        );
                                        ?>
                                </td>
                                <td class="text-center tight" nowrap>
                                    <?php echo xlt('Pop-ups stop'); ?>
                                </td>
                                <td class="text-center tight" nowrap>
                                    <?php echo xlt('Marked as Due in CR widget'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center tight">
                                    <input data-grp-tgt="clinical" type="text" id="clinical-post" value="<?php echo attr($timings['clinical']['pre']['amount']); ?>">
                                    <?php
                                        echo  generate_select_list(
                                            "clinical",
                                            "rule_reminder_intervals",
                                            $timings['clinical']['post']['timeUnit']."",
                                            'clinical-post-timeunit',
                                            '',
                                            'small',
                                            "",
                                            "clinical-post-timeunit",
                                            array( "data-grp-tgt" => "clinical" )
                                        );
                                        ?>
                                </td>
                                <td class="text-center">
                                
                                </td>
                                <td class="text-center">
                                    <?php echo xlt('Marked as'); ?> <span class="past_due"><?php echo xlt('Past Due'); ?></span> <?php echo xlt('in CR widget'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="bold"><?php echo xlt('Patient Reminders'); ?></td>
                                <td class="bold"><?php echo xlt('Provider Alerts'); ?></td>
                            </tr>
                            <tr>
                                <td class="text-center tight" nowrap>
                                    <input data-grp-tgt="patient" type="text" id="patient-pre" value="<?php echo attr($timings['patient']['pre']['amount']); ?>">
                                    <?php
                                        echo  generate_select_list(
                                            "patient",
                                            "rule_reminder_intervals",
                                            $timings['patient']['pre']['timeUnit']."",
                                            'patient-pre-timeunit',
                                            '',
                                            'small',
                                            "",
                                            "patient-pre-timeunit",
                                            array( "data-grp-tgt" => "patient" )
                                        );
                                        ?>
                                </td>
                                <td><?php echo xlt('Reminder is sent'); ?></td>
                                <td><?php echo xlt('Alert is sent'); ?></td>
                            </tr>
                            <tr>
                                <td class="text-center tight" nowrap>
                                    <input data-grp-tgt="patient" type="text" id="patient-post" value="<?php echo attr($timings['patient']['post']['amount']); ?>">
                                    <?php echo $timings['patient']['post']['timeunit'];
                                        echo  generate_select_list(
                                            "patient",
                                            "rule_reminder_intervals",
                                            $timings['patient']['post']['timeUnit']."",
                                            "patient-post-timeunit",
                                            '',
                                            'small',
                                            '',
                                            "patient-post-timeunit",
                                            array( "data-grp-tgt" => "patient" )
                                        );
                                        ?>


                                </td>
                                <td class="text-center tight" nowrap><?php echo xlt('2nd reminder sent'); ?><br /> <?php echo xlt('if still active'); ?>
                                    <input type="hidden" data-grp-tgt="patient" id="patient-post" value="<?php echo attr($timings['patient']['post']['amount'])?:'1'; ?>">
                                    <input type="hidden" name="patient-post-timeunit" id="patient-post-timeunit"  data-grp-tgt="patient" value="day">
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-dismiss="modal"><?php echo xlt('Close'); ?></button>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
    $("#show_summary_edit").hide();
    $("#show_filters_edit").hide();
    $("[id^='help_']").hide();
    $("#show_group_<?php echo attr_js($nextGroupId); ?>").hide();
    $("[id^='show_targets_edit_").hide();
    
    $(function() {
        $("#edit_summary").click(function () {
            $("#show_summary").hide();
            $("#show_summary_edit").show();
        });
        $("#save_summary").click(function () {
            top.restoreSession();
            location.href = 'index.php?action=detail!view&id=' + encodeURIComponent($("#ruleId").val());
        });
        
        $("[name^='summary_'],[name^='fld_ruleTypes'],[name^='intervals_']").change(function () {
            top.restoreSession();
            var url = "index.php?action=edit!submit_summary&id=" + $("#ruleId").val();
            var newTypes = [];
            $('input[name="fld_ruleTypes[]"]:checked').each(function () {
                newTypes.push($(this).val());
            });
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: {
                           'fld_title'              : $("#fld_title").val(),
                           'fld_ruleTypes'          : newTypes,
                           'fld_developer'          : $("#fld_developer").val(),
                           'fld_funding_source'     : $("#fld_funding_source").val(),
                           'fld_release'            : $("#fld_release").val(),
                           'fld_web_reference'      : $("#fld_web_reference").val(),
                           'fld_public_description' : $("#fld_public_description").val(),
                           'clinical_pre'           : $("#clinical-pre").val(),
                           'clinical_post'          : $("#clinical-post").val(),
                           'show': '1'
                       }
                   }).done(function (data) {
                $("#show_summary_1").html(data);
                $("#show_summary_report").html('<?php echo xla("Summary updated successfully"); ?>');
                setTimeout(function () {
                    $("#show_summary_report").html('&nbsp;');
                }, 2000);
            });
            
        });
        $("[id^='clinical-p'],[id^='patient-p'],[id^='provider-p']").change(function () {
            top.restoreSession();
            var url = "index.php?action=edit!submit_intervals&id=" + encodeURIComponent($("#ruleId").val());
            var newTypes = [];
            $('input[name="fld_ruleTypes[]"]:checked').each(function () {
                newTypes.push($(this).val());
            });
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: {
                           'clinical-pre'           : $("#clinical-pre").val(),
                           'clinical-post'          : $("#clinical-post").val(),
                           'patient-pre'            : $("#patient-pre").val(),
                           'patient-post'           : $("#patient-post").val(),
                           'provider-pre'           : $("#provider-pre").val(),
                           'provider-post'          : $("#provider-post").val(),
                           'clinical-pre-timeunit'  : $("#clinical-pre-timeunit").val(),
                           'clinical-post-timeunit' : $("#clinical-post-timeunit").val(),
                           'patient-pre-timeunit'   : $("#patient-pre-timeunit").val(),
                           'patient-post-timeunit'  : $("#patient-post-timeunit").val(),
                           'provider-pre-timeunit'  : $("#provider-pre-timeunit").val(),
                           'provider-post-timeunit' : $("#provider-post-timeunit").val(),
                           'show': '1'
                       }
                   }).done(function (data) {
                $("#show_summary_1").html(data);
                $("#show_summary_report").html('Summary updated successfully');
                setTimeout(function () {
                    $("#show_summary_report").html('&nbsp;');
                }, 2000);
            });
            
        });
        <?php
        if (empty($timings['clinical']['pre']['amount'])) { ?>
        $("#clinical-pre").val('1');
        $("#clinical-post").val('1');
        $("#patient-pre").val('1');
        $("#patient-pre").val('1');
        $("#clinical-pre").trigger('change');
        <?php } ?>
    
        $("#add_filters").click(function () {
            top.restoreSession();
            var url = "index.php?action=edit!add_criteria&id=<?php echo attr_url($rule->id); ?>&criteriaType=filter";
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: {}
                   }).done(function (data) {
                $("#show_filters_edit").html(data);
                $("#show_filters").hide();
                $("#show_filters_edit").show();
            });
        });
        
        $("[id^='edit_filter_']").click(function() {
            top.restoreSession();
            var rf_uid = this.id.match(/edit_filter_(.*)/)[1];
            var url = "index.php?action=edit!filter&id=<?php echo attr_url($rule->id); ?>&rf_uid="+encodeURIComponent(rf_uid);
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: { type : 'filter' }
                   }).done(function (data) {
                $("#show_filters_edit").html(data);
                $("#show_filters").hide();
                $("#show_filters_edit").show();
            });
        });
        
        $("[id^='edit_target_']").click(function() {
            top.restoreSession();
            var group = this.id.match(/edit_target_(.*)_(.*)/)[1];
            var rt_uid = this.id.match(/edit_target_(.*)_(.*)/)[2];
            var url = "index.php?action=edit!target&id=<?php echo attr_url($rule->id); ?>&group_id="+encodeURIComponent(group)+"&rt_uid="+encodeURIComponent(rt_uid);
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: {
                           type : 'target'
                       }
                   }).done(function (data) {
                $("#show_targets_edit_"+group).html(data);
                $("#show_targets_"+group).hide();
                $("#show_targets_edit_"+group).show();
            });
        });
        
        $("[id^='edit_action_'").click(function() {
            top.restoreSession();
            var action = this.id.match(/edit_action_(.*)_(.*)/)[2];
            var group = this.id.match(/edit_action_(.*)_(.*)/)[1];
            $("[name='show_actions_edit']").hide();
            $("[name='show_actions']").show();
    
            var url = 'index.php?action=edit!action&id=<?php echo attr_url($rule->id); ?>group_id='+encodeURIComponent(group)+'&ra_uid='+encodeURIComponent(action);
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: {}
                   }).done(function (data) {
                $("#show_actions_edit_"+group).html(data);
                $("#show_actions_"+group).hide();
                $("#show_actions_edit_"+group).show();
                
            });
            
        });
        
        $("[id^='add_criteria_target_").click(function() {
            top.restoreSession();
            var group = this.id.match(/add_criteria_target_(.*)/)[1];
            var url = 'index.php?action=edit!add_criteria&id=<?php echo attr_url($rule->id); ?>&group_id='+encodeURIComponent(group)+'&criteriaType=target';
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: {}
                   }).done(function (data) {
                $("#show_targets_edit_"+group).html(data);
                $("#show_targets_"+group).hide();
                $("#show_targets_edit_"+group).show();
            });
        });
        $("[id^='add_action_']").click(function() {
            top.restoreSession();
            $("[name='show_actions_edit']").hide();
            $("[name='show_actions']").show();
            var group = this.id.match(/add_action_(.*)/)[1];
            var url = 'index.php?action=edit!add_action&id=<?php echo attr_url($rule->id); ?>&group_id='+encodeURIComponent(group);
            $.ajax({
                       type: 'POST',
                       url: url,
                       data: {}
                   }).done(function (data) {
                $("#show_actions_edit_"+group).html(data).show();
                $("#show_actions_"+group).hide();
            });
        });
        $('#help_intervals').on('show.bs.modal', function () {
            $('#help_intervals').focus();
        });
        
        $('#help_summary').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget) // Button that triggered the modal
            var recipient = button.data('whatever') // Extract info from data-* attributes
            var modal = $(this);
        });
        $("[id^='help_']").click(function () {
            
            $(".modal-dialog").draggable({
                                             handle: ".modal-header",
                                             cursor: 'move',
                                             revert: false,
                                             backdrop: false
                                         });
            
            $(this).css({
                            top: 0,
                            left: 0
                        });
           
            
        });
        $("#timing_toggle").click(function() {
            $("#intervals_edit").toggle();
        });
        $("#new_group_<?php echo (int)($nextGroupId);?>").click(function () {
            $("#show_group_<?php echo (int)($nextGroupId);?>").show();
        });
    });

</script>
