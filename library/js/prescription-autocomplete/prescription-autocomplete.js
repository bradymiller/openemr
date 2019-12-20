/*
 *  @package   OpenEMR
 *  @link      http://www.open-emr.org
 *  @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @copyright Copyright (c )2019. Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 *
 *
 */



$(document).ready(function () {
    $("#sendScript").toggle();
    $('#pharmacy-autocomplete').autocomplete({

        source: function (request, response) {
            // Fetch data
            let autocompleteUrl = $("#pharmacyselect").data('autocomplete-url');
            $.ajax({
                url: autocompleteUrl+'?term='+request.term,
                type: 'GET',
                dataType: "json",
                data: {
                    search: request.term
                },
                success: function (data) {
                    var resp = $.map(data,function(obj){
                        //console.log(obj.id);
                     var   phadd = obj.id + ' ' +obj.name + ' ' + obj.line1 + ' ' +obj.city;
                        return phadd;
                    });
                    response(resp);
                }
            });
        }
    });

});

let p = document.getElementById('savePharmacy');
p.addEventListener('click', savePharm);

function savePharm(e) {
    top.restoreSession();
    let pharmId = document.getElementById('pharmacy-autocomplete').value;
    let id = pharmId.split(" ");
    let setPharm = "../../src/Rx/Weno/PatientPharmacyController.php";//$("#savePharmUrl").data('savePharm-url');
    if (id[0] > 0) {
        $.ajax({
            url: setPharm+'?id='+id[0],
            type: 'GET',
            dataType: 'json',
            success: function (result) {
                $("#savePharmacy").toggle();

            },
            error: function(xhr, status, error){
                console.log(xhr);
                console.log(status);
                console.log(error);
                console.warn(xhr.responseText);
            }
        });
        e.preventDefault();
        $("#sendScript").toggle();
        return false;
    } else {
        $("#savePharmacy").toggle();
        e.preventDefault();
        $("#sendScript").toggle();
    }
}

let s = document.getElementById('sendScript');
s.addEventListener('click', sendScripts);
function sendScripts(e) {
    top.restoreSession();
    let scripts = $('#prescriptIds').data('ids');
    let sendRx = "../../interface/weno/transmitRx.php";
    if (scripts) {
      $ajax({
          url: sendRx+'?scripts='+scripts,
          type: 'GET',
          dataType: 'json',
          success: function (response) {
              responses.push(response);
          },
          error: function (xhr, status, error) {
              console.log(xhr);
              console.log(status);
              console.log(error);
              console.warn(xhr.responseText);
          }

      });
      //hide transmit button after send
        $("#sendScript").toggle();
    } else {
    alert('Let\'s call support https://omp.openmedpractice.com/dev/mantisbt-2.18.0');
    }
    e.preventDefault();

}


