<?php
/**
 * Script to configure the Rules.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Ensoftek
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2019 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__FILE__)."/../../../../../../library/acl.inc");
global $phpgacl_location;
require_once("$phpgacl_location/gacl_api.class.php");
    
    $setting_bootstrap_submenu = prevSetting('', 'setting_bootstrap_submenu', 'setting_bootstrap_submenu', ' ');
?>
<div class="title" style="display:none">
    <?php echo xlt('CR Manager'); ?>
</div>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="header">
                <div class="title">
                    <?php echo xlt('Clinical Reminders & Alerts Manager'); ?>
                </div>
                <div id="show_report" class="red">&nbsp;</div>
            </div>
        </div>
        <div class="col-12">
            <table class="">
                <tr>
                    <td>
                        <a href="javascript:document.cdralertmgr.submit();" class="btn btn-sm btn-primary" onclick="top.restoreSession()"><span><?php echo xlt('Save'); ?></span></a>
                        <a href="javascript:document.cdralertmgr.reset();" class="btn btn-sm btn-primary" onclick="top.restoreSession()"><span><?php echo xlt('Reset'); ?></span></a>
                    </td>
                </tr>
            </table>

            &nbsp;
            <div style="display:block;" class="text-center">

                <form name="cdralertmgr" method="post" action="index.php?action=alerts!submitactmgr" onsubmit="return top.restoreSession()">
                    <table class="table-sm text-center table-striped table-hover thead-light table-responsive">
                        <thead>
                            <th width="450px"><?php echo xlt('Clinical Reminder'); ?></th>
                            <th width="40px">&nbsp;</th>
                            <th width="10px"><?php echo xlt('Edit '); ?></th>
                            <th width="40px">&nbsp;</th>
                            <th width="10px"><?php echo xlt('Active Alert'); ?></th>
                            <th width="40px">&nbsp;</th>
                            <th width="10px"><?php echo xlt('Passive Alert'); ?></th>
                            <th width="40px">&nbsp;</th>
                            <th width="10px"><?php echo xlt('Patient Reminder'); ?></th>
                            <th width="40px">&nbsp;</th>
                            <?php if ($GLOBALS['medex_enable'] == '1') { ?>
                            <th width="10px"><?php echo xlt('Provider Alert'); ?></th>
                            <th width="40px">&nbsp;</th>
                            <?php } ?>
                            <th width="100px"><?php echo xlt('Access Control'); ?> <span title='<?php echo xla('User is required to have this access control for Active Alerts and Passive Alerts'); ?>'>?</span></th>
                            <th width="40px">&nbsp;</th>
                            <th width="100px"><?php echo xlt('Delete'); ?></th>
                        </thead>
                        </th>
                        <?php $index = -1; ?>
                        <?php foreach ($viewBean->rules as $rule) {?>
                            <?php $index++; ?>
                            <tr height="22" id="CR_<?php echo xla($rule->id); ?>">
                                
                                <td class="text-left"><?php echo $rule->get_rule();?></td>
                                <td>&nbsp;</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary"
                                            type="button"
                                            onclick="top.restoreSession();location.href='index.php?action=detail!view&id=<?php echo attr($rule->id); ?>'">
                                        <i class="fa fa-pencil" id="edit_"></i>
                                    </button>
                                </td>
                                <td> </td>
                                <?php if ($rule->active_alert_flag() == "1") {  ?>
                                    <td class="text-center"><input type="checkbox" name="active[<?php echo attr($index); ?>]" checked="yes"></td>
                                <?php } else {?>
                                    <td class="text-center"><input type="checkbox" name="active[<?php echo attr($index); ?>]" ></td>
                                <?php } ?>
                                <td>&nbsp;</td>
                                <?php if ($rule->passive_alert_flag() == "1") { ?>
                                    <td class="text-center"><input type="checkbox" name="passive[<?php echo attr($index); ?>]" checked="yes"></td>
                                <?php } else {?>
                                    <td class="text-center"><input type="checkbox" name="passive[<?php echo attr($index); ?>]"></td>
                                <?php } ?>
                                <td>&nbsp;</td>
                                <?php if ($rule->patient_reminder_flag() == "1") { ?>
                                    <td class="text-center"><input type="checkbox" name="reminder[<?php echo attr($index); ?>]" checked="yes"></td>
                                <?php } else {?>
                                    <td class="text-center"><input type="checkbox" name="reminder[<?php echo attr($index); ?>]"></td>
                                <?php } ?>
                                <td>&nbsp;</td>
                                <?php if ($GLOBALS['medex_enable'] == '1') { ?>
                                    <?php if ($rule->provider_alert_flag() == "1") { ?>
                                    <td class="text-center"><input type="checkbox" name="provider[<?php echo attr($index); ?>]" checked="yes"></td>
                                <?php } else {?>
                                    <td class="text-center"><input type="checkbox" name="provider[<?php echo attr($index); ?>]"></td>
                                <?php } ?>
                                <td>&nbsp;</td>
                                <?php } ?>
                                <td class="text-center">
                                    <?php //Place the ACO selector here
                                        $gacl_temp = new gacl_api();
                                        $list_aco_objects = $gacl_temp->get_objects(null, 0, 'ACO');
                                    foreach ($list_aco_objects as $key => $value) {
                                        asort($list_aco_objects[$key]);
                                    }
                                
                                        echo "<select name='access_control[" . $index . "]'>";
                                    foreach ($list_aco_objects as $section => $array_acos) {
                                        $aco_section_data = $gacl_temp->get_section_data($section, 'ACO');
                                        $aco_section_title = $aco_section_data[3];
                                        foreach ($array_acos as $aco) {
                                            $aco_id = $gacl_temp->get_object_id($section, $aco, 'ACO');
                                            $aco_data = $gacl_temp->get_object_data($aco_id, 'ACO');
                                            $aco_title = $aco_data[0][3];
                                            $select = '';
                                            if ($rule->access_control() == $section.":".$aco) {
                                                $select = 'selected';
                                            }
                                            $start_title = xlt($aco_title);
                                            $show_title =  mb_substr($start_title, 0, 25)."...";
                                            echo "<option value='" . attr($section) . ":" . attr($aco) . "' " . $select . "> " . xlt($aco_section_title) . ": " . $show_title  . "</option>";
                                        }
                                    }
                                
                                        echo "</select>";
                                    ?>
                                </td>
                                <td><input style="display:none" name="id[<?php echo attr($index); ?>]" value="<?php echo attr($rule->get_id()); ?>" /></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-danger"
                                            type="button"
                                            data-ruleid="<?php echo attr($rule->id); ?>"
                                            data-record-title="<?php echo attr($rule->get_rule()); ?>"
                                            data-toggle="modal"
                                            data-target="#confirm-delete"
                                            id="CR_delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php }?>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel"><?php echo xlt('Confirm Delete'); ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <div class="title2"><?php echo xlt('You are about to delete the Clinical Reminder'); ?>:</div>
                    </div>
                    <div class="col-12">
                        <div class="bold indent10"><i class="title"></i></div>
                    </div>
                    <div class="col-11 offset-1">
                        <p><?php echo xlt('This action is not reversible.  Do you want to proceed?'); ?></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-ok">Delete</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
        $('#confirm-delete').on('click', '.btn-ok', function (e) {
            var $modalDiv = $(e.delegateTarget);
            var id = $(this).data('ruleid');
            top.restoreSession();
            $.ajax({
                       url: "index.php?action=edit!delete_rule",
                       type: "post",
                       data: {
                           id: id
                       }
                   });
            $modalDiv.addClass('loading');
            setTimeout(function () {
                $modalDiv.modal('hide').removeClass('loading');
            }, 1000);
            $("#CR_" + id).hide();
        });
        $('#confirm-delete').on('show.bs.modal', function (e) {
            var data = $(e.relatedTarget).data();
            $('.title', this).text(data.recordTitle);
            $('.btn-ok', this).data('ruleid', data.ruleid);
        });
    });
    
</script>

