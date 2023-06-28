define(['jquery', 'core/ajax'], function ($, Ajax) {
    "use strict";

    // Protect against spam
    var alreadyTried = false;

    /**
     * Whatever the mode currently is, it will be swapped with the opposite via API
     */
    function initModeSwap() {
        var request = {
            methodname: "theme_moove_thememodeswitch",
            args: {}
        }

        // Make call & reload
        Ajax.call([request])[0].then(function (response) {
            window.location.reload();
        });

    }

    return {
        init: function () {
            $("#moove-dark-mode-switch").click(function () {
                if (alreadyTried) {
                    return;
                }
                alreadyTried = true;
                initModeSwap();
            })
        }
    };
});

