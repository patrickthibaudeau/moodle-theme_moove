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
 * Theme functions.
 *
 * @package    theme_moove
 * @copyright 2017 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_moove_get_main_scss_content($theme)
{
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_moove', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Safety fallback - maybe new installs etc.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }

    // Moove scss.
    $moovevariables = file_get_contents($CFG->dirroot . '/theme/moove/scss/moove/_variables.scss');
    $moove = file_get_contents($CFG->dirroot . '/theme/moove/scss/default.scss');

    // Combine them together.
    $allscss = $moovevariables . "\n" . $scss . "\n" . $moove;

    return $allscss;
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_moove_get_extra_scss($theme)
{
    $content = '';

    // Sets the login background image.
    $loginbgimgurl = $theme->setting_file_url('loginbgimg', 'loginbgimg');
    if (!empty($loginbgimgurl)) {
        $content .= 'body.pagelayout-login #page { ';
        $content .= "background-image: url('$loginbgimgurl'); background-size: cover;";
        $content .= ' }';
    }

    // Always return the background image with the scss when we have it.
    return !empty($theme->settings->scss) ? $theme->settings->scss . ' ' . $content : $content;
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_moove_get_pre_scss($theme)
{
    $scss = '';
    $configurable = [
        // Config key => [variableName, ...].
        'brandcolor' => ['brand-primary'],
        'secondarymenucolor' => 'secondary-menu-color',
        'fontsite' => 'font-family-sans-serif'
    ];

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        array_map(function ($target) use (&$scss, $value) {
            if ($target == 'fontsite') {
                $scss .= '$' . $target . ': "' . $value . '", sans-serif !default' . ";\n";
            } else {
                $scss .= '$' . $target . ': ' . $value . ";\n";
            }
        }, (array)$targets);
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Get compiled css.
 *
 * @return string compiled css
 */
function theme_moove_get_precompiled_css()
{
    global $CFG;

    return file_get_contents($CFG->dirroot . '/theme/moove/style/moodle.css');
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return mixed
 */
function theme_moove_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    $theme = theme_config::load('moove');

    if ($context->contextlevel == CONTEXT_SYSTEM &&
        ($filearea === 'logo' || $filearea === 'loginbgimg' || $filearea == 'favicon')) {
        $theme = theme_config::load('moove');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && preg_match("/^sliderimage[1-9][0-9]?$/", $filearea) !== false) {
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing1icon') {
        return $theme->setting_file_serve('marketing1icon', $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing2icon') {
        return $theme->setting_file_serve('marketing2icon', $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing3icon') {
        return $theme->setting_file_serve('marketing3icon', $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing4icon') {
        return $theme->setting_file_serve('marketing4icon', $args, $forcedownload, $options);
    }

    send_file_not_found();
}

function theme_moove_get_teachers()
{
    global $CFG, $DB, $PAGE, $OUTPUT;
    require_once($CFG->libdir . '/accesslib.php');
    require_once($CFG->libdir . '/filelib.php');

    $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
    $users = get_role_users($role->id, $PAGE->context);

    $teachers = [];
    $i = 0;
    foreach ($users as $u) {
        $user = $DB->get_record('user', ['id' => $u->id]);
        $user_picture = new user_picture($user);
        $moodle_url = $user_picture->get_url($PAGE);

        $teachers[$i] = new stdClass();
        $teachers[$i]->fullname = fullname($u);
        $teachers[$i]->email = $u->email;
        $teachers[$i]->image = $moodle_url->out();
    }
    return $teachers;
}

function get_current_course_mods()
{
    global $CFG, $DB, $COURSE;

    $context = context_course::instance($COURSE->id);
    $contextArray = convert_to_array($context);
    $course_id = $COURSE->id;

    $cmods = get_course_mods($course_id);
    $mod_names = [];
    $i = 0;
    $assign = 0;
    foreach ($cmods as $m) {
        if ($m->modname != 'label') {
            // For a reason I can't understand, array_search does not detect assign
            // this is a work around
            if ($assign == 0 || $m->modname != 'assign') {
                if ($i == 0) {
                    $mod_names[$i]['name'] = $m->modname;
                    $mod_names[$i]['fullname'] = get_string('pluginname', "mod_" . $m->modname);
                    $mod_names[$i]['courseid'] = $m->course;
                    $i++;
                } else {
                    if (!array_search($m->modname, array_column($mod_names, 'name'))) {
                        $mod_names[$i]['name'] = $m->modname;
                        $mod_names[$i]['fullname'] = get_string('pluginname', "mod_" . $m->modname);
                        $mod_names[$i]['courseid'] = $m->course;
                        $i++;
                    }
                }
            }

            if ($m->modname == 'assign') {
                $assign = 1;
            }
        }
    }


    return $mod_names;
}

function theme_moove_get_more_menu($items,$page)
{
    global $CFG;
    print_object($items);
    $more_menu = [];
    $i = 0;
    for ($x = 5; $x < count($items); $x++) {

        switch ($items[$x]) {
            case 'questionbank':
                $more_menu[$i]['name'] = get_string('questionbank', 'core_question');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/question/edit.php?courseid=" . $page->course->id;
                break;
            case 'contentbank':
                $more_menu[$i]['name'] = get_string($items[$x], 'core');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/contentbank/index.php?courseid=" . $page->course->id;
                break;
            case 'coursecompletion':
                $more_menu[$i]['name'] = get_string($items[$x], 'core');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/course/completion.php?id=" . $page->course->id;
                break;
            case 'badgesview':
                $more_menu[$i]['name'] = get_string('badges', 'core');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/badges/view.php?type=2&id=" . $page->course->id;
                break;
            case 'competencies':
                $more_menu[$i]['name'] = get_string('competencies', 'core_competency');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/admin/tool/lp/coursecompetencies.php?courseid=" . $page->course->id;
                break;
            case 'filtermanagement':
                $more_menu[$i]['name'] = get_string('filters', 'core');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/filter/manage.php?contextid=" . $page->context->id;
                break;
            case '13':
            case '14':
                $more_menu[$i]['name'] = get_string('accessibilityreport', 'tool_brickfield');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/admin/tool/brickfield/index.php?courseid=" . $page->course->id;
                break;
            case 'coursereuse':
                $more_menu[$i]['name'] = get_string($items[$x], 'core');
                $more_menu[$i]['url'] = $CFG->wwwroot . "/backup/import.php?id=" . $page->course->id;
                break;
        }
        $i++;
    }
    return $more_menu;
}