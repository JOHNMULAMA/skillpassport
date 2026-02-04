<?php
/**
 * Page for minting an NFT badge for a credential in the SkillPassport local plugin.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

require_once('../../config.php');
require_once('lib.php');

// Require login and capability to issue credentials (which includes minting NFTs).
require_login();
require_capability('local/skillpassport:issue', context_system::instance());
require_sesskey(); // Crucial for protecting against CSRF

$credentialid = required_param('credentialid', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/skillpassport/mint_nft.php', ['credentialid' => $credentialid]));
$PAGE->set_title(get_string('mintnftbadge', 'local_skillpassport'));
$PAGE->set_heading(get_string('mintnftbadge', 'local_skillpassport'));

// Output header.
echo $OUTPUT->header();

try {
    if (local_skillpassport_mint_nft_badge($credentialid)) {
        // Success message.
        $message = get_string('credentialniftminted', 'local_skillpassport', $credentialid);
        $redirecturl = new moodle_url('/local/skillpassport/index.php');
        redirect($redirecturl, $message, 3);
    } else {
        
        
        throw new moodle_exception('errorcredentialniftminting', 'local_skillpassport', '', null, $credentialid);
    }
} catch (moodle_exception $e) {
    print_error($e->getMessage(), $e->get_component(), new moodle_url('/local/skillpassport/index.php'));
}

echo $OUTPUT->footer();
