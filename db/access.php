<?php
/**
 * Capabilities for the SkillPassport local plugin.
 *
 * @package    local_skillpassport
 * @copyright  2024 John Mulama <johnmulama001@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     John Mulama - Senior Software Engineer (johnmulama001@gmail.com)
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'local/skillpassport:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),
        'description' => 'Allow users to view their Skill Passport dashboard.',
    ),

    'local/skillpassport:issue' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'description' => 'Allow users to issue verified credentials.',
    ),

    'local/skillpassport:manage' => array(
        'captype' => 'manage',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
        'description' => 'Allow users to manage Skill Passport plugin settings.',
    )
);
