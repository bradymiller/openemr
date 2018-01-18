<?php
// +-----------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
//
// A copy of the GNU General Public License is included along with this program:
// openemr/interface/login/GnuGPL.html
// For more information write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
// Author:   Shiqiang Tao <shiqiang.tao@uky.edu>
//
// +------------------------------------------------------------------------------+

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//

require_once("../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");
require_once "$srcdir/options.inc.php";
require_once "$srcdir/formdata.inc.php";
$score = $_POST['score'];
$score = ((float) $score)*10+50;
$stdErr = $_POST['stdErr'];
$assessmentOID = $_POST['assessmentOID'];

?>

<?php
    // echo "<script>console.log( 'Debug Objects: " . $formOID . "' );</script>";


    $query = "UPDATE assessments SET status='completed', score='$score', error='$stdErr' WHERE patient_id='$pid' AND assessment_oid='$assessmentOID'";
    sqlStatement($query);
    $query1 = "SELECT * FROM assessments WHERE assessment_oid='$assessmentOID'";
		$res = sqlStatement($query1);
		$row = sqlFetchArray($res);

    $to = $GLOBALS['Emergency_Login_email_id'];

    $subject = "Patient John Doe completed a measurement";

    $message = '<html><body>';
		$message .= '<table style="border-radius:4px;border:1px #dceaf5 solid" align="center" border="0" cellpadding="0" cellspacing="0">';
		$message .= '<tbody><tr><td>';
		$message .= '<table style="line-height:25px" align="center" border="0" cellpadding="10" cellspacing="0">';
		$message .= '<tbody><tr>';
		$message .= '<td style="color:#444444;border-collapse:collapse;font-size:11pt;font-family:proxima_nova,\'Open Sans\',\'Lucida Grande\',\'Segoe UI\',Arial,Verdana,\'Lucida Sans Unicode\',Tahoma,\'Sans Serif\';max-width:700px" align="left" valign="top" width="700">';

		$message .= 'Dear Dr. Admin, <br><br>Your patient John Doe completed a measurement: <b>';
		$message .= text($row['form_name']);
		$message .= '</b><br>';
		$message .= 'Please log in and review it.';
		$message .= '<center>Go to EasiPro at: http://128.163.202.60/openemr/</center><br>If you need help or have any questions, please call us toll free at 859.218.4962 or email us at easipro@uky.edu.<br>Thanks,<br>EasiPRO Team';
		$message.= '</td></tr></tbody></table>';
		$message .= '</td></tr></tbody></table>';
		$message .= '</body></html>';

    $headers = "From: EasiPRO@uky.edu\r\n";
		$headers .= "Reply-To: EasiPRO@uky.edu\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

    mail($to, $subject, $message, $headers);
?>