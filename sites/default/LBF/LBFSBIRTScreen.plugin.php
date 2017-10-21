<?php

// Copyright (C) 2017 Sherwin Gaddis <sherwin@openmedpractice.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This provides enhancement functions for the referral (REF) form.
// It is invoked by interface/patient_file/transaction/add_transaction.php.

// The purpose of this function is to create JavaScript for the <head>
// section of the page.  This in turn defines desired javaScript
// functions.
//
// @author Sherwin Gaddis <sherwin@openmedpractice.com>
// @co-author Joyce Boyd <jboyd13@gmu.edu>


function LBFSBIRTScreen_javascript_onload(){

  echo "
      $('#div_lbf2').css('display', 'block');
      $('#div_lbf3').css('display', 'block');
      $('#div_lbf4').css('display', 'block');
  ";


 echo "

    $(document).ready(function(){
//
//  ********************* Alcohol Screen *******************************
//  ******************** Refer logic added by Joyce Boyd, jboyd13@gmu.edu ******************
// 
       $(\"input[id='form_alc_yr[1]']\").change(function(){
       	 document.getElementById('form_alc_yr_score').value = 0; 
       });
       $(\"input[id='form_alc_yr[2]']\").change(function(){
       	 document.getElementById('form_alc_yr_score').value = 1; 
       });
       $(\"input[id='form_alc_yr[3]']\").change(function(){
       	 document.getElementById('form_alc_yr_score').value = 2; 
       });
       $(\"input[id='form_alc_yr[4]']\").change(function(){
       	 document.getElementById('form_alc_yr_score').value = 3; 
       });
       $(\"input[id='form_alc_yr[5]']\").change(function(){
       	 document.getElementById('form_alc_yr_score').value = 4; 
       });

       $(\"input[id='form_daily_alc[1]']\").change(function(){
       	 document.getElementById('form_daily_alc_score').value = 0; 
       });
       $(\"input[id='form_daily_alc[2]']\").change(function(){
       	 document.getElementById('form_daily_alc_score').value = 1; 
       });
       $(\"input[id='form_daily_alc[3]']\").change(function(){
       	 document.getElementById('form_daily_alc_score').value = 2; 
       });
       $(\"input[id='form_daily_alc[4]']\").change(function(){
       	 document.getElementById('form_daily_alc_score').value = 3; 
       });
       $(\"input[id='form_daily_alc[5]']\").change(function(){
       	 document.getElementById('form_daily_alc_score').value = 4; 
       });
         
       $(\"input[id='form_drinks_men[1]']\").change(function(){
       document.getElementById('form_drinks_men_score').value = 0; 
       });
       $(\"input[id='form_drinks_men[2]']\").change(function(){ 
       document.getElementById('form_drinks_men_score').value = 1; 
       });
       $(\"input[id='form_drinks_men[3]']\").change(function(){
       document.getElementById('form_drinks_men_score').value = 2; 
       });
       $(\"input[id='form_drinks_men[4]']\").change(function(){
       document.getElementById('form_drinks_men_score').value = 3; 
       });
       $(\"input[id='form_drinks_men[5]']\").change(function(){
       document.getElementById('form_drinks_men_score').value = 4;                           
       });
      
  //get all the scores 
       $('#form_alc_sum').click(function(){
           var score1 = document.getElementById('form_alc_yr_score').value;
           var score2 = document.getElementById('form_daily_alc_score').value;
           var score3 = document.getElementById('form_drinks_men_score').value;
           var totalSum = +score1 + +score2 + +score3;
           document.getElementById('form_alc_sum').value = totalSum;

//   Update Referral Answer on Alcohol sum textbox click. Different answer based on gender.
       var alc_rec = document.getElementById('form_alc_rec');
       var genderS = document.getElementById('form_sex').value;
       if(((genderS == 'Male') && (totalSum >= 4)) || ((genderS == 'Female') && (totalSum >= 3))){
          alc_rec.value = 'YES';
       } else { 
            alc_rec.value = 'NO';
       }
       }); // end alc sum click function
//
//  *********************  Drugs Screen *******************************
//  ******************** Joyce Boyd, jboyd13@gmu.edu ******************
// 

        $(\"input[id='form_mj_use[1]']\").change(function(){
         document.getElementById('form_mj_use_score').value = 0; 
       });
       $(\"input[id='form_mj_use[2]']\").change(function(){
         document.getElementById('form_mj_use_score').value = 1; 
       });
       $(\"input[id='form_mj_use[3]']\").change(function(){
         document.getElementById('form_mj_use_score').value = 2; 
       });
       $(\"input[id='form_mj_use[4]']\").change(function(){
         document.getElementById('form_mj_use_score').value = 3; 
       });
       $(\"input[id='form_mj_use[5]']\").change(function(){
         document.getElementById('form_mj_use_score').value = 4; 
       });

       $(\"input[id='form_misuse_rx[1]']\").change(function(){
         document.getElementById('form_misuse_rx_score').value = 0; 
       });
       $(\"input[id='form_misuse_rx[2]']\").change(function(){
         document.getElementById('form_misuse_rx_score').value = 1; 
       });
       $(\"input[id='form_misuse_rx[3]']\").change(function(){
         document.getElementById('form_misuse_rx_score').value = 2; 
       });
       $(\"input[id='form_misuse_rx[4]']\").change(function(){
         document.getElementById('form_misuse_rx_score').value = 3; 
       });
       $(\"input[id='form_misuse_rx[5]']\").change(function(){
         document.getElementById('form_misuse_rx_score').value = 4; 
       });
         
       $(\"input[id='form_other_use[1]']\").change(function(){
       document.getElementById('form_other_use_score').value = 0; 
       });
       $(\"input[id='form_other_use[2]']\").change(function(){ 
       document.getElementById('form_other_use_score').value = 1; 
       });
       $(\"input[id='form_other_use[3]']\").change(function(){
       document.getElementById('form_other_use_score').value = 2; 
       });
       $(\"input[id='form_other_use[4]']\").change(function(){
       document.getElementById('form_other_use_score').value = 3; 
       });
       $(\"input[id='form_other_use[5]']\").change(function(){
       document.getElementById('form_other_use_score').value = 4;                           
       });

//      
//   Check answer to whether pt has Mj Rx card change Score 1 to 0 
//       ???? How do I get this to check if answer changes again ???? (JB, 2/20/17)
//
       $('#form_mj_rx').change(function(){
         var m0 = document.getElementById('form_mj_rx');
         var mjCard = m0.options[m0.selectedIndex].value;

         if (mjCard=='YES') {  
           document.getElementById('form_mj_use_score').value = 0; 
         }
       }); // end mj_rx change function

  //get all the scores 
       $('#form_drugs_sum').click(function(){
           var drugscore1 = document.getElementById('form_mj_use_score').value;
           var drugscore2 = document.getElementById('form_misuse_rx_score').value;
           var drugscore3 = document.getElementById('form_other_use_score').value;
           
//           if (mjCard=='YES') {  
//                document.getElementById('form_mj_use_score').value = 0; 
//           }
           var totalDrugSum = +drugscore1 + +drugscore2 + +drugscore3;
           document.getElementById('form_drugs_sum').value = totalDrugSum;

//   Update Referral Answer on Drug sum textbox click. 
       var drug_rec = document.getElementById('form_drug_rec');
       if(totalDrugSum >= 1) {
          drug_rec.value = 'YES';
       } else { 
          drug_rec.value = 'NO';
       }
   });  // end drug click function
//
//  ********************* Tobacco Screen *******************************
//  ******************** Joyce Boyd, jboyd13@gmu.edu ******************
// 

       $(\"input[id='form_tob_use[1]']\").change(function(){
         document.getElementById('form_tob_use_score').value = 0; 
       });
       $(\"input[id='form_tob_use[2]']\").change(function(){
         document.getElementById('form_tob_use_score').value = 1; 
       });
       $(\"input[id='form_tob_use[3]']\").change(function(){
         document.getElementById('form_tob_use_score').value = 2; 
       });
       $(\"input[id='form_tob_use[4]']\").change(function(){
         document.getElementById('form_tob_use_score').value = 3; 
       });
       $(\"input[id='form_tob_use[5]']\").change(function(){
         document.getElementById('form_tob_use_score').value = 4; 
       });
             
  //get tobacco score 
       $('#form_tob_score').click(function(){
           var tobscore = document.getElementById('form_tob_use_score').value;
           document.getElementById('form_tob_score').value = tobscore;
           
//   Update Referral Answer on Tobacco score textbox click. 
       var tob_rec = document.getElementById('form_tob_rec');
           if(tobscore >= 1) {
               tob_rec.value = 'YES';
            } else { 
               tob_rec.value = 'NO';
            }
       }); // end tobacco score click function

});  // doc ready function

 ";//end of echo statement

 } //end of function

?>
