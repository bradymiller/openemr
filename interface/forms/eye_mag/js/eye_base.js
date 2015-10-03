/**
 * forms/eye_mag/js/eye_base.js
 *
 * JS Functions for eye_mag form(s)
 *
 * Copyright (C) 2015 Raymond Magauran <magauran@MedFetch.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @author Ray Magauran <magauran@MedFetch.com>
 * @link http://www.open-emr.org
 */
var prior_field;
var prior_text;
var priors = [];
var response = [];
var update_chart;
var obj= [];
var IMP_order = [];
var IMP_target;
if (typeof IMPPLAN_items == "undefined") { var  IMPPLAN_items = []; }

/*
 * Function to add a quick pick selection to the correct fields on the form.
 * This function will ultimately add a new item to the obj.PMSFH['new'] array to be included in the Impression/Plan coding square.
 * In essence when a clinical finding is documented, the Impression list will be rebuilt with this new finding/Dx, if it is not already there.
 * eg. user clicks to add +2 NS catarct to a "lens" field.  We will extract the Dx Cataract, add the ICD-10 code if it exists (it may not if they
 * are not using ICD-10) and then add it to the list of Dxs to build the Impression textarea.  I imagine this can be sent to the billing
 * area too but I'm not sure how to do that, yet... Any one want to help here?
 */
function fill_QP_field(PEZONE, ODOSOU, LOCATION_text, selection,fill_action) {
    if (ODOSOU > '') {
        var FIELDID =  ODOSOU  + LOCATION_text;
    } else {
        var FIELDID =  document.getElementById(PEZONE+'_'+ODOSOU).value  + LOCATION_text;
    }
    var bgcolor = $("#" +FIELDID).css("background-color");
    var prefix = document.getElementById(PEZONE+'_prefix').value;
    var Fvalue = document.getElementById(FIELDID).value;
    if (prefix > '' && prefix !='off') {prefix = prefix + " ";}
    if (prefix =='off') { prefix=''; }
    
    if (fill_action =="REPLACE") {
        $("#" +FIELDID).val(prefix +selection);
        $("#" +FIELDID).css("background-color","#F0F8FF");
    } else {
        if (($("#" +FIELDID).css("background-color")=="rgb(245, 245, 220)") || (Fvalue ==''))  {
            $("#" +FIELDID).val(prefix+selection).css("background-color","#F0F8FF");
        } else {
            if (Fvalue >'') prefix = ", "+prefix;
            $("#" +FIELDID).val(Fvalue + prefix +selection).css("background-color","#F0F8FF");
        }
    }
    submit_form(FIELDID);
}

/*
 * This is the core function of the form.  
 * It submits the data in the background via ajax.
 * It is the reason we don't use a submit button.
 * It is called often, perhaps too often for some installs because it uses bandwidth.  
 * It needs to be keenly looked at by developers as it will affect scalability.
 * It return one piece of data only "Code 400" or nothing.
 * It ensures ownership of the form and providing background updates to READ-ONLY instances of the form.
 * It doesn't unlock a form to change ownership/provide write privileges.  This is done via the unlock() function.
 */
function submit_form(action) {
    var url = "../../forms/eye_mag/save.php?mode=update&id=" + $("#form_id").val();
    if ($("#COPY_SECTION").value == "READONLY") return;
    formData = $("form#eye_mag").serialize();
    $("#menustate").val('0');
    $.ajax({
           type 	: 'POST',   // define the type of HTTP verb we want to use (POST for our form)
           url 		: url,      // the url where we want to POST
           data 	: formData // our data object
           }).done(function(o) {
                   if (o == 'Code 400') {
                   code_400();
                   } else {
                   // Did not get 'Code 400' back from the server so there was no one stole ownership. We own it and lock it.
                   // We have control of the ACTIVE chart.
                   }
                   });
};

/*
 *  This function alerts the user that they have lost write privileges to another user.  
 *  The form is locked (fields disabled) and they enter the READ-ONLY mode.
 *  In READ-ONLY mode the form is refreshed every 15 seconds showing changes made by the user with write privileges.
 */
function code_400() {
        //User lost ownership.  Just watching now...
        //now we should get every variable and update the form, every 15 seconds...
    $("#active_flag").html(" READ-ONLY ");
    toggle_active_flags("off");
    alert("Another user has taken control of this form.\rEntering READ-ONLY mode.");
    update_READONLY();
    this_form_id = $("#form_id").val();
    $("#COPY_SECTION").val("READONLY");
    update_chart = setInterval(function() {
                               if ($("#chart_status").value == "on") { clearInterval(update_chart); }
                               update_READONLY();
                               }, 15000);
    

}
/*
 *  Function to check locked state
 */
function check_lock(modify) {
    var locked = $("#LOCKED").val();
    var locked_by = $("#LOCKEDBY").val();
    var uniqueID = $('#uniqueID').val();
    var url = "../../forms/eye_mag/save.php?mode=update&id=" + $("#form_id").val();
    clearInterval(update_chart);
    if (locked =='1' && locked_by >'' && (uniqueID != locked_by)) {  //form is locked by someone else...
        $("#active_flag").html(" READ-ONLY ");
        if (confirm('\tLOCKED by another user:\t\n\tSelect OK to take ownership or\t\n\tCANCEL to enter READ-ONLY mode.\t')) {
            $.ajax({
                   type 	: 'POST',   // define the type of HTTP verb we want to use (POST for our form)
                   url 		: url,      // the url where we want to POST
                   data     : {
                   'acquire_lock'  : '1',
                   'uniqueID'      : uniqueID,
                   'form_id'       : $("#form_id").val()
                   }
                   }).done(function(d) {
                           $("#LOCKEDBY").val(uniqueID);
                           toggle_active_flags("on");
                           }
                           );
        } else {
            //User doesn't want ownership.  Just watching...
            //now we should get every variable and update the form, every 10 seconds or so...
            toggle_active_flags("off");
            update_chart = setInterval(function() {
                                       $("#COPY_SECTION").trigger('change');
                                       if ($("#chart_status").val() == "on") { clearInterval(update_chart);}
                                       update_READONLY();
                                    }, 15000);
            if ($("#chart_status").value == "on") { clearInterval(update_chart); }
            
        }
    } else if (modify=='1') {
        if ($("#chart_status").val() == "on") {
            unlock();
            toggle_active_flags("off");
            update_chart = setInterval(function() {
                                       if ($("#chart_status").val() == "on") { clearInterval(update_chart);}
                                       update_READONLY();
                                       }, 15000);
            if ($("#chart_status").value == "on") { clearInterval(update_chart); }
            

        } else {
            $.ajax({
                   type 	: 'POST',   // define the type of HTTP verb we want to use (POST for our form)
                   url 		: url,      // the url where we want to POST
                   data     : {
                   'acquire_lock'  : '1',
                   'uniqueID'      : uniqueID,
                   'form_id'       : $("#form_id").val()
                   }
                   }).done(function(d) {
                           $("#LOCKEDBY").val(uniqueID);
                           toggle_active_flags("on");
                           clearInterval(update_chart);
                           });
        }
    }
}
/*
 * Function to save a canvas by zone
 */
function submit_canvas(zone) {
    var id_here = document.getElementById('myCanvas_'+zone);
    var dataURL = id_here.toDataURL('image/jpeg');
    $.ajax({
           type: "POST",
           url: "../../forms/eye_mag/save.php?canvas="+zone+"&id="+$("#form_id").val(),
           data: {
           imgBase64     : dataURL,  //this contains the new strokes, the sketch.js foreground
           'zone'        : zone,
           'visit_date'  : $("#visit_date").val(),
           'encounter'   : $("#encounter").val(),
           'pid'         : $("#pid").val()
           }
           
           }).done(function(o) {
                   });
}
/*
 *  Function to update the user's preferences
 */
function update_PREFS() {
    var url = "../../forms/eye_mag/save.php";
    var formData = {
        'AJAX_PREFS'            : "1",
        'PREFS_VA'              : $('#PREFS_VA').val(),
        'PREFS_W'               : $('#PREFS_W').val(),
        'PREFS_MR'              : $('#PREFS_MR').val(),
        'PREFS_CR'              : $('#PREFS_CR').val(),
        'PREFS_CTL'             : $('#PREFS_CTL').val(),
        'PREFS_ADDITIONAL'      : $('#PREFS_ADDITIONAL').val(),
        'PREFS_VAX'             : $('#PREFS_VAX').val(),
        'PREFS_IOP'             : $('#PREFS_IOP').val(),
        'PREFS_CLINICAL'        : $('#PREFS_CLINICAL').val(),
        'PREFS_EXAM'            : $('#PREFS_EXAM').val(),
        'PREFS_CYL'             : $('#PREFS_CYL').val(),
        'PREFS_EXT_VIEW'        : $('#PREFS_EXT_VIEW').val(),
        'PREFS_ANTSEG_VIEW'     : $('#PREFS_ANTSEG_VIEW').val(),
        'PREFS_RETINA_VIEW'     : $('#PREFS_RETINA_VIEW').val(),
        'PREFS_NEURO_VIEW'      : $('#PREFS_NEURO_VIEW').val(),
        'PREFS_ACT_VIEW'        : $('#PREFS_ACT_VIEW').val(),
        'PREFS_ACT_SHOW'        : $('#PREFS_ACT_SHOW').val(),
        'PREFS_HPI_RIGHT'       : $('#PREFS_HPI_RIGHT').val(),
        'PREFS_PMH_RIGHT'       : $('#PREFS_PMH_RIGHT').val(),
        'PREFS_EXT_RIGHT'       : $('#PREFS_EXT_RIGHT').val(),
        'PREFS_ANTSEG_RIGHT'    : $('#PREFS_ANTSEG_RIGHT').val(),
        'PREFS_RETINA_RIGHT'    : $('#PREFS_RETINA_RIGHT').val(),
        'PREFS_NEURO_RIGHT'     : $('#PREFS_NEURO_RIGHT').val(),
        'PREFS_PANEL_RIGHT'     : $('#PREFS_PANEL_RIGHT').val(),
        'PREFS_IMPPLAN_RIGHT'   : $('#PREFS_IMPPLAN_DRAW').val(),
        'PREFS_KB'              : $('#PREFS_KB').val()
    };
    $.ajax({
           type 		: 'POST',
           url          : url,
           data 		: formData
           }).done(function(o) {
                   //      console.log(o);
                   //$("#tellme").html(o);
                   });
}
/*
 *  Function to finalize chart -  esign??,  at least close it and remove temporary lock at DB level.
 */
function unlock() {
    var url = "../../forms/eye_mag/save.php?mode=update&id=" + $("#form_id").val();
    var formData = {
        'action'           : "unlock",
        'unlock'           : "1",
        'encounter'        : $('#encounter').val(),
        'pid'              : $('#pid').val(),
        'LOCKEDBY'         : $('#LOCKEDBY').val(),
        'form_id'          : $("#form_id").val()
    };
    $.ajax({
           type 		: 'POST',
           url          : url,
           data 		: formData }).done(function(o) {
                                           $("#warning").removeClass("nodisplay");
                                           $('#LOCKEDBY').val('');
                                           $('#chart_status').val('off');
                                           });
}

/*
 *  START OF PMSFH FUNCTIONS
 */
function alter_issue2(issue_number,issue_type,index) {
    if (!obj.PMSFH) { refresh_page(); }
    var here = obj.PMSFH[issue_type][index];
        window.frames[0].frameElement.contentWindow.newtype(issue_type);
        if (issue_type !='SOCH' && issue_type !='FH' && issue_type !='ROS') {
            $('iframe').contents().find('#delete_button').removeClass('nodisplay');
        } else {
            $('iframe').contents().find('#delete_button').addClass('nodisplay');
        }
        $('iframe').contents().find('#issue'                ).val(issue_number);
    if (typeof here !== "undefined") {
        $('iframe').contents().find('#form_title'           ).val(here.title);
        $('iframe').contents().find('#form_diagnosis'       ).val(here.diagnosis);
        $('iframe').contents().find('#form_begin'           ).val(here.begdate);
        $('iframe').contents().find('#form_end'             ).val(here.enddate);
        $('iframe').contents().find('#form_reaction'        ).val(here.reaction);
        $('iframe').contents().find('#form_referredby'      ).val(here.referredby);
        $('iframe').contents().find('#form_classification'  ).val(here.classification);
        $('iframe').contents().find('#form_occur'           ).val(here.occurrence);
        $('iframe').contents().find('#form_comments'        ).val(here.comments);
        $('iframe').contents().find('#form_outcome'         ).val(here.outcome);
        $('iframe').contents().find('#form_destination'     ).val(here.destination);
    }
}
function showArray(arr) {
    var tS = new String()
    for (var iI in arr) {
        tS += "Index "+iI+", Type "+(typeof arr[iI])+", Value "+arr[iI]+"\n"
    }
    return tS
}

/* Function trying to do PMSFH with one call to server without refreshing page at all.
 * Need to delete the issue from server via ajax
 * then remove the issue from obj.PMSFH with javascript without relying on ajax result
 * then then update right_panel and QP_panel
 */
function delete_issue2(issue_number,PMSFH_type) {
    $('#form#theform issue').val(issue_number);
    $('iframe').contents().find('#issue').val(issue_number);
    $('form#theform form_type');
    
    var url = '../../forms/eye_mag/a_issue.php';
    var formData = {
        'a_issue'           : issue_number,
        'deletion'            : '1',
        'PMSFH'             : '1'
    };
    $.ajax({
           type 		: 'POST',
           url          : url,
           data 		: formData,
           success:(function(result) {
                    obj = JSON.parse(result);
                    $("#QP_PMH").html(obj.PMH_panel);
                    //var highestCol = Math.max($('#PMSFH_block_1').height(),$('#PMSFH_block_2').height());
                    //if (highestCol < '344') highestCol = '330';
                    //$('#PMSFH_block_1').height(highestCol);$('#PMSFH_block_2').height(highestCol);
                    if ($('#PMH_right').height() > $('#PMH_left').height()) {
                    $('#PMH_left').height($('#PMH_right').height());
                    } else { $('#PMH_left').height($('#PMH_right').height()); }
                    $("#right_panel_refresh").html(obj.right_panel);
                    build_DX_list(obj);
                    })
           });
    show_QP();
    return false;
        // $("#Enter_PMH").html(result);
}

/*
 *  Function to save the PMSFH array to the server.
 *  This can be removed in the future - save for now
 */
function submit_PMSFH() {
    var url = "../../forms/eye_mag/save.php?PMSFH_save=1&mode=update";
    formData = $("[id^=f]").serialize();
    var f = document.forms[0];
    $.ajax({
           type   : 'POST',   // define the type of HTTP verb we want to use (POST for our form)
           url    : url,      // the url where we want to POST
           data   : formData  // our data object
           }).done(function(result){
                   f.form_title.value = '';
                   f.form_diagnosis.value = '';
                   f.form_begin.value ='';
                   f.form_end.value ='';
                   f.form_referredby.value ='';
                   f.form_reaction.value ='';
                   f.form_classification.value ='';
                   f.form_occur.value='';
                   f.form_comments.value ='';
                   f.form_outcome.value ='';
                   f.form_destination.value ='';
                   f.issue.value ='';
                   //$("#page").html(result);
                   populate_PMSFH(result);
                   //if this is chronic, save it as a chronic
                   //above function will fill in the chronics if not already.
                   });}

/* If a clickoption title is selected, copy it to the title field.
 * We also want to copy any other fields present in object.PMSFH_options
 * We need to build this object first.  The base install options will need ICD-10 codes attached
 * to make this work.
 */
function set_text() {
    var f = document.forms[0];
    f.form_title.value = f.form_titles.options[f.form_titles.selectedIndex].text;
    f.form_titles.selectedIndex = -1;
}
/*
 *  END OF PMSFH FUNCTIONS
 */

/*
 *  Function to refresh the issues, the panels and the Impression/coding areas.
 */
function refresh_page() {
    var url = '../../forms/eye_mag/view.php?display=PMSFH';
    var formData = {
        'action'           : "refresh",
        'id'               : $('#form_id').val(),
        'encounter'        : $('#encounter').val(),
        'pid'              : $('#pid').val(),
        'refresh'          : 'page'
    };
    $.ajax({
           type 		: 'POST',
           url          : url,
           data 		: formData,
           success:(function(result) {
                    populate_PMSFH(result);
                    })
           });
        //Make the height of the panels equal if they grow really large
    if ($('#PMH_right').height() > $('#PMH_left').height()) {
        $('#PMH_left').height($('#PMH_right').height());
    } else { $('#PMH_left').height($('#PMH_right').height()); }
      return false;
}
/*
 *  Server returns a json encoding object: obj to update the page
 *  Here we refresh the PMSFH display panels,
 *  Rebuild the Impression/Plan Builder DX lists
 *  and the CHRONIC fields.  
 */
function populate_PMSFH(result) {
    obj = JSON.parse(result);
    $("#QP_PMH").html(obj.PMH_panel);
    if ($('#PMH_right').height() > $('#PMH_left').height()) {
        $('#PMH_left').height($('#PMH_right').height());
    } else { $('#PMH_left').height($('#PMH_right').height()); }
    $("#right_panel_refresh").html(obj.right_panel);
    build_DX_list(obj); //build the list of DXs to show in the Impression/Plan Builder
    build_Chronics(obj);
}
/*
 *  Function to auto-fill CHRONIC fields
 *  To reach a detailed E&M level of documentation the chart
 *  must comment on the status of 3 or more CHRONIC/Inactive problems.
 *  The user can type them into the CHRONIC fields manually, or 
 *  we can do it programatically if the user does the following:
 *     1.  documenting a PMH diagnosis in the PMSFH area
 *     2.  listing it as "Chronic"
 *     3.  making a comment about it
 *  With these three steps completed, this build_CHRONIC function displays the changes
 *  in the CHRONIC1-3 textareas, if not already filled in, for today's visit.
 *  On subsequent visits, the CHRONIC1-3 fields are blank, unless the above steps
 *  were performed previously, then they are filled in automatically on loading of the new form.
 */
function build_Chronics(obj) {
    var CHRONICS = obj.PMSFH['CHRONIC'];
    var chronic_value;
    var local_comment;
    var here_already;
    $.each(CHRONICS, function(key, value) {
           local_comment = CHRONICS[key].title+" "+CHRONICS[key].diagnosis+"\n"+CHRONICS[key].comments;
           here_already ='0';
           for (i=1; i < 4; i++) {
                chronic_value = $('#CHRONIC'+i).val();
                if (chronic_value == local_comment) {
                    here_already='1';  //this is here, move to next CHRONICS
                    break;
                }
           }
           if (here_already !='1') {
                for (i=1; i < 4; i++) {
                    chronic_value = $('#CHRONIC'+i).val();
                    if (chronic_value == '') {  //if the CHRONIC1-3 field is empty, fill it.
                        $('textarea#CHRONIC'+i).val(local_comment);
                        break;
                    }
                }
           }
        });
        return false;
 }
/*
 * Function to autocreate a PDF of this form as a document linked to this encounter.
 * Each time it is runs it updates by replacing the encounter's PDF.
 * This used to be fired often,  but it is a server resource beast.
 */
function store_PDF() {
    var url = "../../forms/eye_mag/save.php?mode=update";
    var formData = {
        'action'        : 'store_PDF',
        'patient_id'    : $('#pid').val(),
        'pdf'           : '1',
        'printable'     : '1',
        'form_folder'   : $('#form_folder').val(),
        'form_id'       : $('#form_id').val(),
        'encounter'     : $('#encounter').val(),
        'uniqueID'      : $('#uniqueID').val()
    };
    $.ajax({
           type         : 'POST',
           url          : url,
           data 		: formData,
           success:(function(result2) {
                     })
           
           }).done(function(o) {
        });
}

/* START Functions related to form VIEW */
/*
 * Function to blow out the form and display the right side of every section.
 */
function show_right() {
    $("#HPI_1").removeClass("size50").addClass("size100");
    $("#PMH_1").removeClass("size50").addClass("size100");
    $("#EXT_1").removeClass("size50").addClass("size100");
    $("#ANTSEG_1").removeClass("size50").addClass("size100");
    $("#NEURO_1").removeClass("size50").addClass("size100");
    $("#RETINA_1").removeClass("size50").addClass("size100");
    $("#IMPPLAN_1").removeClass("size50").addClass("size100");
    $("#HPI_right").removeClass('nodisplay');
    $("#PMH_right").removeClass('nodisplay');
    $("#EXT_right").removeClass('nodisplay');
    $("#ANTSEG_right").removeClass('nodisplay');
    $("#NEURO_right").removeClass('nodisplay');
    $("#RETINA_right").removeClass('nodisplay');
    $("#IMPPLAN_right").removeClass('nodisplay');
    $("#PMH_1").addClass("clear_both");
    $("#ANTSEG_1").addClass("clear_both");
    $("#RETINA_1").addClass("clear_both");
    $("#NEURO_1").addClass("clear_both");
    $("#IMPPLAN_1").addClass("clear_both");
    hide_PRIORS();
}
/*
 * Function to implode the form and hide the right side of every section.
 */
function hide_right() {
    $("#HPI_1").removeClass("size100").addClass("size50");
    $("#PMH_1").removeClass("size100").addClass("size50");
    $("#EXT_1").removeClass("size100").addClass("size50");
    $("#ANTSEG_1").removeClass("size100").addClass("size50");
    $("#NEURO_1").removeClass("size100").addClass("size50");
    $("#RETINA_1").removeClass("size100").addClass("size50");
    $("#IMPPLAN_1").removeClass("size100").addClass("size50");
    $("#HPI_right").addClass('nodisplay');
    $("#PMH_right").addClass('nodisplay');
    $("#EXT_right").addClass('nodisplay');
    $("#ANTSEG_right").addClass('nodisplay');
    $("#NEURO_right").addClass('nodisplay');
    $("#RETINA_right").addClass('nodisplay');
    $("#PMH_1").removeClass("clear_both");
    $("#ANTSEG_1").removeClass("clear_both");
    $("#RETINA_1").removeClass("clear_both");
    $("#NEURO_1").removeClass("clear_both");
    update_PREFS();
}
/*
 * Function to explode the form and show the left side of every section.
 */
function show_left() {
    $("#HPI_1").removeClass("size100").addClass("size50");
    $("#PMH_1").removeClass("size100").addClass("size50");
    $("#EXT_1").removeClass("size100").addClass("size50");
    $("#ANTSEG_1").removeClass("size100").addClass("size50");
    $("#NEURO_1").removeClass("size100").addClass("size50");
    $("#RETINA_1").removeClass("size100").addClass("size50");
    $("#IMPPLAN_1").removeClass("size100").addClass("size50");
    $("#HPI_left").removeClass('nodisplay');
    $("#PMH_left").removeClass('nodisplay');
    $("#EXT_left").removeClass('nodisplay');
    $("#ANTSEG_left").removeClass('nodisplay');
    $("#RETINA_left").removeClass('nodisplay');
    $("#NEURO_left").removeClass('nodisplay');
    $("#IMPPLAN_left").removeClass('nodisplay');
    $("[name$='_left']").removeClass('nodisplay');
}
/*
 * Function to implode the form and hide the left side of every section.
 */
function hide_left() {
    $("#HPI_1").removeClass("size100").addClass("size50");
    $("#PMH_1").removeClass("size100").addClass("size50");
    $("#EXT_1").removeClass("size100").addClass("size50");
    $("#ANTSEG_1").removeClass("size100").addClass("size50");
    $("#NEURO_1").removeClass("size100").addClass("size50");
    $("#RETINA_1").removeClass("size100").addClass("size50");
    $("#IMPPLAN_1").removeClass("size100").addClass("size50");
    $("#HPI_left").addClass('nodisplay');
    $("#PMH_left").addClass('nodisplay');
    $("#EXT_left").addClass('nodisplay');
    $("#ANTSEG_left").addClass('nodisplay');
    $("#RETINA_left").addClass('nodisplay');
    $("#NEURO_left").addClass('nodisplay');
    $("#IMPPLAN_left").addClass('nodisplay');
    $("[name $='_left']").addClass('nodisplay');
}
/*
 * Function to display only the DRAW panels of every section.
 * The technical section, between HPI and Clinical section is still viible.
 */
function show_DRAW() {
    hide_QP();
    hide_TEXT();
    hide_PRIORS();
    hide_left();
    hide_KB();
    show_right();
        //$("#LayerTechnical_sections").addClass('nodisplay');
        //$("#REFRACTION_sections").addClass('nodisplay');
    $("#HPI_right").addClass('canvas');
    $("#PMH_right").addClass('canvas');
    $("#EXT_right").addClass('canvas');
    $("#ANTSEG_right").addClass('canvas');
    $("#RETINA_right").addClass('canvas');
    $("#NEURO_right").addClass('canvas');
    $("#IMPPLAN_right").addClass('canvas');
    $(".Draw_class").removeClass('nodisplay');
    if ($("#PREFS_CLINICAL").val() !='1') {
            // we want to show text_only which are found on left half
        $("#PREFS_CLINICAL").val('1');
        $("#PREFS_EXAM").val('DRAW');
    }
    update_PREFS();
}
/*
 * Function to display only the TEXT panels in every section.
 */
function show_TEXT() {
    $("#PMH_1").removeClass('nodisplay');
    $("#NEURO_1").removeClass('nodisplay');
    $("#IMPPLAN_1").removeClass('nodisplay');
    $(".TEXT_class").removeClass('nodisplay');
    show_left();
    hide_right(); //this hides the right half
    hide_QP();
    hide_DRAW();
    hide_PRIORS();
    if ($("#PREFS_CLINICAL").val() !='1') {
            // we want to show text_only which are found on left half
        $("#PREFS_CLINICAL").val('1');
    }
        // if (typeof here !== "undefined") {
    $("#PREFS_EXAM").val('TEXT');
    update_PREFS();
}
/*
 * Function to display only the PRIORS panels in every section.
 */
function show_PRIORS() {
    $("#NEURO_sections").removeClass('nodisplay');
    hide_DRAW();
    $("#EXT_right").addClass("PRIORS_color");
    show_TEXT();
    show_right();
    hide_QP();
    $("#QP_HPI").removeClass('nodisplay');//no PRIORS yet here, show QP
    $("#QP_PMH").removeClass('nodisplay');//no PRIORS yet here, show QP
    $("#HPI_right").addClass('canvas');
    $("#PMH_right").addClass('canvas');
    $("#IMPPLAN_right").addClass('canvas');
    $("#EXT_right").addClass('canvas');
    $("#ANTSEG_right").addClass('canvas');
    $("#RETINA_right").addClass('canvas');
    $("#NEURO_right").addClass('canvas');
    $(".PRIORS_class").removeClass('nodisplay');
        //ui is not recognized here...
        //var location1 = parseInt( ui.offset.top );
    var location2 = $("#EXT_anchor").offset().top -55;
    if (location2 > location2) {
        $(document).scrollTop( location2 );
    }
    if ($("#PREFS_CLINICAL").val() !='1') {
            // we want to show text_only which are found on left half now that PRIORS are visible.
        $("#PREFS_CLINICAL").val('1');
    }
    $("#PREFS_EXAM").val('PRIORS');
    update_PREFS();
}
/*
 * Function to show the Quick Picks panel on the right side of every section.
 */
function show_QP() {
    hide_DRAW();
    hide_PRIORS();
    hide_KB();
    show_TEXT();
    show_right();
    show_left();
    $("#HPI_right").addClass('canvas');
    $("#PMH_right").addClass('canvas');
    $("#EXT_right").addClass('canvas');
    $("#ANTSEG_right").addClass('canvas');
    $("#RETINA_right").addClass('canvas');
    $("#NEURO_right").addClass('canvas');
    $("#IMPPLAN_right").addClass('canvas');
    $(".QP_class").removeClass('nodisplay');
    $("#PREFS_EXAM").val('QP');
    update_PREFS();
}
/*
 * Function to display only one DRAW panel of one section.
 */
function show_DRAW_section(zone) {
        //hide_QP();
        //hide_TEXT();
        //hide_PRIORS();
    $("#QP_"+zone).addClass('nodisplay');
    $("#"+zone+"_1").removeClass('nodisplay');
    $("#"+zone+"_left").removeClass('nodisplay');
    $("#"+zone+"_right").addClass('canvas').removeClass('nodisplay');
    $("#Draw_"+zone).addClass('canvas');
    
    $("#Draw_"+zone).removeClass('nodisplay');
    /*
     $("#"+zone+"_1").removeClass('nodisplay');
     $("#"+zone+"_right").addClass('canvas').removeClass('nodisplay');
     $("#QP_"+zone).addClass('nodisplay');
     $("#PRIORS_"+zone+"_left_text").addClass('nodisplay');
     $("#DRAW_"+zone).removeClass('nodisplay');
     */
    $("#PREFS_"+zone+"_DRAW").val(1);
    update_PREFS();
}
/*
 * Function to display only one PRIORS panel of one section.
 */
function show_PRIORS_section(section,newValue) {
    var url = "../../forms/eye_mag/save.php?mode=retrieve";
    
    var formData = {
        'PRIORS_query'          : "1",
        'zone'                  : section,
        'id_to_show'            : newValue,
        'pid'                   : $('#pid').val(),
        'orig_id'               : $('#form_id').val()
    }
    $.ajax({
           type 		: 'POST',
           url       : url,
           data 		: formData,
           success   : function(result) {
           $("#PRIORS_" + section + "_left_text").html(result);
           }
           });
}
/*
 * Function to show one of the Quick Picks section on the right side of its section.
 */
function show_QP_section(zone) {
        //show_left();
    $("#"+zone+"_right").addClass('canvas').removeClass('nodisplay');
    $("#QP_"+zone).removeClass('nodisplay');
    $("#DRAW_"+zone).addClass('nodisplay');
    $("#"+zone+"_1").removeClass('nodisplay');
    $("#"+zone+"_left").removeClass('nodisplay');
    $("#PREFS_"+zone+"_RIGHT").val('QP');
    if (zone == "PMH") {
            //alter_issue('','','');
    }
}
/*
 * Function to hide all the DRAW panels of every section.
 */
function hide_DRAW() {
    $(".Draw_class").addClass('nodisplay');
    hide_right();
    $("#LayerTechnical_sections").removeClass('nodisplay');
    $("#REFRACTION_sections").removeClass('nodisplay');
    $("#PMH_sections").removeClass('nodisplay');
    $("#HPI_right").addClass('nodisplay');
    $("#HPI_right").removeClass('canvas');
    $("#EXT_right").removeClass('canvas');
    $("#RETINA_right").removeClass('canvas');
    $("#ANTSEG_right").removeClass('canvas');
}
/*
 * Function to hide all the Quick Pick panels of every section.
 */
function hide_QP() {
    $(".QP_class").addClass('nodisplay');
    $("[name$='_right']").removeClass('canvas');
}
/*
 * Function to hide all the TEXT panels of every section.
 */
function hide_TEXT() {
    $(".TEXT_class").addClass('nodisplay');
}
/*
 * Function to hide all the PIORS panels of every section.
 */
function hide_PRIORS() {
    $("#EXT_right").removeClass("PRIORS_color");
    $("#PRIORS_EXT_left_text").addClass('nodisplay');
    $("#PRIORS_ANTSEG_left_text").addClass('nodisplay');
    $("#PRIORS_RETINA_left_text").addClass('nodisplay');
    $("#PRIORS_NEURO_left_text").addClass('nodisplay');
    $(".PRIORS_class").addClass('nodisplay');
}
/*
 * Function to hide Shorthand/Keyboard Entry panel.
 */
function hide_KB() {
    $('.kb').addClass('nodisplay');
    $('.kb_off').removeClass('nodisplay');
    if ($("#PREFS_KB").val() > 0) {
        $("#PREFS_KB").val('0');
    }
}
/*
 * Function to show the Shorthand/Keyboard panel.
 */
function show_KB() {
    $('.kb').toggleClass('nodisplay');
    $('.kb_off').toggleClass('nodisplay');
    if ($('#PREFS_EXAM').val() == 'DRAW') {
        show_TEXT();
    }
    
    if ($("#PREFS_KB").val() > 0) {
        $("#PREFS_KB").val('0');
    } else {
        $("#PREFS_KB").val('1');
    }
    update_PREFS();
}
/* END Functions related to form VIEW */

/*
 * Function contains menu commands specific to this form.
 */
function menu_select(zone,che) {
    $("#menu_"+zone).addClass('active');
    if (zone =='PREFERENCES') {
        window.parent.RTop.document.location.href = "/openemr/interface/super/edit_globals.php"
        var url = "/openemr/interface/super/edit_globals.php";
        var formData = {
            'id'               : $('#id').val(),
            'encounter'        : $('#encounter').val(),
            'pid'              : $('#pid').val(),
        };
        $.ajax({
               type 		: 'GET',
               url          : url,
               data 		: formData,
               success      : function(result) {
               window.parent.RTop.document.result;
               }
               });
    }
    if (zone =='PRIORS') $("#PRIORS_ALL_minus_one").trigger("click");
    if (zone =='QP') show_QP();
    if (zone =='KB') show_KB();
    if (zone =='DRAW') show_DRAW();
    if (zone =='TEXT') show_TEXT();
    if (zone =='PMH') $(window).scrollTop( $("#PMH_anchor").offset().top -55);
    if (zone =='EXT') $(window).scrollTop( $("#EXT_anchor").offset().top -55);
    if (zone =='ANTSEG') $(window).scrollTop( $("#ANTSEG_anchor").offset().top -55);
    if (zone =='RETINA') $(window).scrollTop( $("#RETINA_anchor").offset().top -55);
    if (zone =='NEURO') $(window).scrollTop( $("#NEURO_anchor").offset().top -55);
    if (zone =='Right_Panel') $("#right-panel-link").trigger("click");
    if (zone =='IOP_graph') { $("#LayerVision_IOP").removeClass('nodisplay'); $(window).scrollTop( $("#REFRACTION_anchor").offset().top -55); }
}
/*
 * Function for the Track Anything stuff.
 * Not sure yet how to use this but I believe I will use it to display IOP_graph, so keep it.
 *   // plot the current graph
 *   //------------------------------------------------------
 */
function plot_graph(checkedBoxes, theitems, thetrack, thedates, thevalues, trackCount){
    top.restoreSession();
    return $.ajax({ url: '/openemr/library/openflashchart/graph_track_anything.php',
                  type: 'POST',
                  data: {
                  dates:  thedates,   //$the_date_array
                  values: thevalues,  //$the_value_array
                  items:  theitems,   //$the_item_names
                  track:  thetrack,   //$titleGraph
                  thecheckboxes: checkedBoxes //$the_checked_cols
                  },
                  dataType: "json",
                  success: function(returnData){
                  // ofc will look after a variable named "ofc"
                  // inside of the flashvar
                  // However, we need to set both
                  // data and flashvars.ofc
                  data=returnData;
                  flashvars.ofc = returnData;
                  // call ofc with proper falshchart
                  swfobject.embedSWF('/openemr/library/openflashchart/open-flash-chart.swf',
                                     "graph"+trackCount, "650", "200", "9.0.0","",flashvars);
                  },
                  error: function (XMLHttpRequest, textStatus, errorThrown) {
                  // alert(XMLHttpRequest.responseText);
                  //alert("XMLHttpRequest="+XMLHttpRequest.responseText+"\ntextStatus="+textStatus+"\nerrorThrown="+errorThrown);
                  }
                  
                  }); // end ajax query
}
/*
 * Function to test blowing up a section to fullscren - towards tablet functionality?
 * Currently not used.
 */
function show_Section(section) {
        //hide everything, show the section.  For fullscreen perhaps Tablet view per section
    show_right();
    $("div[name='_sections']").style.display= "none"; //
    $('#'+section+'_sections').style.display= "block";
        //.show().appendTo('form_container');
}
/*
 * Function to display Chief Complaint 1-3
 */
function show_CC(CC_X) {
    $("[name^='CC_']").addClass('nodisplay');
    $("#CC_"+CC_X).removeClass('nodisplay');
    $("#CC_"+CC_X).index;
}

/* START Functions related to CODING */

/*
 * Function to determine if add on NeuroSensory(92060) code can be billed.
 */
function check_CPT_92060() {
    var neuro1='';
    var neuro2 ='';
    if ($("#STEREOPSIS").val() > '') (neuro1="1");
    $(".neurosens2").each(function(index) {
                          if ($( this ).val() > '') {
                          neuro2="1";
                          }
                          });
    
    if (neuro1 && neuro2){
        $("#neurosens_code").removeClass('nodisplay');
    } else {
        $("#neurosens_code").addClass('nodisplay');
    }
    
}
/*
 * Function to check documentation level for coding purposes
 * And make suggestions to end user.
 * Working towards integrating coding functionality/tie in to this form.
 */
function check_exam_detail() {
    var detail_reached_HPI ='0';
    var chronic_reached_HPI = '0';
    $(".count_HPI").each(function(index) {
                         // console.log( index + ": " + $( this ).val() );
                         if ($( this ).val() > '') detail_reached_HPI++;
                         
                         });
    if (detail_reached_HPI > '3') {
        $(".detail_4_elements").css("color","red");
        $(".CODE_LOW").addClass("nodisplay");
        $(".CODE_HIGH").removeClass("nodisplay");
        $(".detailed_HPI").css("color","red");
    } else {
        $(".detail_4_elements").css("color","#876F6F");
    }
    $(".chronic_HPI").each(function(index) {
                           if ($( this ).val() > '') chronic_reached_HPI++;
                           });
    if (chronic_reached_HPI > '2') {
        $(".chronic_3_elements").css("color","red");
        $(".CODE_LOW").addClass("nodisplay");
        $(".CODE_HIGH").removeClass("nodisplay");
        $(".detailed_HPI").css("color","red");
        
    } else {
        $(".chronic_3_elements").css("color","#876F6F");
    }
    if ((chronic_reached_HPI > '2')||(detail_reached_HPI > '3')) {
        $(".CODE_LOW").addClass("nodisplay");
        $(".CODE_HIGH").removeClass("nodisplay");
        $(".detailed_HPI").css("color","red");
    } else {
        $(".CODE_LOW").removeClass("nodisplay");
        $(".CODE_HIGH").addClass("nodisplay");
        $(".detailed_HPI").css("color","#876F6F");
    }
}

/* END Functions related to CODING */

/* START Functions related to IMPPLAN Builder */
    /*
     * Function to update the list of Dxs available for Impression/Plan and Coding(?).
     * TODO: will use actual list from IMPPLAN_items for coding.
     * After a new DX is added via PMSFH (or other ways), it updates the sortable and draggable list of DXs
     * available to build the Impression/Plan from.
     */
    function build_DX_list(obj) {
        var out = "";
        var diagnosis;
        $( "#build_DX_list" ).empty();
            //add in inc_FIELDCODES culled from the datafields
        if (!obj.PMSFH['POH']  && !obj.PMSFH['PMH']) {
            out = '<br /><span class="bold">The Past Ocular History (POH) and Past Medical History (PMH) are negative and no diagnosis was auto-generated from the clinical findings.</span><br /><br>Update the chart to activate the Builder.<br />';
            $( "#build_DX_list" ).html(out);
            return;
        }
        if ($('#inc_POH').is(':checked') && obj.PMSFH['POH']) {
            $.each(obj.PMSFH['POH'], function(key, value) {
                   diagnosis='';
                   if (obj.PMSFH['POH'][key].diagnosis > '' ) {
                        diagnosis = "<code class='pull-right ICD_CODE'>"+obj.PMSFH['POH'][key].diagnosis+"</code>";
                   }
                   out += "<li class='ui-widget-content'><span name='DX_POH_"+key+"' id='DX_POH_"+key+"'>"+obj.PMSFH['POH'][key].title+"</span> "+diagnosis+"</li>";
                   });
        }
        if ($('#inc_PMH').is(':checked') && obj.PMSFH['PMH']) {
            $.each(obj.PMSFH['PMH'], function(key, value) {
                   diagnosis='';
                   if (obj.PMSFH['PMH'][key].diagnosis > '') {
                        diagnosis = "<code class='pull-right ICD_CODE'>"+obj.PMSFH['PMH'][key].diagnosis+"</code>";
                   }
                   out += "<li class='ui-widget-content'><span name='DX_PMH_"+key+"' id='DX_PMH_"+key+"'>"+obj.PMSFH['PMH'][key].title+"</span>"+diagnosis+"</li> ";
                   });
        }
            //add in inc_FIELDCODES culled from the datafields
        if (out !="") {
            rebuild_IMP($( "#build_DX_list" ));
            $( "#build_DX_list" )
            .html(out).sortable({ handle: ".handle",stop: function(event, ui){ rebuild_IMP($( "#build_DX_list" )) } })
            .selectable({ filter: "li", cancel: ".handle",stop: function(event, ui){ rebuild_IMP($( "#build_DX_list" )) } })
            .find( "li" )
            .addClass( "ui-corner-all  ui-selected" )
            .dblclick(function(){
                      rebuild_IMP($( "#build_DX_list" ));
                      $('#make_new_IMP').trigger('click'); //any items selected are sent to IMPPLAN directly.
                      })
                //this places the handle for the user to drag the item around.
            .prepend( "<div class='handle ui-icon ui-icon-carat-2-n-s'><i class='fa fa-arrows fa-1'></i></div>" );
        } else {
            out = '<br /><span class="bold">No diagnosis was auto-generated from the clinical findings.</span><br /><br>';
            out += 'Past Ocular History (POH) and Past Medical History (PMH) items added in the PMSFH area appear here and are available to the Builder.<br />';
            $( "#build_DX_list" ).html(out);
        }
    }
    /*
     * Function:  After the Builder DX list is built from all the available options,
     * the end user can select to use only certain Dxs and change their sort order of importance.
     * This function builds the list of DXs selected and in the order as the user sorted them,
     * so we know what to use to build the Impression/Plan area and in what order to display them.
     */
    function rebuild_IMP(obj2) {
        var surface;
        IMP_order=[];
        k='0';
        $( ".ui-selected", obj2 ).each(function() {
                                       var index = $( "#build_DX_list li" ).index( this );
                                       if ($('#build_DX_list li span')[index].id.match(/DX_POH_(.*)/)) {
                                       surface = 'POH_' + $( "#build_DX_list li span" )[index].id.match(/DX_POH_(.*)/)[1];
                                       IMP_order[k] = surface;
                                       } else if ($( "#build_DX_list li span" )[index].id.match(/DX_PMH_(.*)/)) {
                                       surface = 'PMH_' + $( "#build_DX_list li span" )[index].id.match(/DX_PMH_(.*)/)[1];
                                       IMP_order[k] = surface;
                                       }
                                       k++;
                                       });
    }
    /*
     * This function builds the Impression/Plan area using the object supplied: items
     * It appends "items" into the Impression Plan area, complete with:
     *      contenteditable Titles (the Impression), 
     *      its code (if part of the item object),
     *      Plan textareas (autofilled with the item/object's "comment") 
     * for each member of "items".
     * On storing to the server, duplicates are removed on refresh of the IMPPLAN object.
     */
    function build_IMPPLAN(items) {
        console.log(items);
        var contents_here;
        $('#IMPPLAN_zone').html("");//keep generic headers IMP/PLAN fields//count them into IMPPLAN_items
        if ((typeof items == "undefined")|| (items.length =='0') || (items == null)) {
            items = [];
            $('#IMPPLAN_text').removeClass('nodisplay'); //Display Builder instructions for starting out
            $('#IMPPLAN_zone').addClass('nodisplay'); // no need to display this yet either.
        } else if (items ==null) { //same deal, just not smart enough to combine into one line.
            items = [];
            $('#IMPPLAN_text').removeClass('nodisplay');
            $('#IMPPLAN_zone').addClass('nodisplay');
        } else {
            //ok we have at least one item, display them in order; hide the Builder instructions
            $('#IMPPLAN_text').addClass('nodisplay');
            $('#IMPPLAN_zone').removeClass('nodisplay');
            $.each(items, function( index, value ) {
                   if (!value.codetext) value.codetext="";
                   if (value.code=="") value.code="<i class='fa fa-search-plus'></i>&nbsp;Code";
                   contents_here = ( index + 1 ) +
                        ". <span contenteditable title='Click here to edit this Diagnosis' id='IMPRESSION_"+index+"'>" +
                            value.title +"</span>"+
                        "<span contenteditable class='pull-right' ondblclick='sel_diagnosis("+index+");' title='"+value.codetext+"' id='CODE_"+index+"'>"+
                            value.code + "</span>&nbsp;"+
                        "<br /><textarea id='PLAN_"+index+"' name='PLAN_"+index+
                        "' style='width:100%;max-width:100%;height:auto;min-height:3em;overflow-y: hidden;padding-top: 1.1em; '>"+
                        value.plan +"</textarea><br />";
                   //X top right to delete this if desired.
                   $('#IMPPLAN_zone').append('<div id="IMPPLAN_zone_'+index+'" class="IMPPLAN_class">'+
                                             '<i class="pull-right fa fa-close" id="BUTTON_IMPPLAN_'+index+'"></i>'+
                                             contents_here+'</div>');
                   $('#BUTTON_IMPPLAN_'+index).click(function() {
                                                     //what is this item?
                                                     var item = this.id.match(/BUTTON_IMPPLAN_(.*)/)[1];
                                                     //delete this from the imp/plan list
                                                     IMPPLAN_items.splice(item,1);
                                                     // then rebuild the IMPPLAN
                                                     build_IMPPLAN(IMPPLAN_items);
                                                     store_IMPPLAN(IMPPLAN_items);
                                                     });
                   $('#PLAN_'+index).css("background-color","#F0F8FF");
                   
                   }); //end each
            
                // The IMPRESSION DXs are "contenteditable" spans.
                // If the user changes the words in an IMPRESSION Diagnosis area, store it.
            $('[id^=IMPRESSION_]').blur(function() {
                                        var item = this.id.match(/IMPRESSION_(.*)/)[1];
                                        var content = this.innerText || this.innerHTML;
                                        IMPPLAN_items[item].title = content;
                                        store_IMPPLAN(IMPPLAN_items);
                                        $(this).css('background-color','#F0F8FF');
                                        return false;
                                        });
            $('[id^=CODE_]').blur(function() {
                                  var item = this.id.match(/CODE_(.*)/)[1];
                                  var new_code = this.innerText || this.innerHTML;
                                  IMPPLAN_items[item].code =  new_code;
                                  IMPPLAN_items[item].codetext = '';
                                  IMPPLAN_items[item].codedesc = '';
                                  $(this).css('background-color','#F0F8FF');
                                  /*
                                  if (IMPPLAN_items[item].PMSFH_link > '') {
                                      //we may want to change this code in the PMSFH also, but not yet...
                                      //doing so will affect today's PMSFH code,codetext,description etc
                                      //this may not be desirable as the CODE change will likely
                                      //be due to a change in the PMSFH diagnosis.  So it is NEW
                                      //not OLD, and therefore doesn't belong in PMSFH.
                                      //instead, auto include NEW IMP/PLAN DX CODES today
                                      // as new PMSFH[POH] items on the next visit, 
                                      // cause then they'll be PAST HISTORY.
                                      var findme = IMPPLAN_items[item].PMSFH_link;
                                      //obj.PMSFH[group]....
                                      var group = findme.match(/(.*)_(.*)/)[1];
                                      var location = findme.match(/(.*)_(.*)/)[2];
                                      obj.PMSFH[group][location]['code']= new_code;
                                      obj.PMSFH[group][location]['codedesc'] ='';
                                      obj.PMSFH[group][location]['codetext'] ='';
                                  }
                                   */
                                  store_IMPPLAN(IMPPLAN_items);
                                  
                                  });
            
            $('[id^=PLAN_]').change(function() {
                                    var item = this.id.match(/PLAN_(.*)/)[1];
                                    IMPPLAN_items[item].plan =  $(this).val();
                                    store_IMPPLAN(IMPPLAN_items);
                                    $(this).css('background-color','#F0F8FF');
                                    });
            
            $('#IMPPLAN_zone').on( 'keyup', 'textarea', function (e){
                                  $(this).css('height', 'auto' );
                                  $(this).height( this.scrollHeight );
                                  });
            $('#IMPPLAN_zone').find( 'textarea' ).keyup();
                //   $('[id^=PLAN_]').on("blur keyup change", resize);
            IMPPLAN_items = items;
        }
    }

    /*
     * Function to add a CODE to an IMPRESSION/PLAN item
     * This is for callback by the find-code popup in IMPPLAN area.
     * Appends to or erases the current list of diagnoses.
     */
    function set_related(codetype, code, selector, codedesc) {
                //target is the index of IMPRESSION[index].code we are searching for.
            var span = document.getElementById('CODE_'+IMP_target);
            if ('textContent' in span) {
                span.textContent = code;
            } else {
                span.innerText = code;
            }
            $('#CODE_'+IMP_target).attr('title',codetype + ':' + code + ' ('+codedesc+')');
            IMPPLAN_items[IMP_target].code = code;
            IMPPLAN_items[IMP_target].codetype = codetype;
            IMPPLAN_items[IMP_target].codedesc = codedesc;
            IMPPLAN_items[IMP_target].codetext = codetype + ':' + code + ' ('+codedesc+')';
        alert('test'+IMP_target);
        if (IMPPLAN_items[IMP_target].PMSFH_link > '') {
            var data = IMPPLAN_items[IMP_target].PMSFH_link.match(/(.*)_(.*)/);
            obj.PMSFH[data[1]][data[2]].code= code;
            obj.PMSFH[data[1]][data[2]].codetype = codetype;
            obj.PMSFH[data[1]][data[2]].codedesc = codedesc;
            obj.PMSFH[data[1]][data[2]].description = codedesc;
            obj.PMSFH[data[1]][data[2]].diagnosis = codetype + ':' + code;
            obj.PMSFH[data[1]][data[2]].codetext = codetype + ':' + code + ' ('+codedesc+')';
            build_DX_list(obj);
        }
            store_IMPPLAN(IMPPLAN_items);
    }
        


    /*
     *  This function sends the IMPPLAN_items to the server for storage
     */
    function store_IMPPLAN(storage) {
        if (typeof storage !== "undefined") {
            var url = "../../forms/eye_mag/save.php?mode=update";
            var formData =  JSON.stringify(storage);
            $.ajax({
                   type         : 'POST',
                   url          :  url,
                   dataType     : 'json',
                   data 		: {
                       parameter     : formData,
                       action        : 'store_IMPPLAN',
                       pid           : $('#pid').val(),
                       form_id       : $('#form_id').val(),
                       encounter     : $('#encounter').val(),
                       uniqueID      : $('#uniqueID').val()
                   }
                   }).done(function(result) {
                           if (result == 'Code 400') {
                           code_400(); //the user does not have write privileges!
                           return;
                           }
                           IMPPLAN_items =[];
                           IMPPLAN_items = result;
                           build_IMPPLAN(IMPPLAN_items);
                           });
        }
    }
    /*
     * This function allows the user to drag a DX from the Impression/Plan Builder list directly onto the Impression Plan list.
     * This item is appended to the $('#IMPPLAN_zone').
     */
    function dragto_IMPPLAN_zone(event, ui) {
            //find this item in PMSFH
        var findme = ui.draggable.find("span").attr("id");
        var group = findme.match(/DX_(.*)_(.*)/)[1];
        var location = findme.match(/DX_(.*)_(.*)/)[2];
        if (IMPPLAN_items ==null) IMPPLAN_items = [];
        IMPPLAN_items.push({
                           title        : obj.PMSFH[group][location]['title'],
                           code         : obj.PMSFH[group][location]['code'],
                           codetype     : obj.PMSFH[group][location]['codetype'],
                           codedesc     : obj.PMSFH[group][location]['codedesc'],
                           codetext     : obj.PMSFH[group][location]['codetext'].replace(/(\r\n|\n|\r)/gm,""),
                           PMSFH_link   : obj.PMSFH[group][location]['PMSFH_link'],
                           plan         : obj.PMSFH[group][location]['comments']
                           });
        console.log("IMPPLAN_items = "+obj.PMSFH[group][location]);
            //build_IMPPLAN(IMPPLAN_items);
        store_IMPPLAN(IMPPLAN_items);
    }
    /*
     * This function allows the user to drag a DX from the IMPRESSION list directly into the New Dx field $('#IMP') <-- New Dx textarea
     * The data is appended to the end of the text.
     * It doesn't know what is already there (yet) so numbering if desired must be done manually.
     */
    function dragto_IMPPLAN(event, ui) {
        var findme = ui.draggable.find("span").attr("id");
        var group = findme.match(/DX_(.*)_(.*)/)[1];
        var location = findme.match(/DX_(.*)_(.*)/)[2];
        
        var draggable2 = ui.draggable;
        $('#IMP').val(ui.draggable[0].textContent+"\n"+obj.PMSFH[group][location]['comments']);
    }
/* END Functions related to IMPPLAN Builder */

/*
 * Function to make the form fields inactive or active depending on the form's state (Active vs. READ-ONLY)
 */
function toggle_active_flags(new_state) {
    if (($("#chart_status").val() == "off") || (new_state == "on")) {
            //  we are read-only and we want to go active.
        $("#chart_status").val("on");
        $("#active_flag").html(" Active Chart ");
        $("#active_icon").html("<i class='fa fa-toggle-on'></i>");
        $("#warning").addClass("nodisplay");
        $('input, select, textarea, a').removeAttr('disabled');
        $('input, textarea').removeAttr('readonly');
    } else {
            //else clicking this means we want to go from active to read-only
        $("#chart_status").val("off");
        $("#active_flag").html(" READ-ONLY ");
        $("#active_icon").html("<i class='fa fa-toggle-off'></i>");
        $("#warning").removeClass("nodisplay");
            //we should tell the form fields to be disabled. should already be...
        $('input, select, textarea, a').attr('disabled', 'disabled');
        $('input, textarea').attr('readonly', 'readonly');
            //need to also disable Ductions and Versions, PRIORS, Quicks Picks and Drawing!!! AND IMPPLAN area.
            //Either way a save in READ-ONLY mode fails just returns this pop_up again, without saving...
        this_form_id = $("#form_id").val();
        $("#COPY_SECTION").val("READONLY-"+this_form_id);
    }
}
/*
 * Function to update a form in READ-ONLY mode with any data added by the Active version of this form_id/encounter form
 */
function update_READONLY() {
    var data = {
        'action'      : 'retrieve',
        'copy'        : 'READONLY',
        'zone'        : 'READONLY',
        'copy_to'     : $("#form_id").val(),
        'copy_from'   : $("#form_id").val(),
        'pid'         : $("#pid").val()
    };
        //we are going to update the whole form
        //Imagine you are watching on your browser while the tech adds stuff in another room on another computer.
        //We are not ready to actively chart, just looking to see how far along our staff is...
        //or maybe just looking ahead to see who's next in the next room?
        //Either way, we are looking at a record that at present will be disabled/we cannot change...
        // yet it is updating every 10 seconds if another user is making changes.
        //What else needs updating?  Most is done... Will continue testing...
    $.ajax({
           type 	: 'POST',
           dataType : 'json',
           url      :  "../../forms/eye_mag/save.php?copy=READONLY",
           data 	: data,
           success  : function(result) {
           $.map(result, function(valhere, keyhere) {
                 if ($("#"+keyhere).val() != valhere) {
                 $("#"+keyhere).val(valhere).css("background-color","#CCF");
                 } else if (keyhere.match(/MOTILITY_/)) {
                 // Copy forward ductions and versions visually
                 // Make each blank, and rebuild them
                 $("[name='"+keyhere+"_1']").html('');
                 $("[name='"+keyhere+"_2']").html('');
                 $("[name='"+keyhere+"_3']").html('');
                 $("[name='"+keyhere+"_4']").html('');
                 if (keyhere.match(/(_RS|_LS|_RI|_LI|_RRSO|_RRIO|_RLSO|_RLIO|_LRSO|_LRIO|_LLSO|_LLIO)/)) {
                 // Show a horizontal (minus) tag.
                 // When "/" and "\" fa-icons are available at 45 degrees will need to change for obliques.
                 hash_tag = '<i class="fa fa-minus"></i>';
                 } else { //show vertical tag
                 hash_tag = '<i class="fa fa-minus rotate-left"></i>';
                 }
                 for (index =1; index <= valhere; ++index) {
                 $("#"+keyhere+"_"+index).html(hash_tag);
                 }
                 } else if (keyhere.match(/^(ODVF|OSVF)\d$/)) {
                 if (valhere =='1') {
                 $("#FieldsNormal").prop('checked', false);
                 $("#"+keyhere).prop('checked', true);
                 $("#"+keyhere).val('1');
                 } else {
                 $("#"+keyhere).val('0');
                 $("#"+keyhere).prop('checked', false);
                 }
                 } else if (keyhere.match(/AMSLERO(.)/)) {
                 var sidehere = keyhere.match(/AMSLERO(.)/);
                 if (valhere < '1') valhere ='0';
                 $("#"+keyhere).val(valhere);
                 var srcvalue="AmslerO"+sidehere[1];
                 document.getElementById(srcvalue).src = document.getElementById(srcvalue).src.replace(/\_\d/g,"_"+valhere);
                 $("#AmslerO"+sidehere[1]+"value").text(valhere);
                 } else if (keyhere.match(/VA$/)) {
                 $("#"+keyhere+"_copy").val(valhere);
                 $("#"+keyhere+"_copy_brd").val(valhere);
                 }  else if (keyhere.match(/(alert|oriented|confused|PUPIL_NORMAL)/)) {
                 if (valhere =='1') { $('#'+keyhere).val(valhere).prop('checked', true); } else {  $('#'+keyhere).val(valhere).prop('checked', false);}
                 }
                 })
           //.done(function (){
           //if (zone != "READONLY") { submit_form("eye_mag"); }})
           ;
           }});
        refresh_page();
}
function dopopup(url) {
    top.restoreSession();
    window.open(url, 'clinical', 'width=fullscreen,height=fullscreen,resizable=1,scrollbars=1,directories=0,titlebar=0,toolbar=0,location=0,status=0,menubar=0');
}
function goto_url(url) {
    top.restoreSession();
    window.open(url);
}
function openImage() {
    dlgopen('/openemr/controller.php?document&retrieve&patient_id=3&document_id=10&as_file=false', '_blank', 600, 475);
}
/*
 *  Keyboard shortcut commands.
 */

shortcut.add("Control+T",function() {
             show_TEXT();
             });
shortcut.add("Meta+T",function() {
             show_TEXT();
             });
shortcut.add("Control+D",function() {
             show_DRAW();
             });
shortcut.add("Meta+D",function() {
             show_DRAW();
             });
shortcut.add("Control+P",function() {
             $("#PRIOR_ALL").val($('#form_id').val()).trigger("change");
             });
shortcut.add("Meta+P",function() {
             show_PRIORS();
             $("#PRIOR_ALL").val($('#form_id').val()).trigger("change");
             });
shortcut.add("Control+B",function() {
             show_QP();
             });
shortcut.add("Meta+B",function() {
             show_QP();
             });
shortcut.add("Control+K",function() {
             show_KB();
             });
shortcut.add("Meta+K",function() {
             show_KB();
             });

/* Undo feature
 *  RIGHT NOW THIS WORKS PER FIELD ONLY in FF. In Chrome it works great.  Not sure about IE at all.
 *  In FF, you select a field and CTRL-Z reverses/Shift-Ctrl-Z forwards value
 *  To get true Undo Redo, we will need to create two arrays, one with the command/field, prior value, next value to undo
 *  and when undone, add this to the REDO array.  When a Undo command is followed by anything other than Redo, it erases REDO array.
 *  Ctrl-Z works without this extra code!  Fuzzy on the details for specific browsers so TODO.
 */

$(document).ready(function() {
                 check_lock();
                    // Check the initial Position of the Sticky Header
                  if ($("#PREFS_KB").val() =='1') {
                    $(".kb").removeClass('nodisplay');
                    $(".kb_off").addClass('nodisplay');
                  } else {
                    $(".kb").addClass('nodisplay');
                    $(".kb_off").removeClass('nodisplay');
                  }
                  $("[name$='_kb']").click(function() {
                                         $('.kb').toggleClass('nodisplay');
                                         $('.kb_off').toggleClass('nodisplay');
                                         if ($('#PREFS_EXAM').val() == 'DRAW') {
                                           show_TEXT();
                                         }
                                           
                                         if ($("#PREFS_KB").val() > 0) {
                                            $("#PREFS_KB").val('0');
                                         } else {
                                            $("#PREFS_KB").val('1');
                                         }
                                         update_PREFS();
                                         });
                  $('.ke').mouseover(function() {
                                     $(this).toggleClass('yellow');
                                     });
                  $('.ke').mouseout(function() {
                                    $(this).toggleClass('yellow');
                                    });
                  $("[id$='_keyboard'],[id$='_keyboard_left']").on('keydown', function(e) {
                                                                   if (e.which == 13|| e.keyCode == 13||e.which == 9|| e.keyCode == 9) {
                                                                   e.preventDefault();
                                                                   var data_all = $(this).val();
                                                                   var data_seg = data_all.replace(/^[\s]*/,'').match(/([^;]*)/g);
                                                                   var field2 ='';
                                                                   var appendix =".a";
                                                                   var zone;
                                                                   for (index=0; index < data_seg.length; ++index) {
                                                                       if (data_seg[index] =='') continue;
                                                                       if ((index =='0') && (data_seg[index].match(/^D($|;)/i))) {
                                                                           $("#EXT_defaults").trigger("click");
                                                                           $("#ANTSEG_defaults").trigger("click");
                                                                           $("#RETINA_defaults").trigger("click");
                                                                           $("#NEURO_defaults").trigger("click");
                                                                           continue;
                                                                       }
                                                                       appendix=".a";
                                                                       data_seg[index] = data_seg[index].replace(/^[\s]*/,'');
                                                                       var data = data_seg[index].match(/^(\w*)\.?(.*)/);
                                                                       (data[2].match(/\.a$/))?(data[2] = data[2].replace(/\.a$/,'')):(appendix = "nope");
                                                                       var field = data[1].toUpperCase();
                                                                       var text = data[2];
                                                                       text = expand_vocab(text);
                                                                       priors = process_kb(field,text,appendix,prior_field,prior_text);
                                                                   prior_field = priors['field'];
                                                                   prior_text = priors['prior_text'];
                                                                   
                                                                   }
                                                                   submit_form();
                                                                   $(this).val('');
                                                                  
                                                                   }
                                                                   });
                  $("[id^='sketch_tools_']").click(function() {
                                                   var zone = this.id.match(/sketch_tools_(.*)/)[1];
                                                   $("[id^='sketch_tools_"+zone+"']").css("height","30px");
                                                   $(this).css("height","50px");
                                                   });
                  $("[id^='sketch_sizes_']").click(function() {
                                                   var zone = this.id.match(/sketch_sizes_(.*)/)[1];
                                                   $("[id^='sketch_sizes_"+zone+"']").css("background","").css("border-bottom","");
                                                   $(this).css("border-bottom","2pt solid black");
                                                   });
                  //  Here we get CC1 to show
                  $(".tab_content").addClass('nodisplay');
                  $("#tab1_CC_text").removeClass('nodisplay');
                  $("#tab1_HPI_text").removeClass('nodisplay');
                  $("[id$='_CC'],[id$='_HPI_tab']").click(function() {
                                                          //  First remove class "active" from currently active tabs
                                                          $("[id$='_CC']").removeClass('active');
                                                          $("[id$='_HPI_tab']").removeClass('active');
                                                          //  Hide all tab content
                                                          $(".tab_content").addClass('nodisplay');
                                                          //  Here we get the href value of the selected tab
                                                          var selected_tab = $(this).find("a").attr("href");
                                                          //  Now add class "active" to the selected/clicked tab and content
                                                          $(selected_tab+"_CC").addClass('active');
                                                          $(selected_tab+"_CC_text").removeClass('nodisplay');
                                                          $(selected_tab+"_HPI_tab").addClass('active');
                                                          $(selected_tab+"_HPI_text").removeClass('nodisplay');
                                                          //  At the end, we add return false so that the click on the link is not executed
                                                          return false;
                                                          });
                  $("[id^='CONSTRUCTION_']").toggleClass('nodisplay');
                  $("input,textarea,text").css("background-color","#FFF8DC");
                  $("#IOPTIME").css("background-color","#FFFFFF");
                  $("#refraction_width").css("width","8.5in");
                  $(".Draw_class").addClass('nodisplay');
                  $(".PRIORS_class").addClass('nodisplay');
                  hide_DRAW();
                  hide_right();
                  $(window).resize(function() {
                                   if (window.innerWidth >'900') {
                                   $("#refraction_width").css("width","900px");
                                   $("#LayerVision2").css("padding","4px");
                                   }
                                   if (window.innerWidth >'1300') {
                                   $("#refraction_width").css("width","1300px");
                                   //$("#first").css("width","1300px");
                                   }
                                   if (window.innerWidth >'1900') {
                                   $("#refraction_width").css("width","1600px");
                                   }
                                   
                                   });
                  $(window).resize();
                  
                  var hash_tag = '<i class="fa fa-minus"></i>';
                  var index;
                  // display any stored MOTILITY values
                  $("#MOTILITY_RS").value = parseInt($("#MOTILITY_RS").val());
                  if ($("#MOTILITY_RS").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RS").val()); ++index) {
                  $("#MOTILITY_RS_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_RI").value = parseInt($("#MOTILITY_RI").val());
                  if ($("#MOTILITY_RI").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RI").val()); ++index) {
                  $("#MOTILITY_RI_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_LS").value = parseInt($("#MOTILITY_LS").val());
                  if ($("#MOTILITY_LS").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LS").val()); ++index) {
                  $("#MOTILITY_LS_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_LI").value = parseInt($("#MOTILITY_LI").val());
                  if ($("#MOTILITY_LI").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LI").val()); ++index) {
                  $("#MOTILITY_LI_"+index).html(hash_tag);
                  }
                  }
                  
                  $("#MOTILITY_RRSO").value = parseInt($("#MOTILITY_RRSO").val());
                  if ($("#MOTILITY_RRSO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RRSO").val()); ++index) {
                  $("#MOTILITY_RRSO_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_RRIO").value = parseInt($("#MOTILITY_RRIO").val());
                  if ($("#MOTILITY_RRIO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RRIO").val()); ++index) {
                  $("#MOTILITY_RRIO_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_RLIO").value = parseInt($("#MOTILITY_RLIO").val());
                  if ($("#MOTILITY_RLIO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RLIO").val()); ++index) {
                  $("#MOTILITY_RLIO_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_RLSO").value = parseInt($("#MOTILITY_RLSO").val());
                  if ($("#MOTILITY_RLSO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RLSO").val()); ++index) {
                  $("#MOTILITY_RLSO_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_LRSO").value = parseInt($("#MOTILITY_LRSO").val());
                  if ($("#MOTILITY_LRSO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LRSO").val()); ++index) {
                  $("#MOTILITY_LRSO_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_LRIO").value = parseInt($("#MOTILITY_LRIO").val());
                  if ($("#MOTILITY_LRIO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LRIO").val()); ++index) {
                  $("#MOTILITY_LRIO_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_LLSO").value = parseInt($("#MOTILITY_LLSO").val());
                  if ($("#MOTILITY_LLSO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LLSO").val()); ++index) {
                  $("#MOTILITY_LLSO_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_LLIO").value = parseInt($("#MOTILITY_LLIO").val());
                  if ($("#MOTILITY_LLIO").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LLIO").val()); ++index) {
                  $("#MOTILITY_LLIO_"+index).html(hash_tag);
                  }
                  }
                 
                  var hash_tag = '<i class="fa fa-minus rotate-left"></i>';
                  $("#MOTILITY_LR").value = parseInt($("#MOTILITY_LR").val());
                  if ($("#MOTILITY_LR").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LR").val()); ++index) {
                  $("#MOTILITY_LR_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_LL").value = parseInt($("#MOTILITY_LL").val());
                  if ($("#MOTILITY_LL").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_LL").val()); ++index) {
                  $("#MOTILITY_LL_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_RR").value = parseInt($("#MOTILITY_RR").val());
                  if ($("#MOTILITY_RR").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RR").val()); ++index) {
                  $("#MOTILITY_RR_"+index).html(hash_tag);
                  }
                  }
                  $("#MOTILITY_RL").value = parseInt($("#MOTILITY_RL").val());
                  if ($("#MOTILITY_RL").val() > '0') {
                  $("#MOTILITYNORMAL").removeAttr('checked');
                  for (index =1; index <= ($("#MOTILITY_RL").val()); ++index) {
                  $("#MOTILITY_RL_"+index).html(hash_tag);
                  }
                  }
                  
                  // AUTO- CODING FEATURES
                  // onload determine if detailed HPI hit
                  check_CPT_92060();
                  check_exam_detail();
                  
                  $(".chronic_HPI,.count_HPI").blur(function() {
                                                    check_exam_detail();
                                                    });
                  
                  // Dilation status
                  //onload and onchange
                  $("#DIL_RISKS").change(function(o) {
                                         ($(this).is(':checked')) ? ($(".DIL_RISKS").removeClass("nodisplay")) : ($(".DIL_RISKS").addClass("nodisplay"));
                                         });
                  $(".dil_drug").change(function(o) {
                                        if ($(this).is(':checked')) {
                                        ($(".DIL_RISKS").removeClass("nodisplay"));
                                        $("#DIL_RISKS").prop("checked","checked");
                                        
                                        }});
                  
                  //neurosens exam = stereopsis + strab||NPC||NPA||etc
                  $(".neurosens,.neurosens2").blur(function() {
                                                   var neuro1='';
                                                   var neuro2 ='';
                                                   if ($("#STEREOPSIS").val() > '') (neuro1="1");
                                                   $(".neurosens2").each(function(index) {
                                                                         if ($( this ).val() > '') {
                                                                         neuro2="1";
                                                                         }
                                                                         });
                                                   if (neuro1 && neuro2){
                                                   $("#neurosens_code").removeClass('nodisplay');
                                                   } else {
                                                   $("#neurosens_code").addClass('nodisplay');
                                                   }
                                                   });
                  // END AUTO-CODING FEATURES
                  
                  //  functions to improve flow of refraction input
                  $("input[name$='PRISM']").blur(function() {
                                                 //make it all caps
                                                 var str = $(this).val();
                                                 str = str.toUpperCase();
                                                 $(this).val(str);
                                                 });
                  $("input[name$='SPH']").blur(function() {
                                               var mid = $(this).val();
                                               if (mid.match(/^[\+\-]?\d{1}$/)) {
                                               mid = mid+".00";
                                               }
                                               if (mid.match(/\.\d$/)) {
                                               mid = mid + '0';
                                               }
                                               //if near is +2. make it +2.00
                                               if (mid.match(/\.$/)) {
                                               mid= mid + '00';
                                               }
                                               if ((!mid.match(/\./))&&(mid.match(00|25|50|75))) {
                                               var front = mid.match(/(\d{0,2})(00|25|50|75)/)[1];
                                               var back = mid.match(/(\d{0,2})(00|25|50|75)/)[2];
                                               if (front =='') front ='0';
                                               mid = front + "." + back;
                                               }
                                               if (!mid.match(/\./)) {
                                               var front = mid.match(/([\+\-]?\d{0,2})(\d{2})/)[1];
                                               var back  = mid.match(/(\d{0,2})(\d{2})/)[2];
                                               if (front =='') front ='0';
                                               if (front =='-') front ='-0';
                                               mid = front + "." + back;
                                               }
                                               if (!mid.match(/^(\+|\-){1}/)) {
                                               mid = "+" + mid;
                                               }
                                               $(this).val(mid);
                                               submit_form($('#WOSADD1'));
                                               });
                  
                  $("input[name$='ADD'],#WODADD1,#WODADD2,#WOSADD1,#WOSADD2").blur(function() {
                                                                                   var add = $(this).val();
                                                                                   add = add.replace(/=/g,"+");
                                                                                   //if add is one digit, eg. 2, make it +2.00
                                                                                   if (add.match(/^\d{1}$/)) {
                                                                                   add = "+"+add+".00";
                                                                                   }
                                                                                   //if add is '+'one digit, eg. +2, make it +2.00
                                                                                   if (add.match(/^\+\d{1}$/)) {
                                                                                   add = add+".00";
                                                                                   }
                                                                                   //if add is 2.5 or 2.0 make it 2.50 or 2.00
                                                                                   if (add.match(/\.[05]$/)) {
                                                                                   add = add + '0';
                                                                                   }
                                                                                   //if add is 2.2 or 2.2 make it 2.25 or 2.75
                                                                                   if (add.match(/\.[27]$/)) {
                                                                                   add = add + '5';
                                                                                   }
                                                                                   //if add is +2. make it +2.00
                                                                                   if (add.match(/\.$/)) {
                                                                                   add = add + '00';
                                                                                   }
                                                                                   if ((!add.match(/\./))&&(add.match(/(0|25|50|75)$/))) {
                                                                                   var front = add.match(/([\+]?\d{0,1})(00|25|50|75)/)[1];
                                                                                   var back  = add.match(/([\+]?\d{0,1})(00|25|50|75)/)[2];
                                                                                   if (front =='') front ='0';
                                                                                   add = front + "." + back;
                                                                                   }
                                                                                   if (!add.match(/^(\+)/) && (add.length >  0)) {
                                                                                   add= "+" + add;
                                                                                   }
                                                                                   $(this).val(add);
                                                                                   if (this.id=="WODADD1") $('#WOSADD1').val(add);
                                                                                   if (this.id=="WODADD2") $('#WOSADD2').val(add);
                                                                                   if (this.id=="MRODADD") $('#MROSADD').val(add);
                                                                                   if (this.id=="ARODADD") $('#AROSADD').val(add);
                                                                                   if (this.id=="CTLODADD") $('#CTLOSADD').val(add);
                                                                                   submit_form();
                                                                                   });
                  
                  $("input[name$='AXIS']").blur(function() {
                                                //hmmn.  Make this a 3 digit leading zeros number.
                                                // we are not translating text to numbers, just numbers to
                                                // a 3 digit format with leading zeroes as needed.
                                                // assume the end user KNOWS there are only numbers presented and
                                                // more than 3 digits is a mistake...
                                                // (although this may change with topographic answer)
                                                var axis = $(this).val();
                                                // if (!axis.match(/\d/)) return; How do we say this?
                                                var front = this.id.match(/(.*)AXIS$/)[1];
                                                var cyl = $("#"+front+"CYL").val();
                                                if ((cyl !== '') && (cyl != 'SPH')) {
                                                    if (!axis.match(/\d\d\d/)) {
                                                        if (!axis.match(/\d\d/)) {
                                                            if (!axis.match(/\d/)) {
                                                                axis = '0';
                                                            }
                                                            axis = '0' + axis;
                                                        }
                                                        axis = '0' + axis;
                                                    }
                                                } else {
                                                    axis = '';
                                                }
                                                //we can utilize a phoropter dial feature, we can start them at their age appropriate with/against the rule value.
                                                //requires touch screen. requires complete touch interface development. Exists in refraction lanes. Would
                                                //be nice to tie them all together.  Would require manufacturers to publish their APIs to communicate with
                                                //the devices.
                                                $(this).val(axis);
                                                submit_form('eye_mag');
                                                });
                  $("input[name$='CYL']").blur(function() {
                                               var mid = $(this).val();
                                               var group = this.name.match(/(.*)CYL/)[1];
                                               var sphere = $("#"+group+"SPH").val();
                                               if ((mid.length == 0) && (sphere.length >  0)) {
                                                   $(this).val('SPH');
                                                   submit_form($(this));
                                               } else {
                                                   if (mid.match(/^[\+\-]?\d{1}$/)) {
                                                        mid = mid+".00";
                                                   }
                                                   if (mid.match(/\.\d$/)) {
                                                        mid = mid + '0';
                                                   }
                                                   if (mid.match(/([\+\-]?\d{0,2})\.?(00|25|50|75)/)) {
                                                        var front = mid.match(/([\+\-]?\d{0,2})\.?(00|25|50|75)/)[1];
                                                        var back  = mid.match(/([\+\-]?\d{0,2})\.?(00|25|50|75)/)[2];
                                                        if (front =='') front ='0';
                                                        mid = front + "." + back;
                                                   }
                                                   //if mid is -2.5 make it -2.50
                                                   $(this).val(mid);
                                                   if (!$('#PREFS_CYL').val()) {
                                                       $('#PREFS_CYL').val('+');
                                                       update_PREFS();
                                                   }
                                                   if (!mid.match(/^(\+|\-){1}/) && (sphere.length >  0)) {
                                                       //no +/- sign at the start of the field.
                                                       //ok so there is a preference set
                                                       //Since it doesn't start with + or - then give it the preference value
                                                       var plusminus = $('#PREFS_CYL').val() + mid;
                                                       $(this).val(plusminus);  //set this cyl value to plus or minus
                                                   } else if (mid.match(/^(\+|\-){1}/)) {
                                                       pref = mid.match(/^(\+|\-){1}/)[0];
                                                       //so they used a value + or - at the start of the field.
                                                       //The only reason to work on this is to change to cylinder preference
                                                       if ($('#PREFS_CYL').val() != pref){
                                                            //and that is what they are doing here
                                                            $('#PREFS_CYL').val(pref);
                                                            update_PREFS();
                                                       }
                                                   }
                                                   submit_form($(this));
                                               }
                                               });
                   //bootstrap menu functions
                  $("[class='dropdown-toggle']").hover(function(){
                                                       $("[class='dropdown-toggle']").parent().removeClass('open');
                                                       var menuitem = this.id.match(/(.*)/)[1];
                                                       //if the menu is active through a prior click, show it
                                                       // Have to override Bootstrap then
                                                       if ($("#menustate").val() !="1") { //menu not active -> ignore
                                                       $("#"+menuitem).css("background-color", "#C9DBF2");
                                                       $("#"+menuitem).css("color","#000"); /*#262626;*/
                                                       } else { //menu is active -> respond
                                                       $("#"+menuitem).css("background-color", "#1C5ECF");
                                                       $("#"+menuitem).css("color","#fff"); /*#262626;*/
                                                       $("#"+menuitem).css("text-decoration","none");
                                                       $("#"+menuitem).parent().addClass('open');
                                                       }
                                                       },function() {
                                                       var menuitem = this.id.match(/(.*)/)[1];
                                                       $("#"+menuitem).css("color","#000"); /*#262626;*/
                                                       $("#"+menuitem).css("background-color", "#C9DBF2");
                                                       }
                                                       );
                  $("[class='dropdown-toggle']").click(function() {
                                                       $("#menustate").val('1');
                                                       var menuitem = this.id.match(/(.*)/)[1];
                                                       $("#"+menuitem).css("background-color", "#1C5ECF");
                                                       $("#"+menuitem).css("color","#fff"); /*#262626;*/
                                                       $("#"+menuitem).css("text-decoration","none");
                                                       });
                  $("#right-panel-link, #close-panel-bt,#right-panel-link_2").click(function() {
                                                                if ($("#PREFS_PANEL_RIGHT").val() =='1') {
                                                                $("#PREFS_PANEL_RIGHT").val('0');
                                                                } else {
                                                                $("#PREFS_PANEL_RIGHT").val('1');
                                                                }
                                                                update_PREFS();
                                                                });
                  $("[name^='menu_']").click(function() {
                                             $("[name^='menu_']").removeClass('active');
                                             var menuitem = this.id.match(/menu_(.*)/)[1];
                                             $(this).addClass('active');
                                             $("#menustate").val('1');
                                             menu_select(menuitem);
                                             });
                  // set display functions for Draw panel appearance
                  // for each DRAW area, if the value AREA_DRAW = 1, show it.
                  var zones = ["PMH","HPI","EXT","ANTSEG","RETINA","NEURO","IMPPLAN"];
                  for (index = '0'; index < zones.length; ++index) {
                  if ($("#PREFS_"+zones[index]+"_RIGHT").val() =='DRAW') {
                  show_DRAW_section(zones[index]);
                  } else if ($("#PREFS_"+zones[index]+"_RIGHT").val() =='QP') {
                  show_QP_section(zones[index]);
                  }
                  }
                  $("body").on("click","[name$='_text_view']" , function() {
                               var header = this.id.match(/(.*)_text_view$/)[1];
                               $("#"+header+"_text_list").toggleClass('wide_textarea');
                               $("#"+header+"_text_list").toggleClass('narrow_textarea');
                               $(this).toggleClass('fa-plus-square-o');
                               $(this).toggleClass('fa-minus-square-o');
                               if (header != /PRIOR/) {
                               var imagine = $("#PREFS_"+header+"_VIEW").val();
                               imagine ^= true;
                               $("#PREFS_"+header+"_VIEW").val(imagine);
                               update_PREFS();
                               }
                               return false;
                               });
                  $("body").on("change", "select", function(e){
                               if (this.name.match(/PRIOR_(.*)/)) {
                                   var new_section = this.name.match(/PRIOR_(.*)/);
                                   if (new_section[1] =='') return;
                                   if (new_section[1] == /\_/){
                                    return;
                                   }
                               var newValue = this.value;
                               //now go get the prior page via ajax
                               var newValue = this.value;
                               $("#PRIORS_"+ new_section[1] +"_left_text").removeClass('nodisplay');
                               $("#DRAWS_" + new_section[1] + "_right").addClass('nodisplay');
                               $("#QP_" + new_section[1]).addClass('nodisplay');
                               
                               if (new_section[1] =="ALL") {
                               show_PRIORS();
                               show_PRIORS_section("ALL",newValue);
                               show_PRIORS_section("EXT",newValue);
                               show_PRIORS_section("ANTSEG",newValue);
                               show_PRIORS_section("RETINA",newValue);
                               show_PRIORS_section("NEURO",newValue);
                               show_PRIORS_section("IMPPLAN",newValue);
                               // $(document).scrollTop( $("#EXT_anchor").offset().top -55);
                               } else {
                               show_PRIORS_section(new_section[1],newValue);
                               }
                               } else {
                               submit_form();
                               }
                               });
                  $("body").on("click","[id^='Close_PRIORS_']", function() {
                               var new_section = this.id.match(/Close_PRIORS_(.*)$/)[1];
                               $("#PRIORS_"+ new_section +"_left_text").addClass('nodisplay');
                               $("#QP_" + new_section).removeClass('nodisplay');
                               });
                  $("#pupils,#vision_tab,[name='CTL'],[name^='more_'],#ACTTRIGGER").mouseover(function() {
                                         $(this).toggleClass('buttonRefraction_selected').toggleClass('underline').css( 'cursor', 'pointer' );
                                         });
                  $("#pupils,#vision_tab,[name='CTL']").mouseout(function() {
                                        $(this).toggleClass('buttonRefraction_selected').toggleClass('underline');
                                        });
                  $("#pupils").click(function(){
                                     $("#dim_pupils_panel").toggleClass('nodisplay');
                                     });
                  $("#vision_tab").click(function(){
                                         $("#LayerVision2").toggle();
                                         ($("#PREFS_VA").val() =='1') ? ($("#PREFS_VA").val('0')) : $("#PREFS_VA").val('1');
                                         });
                   //set wearing to single vision or bifocal? Bifocal
                  $(".WNEAR").removeClass('nodisplay');
                  $("#WNEARODAXIS").addClass('nodisplay');
                  $("#WNEARODCYL").addClass('nodisplay');
                  $("#WNEARODPRISM").addClass('nodisplay');
                  $("#WNEAROSAXIS").addClass('nodisplay');
                  $("#WNEAROSCYL").addClass('nodisplay');
                  $("#WNEAROSPRISM").addClass('nodisplay');
                  $("#Single").click(function(){
                                     $("#WNEARODAXIS").addClass('nodisplay');
                                     $("#WNEARODCYL").addClass('nodisplay');
                                     $("#WNEARODPRISM").addClass('nodisplay');
                                     $("#WODADD2").addClass('nodisplay');
                                     $("#WOSADD2").addClass('nodisplay');
                                     $("#WNEAROSAXIS").addClass('nodisplay');
                                     $("#WNEAROSCYL").addClass('nodisplay');
                                     $("#WNEAROSPRISM").addClass('nodisplay');
                                     $(".WSPACER").removeClass('nodisplay');
                                     });
                  $("#Bifocal").click(function(){
                                      $(".WSPACER").addClass('nodisplay');
                                      $(".WNEAR").removeClass('nodisplay');
                                      $(".WMid").addClass('nodisplay');
                                      $(".WHIDECYL").removeClass('nodisplay');
                                      $("[name=RX]").val(["1"]);
                                      $("#WNEARODAXIS").addClass('nodisplay');
                                      $("#WNEARODCYL").addClass('nodisplay');
                                      $("#WNEARODPRISM").addClass('nodisplay');
                                      $("#WNEAROSAXIS").addClass('nodisplay');
                                      $("#WNEAROSCYL").addClass('nodisplay');
                                      $("#WNEAROSPRISM").addClass('nodisplay');
                                      $("#WODADD2").removeClass('nodisplay');
                                      $("#WOSADD2").removeClass('nodisplay');
                                      });
                  $("#Trifocal").click(function(){
                                       $(".WSPACER").addClass('nodisplay');
                                       $(".WNEAR").removeClass('nodisplay');
                                       $(".WMid").removeClass('nodisplay');
                                       $(".WHIDECYL").addClass('nodisplay');
                                       $("[name=RX]").val(["2"]);
                                       $("#WNEARODAXIS").addClass('nodisplay');
                                       $("#WNEARODCYL").addClass('nodisplay');
                                       $("#WNEARODPRISM").addClass('nodisplay');
                                       $("#WNEAROSAXIS").addClass('nodisplay');
                                       $("#WNEAROSCYL").addClass('nodisplay');
                                       $("#WNEAROSPRISM").addClass('nodisplay');
                                       $("#WODADD2").removeClass('nodisplay');
                                       $("#WOSADD2").removeClass('nodisplay');
                                       });
                  $("#Progressive").click(function(){
                                          $(".WSPACER").addClass('nodisplay');
                                          $(".WNEAR").removeClass('nodisplay');
                                          $(".WMid").addClass('nodisplay');
                                          $(".WHIDECYL").removeClass('nodisplay');
                                          $("[name=RX]").val(["3"]);
                                          $("#WNEARODAXIS").addClass('nodisplay');
                                          $("#WNEARODCYL").addClass('nodisplay');
                                          $("#WNEARODPRISM").addClass('nodisplay');
                                          $("#WNEAROSAXIS").addClass('nodisplay');
                                          $("#WNEAROSCYL").addClass('nodisplay');
                                          $("#WNEAROSPRISM").addClass('nodisplay');
                                          $("#WODADD2").removeClass('nodisplay');
                                          $("#WOSADD2").removeClass('nodisplay');
                                          });
                  $("#Amsler-Normal").change(function() {
                                             if ($(this).is(':checked')) {
                                             var number1 = document.getElementById("AmslerOD").src.match(/(Amsler_\d)/)[1];
                                             document.getElementById("AmslerOD").src = document.getElementById("AmslerOD").src.replace(number1,"Amsler_0");
                                             var number2 = document.getElementById("AmslerOS").src.match(/(Amsler_\d)/)[1];
                                             document.getElementById("AmslerOS").src = document.getElementById("AmslerOS").src.replace(number2,"Amsler_0");
                                             $("#AMSLEROD").val("0");
                                             $("#AMSLEROS").val("0");
                                             $("#AmslerODvalue").text("0");
                                             $("#AmslerOSvalue").text("0");
                                             submit_form("eye_mag");
                                             return;
                                             }
                                             });
                  $("#PUPIL_NORMAL").change(function() {
                                            if ($(this).is(':checked')) {
                                            $("#ODPUPILSIZE1").val('3.0');
                                            $("#OSPUPILSIZE1").val('3.0');
                                            $("#ODPUPILSIZE2").val('2.0');
                                            $("#OSPUPILSIZE2").val('2.0');
                                            $("#ODPUPILREACTIVITY").val('+2');
                                            $("#OSPUPILREACTIVITY").val('+2');
                                            $("#ODAPD").val('0');
                                            $("#OSAPD").val('0');
                                            submit_form("eye_mag");
                                            return;
                                            }
                                            });
                  $("[name$='PUPILREACTIVITY']").change(function() {
                                                        var react = $(this).val();
                                                        if (react.match(/^\d{1}$/)) {
                                                        react = "+"+react;
                                                        }
                                                        $(this).val(react);
                                                        });
                  
                  $("[name^='EXAM']").mouseover(function(){
                                                $(this).toggleClass("borderShadow2").css( 'cursor', 'pointer' );
                                                });
                  $("[name^='EXAM']").mouseout(function(){
                                               $(this).toggleClass("borderShadow2");
                                               });
                  $("#AmslerOD, #AmslerOS").click(function() {
                                                  if ($('#chart_status').val() !="on") return;
                                                  var number1 = this.src.match(/Amsler_(\d)/)[1];
                                                  var number2 = +number1 +1;
                                                  this.src = this.src.replace('Amsler_'+number1,'Amsler_'+number2);
                                                  this.src = this.src.replace('Amsler_6','Amsler_0');
                                                  $("#Amsler-Normal").removeAttr('checked');
                                                  var number3 = this.src.match(/Amsler_(\d)/)[1];
                                                  this.html =  number3;
                                                  if (number3 =="6") {
                                                  number3 = "0";
                                                  }
                                                  if ($(this).attr("id")=="AmslerOD") {
                                                  $("#AmslerODvalue").text(number3);
                                                  $('#AMSLEROD').val(number3);
                                                  } else {
                                                  $('#AMSLEROS').val(number3);
                                                  $("#AmslerOSvalue").text(number3);
                                                  }
                                                  var title = "#"+$(this).attr("id")+"_tag";
                                                  });
                  
                  $("#AmslerOD, #AmslerOS").mouseout(function() {
                                                     submit_form("eye_mag");
                                                     });
                  $("[name^='ODVF'],[name^='OSVF']").click(function() {
                                                           if ($(this).is(':checked') == true) {
                                                           $("#FieldsNormal").prop('checked', false);
                                                           $(this).val('1');
                                                           }else{
                                                           $(this).val('0');
                                                           $(this).prop('checked', false);
                                                           }
                                                           submit_form("eye_mag");
                                                           });
                  $("#FieldsNormal").click(function() {
                                           if ($(this).is(':checked')) {
                                           $("#ODVF1").removeAttr('checked');
                                           $("#ODVF2").removeAttr('checked');
                                           $("#ODVF3").removeAttr('checked');
                                           $("#ODVF4").removeAttr('checked');
                                           $("#OSVF1").removeAttr('checked');
                                           $("#OSVF2").removeAttr('checked');
                                           $("#OSVF3").removeAttr('checked');
                                           $("#OSVF4").removeAttr('checked');
                                           }
                                           });
                  $("[id^='EXT_prefix']").change(function() {
                                                 var newValue =$('#EXT_prefix').val();
                                                 newValue = newValue.replace('+', '');
                                                 if (newValue =="off") {$(this).val('');}
                                                 $("[name^='EXT_prefix_']").removeClass('eye_button_selected');
                                                 $("#EXT_prefix_"+ newValue).addClass("eye_button_selected");
                                                 });
                  $("#ANTSEG_prefix").change(function() {
                                             var newValue = $(this).val().replace('+', '');
                                             if ($(this).value =="off") {$(this).val('');}
                                             $("[name^='ANTSEG_prefix_']").removeClass('eye_button_selected');
                                             $("#ANTSEG_prefix_"+ newValue).addClass("eye_button_selected");
                                             });
                  $("#RETINA_prefix").change(function() {
                                             var newValue = $("#RETINA_prefix").val().replace('+', '');
                                             if ($(this).value =="off") {$(this).val('');}
                                             $("[name^='RETINA_prefix_']").removeClass('eye_button_selected');
                                             $("#RETINA_prefix_"+ newValue).addClass("eye_button_selected");
                                             });
                  $("#NEURO_ACT_zone").change(function() {
                                              var newValue = $(this).val();
                                              $("[name^='NEURO_ACT_zone']").removeClass('eye_button_selected');
                                              $("#NEURO_ACT_zone_"+ newValue).addClass("eye_button_selected");
                                              $("#PREFS_ACT_SHOW").val(newValue);
                                              update_PREFS;
                                              $("#ACT_tab_"+newValue).trigger('click');
                                              });
                  $("#NEURO_side").change(function() {
                                          var newValue = $(this).val();
                                          $("[name^='NEURO_side']").removeClass('eye_button_selected');
                                          $("#NEURO_side_"+ newValue).addClass("eye_button_selected");
                                          });
                  $('.ACT').focus(function() {
                                  var id = this.id.match(/ACT(\d*)/);
                                  $('#NEURO_field').val(''+id[1]).trigger('change');
                                  });
                  $("#NEURO_field").change(function() {
                                           var newValue = $(this).val();
                                           $("[name^='NEURO_field']").removeClass('eye_button_selected');
                                           $("#NEURO_field_"+ newValue).addClass("eye_button_selected");
                                           $('.ACT').each(function(i){
                                                          var color = $(this).css('background-color');
                                                          if ((color == 'rgb(255, 255, 153)')) {// =='blue' <- IE hack
                                                          $(this).css("background-color","red");
                                                          }
                                                          });
                                           //change to highlight field in zone entry is for
                                           var zone = $("#NEURO_ACT_zone").val();
                                           $("#ACT"+newValue+zone).css("background-color","yellow");
                                           });
                  $("[name^='NEURO_ACT_strab']").click(function() {
                                                       var newValue = $(this).val();
                                                       $("[name^='NEURO_ACT_strab']").removeClass('eye_button_selected');
                                                       $(this).addClass("eye_button_selected");
                                                       });
                  $("#NEURO_value").change(function() {
                                           var newValue = $(this).val();
                                           $("[name^='NEURO_value']").removeClass('eye_button_selected');
                                           $("#NEURO_value_"+ newValue).addClass("eye_button_selected");
                                           if (newValue == "ortho") {
                                           $("#NEURO_ACT_strab").val('');
                                           $("[name^='NEURO_ACT_strab']").removeClass('eye_button_selected');
                                           $("#NEURO_side").val('');
                                           $("[name^='NEURO_side']").removeClass('eye_button_selected');
                                           }
                                           });
                  $("#NEURO_RECORD").mouseover(function() {
                                               $("#NEURO_RECORD").addClass('borderShadow2').css( 'cursor', 'pointer' );
                                               });
                  $("#NEURO_RECORD").mouseout(function() {
                                              $("#NEURO_RECORD").removeClass('borderShadow2');
                                              });
                  $("#NEURO_RECORD").mousedown(function() {
                                               $("#NEURO_RECORD").removeClass('borderShadow2');
                                               $(this).toggleClass('button_over');
                                               });
                  $("#NEURO_RECORD").mouseup(function() {
                                             $("#NEURO_RECORD").removeClass('borderShadow2');
                                             $(this).toggleClass('button_over');
                                             });
                  $("#NEURO_RECORD").click(function() {
                                           //find out the field we are updating
                                           var number = $("#NEURO_field").val();
                                           var zone = $("#NEURO_ACT_zone").val();
                                           var strab = $("#NEURO_value").val() + ' '+ $("#NEURO_side").val() + $("#NEURO_ACT_strab").val();
                                           
                                           $("#ACT"+number+zone).val(strab).css("background-color","#F0F8FF");
                                           
                                           
                                           });
    
                  $("#LayerMood,#LayerVision, #LayerTension, #LayerMotility, #LayerAmsler, #LayerFields, #LayerPupils,#dim_pupils_panel,#PRIORS_ALL_left_text").mouseover(function(){
                                                                                                                                                                          $(this).toggleClass("borderShadow2");
                                                                                                                                                                          });
                  $("#LayerMood,#LayerVision, #LayerTension, #LayerMotility, #LayerAmsler, #LayerFields, #LayerPupils,#dim_pupils_panel,#PRIORS_ALL_left_text").mouseout(function(){
                                                                                                                                                                         $(this).toggleClass("borderShadow2");
                                                                                                                                                                         });
                  $("[id^=LayerVision_]").mouseover(function(){
                                                    //  $(this).toggleClass("borderShadow2");
                                                    });
                  $("[id^=LayerVision_]").mouseout(function(){
                                                   //   $(this).toggleClass("borderShadow2");
                                                   });
                  $("#LayerVision_W_lightswitch, #LayerVision_CR_lightswitch,#LayerVision_MR_lightswitch,#LayerVision_ADDITIONAL_lightswitch,#LayerVision_CTL_lightswitch,#LayerVision_VAX_lightswitch,#LayerVision_IOP_lightswitch").click(function() {
                                                                                                                                                                                                                                            var section = "#"+this.id.match(/(.*)_lightswitch$/)[1];
                                                                                                                                                                                                                                            var section2 = this.id.match(/(.*)_(.*)_lightswitch$/)[2];
                                                                                                                                                                                                                                            var elem = document.getElementById("PREFS_"+section2);
                                                                                                                                                                                                                                            if ($("#PREFS_VA").val() !='1') {
                                                                                                                                                                                                                                            $("#PREFS_VA").val('1');
                                                                                                                                                                                                                                            $("#LayerVision2").removeClass('nodisplay');
                                                                                                                                                                                                                                            elem.value="1";
                                                                                                                                                                                                                                            $(section).removeClass('nodisplay');
                                                                                                                                                                                                                                            if (section2 =="ADDITIONAL") {
                                                                                                                                                                                                                                            $("#LayerVision_ADDITIONAL").removeClass('nodisplay');
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            if (section2 =="VAX") {
                                                                                                                                                                                                                                            $("#LayerVision_ADDITIONAL_VISION").removeClass('nodisplay');
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            if (section2 =="IOP") {
                                                                                                                                                                                                                                            $("#LayerVision_IOP").removeClass('nodisplay');
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            $(this).addClass("buttonRefraction_selected");
                                                                                                                                                                                                                                            } else {
                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                            if (elem.value == "0" || elem.value =='') {
                                                                                                                                                                                                                                            elem.value='1';
                                                                                                                                                                                                                                            if (section2 =="ADDITIONAL") {
                                                                                                                                                                                                                                            $("#LayerVision_ADDITIONAL").removeClass('nodisplay');
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            if (section2 =="IOP") {
                                                                                                                                                                                                                                            $("#LayerVision_IOP").removeClass('nodisplay');
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            $(section).removeClass('nodisplay');
                                                                                                                                                                                                                                            $(this).addClass("buttonRefraction_selected");
                                                                                                                                                                                                                                            } else {
                                                                                                                                                                                                                                            elem.value='0';
                                                                                                                                                                                                                                            $(section).addClass('nodisplay');
                                                                                                                                                                                                                                            if (section2 =="VAX") {
                                                                                                                                                                                                                                            $("#LayerVision_ADDITIONAL_VISION").addClass('nodisplay');
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            if (section2 =="IOP") {
                                                                                                                                                                                                                                            $("#LayerVision_IOP").addClass('nodisplay');
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            $(this).removeClass("buttonRefraction_selected");
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                            $(this).css( 'cursor', 'pointer' );                                                                                                                                         update_PREFS();
                                                                                                                                                                                                                                            //$("#tab1").removeClass('nodisplay');
                                                                                                                                                                                                                                            });
                  
                  $('[id$=_lightswitch]').mouseover(function() {
                                                    $(this).addClass('buttonRefraction_selected').css( 'cursor', 'pointer' );
                                                    });
                  $('[id$=_lightswitch]').mouseout(function() {
                                                   var section2 = this.id.match(/(.*)_(.*)_lightswitch$/)[2];
                                                   var elem = document.getElementById("PREFS_"+section2);
                                                   
                                                   if (elem.value != "1") {                                                                $(this).removeClass('buttonRefraction_selected');
                                                   } else {
                                                   $(this).addClass('buttonRefraction_selected');
                                                   }                                                                });
                  
                  // let users enter "=" sign for "+" to cut down on keyboard movements (keyCode 61)
                  // "+" == "shift" + "=" ==> now "=" == "+", "j" ==> "J" for Jaeger acuity (keyCode 74)
                  // "-" is still == "-"
                  $("input[name$='VA'],input[name$='VA_copy'],input[name$='VA_copy_brd'],input[name$='SPH'],input[name$='CYL'],input[name$='REACTIVITY'],input[name$='APD']").on('keyup', function(e) {
                                                                                                                                                                                 if (e.keyCode=='61' || e.keyCode=='74') {
                                                                                                                                                                                 now = $(this).val();
                                                                                                                                                                                 now = now.replace(/=/g,"+").replace(/^j/g,"J");
                                                                                                                                                                                 $(this).val(now);
                                                                                                                                                                                 }
                                                                                                                                                                                 });
                  //useful to make all VA fields stay in sync
                  $("input[name$='VA']").on('change',function() {
                                            var hereValue = $(this).val();
                                            var newValue = $(this).attr('name').replace('VA', 'VA_copy');
                                            $("#" + newValue).val(hereValue);
                                            $("#" + newValue + "_brd").val(hereValue);
                                            });
                  $("input[name$='_copy']").blur(function() {
                                                 var hereValue = $(this).val();
                                                 var newValue = $(this).attr('name').replace('VA_copy', 'VA');
                                                 $("#" + newValue).val(hereValue);
                                                 $("#" + newValue + "_copy_brd").val(hereValue);
                                                 submit_form("eye_mag");
                                                 });
                  $("input[name$='_copy_brd']").change(function() {
                                                       var hereValue = $(this).val();
                                                       var newValue = $(this).attr('name').replace('VA_copy_brd', 'VA');
                                                       $("#" + newValue).val(hereValue);
                                                       $("#" + newValue + "_copy").val(hereValue);
                                                       submit_form("eye_mag");
                                                       });
                  $("[name^='more_']").mouseout(function() {
                                                $(this).toggleClass('buttonRefraction_selected').toggleClass('underline');
                                                });
                  $("[name^='more_']").click(function() {
                                             $("#Visions_A").toggleClass('nodisplay');
                                             $("#Visions_B").toggleClass('nodisplay');
                                             });
                  // These defaults can also be set server side and retrieved via an ajax call allowing customization at the DB server level via openEMR,
                  // rather than here.  Here however the end user would need to manually edit this file.  Perhaps shifting defaults into the DB makes sense.
                  // FEATURE REQUEST.
                  // Perfect.  This will go under the framework of starting and modifying the record according to ICD-10 codes,
                  // rather than or in addition to the other way around.
                  // Enter the correct ICD-10 code, fill in/addend the field, add Dx code in impression area.
                  // Lots of work in getting the last sentence to work...
                  
                  $("#EXT_defaults").click(function() {
                                           $('#RUL').val('normal lids and lashes').css("background-color","beige");
                                           $('#LUL').val('normal lids and lashes').css("background-color","beige");
                                           $('#RLL').val('good tone').css("background-color","beige");
                                           $('#LLL').val('good tone').css("background-color","beige");
                                           $('#RBROW').val('no brow ptosis').css("background-color","beige");
                                           $('#LBROW').val('no brow ptosis').css("background-color","beige");
                                           $('#RMCT').val('no masses').css("background-color","beige");
                                           $('#RADNEXA').val('normal lacrimal gland and orbit').css("background-color","beige");
                                           $('#LADNEXA').val('normal lacrimal gland and orbit').css("background-color","beige");
                                           $('#LMCT').val('no masses').css("background-color","beige");
                                           $('#RMRD').val('+3').css("background-color","beige");
                                           $('#LMRD').val('+3').css("background-color","beige");
                                           $('#RLF').val('17').css("background-color","beige");
                                           $('#LLF').val('17').css("background-color","beige");
                                           submit_form("eye_mag");
                                           });
                  
                  $("#ANTSEG_defaults").click(function() {
                                              $('#ODCONJ').val('quiet').css("background-color","beige");
                                              $('#OSCONJ').val('quiet').css("background-color","beige");
                                              $('#ODCORNEA').val('clear').css("background-color","beige");
                                              $('#OSCORNEA').val('clear').css("background-color","beige");
                                              $('#ODAC').val('deep and quiet').css("background-color","beige");
                                              $('#OSAC').val('deep and quiet').css("background-color","beige");
                                              $('#ODLENS').val('clear').css("background-color","beige");
                                              $('#OSLENS').val('clear').css("background-color","beige");
                                              $('#ODIRIS').val('round').css("background-color","beige");
                                              $('#OSIRIS').val('round').css("background-color","beige");
                                              submit_form("eye_mag");
                                              });
                  $("#RETINA_defaults").click(function() {
                                              $('#ODDISC').val('pink').css("background-color","beige");
                                              $('#OSDISC').val('pink').css("background-color","beige");
                                              $('#ODCUP').val('0.3').css("background-color","beige");
                                              $('#OSCUP').val('0.3').css("background-color","beige");
                                              $('#ODMACULA').val('flat').css("background-color","beige");
                                              $('#OSMACULA').val('flat').css("background-color","beige");
                                              $('#ODVESSELS').val('2:3').css("background-color","beige");
                                              $('#OSVESSELS').val('2:3').css("background-color","beige");
                                              $('#ODPERIPH').val('flat, no tears, holes or RD').css("background-color","beige");
                                              $('#OSPERIPH').val('flat, no tears, holes or RD').css("background-color","beige");
                                              submit_form("eye_mag");
                                              });
                  $("#NEURO_defaults").click(function() {
                                             $('#ODPUPILSIZE1').val('3.0').css("background-color","beige");
                                             $('#ODPUPILSIZE2').val('2.0').css("background-color","beige");
                                             $('#ODPUPILREACTIVITY').val('+2').css("background-color","beige");
                                             $('#ODAPD').val('0').css("background-color","beige");
                                             $('#OSPUPILSIZE1').val('3.0').css("background-color","beige");
                                             $('#OSPUPILSIZE2').val('2.0').css("background-color","beige");
                                             $('#OSPUPILREACTIVITY').val('+2').css("background-color","beige");
                                             $('#OSAPD').val('0').css("background-color","beige");
                                             $('#ODVFCONFRONTATION1').val('0').css("background-color","beige");
                                             $('#ODVFCONFRONTATION2').val('0').css("background-color","beige");
                                             $('#ODVFCONFRONTATION3').val('0').css("background-color","beige");
                                             $('#ODVFCONFRONTATION4').val('0').css("background-color","beige");
                                             $('#ODVFCONFRONTATION5').val('0').css("background-color","beige");
                                             $('#OSVFCONFRONTATION1').val('0').css("background-color","beige");
                                             $('#OSVFCONFRONTATION2').val('0').css("background-color","beige");
                                             $('#OSVFCONFRONTATION3').val('0').css("background-color","beige");
                                             $('#OSVFCONFRONTATION4').val('0').css("background-color","beige");
                                             $('#OSVFCONFRONTATION5').val('0').css("background-color","beige");
                                             submit_form("eye_mag");
                                             });
                  
                  $("#EXAM_defaults").click(function() {
                                            $('#RUL').val('normal lids and lashes').css("background-color","beige");
                                            $('#LUL').val('normal lids and lashes').css("background-color","beige");
                                            $('#RLL').val('good tone').css("background-color","beige");
                                            $('#LLL').val('good tone').css("background-color","beige");
                                            $('#RBROW').val('no brow ptosis').css("background-color","beige");
                                            $('#LBROW').val('no brow ptosis').css("background-color","beige");
                                            $('#RMCT').val('no masses').css("background-color","beige");
                                            $('#LMCT').val('no masses').css("background-color","beige");
                                            $('#RADNEXA').val('normal lacrimal gland and orbit').css("background-color","beige");
                                            $('#LADNEXA').val('normal lacrimal gland and orbit').css("background-color","beige");
                                            $('#RMRD').val('+3').css("background-color","beige");
                                            $('#LMRD').val('+3').css("background-color","beige");
                                            $('#RLF').val('17').css("background-color","beige");
                                            $('#LLF').val('17').css("background-color","beige");
                                            $('#OSCONJ').val('quiet').css("background-color","beige");
                                            $('#ODCONJ').val('quiet').css("background-color","beige");
                                            $('#ODCORNEA').val('clear').css("background-color","beige");
                                            $('#OSCORNEA').val('clear').css("background-color","beige");
                                            $('#ODAC').val('deep and quiet, -F/C').css("background-color","beige");
                                            $('#OSAC').val('deep and quiet, -F/C').css("background-color","beige");
                                            $('#ODLENS').val('clear').css("background-color","beige");
                                            $('#OSLENS').val('clear').css("background-color","beige");
                                            $('#ODIRIS').val('round').css("background-color","beige");
                                            $('#OSIRIS').val('round').css("background-color","beige");
                                            $('#ODPUPILSIZE1').val('3.0').css("background-color","beige");
                                            $('#ODPUPILSIZE2').val('2.0').css("background-color","beige");
                                            $('#ODPUPILREACTIVITY').val('+2').css("background-color","beige");
                                            $('#ODAPD').val('0').css("background-color","beige");
                                            $('#OSPUPILSIZE1').val('3.0').css("background-color","beige");
                                            $('#OSPUPILSIZE2').val('2.0').css("background-color","beige");
                                            $('#OSPUPILREACTIVITY').val('+2').css("background-color","beige");
                                            $('#OSAPD').val('0').css("background-color","beige");
                                            $('#ODVFCONFRONTATION1').val('0').css("background-color","beige");
                                            $('#ODVFCONFRONTATION2').val('0').css("background-color","beige");
                                            $('#ODVFCONFRONTATION3').val('0').css("background-color","beige");
                                            $('#ODVFCONFRONTATION4').val('0').css("background-color","beige");
                                            $('#ODVFCONFRONTATION5').val('0').css("background-color","beige");
                                            $('#OSVFCONFRONTATION1').val('0').css("background-color","beige");
                                            $('#OSVFCONFRONTATION2').val('0').css("background-color","beige");
                                            $('#OSVFCONFRONTATION3').val('0').css("background-color","beige");
                                            $('#OSVFCONFRONTATION4').val('0').css("background-color","beige");
                                            $('#OSVFCONFRONTATION5').val('0').css("background-color","beige");
                                            $('#ODDISC').val('pink').css("background-color","beige");
                                            $('#OSDISC').val('pink').css("background-color","beige");
                                            $('#ODCUP').val('0.3').css("background-color","beige");
                                            $('#OSCUP').val('0.3').css("background-color","beige");
                                            $('#ODMACULA').val('flat').css("background-color","beige");
                                            $('#OSMACULA').val('flat').css("background-color","beige");
                                            $('#ODVESSELS').val('2:3').css("background-color","beige");
                                            $('#OSVESSELS').val('2:3').css("background-color","beige");
                                            $('#ODPERIPH').val('flat, no tears, holes or RD').css("background-color","beige");
                                            $('#OSPERIPH').val('flat, no tears, holes or RD').css("background-color","beige");
                                            submit_form("eye_mag");
                                            });
                  
                  $("#MOTILITYNORMAL").click(function() {
                                             $("#MOTILITY_RS").val('0');
                                             $("#MOTILITY_RI").val('0');
                                             $("#MOTILITY_RR").val('0');
                                             $("#MOTILITY_RL").val('0');
                                             $("#MOTILITY_LS").val('0');
                                             $("#MOTILITY_LI").val('0');
                                             $("#MOTILITY_LR").val('0');
                                             $("#MOTILITY_LL").val('0');
                                             
                                             $("#MOTILITY_RRSO").val('0');
                                             $("#MOTILITY_RRIO").val('0');
                                             $("#MOTILITY_RLSO").val('0');
                                             $("#MOTILITY_RLIO").val('0');
                                             $("#MOTILITY_LRSO").val('0');
                                             $("#MOTILITY_LRIO").val('0');
                                             $("#MOTILITY_LLSO").val('0');
                                             $("#MOTILITY_LLIO").val('0');
                                             
                                             for (index = '0'; index < 5; ++index) {
                                             $("#MOTILITY_RS_"+index).html('');
                                             $("#MOTILITY_RI_"+index).html('');
                                             $("#MOTILITY_RR_"+index).html('');
                                             $("#MOTILITY_RL_"+index).html('');
                                             $("#MOTILITY_LS_"+index).html('');
                                             $("#MOTILITY_LI_"+index).html('');
                                             $("#MOTILITY_LR_"+index).html('');
                                             $("#MOTILITY_LL_"+index).html('');
                                             
                                             $("#MOTILITY_RRSO_"+index).html('');
                                             $("#MOTILITY_RRIO_"+index).html('');
                                             $("#MOTILITY_RLSO_"+index).html('');
                                             $("#MOTILITY_RLIO_"+index).html('');
                                             $("#MOTILITY_LRSO_"+index).html('');
                                             $("#MOTILITY_LRIO_"+index).html('');
                                             $("#MOTILITY_LLSO_"+index).html('');
                                             $("#MOTILITY_LLIO_"+index).html('');
                                             }
                                             submit_form('eye_mag');
                                             });
                  
                  $("[name^='MOTILITY_']").click(function()  {
                                                 $("#MOTILITYNORMAL").removeAttr('checked');
                                                 
                                                 if (this.id.match(/(MOTILITY_([A-Z]{4}))_(.)/)) {
                                                 var zone = this.id.match(/(MOTILITY_([A-Z]{4}))_(.)/);
                                                 var index   = '0';
                                                 var valued = isNaN($("#"+zone[1]).val());
                                                 if ((zone[2] =='RLSO')||(zone[2] =='LLSO')||(zone[2] =='RRIO')||(zone[2] =='LRIO')) {
                                                 //find or make a hash tage for "\"
                                                 var hash_tag = '<i class="fa fa-minus"></i>';
                                                 } else {
                                                 //find or make a hash tage for "/"
                                                 var hash_tag = '<i class="fa fa-minus"></i>';
                                                 }
                                                 } else {
                                                 var zone = this.id.match(/(MOTILITY_..)_(.)/);
                                                 var section = this.id.match(/MOTILITY_(.)(.)_/);
                                                 var section2 = section[2];
                                                 var Eye = section[1];
                                                 var SupInf = section2.search(/S|I/);
                                                 var RorLside   = section2.search(/R|L/);
                                                 
                                                 
                                                 if (RorLside =='0') {
                                                 var hash_tag = '<i class="fa fa-minus rotate-left"></i>';
                                                 } else {
                                                 var hash_tag = '<i class="fa fa-minus"></i>';
                                                 }
                                                 }
                                                 if (valued != true && $("#"+zone[1]).val() <'4') {
                                                 valued=$("#"+zone[1]).val();
                                                 valued++;
                                                 } else {
                                                 valued = '0';
                                                 $("#"+zone[1]).val('0');
                                                 }
                                                 
                                                 $("#"+zone[1]).val(valued);
                                                 
                                                 for (index = '0'; index < 5; ++index) {
                                                 $("#"+zone[1]+"_"+index).html('');
                                                 }
                                                 if (valued > '0') {
                                                 for (index =1; index < (valued+1); ++index) {
                                                 $("#"+zone[1]+"_"+index).html(hash_tag);
                                                 }
                                                 }
                                                 
                                                 submit_form();
                                                 });
                  
                  $("[name^='Close_']").click(function()  {
                                              var section = this.id.match(/Close_(.*)$/)[1];
                                              if (section =="ACTMAIN") {
                                              $("#ACTTRIGGER").trigger( "click" );
                                              } else {
                                              $("#LayerVision_"+section+"_lightswitch").click();
                                              }
                                              });
                  
                  
                  $("#EXAM_DRAW, #BUTTON_DRAW_menu, #PANEL_DRAW").click(function() {
                                                                        if ($("#PREFS_CLINICAL").value !='0') {
                                                                        show_right();
                                                                        $("#PREFS_CLINICAL").val('0');
                                                                        update_PREFS();
                                                                        }
                                                                        if ($("#PREFS_EXAM").val() != 'DRAW') {
                                                                        $("#PREFS_EXAM").val('DRAW');
                                                                        $("#EXAM_QP").removeClass('button_selected');
                                                                        $("#EXAM_DRAW").addClass('button_selected');
                                                                        $("#EXAM_TEXT").removeClass('button_selected');
                                                                        update_PREFS();
                                                                        }
                                                                        show_DRAW();
                                                                        // $(document).scrollTop( $("#EXT_anchor").offset().top -55);
                                                                        });
                  $("#EXAM_QP,#PANEL_QP").click(function() {
                                                if ($("#PREFS_CLINICAL").value !='0') {
                                                $("#PREFS_CLINICAL").val('0');
                                                update_PREFS();
                                                }
                                                if ($("#PREFS_EXAM").value != 'QP') {
                                                $("#PREFS_EXAM").val('QP');
                                                $("#EXAM_QP").addClass('button_selected');
                                                $("#EXAM_DRAW").removeClass('button_selected');
                                                $("#EXAM_TEXT").removeClass('button_selected');
                                                update_PREFS();
                                                }
                                                show_QP();
                                                //  $(document).scrollTop( $("#EXT_anchor").offset().top -55 );
                                                });
                  
                  $("#EXAM_TEXT,#PANEL_TEXT").click(function() {
                                                    
                                                    // also hide QP, DRAWs, and PRIORS
                                                    hide_DRAW();
                                                    hide_QP();
                                                    hide_PRIORS();
                                                    hide_right();
                                                    show_TEXT();
                                                    for (index = '0'; index < zones.length; ++index) {
                                                    $("#PREFS_"+zones[index]+"_RIGHT").val(0);
                                                    }
                                                    update_PREFS();
                                                    
                                                    $("#EXAM_DRAW").removeClass('button_selected');
                                                    $("#EXAM_QP").removeClass('button_selected');
                                                    $("#EXAM_TEXT").addClass('button_selected');
                                                    //  $(document).scrollTop( $("#EXT_anchor").offset().top -55);
                                                    });
                  $("[id^='BUTTON_TEXT_']").click(function() {
                                                  $("[id^='BUTTON_TEXT_']").click(function() {
                                                                                  var zone = this.id.match(/BUTTON_TEXT_(.*)/)[1];
                                                                                  if (zone != "menu") {
                                                                                  $("#"+zone+"_right").addClass('nodisplay');
                                                                                  $("#"+zone+"_left").removeClass('display');
                                                                                  $("#"+zone+"_left_text").removeClass('display');
                                                                                  $("#PREFS_"+zone+"_RIGHT").val(0);
                                                                                  update_PREFS();
                                                                                  }
                                                                                  show_TEXT();
                                                                                  });
                                                  });
                  $("[id^='BUTTON_TEXTD_']").click(function() {
                                                   var zone = this.id.match(/BUTTON_TEXTD_(.*)/)[1];
                                                   if (zone != "menu") {
                                                   if ((zone =="PMH") || (zone == "HPI")) {
                                                   $("#PMH_right").addClass('nodisplay');
                                                   $("#PREFS_PMH_RIGHT").val(1);
                                                   $("#HPI_right").addClass('nodisplay');
                                                   $("#PREFS_HPI_RIGHT").val(1);
                                                   var reset = $("#HPI_1").height();
                                                   $("#PMH_1").height(reset);
                                                   $("#PMH_left").height(reset-40);
                                                   if (zone == "PMH") {
                                                   $(document).scrollTop( $("#"+zone+"_anchor").offset().top - 25);
                                                   }
                                                   } else {
                                                   $("#"+zone+"_right").addClass('nodisplay');
                                                   // $("#"+zone+"_COMMENTS_DIV").removeClass('QP_lengthen');
                                                   // $("#"+zone+"_keyboard_left").removeClass('nodisplay');
                                                   $("#PREFS_"+zone+"_RIGHT").val(1);
                                                   }
                                                   update_PREFS();
                                                   
                                                   }
                                                   });
                  
                  $("#EXAM_TEXT").addClass('button_selected');
                  
                  if (($("#PREFS_CLINICAL").val() !='1')) {
                  var actionQ = "#EXAM_"+$("#PREFS_EXAM").val();
                  $(actionQ).trigger('click');
                  } else {
                  $("#EXAM_TEXT").addClass('button_selected');
                  }
                  if ($("#ANTSEG_prefix").val() > '') {
                  $("#ANTSEG_prefix_"+$("#ANTSEG_prefix").val()).addClass('button_selected');
                  } else {
                  $("#ANTSEG_prefix").val('off').trigger('change');
                  }
                  $("[name^='ACT_tab_']").mouseover(function() {
                                                    $(this).toggleClass('underline').css( 'cursor', 'pointer' );
                                                    });
                  $("[name^='ACT_tab_']").mouseout(function() {
                                                    $(this).toggleClass('underline');
                                                    });

                  $("[name^='ACT_tab_']").click(function()  {
                                                var section = this.id.match(/ACT_tab_(.*)/)[1];
                                                $("[name^='ACT_']").addClass('nodisplay');
                                                $("[name^='ACT_tab_']").removeClass('nodisplay').removeClass('ACT_selected').addClass('ACT_deselected');
                                                $("#ACT_tab_" + section).addClass('ACT_selected').removeClass('ACT_deselected');
                                                $("#ACT_" + section).removeClass('nodisplay');
                                                $("#PREFS_ACT_SHOW").val(section);
                                                //selection correctt QP zone
                                                $("[name^='NEURO_ACT_zone']").removeClass('eye_button_selected');
                                                $("#NEURO_ACT_zone_"+ section).addClass("eye_button_selected");
                                                $("#NEURO_ACT_zone").val(section);
                                                update_PREFS();
                                                });
                   $("#ACTTRIGGER").mouseout(function() {
                                            $("#ACTTRIGGER").toggleClass('buttonRefraction_selected').toggleClass('underline');
                                            });
                  if ($("#PREFS_ACT_VIEW").val() == '1') {
                  $("#ACTMAIN").toggleClass('nodisplay'); //.toggleClass('fullscreen');
                  $("#NPCNPA").toggleClass('nodisplay');
                  $("#ACTNORMAL_CHECK").toggleClass('nodisplay');
                  $("#ACTTRIGGER").toggleClass('underline');
                  var show = $("#PREFS_ACT_SHOW").val();
                  $("#ACT_tab_"+show).trigger('click');
                  }
                  $("#ACTTRIGGER").click(function() {
                                         $("#ACTMAIN").toggleClass('nodisplay').toggleClass('ACT_TEXT');
                                         $("#NPCNPA").toggleClass('nodisplay');
                                         $("#ACTNORMAL_CHECK").toggleClass('nodisplay');
                                         $("#ACTTRIGGER").toggleClass('underline');
                                         if ($("#PREFS_ACT_VIEW").val()=='1') {
                                         $("#PREFS_ACT_VIEW").val('0');
                                         } else {
                                         $("#PREFS_ACT_VIEW").val('1');
                                         }
                                         var show = $("#PREFS_ACT_SHOW").val();
                                         $("#ACT_tab_"+show).trigger('click');
                                         update_PREFS();
                                         });
                  $("#NEURO_COLOR").click(function() {
                                          $("#ODCOLOR").val("11/11");
                                          $("#OSCOLOR").val("11/11");
                                          submit_form("eye_mag");
                                          });
                  
                  $("#NEURO_COINS").click(function() {
                                          $("#ODCOINS").val("1.00");
                                          //leave currency symbol out unless it is an openEMR defined option
                                          $("#OSCOINS").val("1.00");
                                          submit_form("eye_mag");
                                          });
                  
                  $("#NEURO_REDDESAT").click(function() {
                                             $("#ODREDDESAT").val("100");
                                             $("#OSREDDESAT").val("100");
                                             submit_form("eye_mag");
                                             });
                  
                  $("[id^='myCanvas_']").mouseout(function() {
                                                  var zone = this.id.match(/myCanvas_(.*)/)[1];
                                                  submit_canvas(zone);
                                                  });
                  $("[id^='Undo_']").click(function() {
                                           var zone = this.id.match(/Undo_Canvas_(.*)/)[1];
                                           submit_canvas(zone);
                                           });
                  $("[id^='Redo_']").click(function() {
                                           var zone = this.id.match(/Redo_Canvas_(.*)/)[1];
                                           submit_canvas(zone);
                                           });
                  $("[id^='Clear_']").click(function() {
                                            var zone = this.id.match(/Clear_Canvas_(.*)/)[1];
                                            submit_canvas(zone);
                                            });
                  $("[id^='Base_']").click(function() { //not implemented yet
                                           var zone = this.id.match(/Base_Canvas_(.*)/)[1];
                                           //To change the base img
                                           //delete current image from server
                                           //re-ajax the canvas div
                                           var id_here = document.getElementById('myCanvas_'+zone);
                                           var dataURL = id_here.toDataURL();
                                           $.ajax({
                                                  type: "POST",
                                                  url: "../../forms/eye_mag/save.php?canvas="+zone+"&id="+$("#form_id").val(),
                                                  data: {
                                                  imgBase64     : dataURL,  //this contains the new strokes, the sketch.js foreground
                                                  'zone'        : zone,
                                                  'visit_date'  : $("#visit_date").val(),
                                                  'encounter'   : $("#encounter").val(),
                                                  'pid'         : $("#pid").val()
                                                  }
                                                  
                                                  }).done(function(o) {
                                                          //            console.log(o);
                                                          // $("#tellme").html(o);
                                                          });
                                           
                                           $("#url_"+zone).val("/interface/forms/eye_mag/images/OU_"+zone+"_BASE.png");
                                           canvas.renderAll();
                                           //submit_canvas(zone);
                                           });
                  
                  $("#COPY_SECTION").change(function() {
                                            var start = $("#COPY_SECTION").val();
                                            if (start =='') return;
                                            var value = start.match(/(\w*)-(\w*)/);
                                            var zone = value[1];
                                            var copy_from = value[2];
                                            if (zone =="READONLY") copy_from = $("#form_id").val();
                                            var count_changes='0';
                                            
                                            var data = {
                                            action      : 'copy',
                                            copy        : zone,
                                            zone        : zone,
                                            copy_to     : $("#form_id").val(),
                                            copy_from   : copy_from,
                                            pid         : $("#pid").val()
                                            };
                                            if (zone =="READONLY") {
                                            //we are going to update the whole form
                                            //Imagine you are watching on your browser while the tech adds stuff in another room on another computer.
                                            //We are not ready to actively chart, just looking to see how far along our staff is...
                                            //or maybe just looking ahead to see who's next in the next room?
                                            //Either way, we are looking at a record that at present will be disabled/we cannot change...
                                            // yet it is updating every 10 seconds if another user is making changes.
                                            
                                            //      READONLY does not show IMPPLAN changes!!!!
                                            } else {
                                            //here we are retrieving an old record to copy forward to today's active chart.
                                            data = $("#"+zone+"_left_text").serialize() + "&" + $.param(data);
                                            }
                                            $.ajax({
                                                   type 	: 'POST',
                                                   dataType : 'json',
                                                   url      :  "../../forms/eye_mag/save.php",
                                                   data 	: data,
                                                   success  : function(result) {
                                                   //we have to process impplan differently
                                                   if (zone =='IMPPLAN') {
                                                   //we get a json result.IMPPLAN back from the prior visit
                                                   //we need to add that to the current list?  on top? on bottom? replace? Replace for now.
                                                   build_IMPPLAN(result.IMPPLAN);
                                                   store_IMPPLAN(result.IMPPLAN);
                                                   //                                                   need to make the Plan areas purple//
                                                   } else {
                                                   $.map(result, function(valhere, keyhere) {
                                                         if ($("#"+keyhere).val() != valhere) {
                                                         $("#"+keyhere).val(valhere).css("background-color","#CCF");
                                                         } else if (keyhere.match(/MOTILITY_/)) {
                                                         // Copy forward ductions and versions visually
                                                         // Make each blank, and rebuild them
                                                         $("[name='"+keyhere+"_1']").html('');
                                                         $("[name='"+keyhere+"_2']").html('');
                                                         $("[name='"+keyhere+"_3']").html('');
                                                         $("[name='"+keyhere+"_4']").html('');
                                                         if (keyhere.match(/(_RS|_LS|_RI|_LI|_RRSO|_RRIO|_RLSO|_RLIO|_LRSO|_LRIO|_LLSO|_LLIO)/)) {
                                                         // Show a horizontal (minus) tag.  When "/" and "\" fa-icons are available will need to change.
                                                         // Maybe just use small font "/" and "\" directly.
                                                         hash_tag = '<i class="fa fa-minus"></i>';
                                                         } else { //show vertical tag
                                                         hash_tag = '<i class="fa fa-minus rotate-left"></i>';
                                                         }
                                                         for (index =1; index <= valhere; ++index) {
                                                         $("#"+keyhere+"_"+index).html(hash_tag);
                                                         }
                                                         } else if (keyhere.match(/^(ODVF|OSVF)\d$/)) {
                                                         if (valhere =='1') {
                                                         $("#FieldsNormal").prop('checked', false);
                                                         $("#"+keyhere).prop('checked', true);
                                                         $("#"+keyhere).val('1');
                                                         } else {
                                                         $("#"+keyhere).val('0');
                                                         $("#"+keyhere).prop('checked', false);
                                                         }
                                                         } else if (keyhere.match(/AMSLERO(.)/)) {
                                                         var sidehere = keyhere.match(/AMSLERO(.)/);
                                                         if (valhere < '1') valhere ='0';
                                                         $("#"+keyhere).val(valhere);
                                                         var srcvalue="AmslerO"+sidehere[1];
                                                         document.getElementById(srcvalue).src = document.getElementById(srcvalue).src.replace(/\_\d/g,"_"+valhere);
                                                         $("#AmslerO"+sidehere[1]+"value").text(valhere);
                                                         } else if (keyhere.match(/VA$/)) {
                                                         $("#"+keyhere+"_copy").val(valhere);
                                                         $("#"+keyhere+"_copy_brd").val(valhere);
                                                         }
                                                         });
                                                   if (zone != "READONLY") { submit_form("eye_mag"); }
                                                   }
                                                   }});
                                            });
                  $("[id^='BUTTON_DRAW_']").click(function() {
                                                  var zone =this.id.match(/BUTTON_DRAW_(.*)$/)[1];
                                                  if (zone =="ALL") {
                                                  //show_DRAW();
                                                  } else {
                                                  $("#"+zone+"_1").removeClass('nodisplay');
                                                  $("#"+zone+"_right").addClass('canvas').removeClass('nodisplay');
                                                  $("#QP_"+zone).addClass('nodisplay');
                                                  $("#PRIORS_"+zone+"_left_text").addClass('nodisplay');
                                                  $("#Draw_"+zone).removeClass('nodisplay');
                                                  $("#PREFS_"+zone+"_RIGHT").val('DRAW');
                                                  update_PREFS();
                                                  }
                                                  });
                  $("[id^='BUTTON_QP_']").click(function() {
                                                var zone = this.id.match(/BUTTON_QP_(.*)$/)[1].replace(/_\d*/,'');
                                                if (zone =='IMPPLAN2') {
                                                $('#IMP_start_acc').slideDown();
                                                zone='IMPPLAN';
                                                }
                                                $("#PRIORS_"+zone+"_left_text").addClass('nodisplay');
                                                $("#Draw_"+zone).addClass('nodisplay');
                                                show_QP_section(zone);
                                                $("#PREFS_"+zone+"_RIGHT").val('QP');
                                                if ((zone != 'PMH')&&(zone != 'HPI')) {
                                                }
                                                if (zone == 'PMH') {
                                                if($('#HPI_right').css('display') == 'none') {
                                                $("#PRIORS_HPI_left_text").addClass('nodisplay');
                                                $("#Draw_HPI").addClass('nodisplay');
                                                show_QP_section('HPI');
                                                $("#PREFS_HPI_RIGHT").val('QP');
                                                $(document).scrollTop('400');
                                                }
                                                if ($('#PMH_right').height() > $('#PMH_left').height()) {
                                                $('#PMH_left').height($('#PMH_right').height());
                                                $('#PMH_1').height($('#PMH_right').height()+20);
                                                } else { $('#PMH_1').height($('#HPI_1').height()); }
                                                }
                                                else if (zone == 'HPI') {
                                                if($('#PMH_right').css('display') == 'none') {
                                                $("#PRIORS_PMH_left_text").addClass('nodisplay');
                                                $("#Draw_PMH").addClass('nodisplay');
                                                show_QP_section('PMH');
                                                $("#PREFS_PMH_RIGHT").val('QP');
                                                }
                                                if ($('#PMH_right').height() > $('#PMH_left').height()) {
                                                $('#PMH_left').height($('#PMH_right').height());
                                                } else { $('#PMH_1').height($('#HPI_1').height()); }
                                                } else if (zone == 'menu') {
                                                show_QP();
                                                } else if (zone == 'IMPPLAN') {
                                                show_QP_section('IMPPLAN');
                                                update_PREFS();
                                                }
                                                });
                  
                  // set default to ccDist.  Change as desired.
                  $('#NEURO_ACT_zone').val('CCDIST').trigger('change');
                  if ($("#RXStart").val() =="2") {
                  $("#Trifocal").trigger('click');
                  }
                  $("[name$='_loading']").addClass('nodisplay');
                  $("[name$='_sections']").removeClass('nodisplay');
                  // var highestCol = Math.max($('#PMSFH_block_1').height(),$('#PMSFH_block_2').height());
                  //if (highestCol < '344') highestCol = '330';
                  //$('#PMSFH_block_1').height(highestCol);
                  //$('#PMSFH_block_2').height(highestCol);
                  if ($('#PMH_right').height() > $('#PMH_left').height()) {
                  $('#PMH_left').height($('#PMH_right').height());
                  } else { $('#PMH_1').height($('#HPI_1').height()); }
                
                  $('#left-panel').css("right","0px");
                  $('#EXAM_KB').css({position: 'fixed', top: '29px'});
                  $('#EXAM_KB').css('display', 'block');
                  $('#EXAM_KB').draggable();
                  $('#IMP').droppable({ drop: dragto_IMPPLAN } );
                  $('#IMPPLAN_zone').droppable({ drop: dragto_IMPPLAN_zone } );
                  $('#IMPPLAN_text').droppable({ drop: dragto_IMPPLAN_zone } );
                  
                  $('[id^="PLANS"]').draggable(  { cursor: 'move', revert: true });
                  $('[id^="PLAN_"]').height( $(this).scrollHeight );
                  
                  /*  Sorting of diagnoses in IMP/PLAN right panel builds IMP_order[] array.
                   Foreach index => value in IMP_order[order,PMSFH[type][i]]:
                   retrieve PMSFH[type][value] and build the IMPRESSION/PLAN area
                   openEMR ICD-10 seems to have newlines in codetext?  strip them with replace.
                   All the ISSUE_TYPES and their fields are available in obj.PMSFH:
                   'title' => $disptitle,
                   'status' => $statusCompute,
                   'enddate' => $row['enddate'],
                   'reaction' => $row['reaction'],
                   'referredby' => $row['referredby'],
                   'extrainfo' => $row['extrainfo'],
                   'diagnosis' => $row['diagnosis'],
                   'code' => $code,
                   'codedesc' => $codedesc,
                   'codetext' => $codetext,
                   'codetype' => $codetype,
                   'comments' => $row['comments'],
                   'rowid' => $row['id'],
                   'row_type' => $row['type']
                   eg. IMPPLAN_items[index] =  code: obj.PMSFH['POH'][value]['code'],
                   codedesc:  obj.PMSFH['POH'][value]['codedesc'],
                   codetype:  obj.PMSFH['POH'][value]['codetype']
                   Need to create a way to get Dx codes in the list from the clinical fields themselves.
                   Will need to create an intelligent engine to review fields on submit.
                   */
                  $('#make_new_IMP').click(function() {
                                           var issue;
                                           if (IMP_order.length ==0) rebuild_IMP($( "#build_DX_list" ));
                                           if (IMPPLAN_items ==null) IMPPLAN_items = [];
                                           $.each(IMP_order, function( index, value ) {
                                                  // value = (POH|PMH_#)
                                                  //will include CLINICAL for Dx culled from clinical data fields
                                                  issue= value.match(/(.*)_(.*)/);
                                                  if (issue[1] == "PMH") {
                                                  if (!$('#inc_PMH').is(':checked')) { return; }
                                                  issue[1] = "PMH";
                                                  } else {
                                                  if (!$('#inc_POH').is(':checked')) { return; }
                                                  }
                                                  //add in needed items
                                                  IMPPLAN_items.push({
                                                                     title:obj.PMSFH[issue[1]][issue[2]]['title'],
                                                                     code: obj.PMSFH[issue[1]][issue[2]]['code'],
                                                                     codetype: obj.PMSFH[issue[1]][issue[2]]['codetype'],
                                                                     codedesc: obj.PMSFH[issue[1]][issue[2]]['codedesc'],
                                                                     codetext: obj.PMSFH[issue[1]][issue[2]]['codetext'].replace(/(\r\n|\n|\r)/gm,""),
                                                                     plan: obj.PMSFH[issue[1]][issue[2]]['comments'],
                                                                     PMSFH_link: obj.PMSFH[issue[1]][issue[2]]['PMSFH_link']
                                                                     });
                                                   });
                                           build_IMPPLAN(IMPPLAN_items);
                                           store_IMPPLAN(IMPPLAN_items);
                                           });
                  
                  
                  var allPanels = $('.building_blocks > dd').hide();
                  var allPanels2 = $('.building_blocks2 > dd').hide();
                  
                  $('.building_blocks > dt > span').click(function() {
                                                          allPanels.slideUp();
                                                          $(this).parent().next().slideDown();
                                                          return false;
                                                          });
                  $('.building_blocks2 > dt > span').click(function() {
                                                           allPanels2.slideUp();
                                                           $(this).parent().next().slideDown();
                                                           return false;
                                                           });
                  $('#IMP_start_acc').slideDown();
                  $('[id^=inc_]').click(function() {
                                        build_DX_list(obj);
                                        });
                  
                  $('#active_flag').click(function() { check_lock('1'); });
                  $('#active_icon').click(function() { check_lock('1'); });
                  refresh_page();
                  
                  $("input,textarea,text,checkbox").change(function(){
                                                           $(this).css("background-color","#F0F8FF");
                                                           submit_form($(this));
                                                           });
                  $('#IMP').blur(function() {
                                 //add this DX to the IMPPLAN_items array
                                 //take the first line as the impression and the rest as the plan
                                 var total_imp = $('#IMP').val();
                                 var local_plan = '';
                                 if (total_imp.length == '0') return;
                                 var re = /\r\n|[\n\v\f\r\x85\u2028\u2029]/; //official list of line delimiters for a regex
                                 //local_impression is first line only[1]
                                 var local_imp = total_imp.match(/^(.*)(?:\r\n|[\n\v\f\r\x85\u2028\u2029])(.*)/);
                                 if (local_imp == null || local_imp[1] == null) {
                                    local_imp = total_imp;
                                 } else {
                                     // If the first line was dropped in from the Builder via a draggable DX_list
                                     // it will include the IMPRESSION + CODE.
                                     // Consider stripping out the CODE
                                    var local_imp_code = local_imp[1].match(/(.*)(ICD.*)$/);
                                    if (local_imp_code) {
                                        local_imp = local_imp_code[1];
                                        local_code = local_imp_code[2];
                                        local_plan = total_imp.replace(local_imp_code[0],''); //plan is line 2+ if present, strip off first line
                                        local_plan = local_plan.replace(/^\r\n|[\n\v\f\r\x85\u2028\u2029]/,'');
                                    } else {
                                        local_imp = local_imp[1];
                                        local_code = '';
                                        local_plan = total_imp.replace(local_imp,''); //plan is line 2+ if present, strip off first line
                                        local_plan = local_plan.replace(/^\r\n|[\n\v\f\r\x85\u2028\u2029]/,'');
                                     }
                                }
                                 if (IMPPLAN_items ==null) IMPPLAN_items = [];//can't push if array does not exist
                                IMPPLAN_items.push({
                                                   form_id: $('#form_id').val(),
                                                   pid: $('#pid').val(),
                                                   title: local_imp,
                                                   plan: local_plan,
                                                   code: local_code,
                                                   codetext:'',
                                                   codetype:'',
                                                   codedesc:'',
                                                   PMSFH_link: ''
                                                   });
                                 build_IMPPLAN(IMPPLAN_items);
                                 store_IMPPLAN(IMPPLAN_items);
                                 $('#IMP').val('');//clear the box
                                 submit_form();//tell the server where we stand
                                 });
                  show_QP_section('IMPPLAN');
                  $("input,textarea,text").focus(function(){
                                                 $(this).css("background-color","#ffff99");
                                                 });
                  
                  $(window).bind('beforeunload', function(){
                                 if ($('#chart_status')=="on") { unlock(); }
                                 // uncomment to auto create a PDF of this form in Documents for this encounter/form_id
                                 //   store_PDF();
                                 });
                  // window.onunload = unlock();
                  //window.onbeforeunload = unlock();
                  });



  