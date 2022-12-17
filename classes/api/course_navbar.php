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

/**
 * Accessibility API endpoints
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moove\api;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Accessibility API endpoints class
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_navbar extends external_api
{
    /**
     * Font size endpoint parameters definition
     *
     * @return external_function_parameters
     */
    public static function visibility_parameters()
    {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course id'),
            'visibility' => new external_value(PARAM_INT, 'Current visibility value 1 = Show 0 = Hide'),
        ]);
    }

    /**
     * Change course visibility
     *
     * @param array $action
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public static function visibility($courseid, $visibility)
    {

        $params = self::validate_parameters(
            self::visibility_parameters(),
            [
                'courseid' => $courseid,
                'visibility' => $visibility
            ]);
        if ($visibility == 1) {
            $visible = 0;
        } else {
            $visible = 1;
        }

        course_change_visibility($courseid, $visible);

        return $visible;
    }


    /**
     * Font size endpoint return definition
     *
     * @return external_single_structure
     */
    public static function visibility_returns()
    {
        return new external_single_structure([
            'visibility' => new external_value(PARAM_INT, 'Visibility value')
        ]);
    }
}
