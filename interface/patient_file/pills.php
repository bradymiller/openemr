<?php
$page = basename($_SERVER['PHP_SELF']);
$root = '/interface/patient_file/';

$pills = array(
    'Patient' => $root . 'summary/demographics.php',
    'History' => $root . 'history/history.php',
    'Report' => $root . 'report/patient_report.php',
    'Documents' => $root . "../../controller.php?document&list&patient_id=$pid",
    'Transactions' => $root . 'transaction/transactions.php',
    'Issues' => $root . 'summary/stats_full.php?active=all',
    'Ledger' => $root . "../reports/pat_ledger.php?form=1&patient_id=$pid",
)
?>

<div class="patient-pills">
    <table cellspacing='0' cellpadding='0' border='0'>
        <tr>
            <td class="small" colspan='4'>
                <span class="css_button_link">
                    <?php
                    $i = 0;
                    foreach($pills as $name => $url) {
                        if ($i > 0) {
                            echo "<span class='css_button_separator'>&nbsp|&nbsp</span>";
                        }

                        $active = ($page === strtok(basename($url), '?') ? 'active' : 'no');
                        $text = htmlspecialchars(xl($name), ENT_NOQUOTES);
                        echo "<a href='$url' onclick='top.restoreSession()' class='$active'>$text</a>";
                        $i++;
                    }
                    ?>
                </span>

                <!-- DISPLAYING HOOKS STARTS HERE -->
                <?php
                $module_query = sqlStatement("SELECT msh.*,ms.menu_name,ms.path,m.mod_ui_name,m.type FROM modules_hooks_settings AS msh
					LEFT OUTER JOIN modules_settings AS ms ON obj_name=enabled_hooks AND ms.mod_id=msh.mod_id
					LEFT OUTER JOIN modules AS m ON m.mod_id=ms.mod_id 
					WHERE fld_type=3 AND mod_active=1 AND sql_run=1 AND attached_to='demographics' ORDER BY mod_id");
                $DivId = 'mod_installer';
                if (sqlNumRows($module_query)) {
                    $jid = 0;
                    $modid = '';
                    while ($modulerow = sqlFetchArray($module_query)) {
                        $DivId = 'mod_' . $modulerow['mod_id'];
                        $new_category = $modulerow['mod_ui_name'];
                        $modulePath = "";
                        $added = "";
                        if ($modulerow['type'] == 0) {
                            $modulePath = $GLOBALS['customModDir'];
                            $added = "";
                        } else {
                            $added = "index";
                            $modulePath = $GLOBALS['zendModDir'];
                        }
                        $relative_link = "../../modules/" . $modulePath . "/" . $modulerow['path'];
                        $nickname = $modulerow['menu_name'] ? $modulerow['menu_name'] : 'Noname';
                        $jid++;
                        $modid = $modulerow['mod_id'];
                        ?>
                        |
                        <a href="<?php echo $relative_link; ?>" onclick='top.restoreSession()'>
                            <?php echo htmlspecialchars($nickname, ENT_NOQUOTES); ?></a>
                        <?php
                    }
                }
                ?>
                <!-- DISPLAYING HOOKS ENDS HERE -->
            </td>
        </tr>
    </table>
</div>    