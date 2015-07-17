<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_302m_STG2_Denominator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_302m_STG2 Denominator";
    }

    public function test( AmcPatient $patient, $beginDate, $endDate )
    {
        //Number of unique patients with office visits seen by the EP during the EHR reporting period
		$oneEncounter = array( Encounter::OPTION_ENCOUNTER_COUNT => 1 );
		if (  Helper::check( ClinicalType::ENCOUNTER, Encounter::ENC_OFF_VIS, $patient, $beginDate, $endDate, $oneEncounter ) ){
			return true;
		}
		return false;
    }
}
