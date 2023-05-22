<?php
use \local_yulearn\YULearnUser;
use \local_yulearn\Employee;

global $CFG, $DB, $PAGE, $OUTPUT, $USER;
// Get users positions
$activePositions = $DB->get_records(\local_yulearn\YULearn::TABLE_EMPLOYEE, ['userid' => $USER->id, 'deleted' => 0, 'active' => true]);
$hrbpRole = $DB->get_record('role', ['shortname' => 'yulearn_hrbp']);
$context = context_system::instance();

// depending on the role, start the navigation order differently
if (is_siteadmin($USER->id)) {
    $CFG->custommenuitems .= "-" . get_string('more') . "|\n";
    $CFG->custommenuitems .= "-" . get_string('my_training_history', 'local_yulearn') . "|/local/yulearn/reports/employee_training_history.php\n";
} else {
    $CFG->custommenuitems .= "-" . get_string('my_training_history', 'local_yulearn') . "|/local/yulearn/reports/employee_training_history.php\n";
    $CFG->custommenuitems .= "-" . get_string('more') . "|\n";
}

// Add the learning opportunities
$CFG->custommenuitems .= "-" . get_string('learning_opportunities', 'local_yulearn') . "|/local/yulearn/course_schedules.php\n";

// If the user is a manager, add the
foreach ($activePositions as $ap) {
    $EMPLOYEE = new Employee($ap->id);
    $isManager = $EMPLOYEE->getIsManager();
    if ($isManager) {
        $CFG->custommenuitems .= "-" . get_string('my_team', 'local_yulearn') . "|/local/yulearn/reports/employee_training_history.php\n";
        break;
    }
}

// If user has YU Learn settings, add administration link
if (
    has_capability('local/yulearn:course_view', $context) ||
    has_capability('local/yulearn:program_view', $context) ||
    has_capability('local/yulearn:schedule_view', $context) ||
    has_capability('local/yulearn:certificate_view', $context)
) {
    $CFG->custommenuitems .= "-" . get_string('program_administration', 'local_yulearn') . "|/local/yulearn/admin/scheduledcourses.php\n";
}

// If user has capability HRBP, Add HRBP link
if (user_has_role_assignment($USER->id, $hrbpRole->id, $context->id)) {
    $CFG->custommenuitems .= "-" . get_string('hrbp_view', 'local_yulearn') . "|/local/yulearn/admin/hrbp_employees.php\n";
}

// If user has capability seetings, Add organization settings link
if (has_capability('local/yulearn:settings_view', $context)) {
    $CFG->custommenuitems .= "-" . get_string('company_settings', 'local_yulearn') . "|/local/yulearn/admin/settings.php\n";
}