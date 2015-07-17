<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// Denominator:
// 		Reporting period start and end date
//		Prescription written for drugs requiring a prescription in order to be dispensed
// Denominator exclusion:
// 		Prescription written for controlled substance

// Generate and transmit permissible prescriptions electronically (other than controlled substances) queried for drug formulary).( AMC-2014:170.314(g)(1)/(2)–8 )

class AMC_304b_2_STG2_Denominator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_304b_2_STG2 Denominator";
    }

    public function test( AmcPatient $patient, $beginDate, $endDate )
    {
		return true;
    }
    
}
