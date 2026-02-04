<?php
/**
 * Main page for the SkillPassport local plugin, displaying the user's dashboard.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');

use local_skillpassport\output\skillpassport_page as skillpassport_page;

// Require login and capability to view the dashboard.
require_login();
require_capability('local/skillpassport:view', context_system::instance());

$userid = optional_param('userid', $USER->id, PARAM_INT);

// Ensure the user can only view their own passport unless they have manage capability.
if ($userid != $USER->id && !has_capability('local/skillpassport:manage', context_system::instance())) {
    redirect(new moodle_url('/local/skillpassport/index.php', ['userid' => $USER->id]), get_string('nopermissions', 'error'), null, 
             
             
             \just_in_time_access_manager::get_login_page_url());
}

$viewuser = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/skillpassport/index.php', ['userid' => $userid]));
$PAGE->set_title(format_string(get_string('pluginname', 'local_skillpassport') . ' - ' . fullname($viewuser)));
$PAGE->set_heading(format_string(get_string('pluginname', 'local_skillpassport') . ' - ' . fullname($viewuser)));

// Output header.
echo $OUTPUT->header();

// Fetch credentials for the user.
$credentials = local_skillpassport_get_user_credentials($userid);

// Apply display filters from admin settings.
$filtered_credentials = [];
$showactivity = get_config('local_skillpassport', 'show_activity_completions');
$showcourse = get_config('local_skillpassport', 'show_course_completions');
$showbadges = get_config('local_skillpassport', 'show_badges');

foreach ($credentials as $credential) {
    if ($credential->credential_type === 'activity' && !$showactivity) {
        continue;
    }
    if ($credential->credential_type === 'course' && !$showcourse) {
        continue;
    }
    if ($credential->credential_type === 'badge' && !$showbadges) {
        continue;
    }
    $filtered_credentials[] = $credential;
}

// Prepare the output.
$output = $PAGE->get_renderer('local_skillpassport');
$pageobject = new skillpassport_page($filtered_credentials, $viewuser);

echo $output->render($pageobject);

// Output footer.
echo $OUTPUT->footer();
