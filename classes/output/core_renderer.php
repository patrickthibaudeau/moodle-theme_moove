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
 * Overriden theme boost core renderer.
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moove\output;

use cache;
use context_course;
use Exception;
use html_writer;
use moodle_url;
use theme_config;
use theme_moove\output\core_course\activity_navigation;
use theme_moove\util\savy;
use tool_usertours\tour as tourinstance;


/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer
{

    public function get_cache_num()
    {
        $cache = cache::make('theme_moove', 'theme_mode');
        $cachenum = $cache->get('cachenum');

        if (!$cachenum) {
            $cachenum = rand();
            $cache->set('cachenum', $cachenum);
            return $cachenum;
        }

        return $cachenum;
    }

    public function get_mode_stylesheet($dark_enabled)
    {
        $mode = $dark_enabled ? 'dark' : 'light';
        $cachenum = $this->get_cache_num();

        return "/theme/moove/layout/theme_css.php?mode=$mode&cache=$cachenum";
    }

    public function get_dark_enabled()
    {
        global $DB, $USER;

        $dark_enabled = false;

        if ($record = $DB->get_record('theme_moove', ['userid' => $USER->id], '*')) {
            $dark_enabled = $record->dark_enabled;
        }

        return $dark_enabled;
    }

    public function theme_mode_inject_script($dark_enabled)
    {

        $mode = $dark_enabled ? 'dark' : 'light';

        return ("
           <script id='set-body-tag'>
           
                function addBodyTag() {
                    let body = document.querySelector('body');
                    body.setAttribute('theme-mode', '$mode')
                }
           
                document.getElementById('set-body-tag').remove();
           </script>
        
        ");
    }

    /**
     * The standard tags (meta tags, links to stylesheets and JavaScript, etc.)
     * that should be included in the <head> tag. Designed to be called in theme
     * layout.php files.
     *
     * @return string HTML fragment.
     */
    public function standard_head_html()
    {


        // Load standard
        $output = parent::standard_head_html();

        /*
         * This commented line was left in to note what NOT to do.
         * Removing the /all/ base stylesheet will break certain things (file picker, color picker, etc.).
         * Leave the stylesheet in there as a FALLBACK in case our inline one is missing some stuff
         * (It seems it is due to SCSS being added to the /all/ file somewhere in the moodle core out of our control)
         *
         *
         */
        //$output = preg_replace('/http(s)*:\/\/.*\/moove\/.*\/all/', '', $output);

        $theme = theme_config::load("moove");
        $dark_enabled = $this->get_dark_enabled();
        $output .= $this->theme_mode_inject_script($dark_enabled);
        $output .= '<link rel="stylesheet" type="text/css" href="' . $this->get_mode_stylesheet($dark_enabled) . '">';

        $google_analytics_code = (
        "<script
                async
                src='https://www.googletagmanager.com/gtag/js?id=GOOGLE-ANALYTICS-CODE'>
            </script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag() {
                    dataLayer.push(arguments);
                }
                gtag('js', new Date());
                gtag('config', 'GOOGLE-ANALYTICS-CODE');
            </script>"
        );

        if (!empty($theme->settings->googleanalytics)) {
            $output .= str_replace("GOOGLE-ANALYTICS-CODE", trim($theme->settings->googleanalytics), $google_analytics_code);
        }

        $sitefont = isset($theme->settings->fontsite) ? $theme->settings->fontsite : 'Roboto';

        $output .= ('
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=')
            . $sitefont . ':ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">';

        return $output;
    }

    /**
     * Returns HTML attributes to use within the body tag. This includes an ID and classes.
     *
     * @param string|array $additionalclasses Any additional classes to give the body tag,
     *
     * @return string
     *
     * @throws \coding_exception
     *
     * @since Moodle 2.5.1 2.6
     */
    public function body_attributes($additionalclasses = array())
    {
        $hasaccessibilitybar = get_user_preferences('thememoovesettings_enableaccessibilitytoolbar', '');
        if ($hasaccessibilitybar) {
            $additionalclasses[] = 'hasaccessibilitybar';

            $currentfontsizeclass = get_user_preferences('accessibilitystyles_fontsizeclass', '');
            if ($currentfontsizeclass) {
                $additionalclasses[] = $currentfontsizeclass;
            }

            $currentsitecolorclass = get_user_preferences('accessibilitystyles_sitecolorclass', '');
            if ($currentsitecolorclass) {
                $additionalclasses[] = $currentsitecolorclass;
            }
        }

        $fonttype = get_user_preferences('thememoovesettings_fonttype', '');
        if ($fonttype) {
            $additionalclasses[] = $fonttype;
        }

        if (!is_array($additionalclasses)) {
            $additionalclasses = explode(' ', $additionalclasses);
        }

        return ' id="' . $this->body_id() . '" class="' . $this->body_css_classes($additionalclasses) . '"';
    }

    /**
     * Whether we should display the main theme or site logo in the navbar.
     *
     * @return bool
     */
    public function should_display_logo()
    {
        if ($this->should_display_theme_logo() || parent::should_display_navbar_logo()) {
            return true;
        }

        return false;
    }

    /**
     * Whether we should display the main theme logo in the navbar.
     *
     * @return bool
     */
    public function should_display_theme_logo()
    {
        $logo = $this->get_theme_logo_url();

        return !empty($logo);
    }

    /**
     * Get the main logo URL.
     *
     * @return string
     */
    public function get_logo()
    {
        $logo = $this->get_theme_logo_url();

        if ($logo) {
            return $logo;
        }

        $logo = $this->get_logo_url();

        if ($logo) {
            return $logo->out(false);
        }

        return false;
    }

    /**
     * Get the main logo URL.
     *
     * @return string
     */
    public function get_theme_logo_url()
    {
        $theme = theme_config::load('moove');

        return $theme->setting_file_url('logo', 'logo');
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form)
    {
        global $SITE, $CFG;

        $context = $form->export_for_template($this);

        $context->errorformatted = $this->error_text($context->error);
        $context->logourl = $this->get_logo();
        $context->sitename = format_string($SITE->fullname, true,
            ['context' => context_course::instance(SITEID), "escape" => false]);

        if (!$CFG->auth_instructions) {
            $context->instructions = null;
            $context->hasinstructions = false;
        }

        $context->hastwocolumns = false;
        if ($context->hasidentityproviders || $CFG->auth_instructions) {
            $context->hastwocolumns = true;
        }

        if ($context->identityproviders) {
            foreach ($context->identityproviders as $key => $provider) {
                $isfacebook = false;

                if (strpos($provider['iconurl'], 'facebook') !== false) {
                    $isfacebook = true;
                }

                $context->identityproviders[$key]['isfacebook'] = $isfacebook;
            }
        }

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Returns the HTML for the site support email link
     *
     * @param array $customattribs Array of custom attributes for the support email anchor tag.
     * @return string The html code for the support email link.
     */
    public function supportemail(array $customattribs = []): string
    {
        global $CFG;

        $label = get_string('contactsitesupport', 'admin');
        $icon = $this->pix_icon('t/life-ring', '', 'moodle', ['class' => 'iconhelp icon-pre']);
        $content = $icon . $label;

        if (!empty($CFG->supportpage)) {
            $attributes = ['href' => $CFG->supportpage, 'target' => 'blank', 'class' => 'btn contactsitesupport btn-outline-info'];
        } else {
            $attributes = [
                'href' => $CFG->wwwroot . '/user/contactsitesupport.php',
                'class' => 'btn contactsitesupport btn-outline-info'
            ];
        }

        $attributes += $customattribs;

        return \html_writer::tag('a', $content, $attributes);
    }

    /**
     * Returns the moodle_url for the favicon.
     *
     * @return moodle_url The moodle_url for the favicon
     * @since Moodle 2.5.1 2.6
     */
    public function favicon()
    {
        global $CFG;

        $theme = theme_config::load('moove');

        $favicon = $theme->setting_file_url('favicon', 'favicon');

        if (!empty(($favicon))) {
            $urlreplace = preg_replace('|^https?://|i', '//', $CFG->wwwroot);
            $favicon = str_replace($urlreplace, '', $favicon);

            return new moodle_url($favicon);
        }

        return parent::favicon();
    }

    /**
     * Renders the header bar.
     *
     * @param \context_header $contextheader Header bar object.
     * @return string HTML for the header bar.
     */
    protected function render_context_header(\context_header $contextheader)
    {
        if ($this->page->pagelayout == 'mypublic') {
            return '';
        }

        // Generate the heading first and before everything else as we might have to do an early return.
        if (!isset($contextheader->heading)) {
            $heading = $this->heading($this->page->heading, $contextheader->headinglevel, 'h2');
        } else {
            $heading = $this->heading($contextheader->heading, $contextheader->headinglevel, 'h2');
        }

        // All the html stuff goes here.
        $html = html_writer::start_div('page-context-header');

        // Image data.
        if (isset($contextheader->imagedata)) {
            // Header specific image.
            $html .= html_writer::div($contextheader->imagedata, 'page-header-image mr-4');
        }

        // Headings.
        if (isset($contextheader->prefix)) {
            $prefix = html_writer::div($contextheader->prefix, 'text-muted text-uppercase small line-height-3');
            $heading = $prefix . $heading;
        }
        $html .= html_writer::tag('div', $heading, array('class' => 'page-header-headings'));

        // Buttons.
        if (isset($contextheader->additionalbuttons)) {
            $html .= html_writer::start_div('btn-group header-button-group');
            foreach ($contextheader->additionalbuttons as $button) {
                if (!isset($button->page)) {
                    // Include js for messaging.
                    if ($button['buttontype'] === 'togglecontact') {
                        \core_message\helper::togglecontact_requirejs();
                    }
                    if ($button['buttontype'] === 'message') {
                        \core_message\helper::messageuser_requirejs();
                    }
                    $image = $this->pix_icon($button['formattedimage'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                } else {
                    $image = html_writer::empty_tag('img', array(
                        'src' => $button['formattedimage'],
                        'role' => 'presentation'
                    ));
                }
                $html .= html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes']);
            }
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Returns standard navigation between activities in a course.
     *
     * @return string the navigation HTML.
     */
    public function activity_navigation()
    {
        // First we should check if we want to add navigation.
        $context = $this->page->context;
        if (($this->page->pagelayout !== 'incourse' && $this->page->pagelayout !== 'frametop')
            || $context->contextlevel != CONTEXT_MODULE) {
            return '';
        }

        // If the activity is in stealth mode, show no links.
        if ($this->page->cm->is_stealth()) {
            return '';
        }

        $course = $this->page->cm->get_course();
        $courseformat = course_get_format($course);

        // Get a list of all the activities in the course.
        $modules = get_fast_modinfo($course->id)->get_cms();

        // Put the modules into an array in order by the position they are shown in the course.
        $mods = [];
        $activitylist = [];
        foreach ($modules as $module) {
            // Only add activities the user can access, aren't in stealth mode and have a url (eg. mod_label does not).
            if (!$module->uservisible || $module->is_stealth() || empty($module->url)) {
                continue;
            }
            $mods[$module->id] = $module;

            // No need to add the current module to the list for the activity dropdown menu.
            if ($module->id == $this->page->cm->id) {
                continue;
            }
            // Module name.
            $modname = $module->get_formatted_name();
            // Display the hidden text if necessary.
            if (!$module->visible) {
                $modname .= ' ' . get_string('hiddenwithbrackets');
            }
            // Module URL.
            $linkurl = new moodle_url($module->url, array('forceview' => 1));
            // Add module URL (as key) and name (as value) to the activity list array.
            $activitylist[$linkurl->out(false)] = $modname;
        }

        $nummods = count($mods);

        // If there is only one mod then do nothing.
        if ($nummods == 1) {
            return '';
        }

        // Get an array of just the course module ids used to get the cmid value based on their position in the course.
        $modids = array_keys($mods);

        // Get the position in the array of the course module we are viewing.
        $position = array_search($this->page->cm->id, $modids);

        $prevmod = null;
        $nextmod = null;

        // Check if we have a previous mod to show.
        if ($position > 0) {
            $prevmod = $mods[$modids[$position - 1]];
        }

        // Check if we have a next mod to show.
        if ($position < ($nummods - 1)) {
            $nextmod = $mods[$modids[$position + 1]];
        }

        $activitynav = new activity_navigation($prevmod, $nextmod, $activitylist);
        $renderer = $this->page->get_renderer('core', 'course');
        return $renderer->render($activitynav);
    }

    /**
     * Returns plugins callback renderable data to be printed on navbar.
     *
     * @return string Final html code.
     */
    public function get_navbar_callbacks_data()
    {
        $callbacks = get_plugins_with_function('moove_additional_header', 'lib.php');

        if (!$callbacks) {
            return '';
        }

        $output = '';

        foreach ($callbacks as $plugins) {
            foreach ($plugins as $pluginfunction) {
                if (function_exists($pluginfunction)) {
                    $output .= $pluginfunction();
                }
            }
        }

        return $output;
    }

    public function render_savy()
    {
        global $OUTPUT, $USER;
        $savy_data = [];

        // Set anonymous for test mode
        $savy_data['anonymous'] = boolval(get_config('theme_moove', 'savy_anonymous'));

        // Get the language
        $current_language = current_language();
        $is_glendon = ($USER->profile['facultyaffiliaton'] === 'GL');
        $savy_data['watson-button-icon'] = $OUTPUT->image_url($is_glendon && ($current_language == 'fr' || $current_language == 'fr_ca') ? 'bigsvaiconfr' : 'bigsvaicon', 'theme');

        // If not anon mode is disabled, you must have the sufficient data to form the payload
        if (!$savy_data['anonymous']) {
            try {
                $can_render_savy = savy::can_render_savy();
            } catch (Exception $e) {
                $can_render_savy = false;
            }

            if (!$can_render_savy) {
                return "";
            }
        }

        // Render savy payload
        try {
            $output = $this->render_from_template('/need_savy', $savy_data);
        } catch (Exception $e) {
            $output = '';
        }

        return $output;

    }

    public function render_dark_selector()
    {

        // Must be logged in
        global $USER, $DB;
        if (!($USER->id)) {
            return "";
        }

        $dark_enabled = false;
        if ($record = $DB->get_record('theme_moove', ['userid' => $USER->id], '*')) {
            $dark_enabled = $record->dark_enabled;
        }

        $context = [
            "graphic" => $dark_enabled ? "hollow_moon" : "filled_moon",
            "darkEnabled" => $dark_enabled
        ];

        return $this->render_from_template("/dark_mode", $context);
    }

    public function navbar_plugin_output(): string
    {
        return (parent::navbar_plugin_output()) . $this->render_dark_selector();
    }

    public function get_blocked_themes()
    {
        global $COURSE, $DB;
        $blockedThemes = '';
        //We have to iterate through all categories because this could be a sub category
        $category = $DB->get_record('course_categories', ['id' => $COURSE->category]);
        if ($category) {
            //Convert path into array, remove empty values and reverse
            $categoryPath = array_reverse(array_filter(explode('/', $category->path)));
            //Find themes that must be removed.
            //First category to have plugins blocked overrides parent category
            foreach ($categoryPath as $key => $categoryId) {
                $params = ['categoryid' => $categoryId, 'plugintype' => 'theme'];

                if ($blockedThemes = $DB->get_records('tool_catadmin_categoryplugin', $params)) {
                    break;
                }
            }
            //Get blocked themes
            $themes = '';
            if ($blockedThemes) {
                foreach ($blockedThemes as $bt) {
                    $themes .= trim($bt->pluginname) . ',';
                }
            }

            return rtrim($themes, ',');
        }
    }

    public function get_blocked_formats()
    {
        global $COURSE, $DB;
        $blockedFormats = '';
        //We have to iterate through all categories because this could be a sub category
        $category = $DB->get_record('course_categories', ['id' => $COURSE->category]);
        if ($category) {
            //Convert path into array, remove empty values and reverse
            $categoryPath = array_reverse(array_filter(explode('/', $category->path)));
            //Find themes that must be removed.
            //First category to have plugins blocked overrides parent category
            foreach ($categoryPath as $key => $categoryId) {
                $params = ['categoryid' => $categoryId, 'plugintype' => 'format'];

                if ($blockedFormats = $DB->get_records('tool_catadmin_categoryplugin', $params)) {
                    break;
                }
            }
            //Get blocked themes
            $formats = '';
            if ($blockedFormats) {
                foreach ($blockedFormats as $bf) {
                    $formats .= trim(str_replace('format_', '', $bf->pluginname)) . ',';
                }
            }

            return rtrim($formats, ',');
        }
    }

    public function get_blocked_blocks()
    {
        global $COURSE, $DB;
        $blockedBlocks = '';
        //We have to iterate through all categories because this could be a sub category
        $category = $DB->get_record('course_categories', ['id' => $COURSE->category]);
        if ($category) {
            //Convert path into array, remove empty values and reverse
            $categoryPath = array_reverse(array_filter(explode('/', $category->path)));
            //Find themes that must be removed.
            //First category to have plugins blocked overrides parent category
            foreach ($categoryPath as $key => $categoryId) {
                $params = ['categoryid' => $categoryId, 'plugintype' => 'block'];

                if ($blockedBlocks = $DB->get_records('tool_catadmin_categoryplugin', $params)) {
                    break;
                }
            }
            //Get blocked themes
            $blocks = '';
            if ($blockedBlocks) {
                foreach ($blockedBlocks as $bb) {
                    $blocks .= trim(str_replace('block_', '', $bb->pluginname)) . ',';
                }
            }

            return rtrim($blocks, ',');
        }
    }

    public function get_blocked_mods()
    {
        global $COURSE, $DB;
        $blockedMods = '';
        //We have to iterate through all categories because this could be a sub category
        $category = $DB->get_record('course_categories', ['id' => $COURSE->category]);
        if ($category) {
            //Convert path into array, remove empty values and reverse
            $categoryPath = array_reverse(array_filter(explode('/', $category->path)));
            //Find themes that must be removed.
            //First category to have plugins blocked overrides parent category
            foreach ($categoryPath as $key => $categoryId) {
                $params = ['categoryid' => $categoryId, 'plugintype' => 'mod'];

                if ($blockedMods = $DB->get_records('tool_catadmin_categoryplugin', $params)) {
                    break;
                }
            }
            //Get blocked themes
            $mods = '';
            if ($blockedMods) {
                foreach ($blockedMods as $bm) {
                    $mods .= trim(str_replace('block_', '', $bm->pluginname)) . ',';
                }
            }

            return rtrim($mods, ',');
        }
    }

    public function get_blocked_atto()
    {
        global $COURSE, $DB;
        $blockedAttos = '';
        //We have to iterate through all categories because this could be a sub category
        $category = $DB->get_record('course_categories', ['id' => $COURSE->category]);
        if ($category) {
            //Convert path into array, remove empty values and reverse
            $categoryPath = array_reverse(array_filter(explode('/', $category->path)));
            //Find themes that must be removed.
            //First category to have plugins blocked overrides parent category
            foreach ($categoryPath as $key => $categoryId) {
                $params = ['categoryid' => $categoryId, 'plugintype' => 'atto'];

                if ($blockedAttos = $DB->get_records('tool_catadmin_categoryplugin', $params)) {
                    break;
                }
            }
            //Get blocked themes
            $plugins = '';
            if ($blockedAttos) {
                foreach ($blockedAttos as $bm) {
                    $plugins .= trim(str_replace('atto', '', $bm->pluginname)) . ',';
                }
            }

            return rtrim($plugins, ',');
        }
    }

    public function is_staff()
    {
        global $USER;
        if (substr($USER->idnumber, 0, 1) === '1' || substr($USER->idnumber, 0, 1) === '5') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is there a user tour on this page
     *
     * @return true|void
     * @throws \dml_exception
     */
    public function get_user_tours()
    {
        global $DB, $CFG;

        $url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";;
        if (strpos($url, '?') !== false) {
            $url = strstr($url, '?', true);
        }
        // get all enabled tours
        $tours = $DB->get_records('tool_usertours_tours', ['enabled' => 1]);

        foreach ($tours as $t) {
            $tour = tourinstance::instance($t->id);
            // Clean path match
            $tour_pathmathch = str_replace('%', '', $tour->get_pathmatch());
            // Set path name based on pathmatch
            switch ($tour_pathmathch) {
                case 'FRONTPAGE':
                    $path_name = '/';
                    break;
                case 'FRONTPAGE_MY':
                    $path_name = '/my/';
                    break;
                default:
                    $path_name = $tour_pathmathch;
            }

            // Check to see if url equal path name
            // If it does return true
            if ($CFG->wwwroot . $path_name == $url) {
                return true;
            }
        }
        return false;

    }

    /**
     * Is user editing?
     * @return bool
     */
    public function user_is_editing()
    {
        global $PAGE;
        return $PAGE->user_is_editing();
    }

    /**
     * Returns config setting for Sleekplan product ID
     */
    public function get_sleekplanid()
    {
        global $CFG;
        if (isset($CFG->yorktasks_sleekplanproductid) && !empty($CFG->yorktasks_sleekplanproductid)) {
            return $CFG->yorktasks_sleekplanproductid;
        } else {
            return 'sleekplanidnotset';
        }
    }

}
