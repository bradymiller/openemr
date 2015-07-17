<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_304a_3_Denominator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_304a_3 Denominator";
    }
    
    public function test( AmcPatient $patient, $beginDate, $endDate ) 
    {
        // MEASURE STAGE2: Medication Order(s) Check
		if ( (Helper::checkAnyEncounter($patient, $beginDate, $endDate, $options)) ){
			return true;
		}
		return false;
    }
}
