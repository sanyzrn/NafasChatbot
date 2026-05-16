<?php
/**
 * Plugin Name:       Nafas Chatbot Pro
 * Plugin URI:        https://dbsgraphic.ir
 * Description:       Enterprise-grade AI chatbot for WordPress & Elementor. Modular bootstrap loader.
 * Version:           3.0.0
 * Author:            Saeed Zarrini
 * Author URI:        https://dbsgraphic.ir
 * License:           GPL-2.0-or-later
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Text Domain:       nafas-chatbot-pro
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load plugin constants (defines NCP_FILE, NCP_DIR, NCP_INCLUDES_DIR, etc.)
require_once __DIR__ . '/includes/Core/Constants.php';

// Bootstrap the modular plugin. The bootstrap file will initialize services.
$bootstrap = NCP_INCLUDES_DIR . 'Core/Bootstrap.php';
if ( file_exists( $bootstrap ) ) {
    require_once $bootstrap;
} else {
    error_log( 'Nafas Chatbot Pro: missing bootstrap file: ' . $bootstrap );
}
