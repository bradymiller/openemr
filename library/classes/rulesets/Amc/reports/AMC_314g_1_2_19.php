<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_314g_1_2_19 extends AbstractAmcReport
{
    public function getTitle()
    {
        return "AMC_314g_1_2_19";
    }

    public function getObjectToCount()
    {
        return "encounters";
    }
 
    public function createDenominator() 
    {
        return new AMC_314g_1_2_19_Denominator();
    }
    
    public function createNumerator()
    {
        return new AMC_314g_1_2_19_Numerator();
    }
}
