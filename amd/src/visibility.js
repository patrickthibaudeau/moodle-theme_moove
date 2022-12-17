// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module for the list of discussions on when viewing a forum.
 *
 * @module     mod_forum/discussion_list
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/templates',
    'core/str',
    'core/notification',
    'core/modal_factory',
    'core/modal_events',
    'theme_moove/repository',
    'core/ajax',

], function (
    $,
    Templates,
    Str,
    Notification,
    ModalFactory,
    ModalEvents,
    Repository,
    Ajax
) {

    return {
        init: function () {
            $('#theme-moove-course-visibility').on('click', function () {

                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: Str.get_string('change_course_visibility', 'theme_moove'),
                    body: Str.get_string('change_course_visibility_help', 'theme_moove')
                }).then(function (modal) {

                    modal.setSaveButtonText(Str.get_string('yes', 'theme_moove'));

                    modal.getRoot().on(ModalEvents.save, function () {
                        var saveProgram = Ajax.call([{
                            methodname: 'theme_moove_coursevisibility',
                            args: {
                                courseid: $('#theme-moove-course-visibility').data('courseid'),
                                visibility: $('#theme-moove-course-visibility').data('visibility')
                            }
                        }]);

                        saveProgram[0].done(function (response) {
                            return location.reload();
                        }).fail(function (ex) {
                            alert('An error has occurred. The record was not saved');
                        });
                    });

                    console.log(ModalEvents);
                    modal.getRoot().on(ModalEvents.cancel, function () {
                        console.log('Closed modal');
                    });

                    /**
                     * Open the modal and run some jQuery
                     * for the campusLanguages so that select2 works
                     */
                    modal.show();
                });
            });
        }

    }
});