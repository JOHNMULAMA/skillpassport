<?php
/**
 * Page for issuing new blockchain-verified credentials for the SkillPassport local plugin.
 *
 * This page allows authorized users (like managers or teachers) to manually issue new credentials.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

require_once('../../config.php');
require_once($CFG->libdir . '/formlib.php');
require_once('lib.php');

// Require login and capability to issue credentials.
require_login();
require_capability('local/skillpassport:issue', context_system::instance());

$userid = optional_param('userid', 0, PARAM_INT); // Optionally pre-fill user ID

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/skillpassport/issue.php', ['userid' => $userid]));
$PAGE->set_title(get_string('issuecredential', 'local_skillpassport'));
$PAGE->set_heading(get_string('issuecredential', 'local_skillpassport'));

// Define a Moodle form for issuing credentials.
class local_skillpassport_issue_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('issuecredential', 'local_skillpassport'));

        $mform->addElement('select', 'userid', get_string('user'), $DB->get_records_menu('user', null, 'firstname, lastname', 'id, CONCAT(firstname, " ", lastname)'));
        $mform->addRule('userid', get_string('required'), 'required');
        $mform->setDefault('userid', $this->_customdata['userid']);

        $credential_types = [
            'course' => get_string('credentialtype_course', 'local_skillpassport'),
            'activity' => get_string('credentialtype_activity', 'local_skillpassport'),
            'badge' => get_string('credentialtype_badge', 'local_skillpassport'),
        ];
        $mform->addElement('select', 'credentialtype', get_string('credentialtype', 'local_skillpassport'), $credential_types);
        $mform->addRule('credentialtype', get_string('required'), 'required');

        $mform->addElement('text', 'courseid', get_string('courseid'), ['size' => '10']);
        $mform->setType('courseid', PARAM_INT);
        $mform->addHelpButton('courseid', 'courseid', 'local_skillpassport');

        $mform->addElement('text', 'cmid', get_string('activityid', 'local_skillpassport'), ['size' => '10']);
        $mform->setType('cmid', PARAM_INT);
        $mform->addHelpButton('cmid', 'activityid', 'local_skillpassport');

        $mform->addElement('text', 'badgeid', get_string('badgeid', 'local_skillpassport'), ['size' => '10']);
        $mform->setType('badgeid', PARAM_INT);
        $mform->addHelpButton('badgeid', 'badgeid', 'local_skillpassport');

        $mform->addElement('text', 'blockchain_txhash', get_string('blockchaintxhash', 'local_skillpassport'), ['size' => '64']);
        $mform->setType('blockchain_txhash', PARAM_TEXT);
        $mform->addHelpButton('blockchain_txhash', 'blockchaintxhash', 'local_skillpassport');

        $this->add_action_buttons();
    }

    // Client-side validation for dynamically showing/hiding item ID fields.
    public function definition_after_data() {
        $mform = $this->_form;
        $credentialtype = $mform->getElementValue('credentialtype');

        if ($credentialtype[0] === 'course') {
            $mform->hideIf('cmid', 'credentialtype', 'neq', 'course');
            $mform->hideIf('badgeid', 'credentialtype', 'neq', 'course');
            $mform->addRule('courseid', get_string('required'), 'required');
        } else if ($credentialtype[0] === 'activity') {
            $mform->hideIf('courseid', 'credentialtype', 'neq', 'activity');
            $mform->hideIf('badgeid', 'credentialtype', 'neq', 'activity');
            $mform->addRule('cmid', get_string('required'), 'required');
        } else if ($credentialtype[0] === 'badge') {
            $mform->hideIf('courseid', 'credentialtype', 'neq', 'badge');
            $mform->hideIf('cmid', 'credentialtype', 'neq', 'badge');
            $mform->addRule('badgeid', get_string('required'), 'required');
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Server-side validation for item IDs based on credential type.
        if ($data['credentialtype'] === 'course' && empty($data['courseid'])) {
            $errors['courseid'] = get_string('required');
        }
        if ($data['credentialtype'] === 'activity' && empty($data['cmid'])) {
            $errors['cmid'] = get_string('required');
        }
        if ($data['credentialtype'] === 'badge' && empty($data['badgeid'])) {
            $errors['badgeid'] = get_string('required');
        }

        return $errors;
    }
}

// Create the form instance.
$mform = new local_skillpassport_issue_form(null, ['userid' => $userid]);

// If data is submitted and valid.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/skillpassport/index.php'));
} else if ($fromform = $mform->get_data()) {
    require_sesskey();

    $itemid = null;
    switch ($fromform->credentialtype) {
        case 'course':
            $itemid = $fromform->courseid;
            break;
        case 'activity':
            $itemid = $fromform->cmid;
            break;
        case 'badge':
            $itemid = $fromform->badgeid;
            break;
    }

    try {
        if (local_skillpassport_issue_credential(
            $fromform->userid,
            $fromform->credentialtype,
            $itemid,
            $fromform->blockchain_txhash
        )) {
            // Display success message.
            
            
            // You can use a specific success string too.
            // Redirect to the user's skill passport dashboard.
             redirect(new moodle_url('/local/skillpassport/index.php', ['userid' => $fromform->userid]), get_string('credentialissued', 'local_skillpassport'), 
                     
                     
                     
                     
                     
                     
                     
                     3)
;
        } else {
            
            
            // Redirect back to the form with an error.
            
            
            
            
            throw new moodle_exception('errorissuecredential', 'local_skillpassport');
        }
    } catch (moodle_exception $e) {
        // Display error message.
        
        
        // Redirect back to the form with an error.
        
        
        
        
        print_error($e->getMessage(), $e->get_component());
    }
}

// Output header.
echo $OUTPUT->header();

// Display the form.
$mform->display();

// Output footer.
echo $OUTPUT->footer();
