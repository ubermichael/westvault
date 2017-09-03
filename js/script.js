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

    $(document).ready(function () {
        $('#hello').click(function () {
            alert('Hello from your script file');
        });

        $('#echo').click(function () {
            var url = OC.generateUrl('/apps/westvault/echo');
            var data = {
                echo: $('#echo-content').val()
            };

            $.post(url, data).success(function (response) {
                $('#echo-result').text(response.echo);
            });

        });
    });

})(jQuery, OC);