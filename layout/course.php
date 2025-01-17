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
 * A drawer based layout for the moove theme.
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/theme/moove/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();
$PAGE->requires->js_call_amd('theme_moove/visibility', 'init');

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING')) {
    $blockdraweropen = true;
}
$teachers = theme_moove_get_teachers();

// Is course format menutab?
$is_menutab_format = false;
if ($PAGE->course->format == 'menutab') {
    $is_menutab_format = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));

$addcontentblockbutton = $OUTPUT->addblockbutton('content');
$contentblocks = $OUTPUT->custom_block_region('content');

if (!$hasblocks) {
    $blockdraweropen = false;
}

$themesettings = new \theme_moove\util\settings();

if (!$themesettings->enablecourseindex) {
    $courseindex = '';
} else {
    $courseindex = core_course_drawer();
}

if (!$courseindex) {
    $courseindexopen = false;
}

$edit_settings = false;
if (has_capability('moodle/course:update', $PAGE->context)) {
    $edit_settings = true;
}

$edit_grades = false;
if (has_capability('moodle/grade:edit', $PAGE->context)) {
    $edit_grades = true;
}

$view_reports = false;
if (has_capability('moodle/site:viewreports', $PAGE->context)) {
    $view_reports = true;
}

if (has_capability('local/earlyalert:access_early_alert', context_system::instance())) {
    $PAGE->primarynav->add(
        get_string('earl_alert', 'local_earlyalert'),
        new moodle_url("/local/earlyalert/tool_dashboard.php")
    );
}

$is_site_course = false;
if ($PAGE->course->id == 1) {
    $is_site_course = true;
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
$main_menu = '';
$more_menu = '';
$has_more_menu = false;
if ($PAGE->has_secondary_navigation()) {
    $secondary = $PAGE->secondarynav;
    if ($secondary->get_children_key_list()) {
        $tablistnav = $PAGE->has_tablist_secondary_navigation();
        $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
        $secondarynavigation = $moremenu->export_for_template($OUTPUT);
        $extraclasses[] = 'has-secondarynavigation';
    }

    // For course_navbar
    if ($secondary->get_children_key_list()) {
        $menus = theme_moove_build_secondary_menu($secondary->get_tabs_array());
        $main_menu = $menus->menu;
        $more_menu = $menus->more;
        if ($more_menu) {
            $has_more_menu = true;
        }
    }

    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$strikemessaging = false;
if ($CFG->yorktasks_hideinstructors == 1){
    if (isset($PAGE->course->id)){
        //great this is a course
        if ($strikeinfo = $DB->get_record('strike_feed', array('moodleid' => $PAGE->course->id))){
            //only display data if we have it
            if ($strikeinfo->messagetype == "suspend"){
                $strikemessaging = "<div id='strikenotification' class='alert alert-danger'>" . $strikeinfo->message . "</div>";
            } elseif ($strikeinfo->messagetype == "active") {
                $strikemessaging = "<div id='strikenotification' class='alert alert-success'>" . $strikeinfo->message . "</div>";
            }
        }
    }
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'courseid' => $PAGE->course->id,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'enablecourseindex' => $themesettings->enablecourseindex,
    'addcontentblockbutton' => $addcontentblockbutton,
    'contentblocks' => $contentblocks,
    'edit_settings' => $edit_settings,
    'edit_grades' => $edit_grades,
    'view_reports' => $view_reports,
    'visibility' => $PAGE->course->visible,
    'strikemessaging' => $strikemessaging,
    'is_menutab_format' => $is_menutab_format,
    'teachers' => $teachers,
    'course_mods' => get_current_course_mods(),
    'main_menu' => $main_menu,
    'more_menu' => $more_menu,
    'has_more_menu' => $has_more_menu,
    'is_site_course' => $is_site_course,
];

$templatecontext = array_merge($templatecontext, $themesettings->footer());

echo $OUTPUT->render_from_template('theme_moove/course', $templatecontext);
