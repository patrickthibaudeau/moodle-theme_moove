/* jshint ignore:start */
define(['jquery', 'core/log'], function ($, log) {

    "use strict"; // jshint ;

    log.debug('Remove blocks initialised');

    function init_blocks() {
        var blockedBlocks = $('#blocked_blocks').val();
        // Make sure there is a value in the field, otherwise all blocks will be
        // removed
        if (blockedBlocks) {
            var blocks = blockedBlocks.split(',');
            var i;
            for (i = 0; i < blocks.length; i++) {
                $('a[href*="&bui_addblock=' + blocks[i] + '"]').remove();
            }
        }
    }

    return {
        init: function () {
            init_blocks();
        }
    };

});
/* jshint ignore:end */