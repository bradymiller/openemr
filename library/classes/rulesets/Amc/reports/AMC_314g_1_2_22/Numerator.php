<?php
// Copyright (C) 2014 Ensoftek
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//


class AMC_314g_1_2_22_Numerator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_314g_1_2_22 Numerator";
    }
    
    public function test( AmcPatient $patient, $beginDate, $endDate ) 
    {
		$encQry = "SELECT * FROM forms f ".
				  "INNER JOIN form_encounter fe ON f.encounter = fe.encounter ".
				  "WHERE  f.formdir != 'newpatient' AND f.deleted = 0 AND f.pid = ? AND (f.date BETWEEN ? AND ?) ";
		
		$check = sqlQuery( $encQry, array($patient->id, $beginDate, $endDate) );   
		if (!(empty($check))){
			return true;
		}else{
			return false;
		}
    }
}
?>
