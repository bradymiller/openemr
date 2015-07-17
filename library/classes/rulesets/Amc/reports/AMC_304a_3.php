<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_304a_3 extends AbstractAmcReport
{
    public function getTitle()
    {
        return "AMC_304a_3";
    }

    public function getObjectToCount()
    {
       //return "med_orders";
	   return "prescriptions";
    }
 
    public function createDenominator() 
    {
        return new AMC_304a_3_Denominator();
    }
    
    public function createNumerator()
    {
        return new AMC_304a_3_Numerator();
    }
}
