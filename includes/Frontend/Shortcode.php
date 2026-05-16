<?php
/**
 * Shortcode Handler
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode Manager
 */
class NCP_Shortcode {

	/**
	 * Register shortcodes
	 */
	public static function register(): void {
		add_shortcode( 'nafas_chatbot', [ self::class, 'render' ] );
		add_shortcode( 'ncp_chatbot', [ self::class, 'render' ] );
	}

	/**
	 * Render shortcode
	 */
	public static function render( array $atts = [] ): string {
		$atts = shortcode_atts( [
			'provider'        => 'avalai',
			'floating'        => 'yes',
			'launcher_pos'    => 'right',
			'open_by_default' => 'no',
			'temperature'     => '0.7',
			'max_tokens'      => '512',
		], $atts, 'nafas_chatbot' );
		
		$config = NCP_Frontend::build_config( [
			'provider'        => $atts['provider'],
			'floating_mode'   => 'yes' === $atts['floating'],
			'launcher_position' => $atts['launcher_pos'],
			'open_by_default' => 'yes' === $atts['open_by_default'],
			'temperature'     => (float) $atts['temperature'],
			'max_tokens'      => (int) $atts['max_tokens'],
		] );
		
		NCP_Frontend::enqueue_assets( $config );
		return NCP_Frontend::render_mount( $config );
	}
}
