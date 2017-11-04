/**
 * ownCloud - westvault
 *
 * This file is licensed under the MIT License version 3 or
 * later. See the COPYING file.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 * @copyright Michael Joyce 2017
 */

(function ($, OC) {

    function postConfig(url, formData, callback = null) {
        $.ajax(url, {
            method: 'POST',
            data: formData,
            success: function (responseData, status, jqXhr) {
                alert(responseData.message);
                if(callback) {
                    callback(responseData);
                }
            },
            error: function (jqXhr, status, error) {
                alert("Error: " + status + " " + error);
            }
        });
    }

    $(document).ready(function () {
        $(".restore").click(function(e){            
            e.preventDefault();
            var $this = $(this);
            var url = OC.generateUrl('/apps/westvault/restore');
            var data = {
                uuid: $(this).data('uuid'),
            };
            postConfig(url, data, function(data){
                $this.parent().parent().find('.pln-status').text('restore');
            });
        });
        
        $("#user_save").click(function (e) {
            e.preventDefault();
            var url = OC.generateUrl('/apps/westvault/config/save-user');
            var formData = $("#westvault_user").serialize();
            postConfig(url, formData);
        });
        
        $("#site_save").click(function (e) {
            e.preventDefault();
            var url = OC.generateUrl('/apps/westvault/config/save-site');
            var formData = $("#westvault_site").serialize();
            postConfig(url, formData);
        });
        $("#terms_agree").click(function(e){
            e.preventDefault();
            var url = OC.generateUrl('/apps/westvault/config/save-agreement');
            var formData = $("#westvault_terms").serialize();
            postConfig(url, formData);
        });
        
        $("#pln_terms_refresh").click(function(e){
            e.preventDefault();
            var url = OC.generateUrl('/apps/westvault/config/refresh');
            console.log("starting terms refresh.");
            $.ajax(url, {
                method: 'POST',
                success: function (responseData, status, jqXhr) {                    
                    alert("The terms have been updated.");
                    var $ol = $("#westvault_terms blockquote").html('<ol></ol>');
                    responseData.terms.forEach(function(term) {
                        $ol.append("<li>" + term['text'] + "<br><em>updated " + term['updated'] + "</li>");
                    });
                },
                error: function (jqXhr, status, error) {
                    alert("Status: " + status + " " + error);
                    console.log('error', [error, status, jqXhr]);
                }
            });
        });
    });

})(jQuery, OC);