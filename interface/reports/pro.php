<?php
// +-----------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
//
// A copy of the GNU General Public License is included along with this program:
// openemr/interface/login/GnuGPL.html
// For more information write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
// Author:   Shiqiang Tao <shiqiang.tao@uky.edu>
//
// +------------------------------------------------------------------------------+

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//

require_once("../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");
require_once "$srcdir/options.inc.php";
require_once "$srcdir/formdata.inc.php";
?>
<html>
    <!-- HTML Head -->
    <head>
        <?php html_header_show(); ?>
        <title><?php echo xlt('PRO'); ?></title>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/crypto.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1-7-1-min.js"></script>
        <link rel='stylesheet' href='<?php echo $css_header ?>' type='text/css'>
        <style>
            .list-title-close, .list-title-open{
                position: relative;
                padding:5px 5px 5px 20px;
                min-width:500px;
                background-color: #cceeff;
            }
            .list-title-close:after{
                position: absolute;
                display: block;
                content: " ";
                left: 5px;
                bottom: 7px;
                width: 0;
                height: 0;
                border-color: transparent;
                border-style: solid;
                border-width: 5px 0 5px 5px;
                border-left-color: #000;
                margin-top: 5px;
                margin-right: -10px;
            }

            .list-title-open:after{
                display: block;
                content: " ";
                left: 5px;
                bottom: 7px;
                width: 0;
                height: 0;
                border-color: transparent;
                border-style: solid;
                border-width: 5px 5px 0px 5px;
                border-left-color: #000;
                margin-top: 5px;
                margin-right: -10px;
            }
            .dm-ed-in-1 {
                height: 35px;
                width: 100%;
                background-color: #E7ECF2;
                border-bottom: thin solid #C0C0C0;
                padding: 5px 0; z-index: 100;
            }
            .dm-ed-in-1 h3 {
                /*color: rgb(8, 102, 198);*/
                font-size: 20px; float: left;
                line-height: 0px;
            }
            .panel-padding{
                padding: 10px;
            }
        
            .panel-bordered{
                border: 1px solid #c8c8c8;
                border-color: rgba(0,0,0,0.2);
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
                border-radius: 5px;
                -webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;
                box-sizing: border-box;
            }

            .panel-shadow{
                box-shadow: 0 1px 2px #999;
            }
            .dm-ed-in-4 {
                display: none;
            }
            
            .dm-ed-in-6 {
                width: 20% !important;
                /*border-top: 1px solid #ccc;*/
            }
            .odd{
                background-color: #ccc;     
            }
            .even{
                background-color: #fff;
            }

            .dm-ed-in-7 {
                font-size: 12px;
            }
            .dm-ed-in-8 {
                width: 60%;
                margin-left: 10%;
                margin-top: 40px;
                border: 1px solid #CCCCCC;
                text-align:center;
                padding: 30px;
                font-size: 15px;
                font-weight: bold;
                background: #f7f7f7;
            }
            .dm-ed-in-9 {
                line-height:30px;
            }
            ul.ext-tab-head{
                margin: 0px;
                padding: 0px;
            }
            ul.ext-tab-head li {
                border-bottom: 2px solid #faffff;
                color: #292b2c;
                cursor: pointer;
                display: inline-block;
                margin-bottom: 0;
                padding: 3px 5px;
                text-decoration: none;
                background-color: #fff;
                font-weight: 400;
                line-height: 1.25;
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
                border: 1px solid #ccc;
                padding: .5rem 1rem;
                font-size: 1rem;
                border-radius: .25rem;
                -webkit-transition: all .2s ease-in-out;
                -o-transition: all .2s ease-in-out;
                transition: all .2s ease-in-out;
            }
            ul.ext-tab-head li.child-active {
                border-bottom: 2px solid #003366;
                background-color: #007fff;
                color: #ffffff;
                border-radius: 6px;
            }
            .page-title{
                position:relative;
               padding-left:10px;
               color: #ffffff; 
               font-size:2em; 
               background: linear-gradient(to right, #0c83e2, #ffffff);
               /*height:50px;*/
               border-radius: 4px;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

        </style>
    </head>
    <!-- HTML Body -->
    <body class="body_top">
        <div class="dm-ed-in-1 panel-padding page-title" style="margin-top:0px; margin-bottom: 2px;">
            <h3><?php echo xlt('Patient Reported Outcomes') ?></h3>
        </div>
        <div class="clear"></div>
        <div class='panel-padding panel-bordered panel-shadow' style='margin-bottom: 10px;'>
            <ul class="ext-tab-head">
                <li class="child-active ext-enc"><?php echo xlt('Existing Forms'); ?></li>
                <li class="ext-proc"><?php echo xlt('Add New Form'); ?></li>
            </ul>
        </div>

        <div class="dm-ed-in-3 dm-ed-in-5 panel-padding panel-bordered">
            <?php
                $query1 = "SELECT *
                           FROM assessments
                           WHERE patient_id=?";
                $res1 = sqlStatement($query1, array($pid));
                while ($row1 = sqlFetchArray($res1)) {
                    $records1[] = $row1;
                }
            ?>
            <?php if (!empty($records1)) { ?>
                <table width="100%;" class='table-striped'>
                    <tr class="dm-ed-in-9">
                        <td class="dm-ed-in-6 "><label><?php echo xlt('Name'); ?></label></td>
                        <td class="dm-ed-in-6 "><label><?php echo xlt('Deadline (CST)'); ?></label></td>
                        <td class="dm-ed-in-6 "><label><?php echo xlt('Status'); ?></label></td>
                        <td class="dm-ed-in-6 "><label><?php echo xlt('T-Score'); ?></label></td>
                    </tr>
                    <?php foreach ($records1 as $value1) { ?>
                        <tr>
                            <td class="dm-ed-in-6 "><span class="dm-ed-in-7"><?php echo oeFormatShortDate($value1['form_name']); ?></span></td>
                            <td class="dm-ed-in-6 "><span class="dm-ed-in-7"><?php echo htmlspecialchars($value1['deadline'], ENT_NOQUOTES); ?></span></td>
                            <td class="dm-ed-in-6  "><span class="dm-ed-in-7"><?php echo htmlspecialchars($value1['status'], ENT_NOQUOTES); ?></span></td>
                            <td class="dm-ed-in-6 "><span class="dm-ed-in-7"><?php echo htmlspecialchars(substr($value1['score'], 0, 4), ENT_NOQUOTES); ?></span></td>
                        </tr>
                    <?php } ?>
                </table>   
            <?php }?>

            <?php if (empty($records1)) { ?>
                <div class="dm-ed-in-8">
                    <?php echo xlt('Nothing to display'); ?>
                </div>
            <?php } ?>
        </div>
        <div class="dm-ed-in-4 dm-ed-in-5 panel-padding panel-bordered" style='height: 450px'>
           <a href="#" onclick="listForms()">List Forms</a>
           |
           <a href="#" onclick="orderForm()">Order Form</a>
           <hr>
           <div class='panel-padding panel-bordered panel-shadow' id='form-list' style='height: 400px; overflow: scroll;'>
               
           </div>
        </div>
        
        <!-- Javascript goes here -->
        <script>
            var Server = "https://www.assessmentcenter.net/ac_api";
            $(document).ready(function() {
                $('.ext-tab-head li').click(function() {
                    $('.ext-tab-head li').removeClass("child-active");
                    $(this).addClass("child-active");
                });
                $('.ext-enc').click(function() {
                    $('.dm-ed-in-4').hide();
                    $('.dm-ed-in-3').show();
                });
                $('.ext-proc').click(function() {
                    $('.dm-ed-in-3').hide();
                    $('.dm-ed-in-4').show();
                });
            });

            function listForms() {
                $.ajax({
                    url: "https://www.assessmentcenter.net/ac_api/2014-01/Forms/.json",
                    cache: false,
                    type: "POST",
                    data: "",
                    dataType: "json",
                    beforeSend: function(xhr) {
                        var bytes = Crypto.charenc.Binary.stringToBytes("BBD62935-F76F-4EC8-8834-BDAA75DAD8AB:9A35D313-E7BC-41C9-8933-3A3D73953F73");
                        var base64 = Crypto.util.bytesToBase64(bytes);
                        // alert(base64);
                        xhr.setRequestHeader("Authorization", "Basic " + base64);
                    },
                    success: function(data) { 
                        $('#form-list').html("");
                        var forms = data.Form;
                        var ascq_me_forms = [];
                        var neuro_qol_forms = [];
                        var nih_tb_forms = [];
                        var promis_forms = [];
                        var sci_fi_forms = [];
                        var sci_qol_forms = [];
                        var tbi_qol_forms = [];
                        for (var i=0; i < forms.length; i++) {
                            if(forms[i].Name.startsWith("ASCQ-Me")){
                                ascq_me_forms.push(forms[i])
                            }else if(forms[i].Name.startsWith("Neuro-QoL")||forms[i].Name.startsWith("Neuro-QOL")){
                                neuro_qol_forms.push(forms[i])
                            }else if(forms[i].Name.startsWith("NIH TB")){
                                // alert(forms[i].Name);
                                nih_tb_forms.push(forms[i])
                            }else if(forms[i].Name.startsWith("PROMIS")){
                                promis_forms.push(forms[i])
                            }else if(forms[i].Name.startsWith("SCI-FI")){
                                sci_fi_forms.push(forms[i])
                            }else if(forms[i].Name.startsWith("SCI-QOL")||forms[i].Name.startsWith("SCI-QoL")){
                                sci_qol_forms.push(forms[i])
                            }else if(forms[i].Name.startsWith("TBI-QOL")||forms[i].Name.startsWith("TBI-QoL")){
                                tbi_qol_forms.push(forms[i])
                            }
                        }

                        // ascq
                        var ascq_me_container = "<div onclick='openCloseList(this)' style='cursor:pointer;'><div class='list-title-close'><b>ASCQ-Me ("+ascq_me_forms.length+")</b></div></div>"
                        var alist = "<ul style='list-style:none;margin:0px;padding:0px;display:none'>"
                        for (var i=0; i < ascq_me_forms.length; i++) {
                            var myform = "<li><input type='checkbox' value='"+ascq_me_forms[i].OID+"' desc='"+ ascq_me_forms[i].Name +"'>"+ascq_me_forms[i].Name+"</input></li>";
                            alist += myform;
                        }
                        alist += "</ul>"
                        $('#form-list').append(ascq_me_container);
                        $('#form-list').append(alist);
                        // neuro_qol
                        var neuro_qol_container = "<div onclick='openCloseList(this)' style='cursor:pointer;'><div class='list-title-close'><b>Neuro-QOL ("+neuro_qol_forms.length+")</b></div></div>"
                        var blist = "<ul style='list-style:none;margin:0px;padding:0px;display:none'>"
                        for (var i=0; i < neuro_qol_forms.length; i++) {
                            var myform = "<li><input type='checkbox' value='"+neuro_qol_forms[i].OID+"' desc='"+ neuro_qol_forms[i].Name +"'>"+neuro_qol_forms[i].Name+"</input></li>";
                            blist += myform;
                        }
                        blist += "</ul>"
                        $('#form-list').append(neuro_qol_container);
                        $('#form-list').append(blist);
                        // nih tb
                        var nih_tb_container = "<div onclick='openCloseList(this)' style='cursor:pointer;'><div class='list-title-close'><b>NIH TB ("+nih_tb_forms.length+")</b></div></div>"
                        var list = "<ul style='list-style:none;margin:0px;padding:0px;display:none'>"
                        for (var i=0; i < nih_tb_forms.length; i++) {
                            var myform = "<li><input type='checkbox' value='"+nih_tb_forms[i].OID+"' desc='"+ nih_tb_forms[i].Name +"'>"+nih_tb_forms[i].Name+"</input></li>";
                            list += myform;
                        }
                        list += "</ul>"
                        $('#form-list').append(nih_tb_container);
                        $('#form-list').append(list);
                        //prmois
                        var promis_container = "<div onclick='openCloseList(this)' style='cursor:pointer;'><div class='list-title-close'><b>PROMIS ("+promis_forms.length+")</b></div></div>"
                        var list = "<ul style='list-style:none;margin:0px;padding:0px;display:none'>"
                        for (var i=0; i < promis_forms.length; i++) {
                            var myform = "<li><input type='checkbox' value='"+promis_forms[i].OID+"' desc='"+ promis_forms[i].Name +"'>"+promis_forms[i].Name+"</input></li>";
                            list += myform;
                        }
                        list += "</ul>"
                        $('#form-list').append(promis_container);
                        $('#form-list').append(list);

                        // sci-fi
                        var sci_fi_container = "<div onclick='openCloseList(this)' style='cursor:pointer;'><div class='list-title-close'><b>SCI-FI ("+sci_fi_forms.length+")</b></div></div>"
                        var list = "<ul style='list-style:none;margin:0px;padding:0px;display:none'>"
                        for (var i=0; i < sci_fi_forms.length; i++) {
                            var myform = "<li><input type='checkbox' value='"+sci_fi_forms[i].OID+"' desc='"+ sci_fi_forms[i].Name +"'>"+sci_fi_forms[i].Name+"</input></li>";
                            list += myform;
                        }
                        list += "</ul>"
                        $('#form-list').append(sci_fi_container);
                        $('#form-list').append(list);

                        // sci-qol
                        var sci_qol_container = "<div onclick='openCloseList(this)' style='cursor:pointer;'><div class='list-title-close'><b>SCI-QOL ("+sci_qol_forms.length+")</b></div></div>"
                        var list = "<ul style='list-style:none;margin:0px;padding:0px;display:none'>"
                        for (var i=0; i < sci_qol_forms.length; i++) {
                            var myform = "<li><input type='checkbox' value='"+sci_qol_forms[i].OID+"' desc='"+ sci_qol_forms[i].Name +"'>"+sci_qol_forms[i].Name+"</input></li>";
                            list += myform;
                        }
                        list += "</ul>"
                        $('#form-list').append(sci_qol_container);
                        $('#form-list').append(list);
                        // 
                        // tbi-qol
                        var tbi_qol_container = "<div onclick='openCloseList(this)' style='cursor:pointer;'><div class='list-title-close'><b>TBI-QOL ("+tbi_qol_forms.length+")</b></div></div>"
                        var list = "<ul style='list-style:none;margin:0px;padding:0px;display:none'>"
                        for (var i=0; i < tbi_qol_forms.length; i++) {
                            var myform = "<li><input type='checkbox' value='"+tbi_qol_forms[i].OID+"' desc='"+ tbi_qol_forms[i].Name +"'>"+tbi_qol_forms[i].Name+"</input></li>";
                            list += myform;
                        }
                        list += "</ul>"
                        $('#form-list').append(tbi_qol_container);
                        $('#form-list').append(list);
                    },
                
                    error: function(jqXHR, textStatus, errorThrown) {
                        document.write(jqXHR.responseText + ':' + textStatus + ':' + errorThrown);
                    }
                })
            }

            function openCloseList(ele){
                $(ele).next().toggle();
                child = $(ele).find(">:first-child")
                if(child.hasClass('list-title-close')){
                   child.removeClass('list-title-close');
                   child.addClass('list-title-open');
                }else{
                   child.removeClass('list-title-open');
                   child.addClass('list-title-close');
                }
            }

            function orderForm(){
                var selectedForm = $('#form-list').find('input:checked');
                if(selectedForm.length>0){
                    // Ajax call started to start an assessment
                    // alert(selectedForm.length)
                    for(var i=0; i<selectedForm.length;i++){
                        // writeOrder("test", "testname", "testOID", 1, "2018-01-25 00:00:00")
                        (function(i){
                            var formOID = $(selectedForm[i]).val();
                            var formName = $(selectedForm[i]).attr('desc');
                            $.ajax({
                                url: Server + "/2014-01/Assessments/" + formOID + ".json",
                                cache: false,
                                type: "POST",
                                // TODO: assign UID value dynamically
                                data: "UID=1",
                                dataType: "json",

                                beforeSend: function(xhr) {
                                    var bytes = Crypto.charenc.Binary.stringToBytes("BBD62935-F76F-4EC8-8834-BDAA75DAD8AB:9A35D313-E7BC-41C9-8933-3A3D73953F73");
                                    var base64 = Crypto.util.bytesToBase64(bytes);
                                    xhr.setRequestHeader("Authorization", "Basic " + base64);
                                },
                            
                                success: function(data) {
                                    // OID=85746adc-e6d7-42a3-8985-859dcbf7ece2
                                    // UID=1
                                    // Expiration: Timestamp; duration: 3 days; timezone: CST

                                    writeOrder(formOID, formName, data.OID, data.UID, data.Expiration, 'ordered')
                                    
                                },
                            
                                error: function(jqXHR, textStatus, errorThrown) {
                                    document.write(jqXHR.responseText + ':' + textStatus + ':' + errorThrown);
                                }
                            });
                            // Ajax call ended 
                        })(i)
                    }
                                       
                }else{
                    alert("No form selected to order!");
                }
            }
            function writeOrder(formOID, formName, assessmentOID, uid, expiration, status){
                $.ajax({
                    url: "./write_assessment.php",
                    type: 'POST',
                    data: {"formOID": formOID, 'formName': formName, 'assessmentOID': assessmentOID, 'uid': uid, 'expiration': expiration, 'status': status},
                    success: function() {
                        alert("Successfully ordered form "+ formName);
                    }
                });
            }
        </script>
    </body>
</html>