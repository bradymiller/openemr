<?php
/**
 * File use to transmit Rx
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2019 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
require_once "../globals.php";

use OpenEMR\Rx\Weno\NewRx;

$list = $_GET['scripts'];


$sendScript = new NewRx();

$payload = $sendScript->creatOrderXMLBody($list);

$defaults = [
  CURLOPT_URL => 'https://apa.openmedpractice.com/weno/receiving_v2.php',
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $payload,
];

$ch = curl_init();
curl_setopt_array($ch, ($defaults));


