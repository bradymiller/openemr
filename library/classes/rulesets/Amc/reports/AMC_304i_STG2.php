<?php
// Copyright (C) 2015 Ensoftek Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class AMC_304i_STG2 extends AbstractAmcReport
{
    public function getTitle()
    {
        return "AMC_304i_STG2";
    }

    public function getObjectToCount()
    {
        return "transitions-out-new";
    }
 
    public function createDenominator() 
    {
        return new AMC_304i_STG2_Denominator();
    }
    
    public function createNumerator()
    {
        return new AMC_304i_STG2_Numerator();
    }
}
