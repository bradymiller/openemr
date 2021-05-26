<?php

/**
 * Patient Portal Home
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Shiqiang Tao <StrongTSQ@gmail.com>
 * @author    Ben Marte <benmarte@gmail.com>
 * @copyright Copyright (c) 2016-2019 Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2019-2020 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2020 Shiqiang Tao <StrongTSQ@gmail.com>
 * @copyright Copyright (c) 2021 Ben Marte <benmarte@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("verify_session.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/options.inc.php");
require_once("lib/portal_mail.inc");
require_once(__DIR__ . "/../library/appointments.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Header;

if (isset($_SESSION['register']) && $_SESSION['register'] === true) {
    require_once(__DIR__ . "/../src/Common/Session/SessionUtil.php");
    OpenEMR\Common\Session\SessionUtil::portalSessionCookieDestroy();
    header('Location: ' . $landingpage . '&w');
    exit();
}

if (!isset($_SESSION['portal_init'])) {
    $_SESSION['portal_init'] = true;
}

$whereto = $_SESSION['whereto'] ?? 'documentscard';

$user = isset($_SESSION['sessionUser']) ? $_SESSION['sessionUser'] : 'portal user';
$result = getPatientData($pid);

$msgs = getPortalPatientNotes($_SESSION['portal_username']);
$msgcnt = count($msgs);
$newcnt = 0;
foreach ($msgs as $i) {
    if ($i['message_status'] == 'New') {
        $newcnt += 1;
    }
}

Header::setupHeader(['no_main-theme', 'datetime-picker', 'patientportal-style']);

$messagesURL = $GLOBALS['web_root'] . '' . "/portal/messaging/messages.php";

$isEasyPro = $GLOBALS['easipro_enable'] && !empty($GLOBALS['easipro_server']) && !empty($GLOBALS['easipro_name']);

$current_date2 = date('Y-m-d');
$apptLimit = 30;
$appts = fetchNextXAppts($current_date2, $pid, $apptLimit);

$appointments = array();

if ($appts) {
    $stringCM = "(" . xl("Comments field entry present") . ")";
    $stringR = "(" . xl("Recurring appointment") . ")";
    $count = 0;
    foreach ($appts as $row) {
        $status_title = getListItemTitle('apptstat', $row['pc_apptstatus']);
        $count++;
        $dayname = xl(date("l", strtotime($row['pc_eventDate'])));
        $dispampm = "am";
        $disphour = substr($row['pc_startTime'], 0, 2) + 0;
        $dispmin = substr($row['pc_startTime'], 3, 2);
        if ($disphour >= 12) {
            $dispampm = "pm";
            if ($disphour > 12) {
                $disphour -= 12;
            }
        }

        if ($row['pc_hometext'] != "") {
            $etitle = xlt('Comments') . ": " . $row['pc_hometext'] . "\r\n";
        } else {
            $etitle = "";
        }

        array_push($appointments, [
            'appointmentDate' => $dayname . ', ' . $row['pc_eventDate'] . ' ' . text($disphour . ":" . $dispmin . " " . $dispampm),
            'appointmentType' => xlt("Type") . ": " . text($row['pc_catname']),
            'provider' => xlt("Provider") . ": " . text($row['ufname'] . " " . $row['ulname']),
            'status' => xlt("Status") . ": " . text($status_title),
            'mode'  => (int)$row['pc_recurrtype'] > 0 ? text("recurring") : $row['pc_recurrtype'],
            'icon_type' => (int)$row['pc_recurrtype'] > 0,
            'etitle' => $etitle,
            'pc_eid' => $row['pc_eid'],
        ]);
    }
}

function buildNav($newcnt, $pid, $result)
{
    $navItems = [
        [
            'url' => '#',
            'label' => text($result['fname'] . " " . $result['lname']),
            'icon' => 'fa-user',
            'dropdownID' => 'account',
            'children' => [
                [
                    'url' => '#profilecard',
                    'label' => xlt(' My Profile'),
                    'icon' => 'fa-user',
                    'dataToggle' => 'collapse',
                ],

                [
                    'url' => '#secure-msgs-card',
                    'label' => xlt('My Messages'),
                    'icon' => 'fa-envelope',
                    'dataToggle' => 'collapse',
                ],
                [
                    'url' => '#documentscard',
                    'label' => xlt('My Documents'),
                    'icon' => 'fa-file-medical',
                    'dataToggle' => 'collapse'
                ],
                [
                    'url' => '#lists',
                    'label' => xlt('My Lists'),
                    'icon' => 'fa-list',
                    'dataToggle' => 'collapse'
                ],
                [
                    'url' => '#openSignModal',
                    'label' => xlt('My Signature'),
                    'icon' => 'fa-file-signature',
                    'dataToggle' => 'modal',
                    'dataType' => 'patient-signature'
                ]
            ],
        ],
        [
            'url' => '#',
            'label' => xlt('Reports'),
            'icon' => 'fa-book-medical',
            'dropdownID' => 'reports',
            'children' => [
                [
                    'url' => $GLOBALS['web_root'] . '' . "/ccdaservice/ccda_gateway.php?action=startandrun",
                    'label' => xlt('View CCD'),
                    'icon' => 'fa-envelope',
                ]
            ]
        ]
    ];
    if (($GLOBALS['portal_two_ledger'] || $GLOBALS['portal_two_payments'])) {

        if ($GLOBALS['portal_two_ledger']) {
            array_push($navItems, [
                'url' => '#',
                'label' => xlt('Accountings'),
                'icon' => 'fa-file-invoice-dollar',
                'dropdownID' => 'accounting',
                'children' => [
                    [
                        'url' => "#ledgercard",
                        'label' => xlt('Ledger'),
                        'icon' => 'fa-folder-open',
                        'dataToggle' => 'collapse'
                    ]
                ]
            ]);
        }
    }

    // Build sub nav items

    if ($GLOBALS['allow_portal_chat']) {
        array_push(
            $navItems,
            [
                'url' => '#messagescard',
                'label' => xlt('Chat'),
                'icon' => 'fa-comment-medical',
                'dataToggle' => 'collapse',
                'dataType' => 'cardgroup'
            ]
        );
    }

    for ($i = 0; $i < count($navItems); $i++) {
        if ($GLOBALS['allow_portal_appointments'] && $navItems[$i]['label'] === text($result['fname'] . " " . $result['lname'])) {
            array_push($navItems[$i]['children'], [
                'url' => '#appointmentcard',
                'label' => xlt("My Appointments"),
                'icon' => 'fa-calendar-check',
                'dataToggle' => 'collapse'
            ]);
        }

        if ($navItems[$i]['label'] === text($result['fname'] . " " . $result['lname'])) {
            array_push(
                $navItems[$i]['children'],
                [
                    'url' => 'javascript:changeCredentials(event)',
                    'label' => xlt('Change Credentials'),
                    'icon' => 'fa-cog fa-fw',
                ],
                [
                    'url' => 'logout.php',
                    'label' => xlt('Logout'),
                    'icon' => 'fa-ban fa-fw',
                ]
            );
        }

        if (!empty($GLOBALS['portal_onsite_document_download']) && $navItems[$i]['label'] === xlt('Reports')) {
            array_push(
                $navItems[$i]['children'],
                [
                    'url' => '#reportcard',
                    'label' => xlt('Report Content'),
                    'icon' => 'fa-folder-open',
                    'dataToggle' => 'collapse'
                ],
                [
                    'url' => '#downloadcard',
                    'label' => xlt('Download Lab Documents'),
                    'icon' => 'fa-download',
                    'dataToggle' => 'collapse'
                ]
            );
        }
        if ($GLOBALS['portal_two_payments'] && $navItems[$i]['label'] === xlt('Accountings')) {

            array_push($navItems[$i]['children'], [
                'url' => "#paymentcard",
                'label' => xlt('Make Payment'),
                'icon' => 'fa-credit-card',
                'dataToggle' => 'collapse'
            ]);
        }
    }

    return $navItems;
}

$navMenu = buildNav($newcnt, $pid, $result);

echo (new TwigContainer('portal'))->getTwig()->render('home.html.twig', [
    'user' => $user,
    'whereto' => $whereto,
    'result' => $result,
    'msgs' => $msgs,
    'msgcnt' => $msgcnt,
    'newcnt' => text($newcnt),
    'allow_portal_appointments' => $GLOBALS['allow_portal_appointments'],
    'web_root' => $GLOBALS['web_root'],
    'payment_gateway' => $GLOBALS['payment_gateway'],
    'gateway_mode_production' => $GLOBALS['gateway_mode_production'],
    'portal_two_payments' => $GLOBALS['portal_two_payments'],
    'allow_portal_chat' => $GLOBALS['allow_portal_chat'],
    'portal_onsite_document_download' => $GLOBALS['portal_onsite_document_download'],
    'portal_two_ledger' => $GLOBALS['portal_two_ledger'],
    'images_static_relative' => $GLOBALS['images_static_relative'],
    'youHave' => xlt('You have'),
    'navMenu' => $navMenu,
    'pagetitle' => xlt('Home') . ' | ' . xlt('OpenEMR Portal'),
    'jsVersion' => $v_js_includes,
    'messagesURL' => $messagesURL,
    'patientID' => $pid,
    'patientName' => js_escape($_SESSION['ptName']),
    'profileModalTitle' => xlj('Profile Edits Red = Charted Values Blue = Patient Edits'),
    'helpButtonLabel' => xlj('Help'),
    'cancelButtonLabel' => xlj('Cancel'),
    'revertButtonLabel' => xlj('Revert Edits'),
    'reviewButtonLabel' => xlj('Send for Review'),
    'newAppointmentLabel' => xlj('Request New Appointment'),
    'recurringAppointmentLabel' => xlj("A Recurring Appointment. Please contact your appointment desk for any changes."),
    'editAppointmentLabel' => xlj('Edit Appointment'),
    'newCredentialsLabel' => xlj('Please Enter New Credentials'),
    'csrfUtils' => js_escape(CsrfUtils::collectCsrfToken()),
    'finishedAssesmentLabel' => xlj('You have finished the assessment.'),
    'thankYouLabel' => xlj('Thank you'),
    'loadingTextLabel' => xlj('Loading'),
    'startAssesmentLabel' => xlj('Start Assessment'),
    'workingLabel' => xlt('Working!'),
    'pleaseWaitLabel' => xlt('Please wait...'),
    'medicationsLabel' => xlt('Medications'),
    'medicationsAllergyLabel' => xlt('Medications Allergy List'),
    'issuesListLabel' => xlt('Issues List'),
    'ammendmentListLabel' => xlt('Amendment List'),
    'labResultsLabel' => xlt('Lab Results'),
    'appointmentsLabel' => xlt('Appointments'),
    'noAppointmentsLabel' => xlt('No Appointments'),
    'scheduleNewAppointmentLabel' => xlt('Schedule A New Appointment'),
    'paymentsLabel' => xlt('Payments'),
    'secureChatLabel' => xlt('Secure Chat'),
    'reportsLabel' => xlt('Reports'),
    'downloadDocumentsLabel' => xlt('Download Documents'),
    'downloadAllPatientDocumentsLabel' => xlt('Download all patient documents'),
    'downloadLabel' => xla('Download'),
    'ledgerLabel' => xlt('Ledger'),
    'patientReportedOutcomeLabel' => xlt('Patient Reported Outcomes'),
    'isEasyPro' => $isEasyPro,
    'appointments' => $appointments,
    'appts' => $appts,
    'appointmentLimit' => $apptLimit,
    'appointmentCount' => $count,
    'displayLimitLabel' => xlt("Display limit reached"),
    'moreAppointsmentsLabel' => xlt("More appointments may exist"),
]);
