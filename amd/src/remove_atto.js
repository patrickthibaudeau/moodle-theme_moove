/* jshint ignore:start */
define(['jquery', 'core/log'], function ($, log) {

    "use strict"; // jshint ;

    log.debug('Remove atto initialised');

    function init_atto() {
        let blockedAttos = $('#blocked_atto').val();

        // Make sure there is a value in the field, otherwise all mods will be
        // removed
        if (blockedAttos) {
            var attos = blockedAttos.split(',');
            var i;
            // Because we can't determine the time it will take to load the items
            // I set an interval to run the loop for up to 10 seconds: See setTimeout below
            var interval = setInterval(function () {
                for (i = 0; i < attos.length; i++) {
                    //echo360 does not follow button naming convention
                    if (attos[i] == "echo360plugin") {
                        attos[i] = "echo360attoplugin_button_echoIcon";
                    }
                    $('.atto_' + attos[i]).hide();
                }
            }, 100);

            setTimeout(() => {
                interval;
            }, 10000);
        }

    }

    return {
        init: function () {
            init_atto();
        }
    };

});
/* jshint ignore:end */