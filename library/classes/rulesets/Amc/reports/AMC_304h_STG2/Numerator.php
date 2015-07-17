<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_304h_STG2_Numerator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_304h_STG2 Numerator";
    }
    
    public function test( AmcPatient $patient, $beginDate, $endDate ) 
    {
		//The number of office visits in the denominator where the patient or a patient authorized Representative is provided a clinical summary of their visit within 1 Business day.
		$amcElement = amcCollect('provide_sum_pat_amc',$patient->id,'form_encounter',$patient->object['encounter']);
		if (!(empty($amcElement))) {
		  $daysDifference = businessDaysDifference( date("Y-m-d",strtotime($patient->object['date'])) , date("Y-m-d",strtotime($amcElement['date_completed'])) );
		  error_log("DEBUG: ".$daysDifference,0);
		  if ($daysDifference < 2) {
			return true;
		  }
		}

		return false;
    }
}
