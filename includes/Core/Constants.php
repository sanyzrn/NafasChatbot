<?php
/**
 * Plugin Constants
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Information
define( 'NCP_VERSION', '3.0.0' );
define( 'NCP_FILE', dirname( dirname( dirname( __FILE__ ) ) ) . '/nafas-chatbot-pro.php' );
define( 'NCP_DIR', plugin_dir_path( NCP_FILE ) );
define( 'NCP_URL', plugin_dir_url( NCP_FILE ) );
define( 'NCP_PLUGIN_SLUG', 'nafas-chatbot-pro' );

// Database
define( 'NCP_TABLE_CHATS', 'ncp_chat_logs' );
define( 'NCP_TABLE_SESSIONS', 'ncp_sessions' );
define( 'NCP_TABLE_ANALYTICS', 'ncp_analytics' );
define( 'NCP_TABLE_FEEDBACK', 'ncp_feedback' );
define( 'NCP_TABLE_CONVERSATIONS', 'ncp_conversations' );

// Security
define( 'NCP_NONCE_CHAT', 'ncp_chat_nonce' );
define( 'NCP_NONCE_SETTINGS', 'ncp_settings_nonce' );
define( 'NCP_NONCE_EXPORT', 'ncp_export_nonce' );
define( 'NCP_SETTINGS_GROUP', 'ncp_settings_group' );

// Admin Menu
define( 'NCP_MENU_SLUG', 'ncp_dashboard' );
define( 'NCP_MENU_CAP', 'manage_options' );
define( 'NCP_MENU_ICON', 'dashicons-format-chat' );
define( 'NCP_MENU_POSITION', 57 );

// Limits
define( 'NCP_MAX_MESSAGE_LENGTH', 2000 );
define( 'NCP_MIN_MESSAGE_LENGTH', 1 );
define( 'NCP_MAX_HISTORY_LENGTH', 24 );
define( 'NCP_MAX_TOKENS', 4096 );
define( 'NCP_MIN_TOKENS', 64 );

// Cache
define( 'NCP_CACHE_TTL_DEFAULT', 30 ); // Minutes
define( 'NCP_CACHE_KEY_PREFIX', 'ncp_' );

// Features
define( 'NCP_ENABLE_LOGGING', true );
define( 'NCP_ENABLE_CACHING', true );
define( 'NCP_ENABLE_ANALYTICS', true );

// Paths
define( 'NCP_ASSETS_DIR', NCP_DIR . 'assets/' );
define( 'NCP_ASSETS_URL', NCP_URL . 'assets/' );
define( 'NCP_INCLUDES_DIR', NCP_DIR . 'includes/' );
define( 'NCP_TEMPLATES_DIR', NCP_DIR . 'templates/' );
define( 'NCP_LANGUAGES_DIR', dirname( plugin_basename( NCP_FILE ) ) . '/languages' );

// API Defaults
define( 'NCP_API_TIMEOUT', 45 );
define( 'NCP_EXTERNAL_API_TIMEOUT', 10 );
