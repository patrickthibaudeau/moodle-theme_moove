define(['jquery'], function ($) {
    "use strict";

    function initWatson() {
        var watsoncontainer = $('#WACWidget');
        var watsonbuttoncontainer = $('#watson-btn-container');
        var ariaControls = $(watsoncontainer).find('div[aria-controls]').attr('aria-controls');
        $('.watson-btn').unbind('click').bind('click', function () {
            if ($(watsoncontainer).hasClass('collapsed')) {
                $(watsoncontainer).removeClass('collapsed');
                $("#" + ariaControls).attr('aria-expanded', 'true');
                $("#" + ariaControls).attr('aria-hidden', 'false');
            } else {
                $(watsoncontainer).addClass('collapsed');
                $("#" + ariaControls).attr('aria-expanded', 'false');
                $("#" + ariaControls).attr('aria-hidden', 'true');
            }

            return false;
        });

        $('body').unbind('click').bind('click', function (e) {
            // If clicked inside the container, do not close popup
            if (e.target.id == 'WACWidget') {
                return;
            }

            // If clicked inside the container but not the actual div
            // check to see if its parent is the popup div and do not close.
            if ($(e.target).closest('#WACWidget').length) {
                return;
            }

            // Only close the popup if it is open
            if (!$(watsoncontainer).hasClass('collapsed')) {
                $(watsoncontainer).addClass('collapsed');
                $("#" + ariaControls).attr('aria-expanded', 'false');
                $("#" + ariaControls).attr('aria-hidden', 'true');
            }
        });
    }

    return {
        init: function () {
            initWatson();
        }
    };
});
