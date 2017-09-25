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

    /**
     * Post a configuration form to the config controller.
     * 
     * @param string url
     * @param array formData
     * @returns null
     */
    function postConfig(url, formData) {
        $.ajax(url, {
            method: 'POST',
            data: formData,
            success: function (responseData, status, jqXhr) {
                alert(responseData.message);
            },
            error: function (jqXhr, status, error) {
                alert("Error: " + status + " " + error);
            }
        });
    }

    $(document).ready(function () {
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
                complete: function(){
                    console.log("finished terms refresh.");
                },
                success: function (responseData, status, jqXhr) {
                    alert(responseData.result);
                    console.log(responseData);
                },
                error: function (jqXhr, status, error) {
                    alert("Status: " + status + " " + error);
                    console.log('error', [error, status, jqXhr]);
                }
            });
        });
    });

})(jQuery, OC);