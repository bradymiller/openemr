<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_314g_1_2_14_STG2_Numerator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_314g_1_2_14_STG2 Numerator";
    }
    
    public function test( AmcPatient $patient, $beginDate, $endDate ) 
    {
		$portalQry = "SELECT count(*) as cnt FROM `patient_access_onsite` WHERE pid=?";
		$check = sqlQuery( $portalQry, array($patient->id) );  
		if ($check['cnt'] > 0){
			return true;
		}else{
			return false;
		}
    }
}
?>