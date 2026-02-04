# local_skillpassport - Skill Passport Plugin

## Overview

The Skill Passport is a Moodle local plugin designed to revolutionize how learner achievements are recognized and verified. It integrates with blockchain technology to issue verifiable credentials for course completions, activity completions, and badge awards, storing an immutable hash on a chosen blockchain network (Ethereum/Polygon testnets for demo purposes).

This plugin provides learners with a centralized, verifiable 'Skill Passport' dashboard, showcasing their achievements with robust blockchain backing. It also includes optional NFT-style badges for gamified recognition and AI-ready hooks for future personalized learning recommendations.

## Features

*   **Blockchain-Verified Credentials**: Issue verifiable credentials for:
    *   Course Completions
    *   Activity Completions
    *   Badge Awards
*   **Immutable Records**: Store a blockchain transaction hash for each credential, ensuring verifiable authenticity.
*   **Learner Dashboard**: A personalized 'Skill Passport' dashboard for each learner to view all their verified achievements.
*   **Blockchain Verification Links**: Direct links from the dashboard allow anyone to verify credential authenticity on a blockchain explorer.
*   **Gamified Achievements (NFT-Ready)**: Option to mint NFT-style badges for credentials, enhancing learner engagement and recognition.
*   **AI-Ready Hooks**: Designed with hooks to integrate with future AI services for personalized learning recommendations based on completed achievements.
*   **Admin Configuration**: Comprehensive admin settings to configure blockchain network, demo node URLs, NFT metadata templates, and dashboard display options.
*   **Secure & Compliant**: Built with Moodle's security best practices, including capability checks, CSRF protection, and input sanitization.

## Installation

1.  **Download the Plugin**: Obtain the plugin's ZIP file.
2.  **Unzip**: Unzip the contents into the `local` directory of your Moodle installation (`moodle/local/`). Ensure the folder name is `skillpassport`.
    *   The path should look like: `moodle/local/skillpassport/version.php`.
3.  **Navigate to Moodle Admin**: Log in to your Moodle site as an administrator.
4.  **Perform Upgrade**: Go to `Site administration > Notifications`. Moodle will detect the new plugin and guide you through the installation process. Click 