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
 * Database upgrade.
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

/**
 * Upgrade.
 *
 * @param int   $oldversion Is this an old version
 * @return bool Success.
 */
function xmldb_theme_moove_upgrade($oldversion = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/db/upgradelib.php');
    $dbman = $DB->get_manager();

    if ($oldversion < 2022052800) {
        $usertours = $DB->get_records('tool_usertours_tours');

        if ($usertours) {
            foreach ($usertours as $usertour) {
                $configdata = json_decode($usertour->configdata);

                if (in_array('boost', $configdata->filtervalues->theme)) {
                    $configdata->filtervalues->theme[] = 'moove';
                }

                $updatedata = new stdClass();
                $updatedata->id = $usertour->id;
                $updatedata->configdata = json_encode($configdata);

                $DB->update_record('tool_usertours_tours', $updatedata);
            }
        }

        upgrade_plugin_savepoint(true, 2022052800, 'theme', 'moove');
    }


    if ($oldversion < 2023051402) {

        // Define table theme_moove to be created.
        $table = new xmldb_table('theme_moove');

        // Adding fields to table theme_moove.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('dark_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table theme_moove.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('theme_mooveuserid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);

        // Conditionally launch create table for theme_moove.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Moove savepoint reached.
        upgrade_plugin_savepoint(true, 2023051402, 'theme', 'moove');
    }

    if ($oldversion < 2024_11_04_0004) {  // Replace YYYYMMDDXX with the actual version date and increment.

        // Set default values for the new settings if needed.

        // Add default for 'cria_embed_url'.
        set_config('cria_embed_url', '', 'theme_moove');

        // Add default for 'savy_bot_id'.
        set_config('savy_bot_id', '', 'theme_moove');

        // Add default for 'embed_api_key'.
        set_config('savy_cria_api_key', '', 'theme_moove');

        // Moodle needs to know that config was updated.
        upgrade_plugin_savepoint(true, 2024_11_04_0004, 'theme', 'moove');
    }

    return true;

    // add config fo

}
