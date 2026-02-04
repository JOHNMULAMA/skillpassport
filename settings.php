<?php
/**
 * Admin settings for the SkillPassport local plugin.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

defined('MOODLE_INTERNAL') || die();

if (has_capability('local/skillpassport:manage', context_system::instance())) {

    $settings = new admin_settingpage('local_skillpassport_settings',
                                    get_string('pluginname', 'local_skillpassport'));
    $ADMIN->add('localplugins', $settings);

    // Blockchain Settings Header
    $settings->add(new admin_setting_heading(
        'local_skillpassport/blockchainsettingsheader',
        get_string('blockchainsettingsheader', 'local_skillpassport'),
        get_string('blockchainsettingsheader', 'local_skillpassport')
    ));

    // Blockchain Network Selection
    $settings->add(new admin_setting_configselect(
        'local_skillpassport/blockchain_network',
        get_string('networkselection', 'local_skillpassport'),
        get_string('networkselection_desc', 'local_skillpassport'),
        'ethereum_sepolia', // Default value
        [
            'ethereum_sepolia' => 'Ethereum Sepolia (Testnet)',
            'polygon_mumbai' => 'Polygon Mumbai (Testnet)',
            // 'ethereum_mainnet' => 'Ethereum Mainnet (Production - Coming Soon)',
            // 'polygon_mainnet' => 'Polygon Mainnet (Production - Coming Soon)',
        ]
    ));

    // Demo Blockchain Node URL
    $settings->add(new admin_setting_configtext(
        'local_skillpassport/demo_node_url',
        get_string('demoboardurl', 'local_skillpassport'),
        get_string('demoboardurl_desc', 'local_skillpassport'),
        'https://sepolia.infura.io/v3/YOUR_INFURA_PROJECT_ID', // Placeholder for Infura project ID
        PARAM_URL
    ));

    // NFT Metadata Template Header
    $settings->add(new admin_setting_heading(
        'local_skillpassport/nftmetadataheader',
        get_string('nftmetadataheader', 'local_skillpassport'),
        get_string('nftmetadataheader', 'local_skillpassport')
    ));

    // NFT Metadata Template
    $settings->add(new admin_setting_configtextarea(
        'local_skillpassport/nft_metadata_template',
        get_string('nftmetadatatemplate', 'local_skillpassport'),
        get_string('nftmetadatatemplate_desc', 'local_skillpassport'),
        json_encode([
            "name" => "{credential_name} Issued to {learner_name}",
            "description" => "This NFT certifies the achievement of {credential_name} by {learner_name} on {issue_date}. Verified on {blockchain_network}.",
            "image" => "https://example.com/skillpassport/nft_badge_template.png"
        ], JSON_PRETTY_PRINT),
        PARAM_RAW
    ));

    // Dashboard Display Options Header
    $settings->add(new admin_setting_heading(
        'local_skillpassport/dashboarddisplayheader',
        get_string('dashboarddisplayheader', 'local_skillpassport'),
        get_string('dashboarddisplayheader', 'local_skillpassport')
    ));

    // Show Activity Completions
    $settings->add(new admin_setting_configcheckbox(
        'local_skillpassport/show_activity_completions',
        get_string('showactivitycompletions', 'local_skillpassport'),
        get_string('showactivitycompletions_desc', 'local_skillpassport'),
        '1' // Default to checked
    ));

    // Show Course Completions
    $settings->add(new admin_setting_configcheckbox(
        'local_skillpassport/show_course_completions',
        get_string('showcoursecompletions', 'local_skillpassport'),
        get_string('showcoursecompletions_desc', 'local_skillpassport'),
        '1' // Default to checked
    ));

    // Show Badges
    $settings->add(new admin_setting_configcheckbox(
        'local_skillpassport/show_badges',
        get_string('showbadges', 'local_skillpassport'),
        get_string('showbadges_desc', 'local_skillpassport'),
        '1' // Default to checked
    ));

    // AI Integration Status (Placeholder for future development)
    $settings->add(new admin_setting_heading(
        'local_skillpassport/ai_integration_status_head',
        get_string('ai_integration_status', 'local_skillpassport'),
        get_string('ai_integration_desc', 'local_skillpassport')
    ));
}
