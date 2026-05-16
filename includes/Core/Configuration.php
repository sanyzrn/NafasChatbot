<?php
/**
 * Plugin Configuration
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configuration Singleton
 */
class NCP_Configuration {
	private static ?self $instance = null;
	private array $defaults = [];

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->defaults = $this->get_default_options();
	}

	/**
	 * Get all default options
	 */
	public function get_default_options(): array {
		return [
			// Provider Settings
			'ncp_default_provider'     => 'avalai',
			'ncp_avalai_endpoint'      => 'https://api.avalai.ir/v1/chat/completions',
			'ncp_avalai_api_key'       => '',
			'ncp_openai_endpoint'      => 'https://api.openai.com/v1/chat/completions',
			'ncp_openai_api_key'       => '',
			'ncp_custom_endpoint'      => '',
			'ncp_custom_api_key'       => '',
			
			// Rate Limiting (Security)
			'ncp_rate_per_minute'      => 10,
			'ncp_rate_per_hour'        => 100,
			'ncp_rate_per_day'         => 500,
			
			// Company Settings
			'ncp_company_name'         => 'Nafas Pharmed',
			'ncp_company_id'           => 'nafas',
			
			// UI Components
			'ncp_show_launcher'        => '1',
			'ncp_show_company'         => '1',
			'ncp_show_products'        => '1',
			'ncp_show_adr'             => '1',
			'ncp_show_consult'         => '1',
			
			// Typography
			'ncp_font_family'          => 'Vazirmatn, Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif',
			'ncp_font_size_heading'    => '1.125rem',
			'ncp_font_size_body'       => '0.9375rem',
			'ncp_font_size_caption'    => '0.8125rem',
			
			// Layout
			'ncp_panel_width'          => '380px',
			'ncp_panel_height'         => '600px',
			'ncp_launcher_position'    => 'right',
			
			// Theme Colors
			'ncp_theme_primary'        => '#b01618',
			'ncp_theme_primary_hover'  => '#8c0f11',
			'ncp_theme_bg_base'        => '#f7f5f2',
			'ncp_theme_bg_card'        => '#ffffff',
			'ncp_theme_border'         => '#e6e1da',
			'ncp_theme_text_base'      => '#1c1a18',
			'ncp_theme_text_muted'     => '#6a625a',
			'ncp_theme_control_bg'     => '#efe9e2',
			'ncp_theme_control_hover'  => '#e4ddd6',
			'ncp_theme_control_text'   => '#3c352f',
			
			// Chat Settings
			'ncp_system_prompt'        => '',
			'ncp_chat_placeholder'     => 'سلام! چطور میتونم کمکتون کنم؟',
			'ncp_cache_ttl'            => 30,
			'ncp_log_enabled'          => '1',
			'ncp_products_json'        => '[]',
			
			// Notifications
			'ncp_bale_token'           => '',
			'ncp_bale_chat_id'         => '',
			'ncp_telegram_token'       => '',
			'ncp_telegram_chat_id'     => '',
			
			// External APIs
			'ncp_external_chat_api'    => '',
			'ncp_external_submit_api'  => '',
		];
	}

	/**
	 * Get configuration value
	 */
	public function get( string $key, mixed $default = null ): mixed {
		if ( array_key_exists( $key, $this->defaults ) ) {
			return get_option( $key, $this->defaults[ $key ] );
		}
		return $default ?? get_option( $key, '' );
	}

	/**
	 * Get all options
	 */
	public function get_all(): array {
		$options = [];
		foreach ( $this->defaults as $key => $value ) {
			$options[ $key ] = $this->get( $key );
		}
		return $options;
	}

	/**
	 * Set configuration value
	 */
	public function set( string $key, mixed $value ): bool {
		return update_option( $key, $value );
	}

	/**
	 * Get theme colors
	 */
	public function get_theme(): array {
		return [
			'primary'        => sanitize_hex_color( $this->get( 'ncp_theme_primary' ) ),
			'primary_hover'  => sanitize_hex_color( $this->get( 'ncp_theme_primary_hover' ) ),
			'bg_base'        => sanitize_hex_color( $this->get( 'ncp_theme_bg_base' ) ),
			'bg_card'        => sanitize_hex_color( $this->get( 'ncp_theme_bg_card' ) ),
			'border'         => sanitize_hex_color( $this->get( 'ncp_theme_border' ) ),
			'text_base'      => sanitize_hex_color( $this->get( 'ncp_theme_text_base' ) ),
			'text_muted'     => sanitize_hex_color( $this->get( 'ncp_theme_text_muted' ) ),
			'control_bg'     => sanitize_hex_color( $this->get( 'ncp_theme_control_bg' ) ),
			'control_hover'  => sanitize_hex_color( $this->get( 'ncp_theme_control_hover' ) ),
			'control_text'   => sanitize_hex_color( $this->get( 'ncp_theme_control_text' ) ),
		];
	}

	/**
	 * Get rate limits
	 */
	public function get_rate_limits(): array {
		return [
			'per_minute' => max( 1, (int) $this->get( 'ncp_rate_per_minute', 10 ) ),
			'per_hour'   => max( 1, (int) $this->get( 'ncp_rate_per_hour', 100 ) ),
			'per_day'    => max( 1, (int) $this->get( 'ncp_rate_per_day', 500 ) ),
		];
	}
}
