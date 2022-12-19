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
        $i++;
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

/**
 * Build secondary menu
 * @param $items array Taken from $secondary->get_tabs_array()
 * @return stdClass
 */
function theme_moove_build_secondary_menu($items)
{
    global $CFG, $COURSE;

    // Put tab items into one variable
    $tabs = $items[0][0];
    // Menus is the obkect that will be returned
    $menus = new stdClass();
    // Build primary menu
    $menu = [];
    $i = 0;
    for ($a = 0; $a < 5; $a++) {
        // Do not add course home because it is added on all pages.
        if (isset($tabs[$a]->id)) {
                $menu[$i]['id'] = $tabs[$a]->id;
                $menu[$i]['name'] = $tabs[$a]->title;
                $menu[$i]['url'] = str_replace('&amp;', '&', $tabs[$a]->link->out());
                $menu[$i]['format'] = $COURSE->format;
                $menu[$i]['icon'] = theme_moove_get_menu_icon($tabs[$a]->id);
                $i++;
        }
    }
    // Build more menu
    $more_menu = [];
    $m = 0;
    // Start at 5 because more menu begins at element 5
    for ($b = 5; $b < count($tabs); $b++) {
        $more_menu[$m]['id'] = $tabs[$b]->id;
        $more_menu[$m]['name'] = $tabs[$b]->title;
        $more_menu[$m]['url'] = str_replace('&amp;', '&', $tabs[$b]->link->out());
        $m++;
    }
    // Add both arrays into menus object
    $menus->menu = $menu;
    $menus->more = $more_menu;

    return $menus;
}

function theme_moove_get_menu_icon($type)
{
    $icon = 'fa fa-circle-o';
    switch ($type) {
        case 'coursehome':
            $icon = 'fa fa-bookmark';
            break;
        case 'editsettings':
        case 'modedit':
            $icon = 'fa fa-sliders';
            break;
        case 'participants':
            $icon = 'fa fa-users';
            break;
        case 'grades':
        case 'advgrading':
            $icon = 'fa fa-font';
            break;
        case 'coursereports':
            $icon = 'fa fa-bar-chart';
            break;
        case 'filtermanage':
            $icon = 'fa fa-filter';
            break;
        case 'roleoverride':
        case 'mod_assign_useroverrides':
            $icon = 'fa fa-check-square-o';
            break;
        case 'backup':
            $icon = 'fa fa-download';
            break;
        case 'competencies':
            $icon = 'fa fa-lightbulb-o';
            break;
        default:
            $icon = 'fa fa-circle-o';
            break;
    }

    return $icon;
}