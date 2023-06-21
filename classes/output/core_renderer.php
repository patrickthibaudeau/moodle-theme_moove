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

use core_scss;
use theme_config;
use context_course;
use moodle_url;
use html_writer;
use theme_moove\output\core_course\activity_navigation;


/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {

    public function get_mode_stylesheet() {
        global $DB, $USER;

        $record = $DB->get_record('theme_moove', ['userid' => $USER->id], '*');
        $dark_enabled = $record->dark_enabled;

        $mode = $dark_enabled ? 'dark' : 'normal';

        return "/theme/moove/layout/theme_css.php?mode=$mode";
    }

    /**
     * The standard tags (meta tags, links to stylesheets and JavaScript, etc.)
     * that should be included in the <head> tag. Designed to be called in theme
     * layout.php files.
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {

        // Load standard
        $output = parent::standard_head_html();

        /*
         * This commented line was left in to note what NOT to do.
         * Removing the /all/ base stylesheet will break certain things (file picker, color picker, etc.).
         * Leave the stylesheet in there as a FALLBACK in case our inline one is missing some stuff
         * (It seems it is due to SCSS being added to the /all/ file somewhere in the moodle core out of our control)
         *
         * $output = preg_replace('/http:\/\/.*\/moove\/.*\/all/', '', $output);
         */

        $theme = theme_config::load("moove");
        $output .= '<link rel="stylesheet" type="text/css" href="' .  $this->get_mode_stylesheet() . '">';

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
            <link href="https://fonts.googleapis.com/css2?family=
        ') . $sitefont . ':ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">';

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
    public function body_attributes($additionalclasses = array()) {
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

        return ' id="'. $this->body_id().'" class="'.$this->body_css_classes($additionalclasses).'"';
    }

    /**
     * Whether we should display the main theme or site logo in the navbar.
     *
     * @return bool
     */
    public function should_display_logo() {
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
    public function should_display_theme_logo() {
        $logo = $this->get_theme_logo_url();

        return !empty($logo);
    }

    /**
     * Get the main logo URL.
     *
     * @return string
     */
    public function get_logo() {
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
    public function get_theme_logo_url() {
        $theme = theme_config::load('moove');

        return $theme->setting_file_url('logo', 'logo');
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
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
    public function supportemail(array $customattribs = []): string {
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
     * @since Moodle 2.5.1 2.6
     * @return moodle_url The moodle_url for the favicon
     */
    public function favicon() {
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
    protected function render_context_header(\context_header $contextheader) {
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
            $html .= html_writer::div($contextheader->imagedata, 'page-header-image mr-2');
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
    public function activity_navigation() {
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
    public function get_navbar_callbacks_data() {
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

    public function render_watson() {
        global $CFG, $USER, $OUTPUT, $DB;
        // Early bail out conditions.

        if (!in_array($USER->idnumber,array('102102735','102051899','102109330','102086179','102079620'))){
            return '';
        }
        if (!isloggedin() || isguestuser() || user_not_fully_set_up($USER) || get_user_preferences('auth_forcepasswordchange') || (!$USER->policyagreed && !is_siteadmin() && ($manager = new \core_privacy\local\sitepolicy\manager()) && $manager->is_defined())) {
            return '';
        }

        $output = '';

        // Add the messages popover.
        //replace with check for faculty
        if (isset($USER->profile['facultyaffiliaton']) && !empty($USER->profile['facultyaffiliaton'])) {
            if (in_array($USER->profile['facultyaffiliaton'], explode(",", $CFG->yorktasks_yubiefacs))) {
                //faculty found, show 'em yubie!
            } else {
                //faculty not found in list, so don't show
                return '';
            }
        } elseif (isset($USER->profile['usertypes']) && ((strpos($USER->profile['usertypes'], 'professor') !== false) || (strpos($USER->profile['usertypes'], 'staff')))) {
            //this is a faculty or staff user, show 'em yubie!
        }
        $context = \context_system::instance();

        $theme = \theme_config::load('moove');
        $current_language = current_language();

        //if Oracle settings have not been set
        if (!$CFG->yorktasks_sishost || !$CFG->yorktasks_sisport || !$CFG->yorktasks_sissid || !$CFG->yorktasks_sisuser || !$CFG->yorktasks_sispass) {
            return '';
        }
        //If watson seetings have not been set
       // if (!$CFG->yorktasks_watsonapiendpoint || !$CFG->yorktasks_watsonapikey || !$CFG->yorktasks_watsonchecksrc) {
       //     return '';
       // }
        // EAM - Added watson integration... kinda
        if ($USER->idnumber) {
            $watsondata = array();

            if ($coursedata = $DB->get_records('svadata', array('sisid' => $USER->idnumber))){
                //found course data so set 'registeredactive' to true, then process the courses
                $watsondata['registeredactive'] = 'true';
                $courses = array();
                $subjects = array();
                foreach ($coursedata as $course){
                    $userinfo = $course;
                    $courses[] = array(
                        'uniqueid' => htmlentities($course->uniqueid) ,
                        'id' => htmlentities($course->courseid) ,
                        'title' => htmlentities($course->title) ,
                        'campus' => htmlentities($course->campus) ,
                        'period' => htmlentities($course->period) ,
                        'session' => htmlentities($course->studysession) . htmlentities($course->academicyear) ,
                        'faculty' => htmlentities($course->faculty)
                    );
                    $subjects[$course->seqpersprog] = array(
                        'desc' => $course->description,
                        'title1' => $course->subtitle1,
                        'subject1' => $course->subject1,
                        'unit1' => $course->unit1,
                        'subject1facultydesc' => $course->subject1facultydesc,
                        'subject1faculty' => $course->subject1faculty,
                        'title2' => $course->subtitle2,
                        'subject2' => $course->subject2,
                        'unit2' => $course->unit2,
                        'subject2facultydesc' => $course->subject2facultydesc,
                        'subject2faculty' => $course->subject2faculty,
                    );
                    // ED Sep 9th, 2020 putting this here on purpose, just care about the last progfaculty to get set, adding progfaculty for the EU faculty name change just until 2021
                    $watsondata['progfaculty'] = $course->progfaculty;
                }
                //sort subjects from most recent to oldest
                krsort($subjects);
                if (count($subjects) == 1){
                    //only one found, don't pass a comma for the json data
                    $watsondata['onesubject'] = true;
                    $tempsubjects = array();
                    foreach ($subjects as $k => $v) {
                        $tempsubjects[] = $v;
                    }
                    $subjects = $tempsubjects;
                } elseif (count($subjects) > 1) {
                    //multiple found, make sure you pass a comma for all subjects except the last one
                    $watsondata['moresubjects'] = true;
                    $i = 1;
                    $tempsubjects = array();
                    foreach ($subjects as $k => $v) {
                        if ($i == count($subjects)) {
                            $lastsubject[] = $v;
                            unset($subjects[$k]);
                        } else {
                            $tempsubjects[] = $v;
                        }
                        $i++;
                    }
                    $subjects = $tempsubjects;
                    $watsondata['lastsubject'] = $lastsubject;
                }
                $watsondata['subjects'] = $subjects;
                $i = 1;
                foreach ($courses as $k => $v) {
                    if ($i == count($courses)) {
                        $lastcourse[] = $v;
                        unset($courses[$k]);
                    }
                    $i++;
                }
            } else {
                //no course data found in svadata so set 'registeredactive' to false and pass empty subjects data
                $watsondata['registeredactive'] = 'false';
                $watsondata['subjects'] = '';
            }
            if ($USER->profile['facultyaffiliaton'] === 'GL' && ($current_language == 'fr' || $current_language == 'fr_ca')) {
                $lang = 'fr';
                $brand = $CFG->yorktasks_watsonbrandfr;
                $adabrand = $CFG->yorktasks_adabrandfr;
                $bigimg = $OUTPUT->image_url('bigsvaiconfr', 'theme');
            } else {
                $lang = 'en';
                $brand = $CFG->yorktasks_watsonbranden;
                $adabrand = $CFG->yorktasks_adabranden;
                $bigimg = $OUTPUT->image_url('bigsvaicon', 'theme');
            }
            $smallimg = $OUTPUT->image_url('smallsvaicon', 'theme');
            $endpoint = $CFG->yorktasks_watsonapiendpoint . $CFG->yorktasks_watsonfilepath;
            $watsondata['userid'] = $USER->id;
            $watsondata['apikey'] = $CFG->yorktasks_watsonapikey;
            $watsondata['endpointurl'] = $endpoint;
            $watsondata['moodleid'] = hash("sha256", $USER->idnumber) ??'';
            //make this detect automatically?
            $watsondata['isglendon'] = false;
            $watsondata['firstname'] = $USER->firstname;
            $watsondata['commonname'] = $userinfo->commonname ?? ''; //If isset write info otherwise blank
            $watsondata['idnumber'] = preg_replace("/[^0-9]/","",hash("sha256",$USER->idnumber));
            $watsondata['isinternational'] = $userinfo->isinternational ?? '';
            $watsondata['studylevel'] = $userinfo->studylevel ?? '';
            //$watsondata['language'] = $userinfo->language ?? '';
            $watsondata['collegeaffiliation'] = $userinfo->collegeaffiliation ?? '';
            $watsondata['courses'] = $courses ?? '';
            $watsondata['lastcourse'] = $lastcourse ?? '';
            $watsondata['language'] = $lang;
            $watsondata['smallwatsonicon'] = $smallimg;
            $watsondata['unsupported_browser'] = get_string('unsupported_browser', 'theme_edyucate');
            $watsondata['popup_enabled_text'] = get_string('popup_enabled_text', 'theme_edyucate');
            $watsondata['quiz_help'] = get_string('quiz_help', 'theme_edyucate');

            if (isset($USER->profile['usertypes'])){
                if (strpos($USER->profile['usertypes'], 'student') !== false){
                    $watsondata['usertype'] = 'student';
                } elseif (strpos($USER->profile['usertypes'], 'professor') !== false){
                    $watsondata['usertype'] = 'professor';
                } elseif (strpos($USER->profile['usertypes'], 'staff') !== false){
                    $watsondata['usertype'] = 'staff';
                } else {
                    $watsondata['usertype'] = 'student';
                }
            } else {
                $watsondata['usertype'] = 'student';
            }
            switch ($watsondata['usertype']){
                case "professor":
                case "staff":
//                    $watsondata['brand'] = $adabrand;
//                    $bigimg = $OUTPUT->image_url('bigadaicon', 'theme');
//                    $watsondata['bigwatsonicon'] = $bigimg;
//                    $output .= $this->render_from_template('/need_ada', $watsondata);
//                    break;
                case "student":
                default:
                    $watsondata['brand'] = $brand;
                    $watsondata['bigwatsonicon'] = $bigimg;
                    $output .= $this->render_from_template('/need_watson', $watsondata);
                    break;
            }
        }
        return $output;
    }

    public function render_dark_selector() {

        // Must be logged in
        global $USER, $DB;
        if (!($USER->id)) {
            return "";
        }

        $record = $DB->get_record('theme_moove', ['userid' => $USER->id], '*');
        $dark_enabled = $record->dark_enabled;

        $context = [
            "graphic" => $dark_enabled ? "hollow_moon" : "filled_moon",
        ];

        return $this->render_from_template("/dark_mode", $context);
    }

    public function navbar_plugin_output(): string {
        return (parent::navbar_plugin_output()) . $this->render_dark_selector();
    }
}
