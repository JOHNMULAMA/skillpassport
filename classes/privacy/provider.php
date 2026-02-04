<?php
/**
 * Privacy provider for the SkillPassport local plugin.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

namespace local_skillpassport\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\context_all_userlist_provider;
use core_privacy\local\request\not_found_exception;
use core_privacy\local\request\user_preference_provider;
use core_privacy\local\request\user_private_data_provider;
use core_privacy\local\request\user_request;

/**
 * Privacy provider for local_skillpassport.
 *
 * Provides information about data stored by the plugin that relates to users.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.5
 */
class provider implements context_all_userlist_provider, user_private_data_provider, user_preference_provider {

    /**
     * Returns the list of contexts for a user where data is stored.
     *
     * Since skillpassport credentials are at the system level for a user, specify CONTEXT_SYSTEM.
     *
     * @param int $userid The user to get the list of contexts for.
     * @return approved_contextlist
     */
    public static function get_contexts_for_userid(int $userid): approved_contextlist {
        global $DB;

        $contextlist = new approved_contextlist();

        if ($DB->record_exists('local_skillpassport_credentials', ['userid' => $userid])) {
            $contextlist->add_context(context_system::instance());
        }

        return $contextlist;
    }

    /**
     * Returns the list of information that is stored for the specified user.
     *
     * @param user_request $request The user request to action.
     * @return array|
ull
     */
    public static function get_user_private_data(user_request $request) {
        global $DB;

        $userid = $request->get_userid();
        $context = $request->get_context();

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            throw new not_found_exception('Non-system context encountered during data export.');
        }

        // Retrieve all credentials for the user.
        $credentials = $DB->get_records('local_skillpassport_credentials', ['userid' => $userid], 'timecreated DESC');
        $data = [];

        foreach ($credentials as $credential) {
            $credentialdata = [
                'id' => $credential->id,
                'credential_type' => $credential->credential_type,
                'timestamp' => userdate($credential->timestamp, get_string('strftimedatetime')), // Format for readability
                'blockchain_txhash' => $credential->blockchain_txhash,
                'timecreated' => userdate($credential->timecreated, get_string('strftimedatetime')),
            ];

            if (!empty($credential->courseid)) {
                 $course = $DB->get_record('course', ['id' => $credential->courseid]);
                 $credentialdata['course'] = $course ? s($course->fullname) : 'N/A';
            }
            if (!empty($credential->cmid)) {
                $cm = get_coursemodule_from_id('coursemodule', $credential->cmid);
                $credentialdata['activity'] = $cm ? s($cm->name) : 'N/A';
            }
            if (!empty($credential->badgeid)) {
                $badge = $DB->get_record('badge', ['id' => $credential->badgeid]);
                $credentialdata['badge'] = $badge ? s($badge->name) : 'N/A';
            }

            // Include NFT data if available.
            $nft = $DB->get_record('local_skillpassport_nft', ['credentialid' => $credential->id]);
            if ($nft) {
                $credentialdata['nft_data'] = [
                    'id' => $nft->id,
                    'tokenid' => $nft->tokenid,
                    'contractaddress' => $nft->contractaddress,
                    'nft_metadata_json' => json_decode($nft->nft_metadata),
                    'timecreated' => userdate($nft->timecreated, get_string('strftimedatetime')),
                ];
            }
            $data['credentials_' . $credential->id] = (object)$credentialdata;
        }

        return $data;
    }

    /**
     * Delete all data for the specified user. This will delete credentials and associated NFTs.
     *
     * @param user_request $request The user request to action.
     * @throws not_found_exception If the context is not the system context.
     * @return void
     */
    public static function delete_data_for_user(user_request $request) {
        global $DB;

        $userid = $request->get_userid();
        $context = $request->get_context();

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            throw new not_found_exception('Non-system context encountered during data deletion.');
        }

        // Get all credential IDs for the user.
        $credentialids = $DB->get_fieldset('local_skillpassport_credentials', 'id', ['userid' => $userid]);

        if (!empty($credentialids)) {
            // Delete associated NFTs first.
            $DB->delete_records_list('local_skillpassport_nft', 'credentialid', $credentialids);

            // Delete credentials.
            $DB->delete_records('local_skillpassport_credentials', ['userid' => $userid]);
        }

        // Since we are deleting all records, there's no specific file data or preferences to delete here, 
        // as the local_skillpassport plugin does not set user preferences that need deletion upon data request.
    }

    /**
     * Get a list of components that the current component is dependent on.
     *
     * @return array Component list.
     */
    public static function get_component_dependencies(): array {
        return ['moodle', 'mod_assign', 'mod_quiz', 'mod_forum', 'mod_page', 'block_badges', 'totara_badges'];
    }

    /**
     * Returns the name of the plugin for the provided data.
     *
     * @return string
     */
    public static function get_plugin_fullname(): string {
        return get_string('pluginname', 'local_skillpassport');
    }

    /**
     * Is it possible for the plugin to create a user, meaning this callback needs to be implemented?
     *
     * @return bool
     */
    public static function can_create_users(): bool {
        return false;
    }

    /**
     * Is it possible for the plugin to delete a user, meaning this callback needs to be implemented?
     *
     * Only required if the plugin handles users that may not exist in Moodle and may store their data in
     * other tables for synchronisation purposes.
     *
     * @return bool
     */
    public static function can_delete_users(): bool {
        return false;
    }

    /**
     * Get the data stored for the specified user and context as it relates to a site feature or setting.
     *
     * @param user_request $request The user request to action.
     * @return array
     */
    public static function get_user_preference_data(user_request $request): array {
        // This plugin does not store user preferences directly, so return an empty array.
        return [];
    }

    /**
     * Delete the data stored for the specified user and context as it relates to a site feature or setting.
     *
     * @param user_request $request The user request to action.
     * @return void
     */
    public static function delete_user_preference_data(user_request $request) {
        // No user preferences to delete.
    }
}
