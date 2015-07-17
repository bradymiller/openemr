<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_304i_STG2_Denominator implements AmcFilterIF
{
    public function getTitle()
    {
        return "AMC_304i_STG2 Denominator";
    }

    public function test( AmcPatient $patient, $beginDate, $endDate )
    {
        //  (basically needs a referral within the report dates,
        //   which are already filtered for, so all the objects are a positive)
        return true;
    }
    
}
