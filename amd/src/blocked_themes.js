/* jshint ignore:start */
define(['jquery', 'core/log'], function ($, log) {

    "use strict"; // jshint ;

    log.debug('Blocked themes initialised');

    function init_theme() {
        var themeAccess = $('#theme_access').val();
        var blockedThemes = $('#blocked_themes').val();
        var path = window.location.pathname;
        var blockedFormats = $('#blocked_formats').val();
        var t;
        var m;

        if ((path.includes('/course/edit.php'))) {
            // Make sure there is a value in the field, otherwise all themes will be
            // removed
            if (blockedThemes) {
                var themes = blockedThemes.split(',');
                for (t = 0; t < themes.length; t++) {
                    console.log(themes[t]);
                    $("#id_theme option[value='" + themes[t] + "']").remove();
                }
            }
            // Make sure there is a value in the field, otherwise all formats will be
            // removed
            if (blockedFormats) {
                var formats = blockedFormats.split(',');
                for (t = 0; t < formats.length; t++) {
                    $("#id_format option[value='" + formats[t] + "']").remove();
                }
            }
        }

    }

    return {
        init: function () {
            init_theme();
        }
    };

});
/* jshint ignore:end */