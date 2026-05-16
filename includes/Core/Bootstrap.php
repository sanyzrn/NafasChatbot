<?php
/**
 * Plugin Loader & Bootstrap
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Bootstrap
 */
class NCP_Bootstrap {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load all plugin dependencies
	 */
	private function load_dependencies(): void {
		// Core
		require_once NCP_INCLUDES_DIR . 'Core/Constants.php';
		require_once NCP_INCLUDES_DIR . 'Core/Configuration.php';
		
		// Security
		require_once NCP_INCLUDES_DIR . 'Security/Security.php';
		
		// Database
		require_once NCP_INCLUDES_DIR . 'Database/Database.php';
		
		// Providers
		require_once NCP_INCLUDES_DIR . 'Providers/Manager.php';
		
		// API
		require_once NCP_INCLUDES_DIR . 'API/Handler.php';
		
		// Frontend
		require_once NCP_INCLUDES_DIR . 'Frontend/Frontend.php';
		require_once NCP_INCLUDES_DIR . 'Frontend/Shortcode.php';
		
		// Admin
		require_once NCP_INCLUDES_DIR . 'Admin/Admin.php';
		
		// Elementor
		require_once NCP_INCLUDES_DIR . 'Elementor/Widget.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks(): void {
		// Plugin activation/deactivation
		register_activation_hook( NCP_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( NCP_FILE, [ $this, 'deactivate' ] );
		
		// Text domain
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		
		// Frontend
		add_action( 'init', [ NCP_Shortcode::class, 'register' ] );
		add_action( 'wp_enqueue_scripts', [ NCP_Frontend::class, 'register_assets' ] );
		
		// Admin
		add_action( 'plugins_loaded', [ NCP_Admin::class, 'register' ] );
		
		// Elementor
		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_widget' ] );
		
		// API
		add_action( 'init', [ NCP_API::class, 'register_endpoints' ] );
	}

	/**
	 * Plugin activation
	 */
	public function activate(): void {
		// Create database tables
		NCP_Database::init_tables();
		
		// Add default options if not exist
		$config = NCP_Configuration::instance();
		foreach ( $config->get_default_options() as $key => $value ) {
			add_option( $key, $value );
		}
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate(): void {
		// Data is preserved on deactivation
		flush_rewrite_rules();
	}

	/**
	 * Load text domain for translations
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 
			'nafas-chatbot-pro',
			false,
			NCP_LANGUAGES_DIR
		);
	}

	/**
	 * Register Elementor widget
	 */
	public function register_elementor_widget( $manager ): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}
		
		if ( class_exists( 'NCP_Elementor_Widget' ) ) {
			$manager->register( new NCP_Elementor_Widget() );
		}
	}
}

// Initialize plugin
NCP_Bootstrap::instance();
