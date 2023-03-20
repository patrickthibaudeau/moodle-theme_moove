<?php


/**
 * Watson page for the moove theme.
 *
 * @package    theme_moove
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/moove/config.php');
require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/moove/lib.php');
global $CFG, $PAGE, $USER, $DB;
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('savy_mobile');
$PAGE->set_url($CFG->wwwrooot . '/theme/moove/layout/savy_mobile.php');
$PAGE->set_title("Savy");

$device = optional_param('device', '', PARAM_TEXT);

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'overflow' => $overflow
];

echo $OUTPUT->render_from_template('theme_moove/watson_fullscreen_app', $templatecontext);

