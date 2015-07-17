<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// Generate and transmit permissible prescriptions electronically and queried for drug formulary.( AMC-2014:170.314(g)(1)/(2)–8 )

class AMC_304b_1_STG2_Numerator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_304b_1_STG2 Numerator";
    }
    
    public function test( AmcPatient $patient, $beginDate, $endDate ) 
    {
       //The number of prescriptions in the denominator generated, queried for a drug formulary and transmitted electronically
       if ( ($patient->object['eTransmit'] == 1) && ($patient->object['formulary'] == 'yes') )  {
		   return true;
	   }else{
		   return false;
	   }
    }
}
