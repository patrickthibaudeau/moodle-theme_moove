<?php

namespace theme_moove\util;

class savy
{

    public static function exec_embed($payload)
    {
        // Get the plugin configuration
        $config = get_config('theme_moove');

        // Set the URL
        $bot_id = $config->savy_bot_id;
        $api_key = $config->savy_cria_api_key;
        $url = $config->cria_embed_url . '/embed/' . $bot_id . '/load?hideLauncher=true';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'accept: application/javascript',
                'X-Api-Key: ' . $api_key,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;

    }

    /**
     * Checks if the user can render the savy button.
     *
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function can_render_savy()
    {
        global $CFG, $USER, $DB;

        // Early bail out conditions from render_watson.
        if (!isloggedin() || isguestuser() || user_not_fully_set_up($USER) || get_user_preferences('auth_forcepasswordchange')) {
            return false;
        }

        // Policy agreement check.
        if (!$USER->policyagreed && !is_siteadmin()) {
            $manager = new \core_privacy\local\sitepolicy\manager();
            if ($manager->is_defined()) {
                return false;
            }
        }

        // Faculty affiliation check.
        if (isset($USER->profile['facultyaffiliaton']) && !empty($USER->profile['facultyaffiliaton'])) {
            if (!in_array($USER->profile['facultyaffiliaton'], explode(",", $CFG->yorktasks_yubiefacs))) {
                return false;
            }
        }

        // Oracle settings check.
        if (isset($CFG->yorktasks_sishost)) {
            if (!$CFG->yorktasks_sishost || !$CFG->yorktasks_sisport || !$CFG->yorktasks_sissid || !$CFG->yorktasks_sisuser || !$CFG->yorktasks_sispass) {
                return false;
            }
        }

        // Check if user has an idnumber.
        if (!$USER->idnumber) {
            return false;
        }

        // Check if yorktasks plugin is installed.
        $plugins = \core_plugin_manager::instance()->get_plugins_of_type('local');
        $yorktasks_exists = false;
        foreach ($plugins as $plugin => $pluginobject) {
            if ($plugin == 'yorktasks') {
                $yorktasks_exists = true;
                break;
            }
        }

        if (!$yorktasks_exists) {
            return false;
        }

        // Check if there is course data in the 'svadata' table.
        if (!$DB->record_exists('svadata', array('sisid' => $USER->idnumber))) {
            return false;
        }

        // If all checks pass, there is sufficient data.
        return true;
    }

    public static function get_savy_payload($anonymous): ?array
    {

        // If anonymous, return a dummy payload.
        if ($anonymous) {
            return [
                'anonymous' => true,
                'user' => [
                    'id' => 0,
                    'name' => 'Anonymous',
                    'email' => '',
                    'role' => 'anonymous',
                ]
            ];
        }

        global $CFG, $USER, $OUTPUT, $DB;
        // Early bail out conditions.
        if (!isloggedin() || isguestuser() || user_not_fully_set_up($USER) || get_user_preferences('auth_forcepasswordchange') || (!$USER->policyagreed && !is_siteadmin() && ($manager = new \core_privacy\local\sitepolicy\manager()) && $manager->is_defined())) {
            return null;
        }

        // Add the messages popover.
        // replace with check for faculty
        if (isset($USER->profile['facultyaffiliaton']) && !empty($USER->profile['facultyaffiliaton'])) {
            if (in_array($USER->profile['facultyaffiliaton'], explode(",", $CFG->yorktasks_yubiefacs))) {
                //faculty found, show 'em yubie!
            } else {
                //faculty not found in list, so don't show
                return null;
            }
        } elseif (isset($USER->profile['usertypes']) && ((strpos($USER->profile['usertypes'], 'professor') !== false) || (strpos($USER->profile['usertypes'], 'staff')))) {
            //this is a faculty or staff user, show 'em yubie!
        }
        $context = \context_system::instance();
        $theme = \theme_config::load('moove');
        $current_language = current_language();

        //if Oracle settings have not been set
        if (isset($CFG->yorktasks_sishost)) {
            if (!$CFG->yorktasks_sishost || !$CFG->yorktasks_sisport || !$CFG->yorktasks_sissid || !$CFG->yorktasks_sisuser || !$CFG->yorktasks_sispass) {
                return null;
            }
        }
        //If watson settings have not been set
        // EAM - Added watson integration... kinda
        if ($USER->idnumber) {
            // Check to see if yorktasks plugin is installed
            $plugins = \core_plugin_manager::instance()->get_plugins_of_type('local');
            $yorktasks_exists = false;
            foreach ($plugins as $plugin => $pluginobject) {
                if ($plugin == 'yorktasks') {
                    $yorktasks_exists = true;
                }
            }
            // If yortaks plugin is installed, then use it to get the data
            if ($yorktasks_exists) {
                $watsondata = array();
                if ($coursedata = $DB->get_records('svadata', array('sisid' => $USER->idnumber))) {

                    //found course data so set 'registeredactive' to true, then process the courses
                    $watsondata['registeredactive'] = 'true';
                    $courses = array();
                    $subjects = array();

                    foreach ($coursedata as $course) {
                        $userinfo = $course;
                        $courses[] = array(
                            'uniqueid' => htmlentities($course->uniqueid),
                            'id' => htmlentities($course->courseid),
                            'title' => htmlentities($course->title),
                            'campus' => htmlentities($course->campus),
                            'period' => htmlentities($course->period),
                            'session' => htmlentities($course->studysession) . htmlentities($course->academicyear),
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

                    if (count($subjects) == 1) {
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
                $watsondata['moodleid'] = hash("sha256", $USER->idnumber) ?? '';
                //make this detect automatically?
                $watsondata['isglendon'] = false;
                $watsondata['firstname'] = $USER->firstname;
                $watsondata['commonname'] = $userinfo->commonname ?? ''; //If isset write info otherwise blank
                $watsondata['idnumber'] = preg_replace("/[^0-9]/", "", hash("sha256", $USER->idnumber));

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

                if (isset($USER->profile['usertypes'])) {
                    if (strpos($USER->profile['usertypes'], 'student') !== false) {
                        $watsondata['usertype'] = 'student';
                    } elseif (strpos($USER->profile['usertypes'], 'professor') !== false) {
                        $watsondata['usertype'] = 'professor';
                    } elseif (strpos($USER->profile['usertypes'], 'staff') !== false) {
                        $watsondata['usertype'] = 'staff';
                    } else {
                        $watsondata['usertype'] = 'student';
                    }
                } else {
                    $watsondata['usertype'] = 'student';
                }
                $watsondata['brand'] = $brand;
                $watsondata['bigwatsonicon'] = $bigimg;
                return $watsondata;

            }
        }

        return null;
    }
}