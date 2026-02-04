<?php
/**
 * Core functions for the SkillPassport local plugin.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the global ANCHOR_NAV_ID and adds a node for the Skill Passport dashboard.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @return void
 */
function local_skillpassport_extend_navigation(navigation_node $navigation) {
    global $USER, $CFG;

    require_once($CFG->libdir . '/accesslib.php');

    if (isloggedin() && !isguestuser($USER) && has_capability('local/skillpassport:view', context_system::instance())) {
        $url = new moodle_url('/local/skillpassport/index.php', ['userid' => $USER->id]);
        $navigation->add(
            get_string('pluginname', 'local_skillpassport'),
            $url,
            navigation_node::TYPE_CONTAINER,
            'skillpassport',
            'skillpassport',
            new pix_icon('i/grades', '')
        );
    }
}

/**
 * Extends the admin settings navigation for the Skill Passport plugin.
 *
 * @param settings_navigation $settingsnav The settings navigation object.
 * @param context_system $context The system context.
 * @return void
 */
function local_skillpassport_extend_settings_navigation(settings_navigation $settingsnav, context_system $context) {
    if (has_capability('local/skillpassport:manage', $context)) {
        $url = new moodle_url('/admin/settings.php', ['section' => 'local_skillpassport_settings']);
        $settingsnav->add(
            get_string('pluginname', 'local_skillpassport'),
            $url,
            navigation_node::TYPE_SETTING,
            'skillpassport',
            'skillpassport',
            new pix_icon('i/grades', '')
        );
    }
}

/**
 * Issues a new blockchain-verified credential.
 *
 * @param int $userid The ID of the user receiving the credential.
 * @param string $credentialtype The type of credential (course, activity, badge).
 * @param int|null $itemid ID of the related item (course, module, or badge).
 * @param string|null $blockchaintxhash Blockchain transaction hash if known.
 * @return stdClass|false Credential object on success, false on failure.
 * @throws moodle_exception
 */
function local_skillpassport_issue_credential(int $userid, string $credentialtype, ?int $itemid = null, ?string $blockchaintxhash = null) {
    global $DB;

    require_capability('local/skillpassport:issue', context_system::instance());

    if (empty($userid) || empty($credentialtype)) {
        throw new moodle_exception('errorissuecredential', 'local_skillpassport', '', null, 'Missing user ID or credential type.');
    }

    $credential = new stdClass();
    $credential->userid = $userid;
    $credential->credential_type = clean_param($credentialtype, PARAM_ALPHANUMEXT);
    $credential->timestamp = time();
    $credential->blockchain_txhash = clean_param($blockchaintxhash, PARAM_TEXT);
    $credential->timecreated = time();
    $credential->timemodified = time();

    switch ($credentialtype) {
        case 'course':
            if (empty($itemid)) {
                throw new moodle_exception('errorissuecredential', 'local_skillpassport', '', null, 'Missing course ID.');
            }
            $credential->courseid = $itemid;
            break;
        case 'activity':
            if (empty($itemid)) {
                throw new moodle_exception('errorissuecredential', 'local_skillpassport', '', null, 'Missing activity ID.');
            }
            $credential->cmid = $itemid;
            break;
        case 'badge':
            if (empty($itemid)) {
                throw new moodle_exception('errorissuecredential', 'local_skillpassport', '', null, 'Missing badge ID.');
            }
            $credential->badgeid = $itemid;
            break;
        default:
            throw new moodle_exception('errorissuecredential', 'local_skillpassport', '', null, 'Invalid credential type.');
    }

    // Generate dummy blockchain hash if none provided.
    if (empty($credential->blockchain_txhash)) {
        $credential->blockchain_txhash = '0x' . hash('sha256', uniqid($userid . $credentialtype . $itemid . time(), true));
    }

    try {
        $credential->id = $DB->insert_record('local_skillpassport_credentials', $credential);
        local_skillpassport_ai_trigger_recommendation($userid, $credential);
        return $credential;
    } catch (dml_exception $e) {
        debugging('Error inserting credential: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Retrieve all credentials for a user.
 *
 * @param int $userid
 * @return stdClass[]
 */
function local_skillpassport_get_user_credentials(int $userid): array {
    global $DB;
    return $DB->get_records('local_skillpassport_credentials', ['userid' => $userid], 'timestamp DESC');
}

/**
 * Retrieve a specific credential by ID.
 *
 * @param int $credentialid
 * @return stdClass|false
 */
function local_skillpassport_get_credential(int $credentialid) {
    global $DB;
    return $DB->get_record('local_skillpassport_credentials', ['id' => $credentialid]);
}

/**
 * Trigger AI recommendations for a credential.
 *
 * @param int $userid
 * @param stdClass $credential
 * @return bool
 */
function local_skillpassport_ai_trigger_recommendation(int $userid, stdClass $credential): bool {
    $ai_status = get_config('local_skillpassport', 'ai_integration_status_head');
    return !empty($ai_status);
}

/**
 * Mint an NFT badge for a credential.
 *
 * @param int $credentialid
 * @return stdClass|false
 * @throws moodle_exception
 */
function local_skillpassport_mint_nft_badge(int $credentialid) {
    global $DB;

    require_capability('local/skillpassport:issue', context_system::instance());

    $credential = local_skillpassport_get_credential($credentialid);
    if (!$credential) {
        throw new moodle_exception('errorissuecredential', 'local_skillpassport', '', null, 'Credential not found.');
    }

    $template = get_config('local_skillpassport', 'nft_metadata_template');
    $nft_metadata = json_decode($template, true);

    $user = $DB->get_record('user', ['id' => $credential->userid], '*', MUST_EXIST);

    $credential_name = '';
    switch ($credential->credential_type) {
        case 'course':
            $course = $DB->get_record('course', ['id' => $credential->courseid], 'fullname', MUST_EXIST);
            $credential_name = $course->fullname;
            break;
        case 'activity':
            $cm = get_coursemodule_from_id('coursemodule', $credential->cmid, 0, false, MUST_EXIST);
            $credential_name = $cm->name;
            break;
        case 'badge':
            $badge = $DB->get_record('badge', ['id' => $credential->badgeid], 'name', MUST_EXIST);
            $credential_name = $badge->name;
            break;
    }

    $replacements = [
        '{credential_name}' => s($credential_name),
        '{learner_name}' => fullname($user),
        '{issue_date}' => userdate($credential->timestamp),
        '{blockchain_network}' => get_config('local_skillpassport', 'blockchain_network'),
    ];

    foreach ($nft_metadata as $key => $value) {
        if (is_string($value)) {
            $nft_metadata[$key] = str_replace(array_keys($replacements), array_values($replacements), $value);
        }
    }

    $nftdata = new stdClass();
    $nftdata->credentialid = $credentialid;
    $nftdata->nft_metadata = json_encode($nft_metadata);
    $nftdata->tokenid = 'DEMO_TOKEN_' . uniqid();
    $nftdata->contractaddress = '0xDEMO_CONTRACT_ADDRESS';
    $nftdata->timecreated = time();
    $nftdata->timemodified = time();

    try {
        $nftdata->id = $DB->insert_record('local_skillpassport_nft', $nftdata);
        return $nftdata;
    } catch (dml_exception $e) {
        debugging('Error minting NFT: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Retrieve NFT for a credential.
 *
 * @param int $credentialid
 * @return stdClass|false
 */
function local_skillpassport_get_nft_for_credential(int $credentialid) {
    global $DB;
    return $DB->get_record('local_skillpassport_nft', ['credentialid' => $credentialid]);
}
