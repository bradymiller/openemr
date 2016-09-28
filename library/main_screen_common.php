<?php
/**
 * This script runs in a hidden iframe and reloads itself periodically
 * to support auto logout timeout.
 *
 * Copyright (C) 2016 Brady Miller <brady.g.miller@gmail.com>
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
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @link    http://www.open-emr.org
 */

// Set the array that maps the paths to the targets
$map_paths_to_targets = array(
    'main_info.php' => ('cal'),
    '../new/new.php' => ('pat'),
    '../../interface/main/finder/dynamic_finder.php' => ('pat'),
    '../../interface/patient_tracker/patient_tracker.php?skip_timeout_reset=1' => ('flb')
)

function main_screen_common() {
	globals $map_paths_to_targets;

    // Fetch the password expiration date
    $is_expired=false;
    if($GLOBALS['password_expiration_days'] != 0){
        $is_expired=false;
        $q= (isset($_POST['authUser'])) ? $_POST['authUser'] : '';
        $result = sqlStatement("select pwd_expiration_date from users where username = ?", array($q));
        $current_date = date('Y-m-d');
        $pwd_expires_date = $current_date;
        if($row = sqlFetchArray($result)) {
            $pwd_expires_date = $row['pwd_expiration_date'];
        }

        // Display the password expiration message (starting from 7 days before the password gets expired)
        $pwd_alert_date = date('Y-m-d', strtotime($pwd_expires_date . '-7 days'));

        if (strtotime($pwd_alert_date) != '' &&
            strtotime($current_date) >= strtotime($pwd_alert_date) &&
            (!isset($_SESSION['expiration_msg'])
            or $_SESSION['expiration_msg'] == 0)) {
            $is_expired = true;
            $_SESSION['expiration_msg'] = 1; // only show the expired message once
        }
    }

    if ($is_expired) {
        //display the php file containing the password expiration message.
        $frame1url = "pwd_expires_alert.php";
        $frame1target = "adm";
    }
    else if (!empty($_POST['patientID'])) {
        $patientID = 0 + $_POST['patientID'];
        if (empty($_POST['encounterID'])) {
            // Open patient summary screen (without a specific encounter)
            $frame1url = "../patient_file/summary/demographics.php?set_pid=".attr($patientID);
            $frame1target = "pat";
        }
        else {
            // Open patient summary screen with a specific encounter
            $encounterID = 0 + $_POST['encounterID'];
            $frame1url = "../patient_file/summary/demographics.php?set_pid=".attr($patientID)."&set_encounterid=".attr($encounterID);
            $frame1target = "pat";
        }
    }
    else if (isset($_GET['mode']) && $_GET['mode'] == "loadcalendar") {
        $frame1url = "calendar/index.php?pid=" . attr($_GET['pid']);
        if (isset($_GET['date'])) $frame1url .= "&date=" . attr($_GET['date']);
        $frame1target = "cal";
    }
    else {
        // standard layout
        if ($GLOBALS['default_top_pane']) {
            $frame1url = attr($GLOBALS['default_top_pane']);
            $frame1target = $map_paths_to_targets[$GLOBALS['default_top_pane']];
            if empty($frame1target) $frame1target = "msc";
        } else {
            $frame1url = "main_info.php";
            $frame1target = "cal";
        }
    }
    return(array('url'=>$frame1url, 'target'=>$frame1target));
}