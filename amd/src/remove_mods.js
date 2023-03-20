/* jshint ignore:start */
define(['jquery', 'core/log'], function ($, log) {

    "use strict"; // jshint ;

    log.debug('Remove mods initialised');

    function init_mods() {
        var blockedMods = $('#blocked_mods').val();

        //Capture activity chooser has been clicked
        $('.section-modchooser-link').click(function () {
            // Make sure there is a value in the field, otherwise all mods will be
            // removed
            if (blockedMods) {
                var mods = blockedMods.split(',');
                var i;
                // Because we can't determine the time it will take to load the items
                // I set an interval to run the loop for up to 10 seconds: See setTimeout below
                var interval = setInterval(function () {
                    for (i = 0; i < mods.length; i++) {
                        $('*[data-internal="' + mods[i] + '"]').hide();
                    }
                }, 100);

                setTimeout(function(){
                   interval
                }, 10000);
            }
        });
    }

    return {
        init: function () {
            init_mods();
        }
    };

});
/* jshint ignore:end */
