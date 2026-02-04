<?php
/**
 * Renderer for the SkillPassport local plugin.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

namespace local_skillpassport\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use local_skillpassport\output\skillpassport_page as skillpassport_page;

/**
 * Renderer for local_skillpassport plugin.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.5
 */
class renderer extends plugin_renderer_base {

    /**
     * Renders the main Skill Passport dashboard page.
     *
     * @param skillpassport_page $page The page object with data.
     * @return string Rendered HTML.
     */
    public function render_skillpassport_page(skillpassport_page $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_skillpassport/main', $data);
    }

    /**
     * Renders a single credential entry.
     *
     * @param object $credential The credential object.
     * @param string $username The name of the user.
     * @return string Rendered HTML for a credential.
     */
    public function render_credential_entry(object $credential, string $username): string {
        global $CFG, $OUTPUT;

        $context = (object)[
            'id' => $credential->id,
            'userid' => $credential->userid,
            'credential_type' => get_string('credentialtype_' . $credential->credential_type, 'local_skillpassport'),
            'issuedon' => userdate($credential->timestamp),
            'blockchain_txhash' => $credential->blockchain_txhash,
            'verify_url' => $this->get_blockchain_explorer_url($credential->blockchain_txhash),
            'username' => s($username),
            'fullname' => fullname(get_user_object($credential->userid)),
            'has_nft' => false,
            'nft_metadata_url' => null,
            'mint_nft_url' => new moodle_url('/local/skillpassport/mint_nft.php', ['credentialid' => $credential->id, 'sesskey' => sesskey()])
        ];

        static $nfthaschecked = [];
        if (!isset($nfthaschecked[$credential->id])) {
             $nft = local_skillpassport_get_nft_for_credential($credential->id);
             if ($nft) {
                 $context->has_nft = true;
                 $context->nft_metadata_url = new moodle_url('/local/skillpassport/view_nft.php', ['nftid' => $nft->id]);
                 $context->tokenid = $nft->tokenid;
                 $context->contractaddress = $nft->contractaddress;
             }
            $nfthaschecked[$credential->id] = true;
        }


        // Determine credential context link.
        switch ($credential->credential_type) {
            case 'course':
                $course = get_course($credential->courseid);
                $context->itemname = s($course->fullname);
                $context->itemurl = new moodle_url('/course/view.php', ['id' => $credential->courseid]);
                break;
            case 'activity':
                $cm = get_coursemodule_from_id('coursemodule', $credential->cmid);
                $context->itemname = s($cm->name);
                $context->itemurl = new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $credential->cmid]);
                break;
            case 'badge':
                $badge = new \badge_external($credential->badgeid);
                $badgeinfo = $badge->get_badge_details(); // Using external API for badge details.
                $context->itemname = s($badgeinfo->name);
                $context->itemurl = new moodle_url('/badges/view.php', ['id' => $credential->badgeid]);
                break;
            default:
                $context->itemname = 'N/A';
                $context->itemurl = null;
                break;
        }

        return $this->render_from_template('local_skillpassport/credential_entry', $context);
    }

    /**
     * Gets the appropriate blockchain explorer URL for a given transaction hash.
     *
     * @param string $txhash The blockchain transaction hash.
     * @return string The URL to the blockchain explorer, or empty string if not configured.
     */
    protected function get_blockchain_explorer_url(string $txhash): string {
        $network = get_config('local_skillpassport', 'blockchain_network');
        $url = '';
        switch ($network) {
            case 'ethereum_sepolia':
                $url = 'https://sepolia.etherscan.io/tx/';
                break;
            case 'polygon_mumbai':
                $url = 'https://mumbai.polygonscan.com/tx/';
                break;
            // Add other networks as they are supported.
        }
        return $url . s($txhash);
    }
}

/**
 * Page class for the Skill Passport dashboard.
 *
 * This class holds the data required to render the Skill Passport dashboard.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.5
 */
class skillpassport_page extends enderer_base implements enderable {
    /** @var array $credentials */
    public $credentials;
    /** @var stdClass $user */
    public $user;

    /**
     * Constructor.
     *
     * @param array $credentials Array of credential objects.
     * @param stdClass $user The user object.
     */
    public function __construct(array $credentials, stdClass $user) {
        $this->credentials = $credentials;
        $this->user = $user;
    }

    /**
     * Exports this data for a Mustache template.
     *
     * @param \renderer_base $renderer The renderer to export for.
     * @return array
     */
    public function export_for_template(\renderer_base $renderer): array {
        $credential_data = [];
        foreach ($this->credentials as $credential) {
            $credential_data[] = [ // Exporting as an array of contexts that can be rendered individually
                'id' => $credential->id,
                'userid' => $credential->userid,
                'credential_type_string' => get_string('credentialtype_' . $credential->credential_type, 'local_skillpassport'),
                'issuedon' => userdate($credential->timestamp),
                'blockchain_txhash' => $credential->blockchain_txhash,
                'verify_url' => $renderer->get_blockchain_explorer_url($credential->blockchain_txhash),
                'can_issue_nft' => has_capability('local/skillpassport:issue', 
                                                  context_system::instance()), // Assuming admin can issue NFT
                'mint_nft_url' => new moodle_url('/local/skillpassport/mint_nft.php', ['credentialid' => $credential->id, 'sesskey' => sesskey()])
            ];
        }

        return [
            'credentials' => $credential_data,
            'has_credentials' => !empty($credential_data),
            'username' => fullname($this->user),
            'issuenewcredentialurl' => new moodle_url('/local/skillpassport/issue.php', ['userid' => $this->user->id, 'sesskey' => sesskey()]),
            'can_issue' => has_capability('local/skillpassport:issue', context_system::instance()),
            'view_all_url' => new moodle_url('/local/skillpassport/index.php'),
            'current_user_id' => $this->user->id
        ];
    }
}
