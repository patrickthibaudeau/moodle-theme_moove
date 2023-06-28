<?php
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

namespace theme_moove\api;

global $CFG;
require_once($CFG->libdir . "/externallib.php");
require_once("$CFG->dirroot/config.php");

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use stdClass;


/**
 * Accessibility API endpoints class
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_mode extends external_api
{

    public static function theme_mode_switch_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function theme_mode_switch_returns()
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success Value', true),
            'stack' => new external_value(PARAM_TEXT, 'Fail Reason', false)
        ]);
    }

    public static function theme_mode_switch()
    {
        global $DB, $USER;
        $success = true;

        try {
            $record = $DB->get_record('theme_moove', ['userid' => $USER->id], '*');
            $update = new stdClass();

            if ($record) {
                $update->id = $record->id;
                $update->dark_enabled = $record->dark_enabled ? 0 : 1;
                $DB->update_record('theme_moove', $update);
            } else {
                $update->dark_enabled = 1;
                $update->userid = $USER->id;
                $DB->insert_record('theme_moove', $update);
            }

            $user = \core\session\manager::get_realuser();

            $stack = (
                'New Value: ' .
                $DB->get_record('theme_moove', ['userid' => $USER->id], '*')->dark_enabled
                . ' | User ID: ' . $user->id
            );

        } catch (\Throwable $ex) {
            $stack = $ex->getMessage();
            $success = false;
        }

        return ["success" => $success, "stack" => $stack];
    }

}