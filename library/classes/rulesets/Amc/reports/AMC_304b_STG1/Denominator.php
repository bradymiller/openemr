<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// Denominator: 
// Number of prescriptions written for drugs requiring a prescription in order to be
// dispensed other than controlled substances during the EHR reporting period

class AMC_304b_STG1_Denominator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_304b_STG1 Denominator";
    }

    public function test( AmcPatient $patient, $beginDate, $endDate )
    {
		return true;
    }
    
}
