/**
 * Theme moove to encapsulate all of the AJAX requests
 * can be sent for forum.
 *
 * @module     theme_moove
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/modal_factory'], function(Ajax, Modal) {

    var changeCourseVisibility = function(courseid, visibility) {
        var request = {
            methodname: 'theme_moove_coursevisibility',
            args: {
                courseid: courseid,
                visibility: visibility
            }
        };
        return Ajax.call([request])[0];
    };



    return {
        changeCourseVisibility: changeCourseVisibility,
    };
});
