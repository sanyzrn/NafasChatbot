<?php
/**
 * Frontend Handler
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Manager
 */
class NCP_Frontend {

	/**
	 * Register frontend scripts and styles
	 */
	public static function register_assets(): void {
		// Register styles
		wp_register_style(
			'ncp-vazir',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap',
			[],
			null,
			'all'
		);
		
		wp_register_style(
			'ncp-chatbot',
			NCP_ASSETS_URL . 'css/chatbot.css',
			[ 'ncp-vazir' ],
			NCP_VERSION,
			'all'
		);
		
		// Register scripts
		wp_register_script(
			'ncp-chatbot',
			NCP_ASSETS_URL . 'js/chatbot.js',
			[],
			NCP_VERSION,
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public static function enqueue_assets( array $config ): void {
		wp_enqueue_style( 'ncp-chatbot' );
		wp_enqueue_script( 'ncp-chatbot' );
		
		// Add global configuration
		$global_config = [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => NCP_Security::get_nonce( NCP_NONCE_CHAT ),
			'i18n'    => self::get_i18n(),
		];
		
		wp_add_inline_script(
			'ncp-chatbot',
			'window.ncpGlobal = ' . NCP_Security::json_encode( $global_config ) . ';',
			'before'
		);
	}

	/**
	 * Render chatbot mount point
	 */
	public static function render_mount( array $config ): string {
		$config_json = NCP_Security::json_encode( $config );
		$is_floating = ! empty( $config['floating_mode'] );
		
		$classes = [
			'ncp-mount',
			$is_floating ? 'ncp-mount-floating' : 'ncp-mount-inline',
		];
		
		return sprintf(
			'<div class="%s" data-ncp-config="%s"></div>',
			NCP_Security::esc_attr( implode( ' ', $classes ) ),
			NCP_Security::esc_attr( $config_json )
		);
	}

	/**
	 * Build frontend configuration
	 */
	public static function build_config( array $atts = [] ): array {
		$config = NCP_Configuration::instance();
		
		$defaults = [
			'company_name'         => $config->get( 'ncp_company_name' ),
			'company_id'           => $config->get( 'ncp_company_id' ),
			'products_json'        => $config->get( 'ncp_products_json' ),
			'provider'             => $config->get( 'ncp_default_provider' ),
			'model'                => 'gpt-4o-mini',
			'temperature'          => 0.7,
			'max_tokens'           => 512,
			'history_length'       => 6,
			'system_prompt'        => $config->get( 'ncp_system_prompt' ),
			'show_launcher'        => self::to_bool( $config->get( 'ncp_show_launcher' ) ),
			'show_company'         => self::to_bool( $config->get( 'ncp_show_company' ) ),
			'show_products'        => self::to_bool( $config->get( 'ncp_show_products' ) ),
			'show_adr'             => self::to_bool( $config->get( 'ncp_show_adr' ) ),
			'show_consult'         => self::to_bool( $config->get( 'ncp_show_consult' ) ),
			'floating_mode'        => true,
			'open_by_default'      => false,
			'text_direction'       => 'rtl',
			'theme_mode'           => 'auto',
			'launcher_position'    => $config->get( 'ncp_launcher_position' ),
			'persist_history'      => true,
			'enable_markdown'      => true,
			'enable_emoji'         => true,
			'font_family'          => $config->get( 'ncp_font_family' ),
			'font_size_heading'    => $config->get( 'ncp_font_size_heading' ),
			'font_size_body'       => $config->get( 'ncp_font_size_body' ),
			'font_size_caption'    => $config->get( 'ncp_font_size_caption' ),
			'panel_width'          => $config->get( 'ncp_panel_width' ),
			'panel_height'         => $config->get( 'ncp_panel_height' ),
			'theme_primary'        => $config->get( 'ncp_theme_primary' ),
			'theme_primary_hover'  => $config->get( 'ncp_theme_primary_hover' ),
			'theme_bg_base'        => $config->get( 'ncp_theme_bg_base' ),
			'theme_bg_card'        => $config->get( 'ncp_theme_bg_card' ),
			'theme_border'         => $config->get( 'ncp_theme_border' ),
			'theme_text_base'      => $config->get( 'ncp_theme_text_base' ),
			'theme_text_muted'     => $config->get( 'ncp_theme_text_muted' ),
			'theme_control_bg'     => $config->get( 'ncp_theme_control_bg' ),
			'theme_control_hover'  => $config->get( 'ncp_theme_control_hover' ),
			'theme_control_text'   => $config->get( 'ncp_theme_control_text' ),
		];
		
		// Merge with shortcode attributes
		return array_merge( $defaults, self::sanitize_config( $atts ) );
	}

	/**
	 * Sanitize configuration array
	 */
	private static function sanitize_config( array $config ): array {
		$clean = [];
		
		// String fields
		$str_fields = [ 'company_name', 'company_id', 'provider', 'model', 'system_prompt', 'launcher_position', 'text_direction', 'theme_mode', 'font_family' ];
		foreach ( $str_fields as $field ) {
			if ( isset( $config[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( (string) $config[ $field ] );
			}
		}
		
		// Boolean fields
		$bool_fields = [ 'floating_mode', 'open_by_default', 'persist_history', 'enable_markdown', 'enable_emoji', 'show_launcher', 'show_company', 'show_products', 'show_adr', 'show_consult' ];
		foreach ( $bool_fields as $field ) {
			if ( isset( $config[ $field ] ) ) {
				$clean[ $field ] = self::to_bool( $config[ $field ] );
			}
		}
		
		// Numeric fields
		if ( isset( $config['temperature'] ) ) {
			$clean['temperature'] = NCP_Security::sanitize_temperature( $config['temperature'] );
		}
		if ( isset( $config['max_tokens'] ) ) {
			$clean['max_tokens'] = NCP_Security::sanitize_max_tokens( $config['max_tokens'] );
		}
		if ( isset( $config['history_length'] ) ) {
			$clean['history_length'] = min( NCP_MAX_HISTORY_LENGTH, max( 0, (int) $config['history_length'] ) );
		}
		
		// CSS fields
		$css_fields = [ 'font_size_heading', 'font_size_body', 'font_size_caption', 'panel_width', 'panel_height' ];
		foreach ( $css_fields as $field ) {
			if ( isset( $config[ $field ] ) ) {
				if ( preg_match( '/^-?[0-9]*\.?[0-9]+(px|rem|em|vw|vh|%)$/', (string) $config[ $field ] ) ) {
					$clean[ $field ] = (string) $config[ $field ];
				}
			}
		}
		
		// Color fields
		$color_fields = [ 'theme_primary', 'theme_primary_hover', 'theme_bg_base', 'theme_bg_card', 'theme_border', 'theme_text_base', 'theme_text_muted', 'theme_control_bg', 'theme_control_hover', 'theme_control_text' ];
		foreach ( $color_fields as $field ) {
			if ( isset( $config[ $field ] ) ) {
				$color = sanitize_hex_color( (string) $config[ $field ] );
				if ( $color ) {
					$clean[ $field ] = $color;
				}
			}
		}
		
		return $clean;
	}

	/**
	 * Convert to boolean
	 */
	private static function to_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( trim( (string) $value ) ), [ '1', 'true', 'yes', 'on' ], true );
	}

	/**
	 * Get i18n strings
	 */
	public static function get_i18n(): array {
		return [
			'placeholder'           => __( 'سلام! چطور میتونم کمکتون کنم؟', 'nafas-chatbot-pro' ),
			'send_button'           => __( 'ارسال', 'nafas-chatbot-pro' ),
			'loading'               => __( 'درحال بارگذاری...', 'nafas-chatbot-pro' ),
			'error'                 => __( 'خرابی رخ داده است. لطفا دوباره تلاش کنید.', 'nafas-chatbot-pro' ),
			'typing'                => __( 'درحال تایپ...', 'nafas-chatbot-pro' ),
			'close'                 => __( 'بستن', 'nafas-chatbot-pro' ),
			'clear_chat'            => __( 'پاک کردن چت', 'nafas-chatbot-pro' ),
			'feedback'              => __( 'نظر خود را بیان کنید', 'nafas-chatbot-pro' ),
			'feedback_thanks'       => __( 'از نظر شما سپاسگزاریم!', 'nafas-chatbot-pro' ),
			'form_name'             => __( 'نام', 'nafas-chatbot-pro' ),
			'form_phone'            => __( 'شماره تماس', 'nafas-chatbot-pro' ),
			'form_description'      => __( 'توضیح', 'nafas-chatbot-pro' ),
			'form_submit'           => __( 'ارسال درخواست', 'nafas-chatbot-pro' ),
			'validation_error'      => __( 'لطفا اطلاعات را درست کنید.', 'nafas-chatbot-pro' ),
		];
	}
}
