<?php
// Copyright (C) 2014 Ensoftek
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//

class AMC_314g_1_2_20_Denominator implements AmcFilterIF
{
	public $patArr = array();
    public function getTitle()
    {
        return "AMC_314g_1_2_20 Denominator";
    }
    
    public function test( AmcPatient $patient, $beginDate, $endDate ) 
    {
		return true;
    }
}
