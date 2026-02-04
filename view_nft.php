<?php
/**
 * Page for viewing NFT metadata for the SkillPassport local plugin.
 *
 * This page displays the JSON metadata of an NFT badge.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

require_once('../../config.php');
require_once('lib.php');

// No specific capability needed to view public NFT metadata, but require login.
require_login();

$nftid = required_param('nftid', PARAM_INT);

$nft = $DB->get_record('local_skillpassport_nft', ['id' => $nftid], '*', MUST_EXIST);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/skillpassport/view_nft.php', ['nftid' => $nftid]));
$PAGE->set_title(get_string('nftmetadataheader', 'local_skillpassport') . ' #' . $nftid);
$PAGE->set_heading(get_string('nftmetadataheader', 'local_skillpassport') . ' #' . $nftid);

echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox');
echo '<h3>' . s(get_string('nftmetadataheader', 'local_skillpassport')) . '</h3>';
echo '<pre>' . s(json_encode(json_decode($nft->nft_metadata), JSON_PRETTY_PRINT)) . '</pre>';
echo '<p><strong>Token ID:</strong> ' . s($nft->tokenid) . '</p>';
echo '<p><strong>Contract Address:</strong> ' . s($nft->contractaddress) . '</p>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
