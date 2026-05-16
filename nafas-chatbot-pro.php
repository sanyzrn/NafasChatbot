<?php
/**
 * Plugin Name:       Nafas Chatbot Pro
 * Plugin URI:        https://dbsgraphic.ir
 * Description:       Enterprise-grade AI chatbot for WordPress & Elementor. Multi-provider AI (AvalAI, OpenAI, custom), RTL/LTR, dark mode, persistent history, rate limiting, Bale/Telegram notifications, chat logging, and full shortcode + Elementor widget integration.
 * Version:           2.0.1
 * Author:            Saeed Zarrini
 * Author URI:        https://dbsgraphic.ir
 * License:           GPL-2.0-or-later
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Text Domain:       nafas-chatbot-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NCP_VERSION', '2.0.1' );
define( 'NCP_FILE', __FILE__ );
define( 'NCP_DIR', plugin_dir_path( __FILE__ ) );
define( 'NCP_URL', plugin_dir_url( __FILE__ ) );
define( 'NCP_TABLE', 'ncp_chat_log' );
define( 'NCP_NONCE', 'ncp_security_nonce' );
define( 'NCP_OPT_GROUP', 'ncp_settings_group' );
define( 'NCP_MENU_SLUG', 'ncp-dashboard' );

final class Nafas_Chatbot_Pro {

	private static ?self $instance = null;
	private bool $frontend_globals_injected = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( NCP_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( NCP_FILE, [ $this, 'deactivate' ] );

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'register_shortcodes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_widget' ] );

		add_action( 'wp_ajax_ncp_chat', [ $this, 'ajax_ncp_chat' ] );
		add_action( 'wp_ajax_nopriv_ncp_chat', [ $this, 'ajax_ncp_chat' ] );
		add_action( 'wp_ajax_ncp_form_submit', [ $this, 'ajax_ncp_form_submit' ] );
		add_action( 'wp_ajax_nopriv_ncp_form_submit', [ $this, 'ajax_ncp_form_submit' ] );

		add_action( 'wp_ajax_ncp_export_log', [ $this, 'ajax_ncp_export_log' ] );
		add_action( 'wp_ajax_ncp_clear_log', [ $this, 'ajax_ncp_clear_log' ] );
		add_action( 'wp_ajax_ncp_apply_preset', [ $this, 'ajax_ncp_apply_preset' ] );
	}

	public function activate(): void {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . NCP_TABLE;

		$sql = "CREATE TABLE {$table} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id  VARCHAR(128)    NOT NULL DEFAULT '',
			user_ip     VARCHAR(45)     NOT NULL DEFAULT '',
			provider    VARCHAR(32)     NOT NULL DEFAULT '',
			model       VARCHAR(100)    NOT NULL DEFAULT '',
			message     TEXT            NOT NULL,
			response    TEXT            NOT NULL,
			tokens_used INT UNSIGNED    NOT NULL DEFAULT 0,
			cached      TINYINT(1)      NOT NULL DEFAULT 0,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_session (session_id),
			INDEX idx_ip      (user_ip),
			INDEX idx_created (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$defaults = [
			'ncp_default_provider'     => 'avalai',
			'ncp_avalai_endpoint'      => 'https://api.avalai.ir/v1/chat/completions',
			'ncp_openai_endpoint'      => 'https://api.openai.com/v1/chat/completions',
			'ncp_custom_endpoint'      => '',
			'ncp_rate_per_minute'      => 10,
			'ncp_rate_per_hour'        => 100,
			'ncp_company_name'         => 'Nafas Pharmed',
			'ncp_company_id'           => 'nafas',
			'ncp_show_launcher'        => '1',
			'ncp_show_company'         => '1',
			'ncp_show_products'        => '1',
			'ncp_show_adr'             => '1',
			'ncp_show_consult'         => '1',
			'ncp_font_family'          => 'Vazirmatn, Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif',
			'ncp_font_size_heading'    => '1.125rem',
			'ncp_font_size_body'       => '0.9375rem',
			'ncp_font_size_caption'    => '0.8125rem',
			'ncp_panel_width'          => '380px',
			'ncp_panel_height'         => '600px',
			'ncp_launcher_position'    => 'right',
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
			'ncp_cache_ttl'            => 30,
			'ncp_log_enabled'          => '1',
			'ncp_products_json'        => $this->default_products_json(),
			'ncp_chat_placeholder'     => 'Thanks for your message. Please configure an API key in the plugin settings.',
		];

		foreach ( $defaults as $key => $value ) {
			add_option( $key, $value );
		}
	}

	public function deactivate(): void {
		// Intentionally blank. Data is preserved on deactivation.
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'nafas-chatbot-pro', false, dirname( plugin_basename( NCP_FILE ) ) . '/languages' );
	}

	public function register_assets(): void {
		wp_register_style(
			'ncp-vazirmatn',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap',
			[],
			null
		);

		wp_register_style( 'ncp-chatbot', NCP_URL . 'assets/chatbot.css', [ 'ncp-vazirmatn' ], NCP_VERSION );
		wp_register_script(
			'ncp-chatbot',
			NCP_URL . 'assets/chatbot.js',
			[],
			NCP_VERSION,
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);
	}

	public function admin_assets( string $hook ): void {
		if ( ! str_contains( $hook, NCP_MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'ncp-admin', NCP_URL . 'assets/admin.css', [], NCP_VERSION );
		wp_enqueue_script( 'ncp-admin', NCP_URL . 'assets/admin.js', [ 'jquery' ], NCP_VERSION, true );
		wp_localize_script( 'ncp-admin', 'ncpAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( NCP_NONCE ),
			'presets' => $this->get_theme_presets(),
			'i18n'    => $this->get_admin_i18n(),
		] );
	}

	public function enqueue_frontend_assets( array $config ): void {
		wp_enqueue_style( 'ncp-chatbot' );
		wp_enqueue_script( 'ncp-chatbot' );

		if ( ! $this->frontend_globals_injected ) {
			$global = [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( NCP_NONCE ),
				'i18n'    => $this->default_i18n(),
			];
			$global_json = wp_json_encode( $global, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( $global_json ) {
				wp_add_inline_script( 'ncp-chatbot', 'window.ncpGlobal=' . $global_json . ';', 'before' );
			}
			$this->frontend_globals_injected = true;
		}
	}

	public function register_shortcodes(): void {
		add_shortcode( 'nafas_chatbot', [ $this, 'shortcode_nafas' ] );
		add_shortcode( 'ncp_chatbot', [ $this, 'shortcode_nafas' ] );
	}

	public function shortcode_nafas( array $atts ): string {
		$atts   = shortcode_atts( $this->default_shortcode_atts(), $atts, 'nafas_chatbot' );
		$config = $this->build_config( $atts );
		$this->enqueue_frontend_assets( $config );
		return $this->render_mount( $config );
	}

	public function build_config( array $overrides = [] ): array {
		$defaults = [
			'company_name'         => (string) get_option( 'ncp_company_name', 'Nafas Pharmed' ),
			'company_id'           => (string) get_option( 'ncp_company_id', 'nafas' ),
			'products_json'        => (string) get_option( 'ncp_products_json', $this->default_products_json() ),
			'provider'             => (string) get_option( 'ncp_default_provider', 'avalai' ),
			'model'                => 'gpt-4o-mini',
			'temperature'          => 0.7,
			'max_tokens'           => 512,
			'history_length'       => 6,
			'system_prompt'        => (string) get_option( 'ncp_system_prompt', '' ),
			'show_launcher'        => $this->bool_opt( 'ncp_show_launcher', true ),
			'show_company'         => $this->bool_opt( 'ncp_show_company', true ),
			'show_products'        => $this->bool_opt( 'ncp_show_products', true ),
			'show_adr'             => $this->bool_opt( 'ncp_show_adr', true ),
			'show_consult'         => $this->bool_opt( 'ncp_show_consult', true ),
			'floating_mode'        => true,
			'open_by_default'      => false,
			'text_direction'       => 'rtl',
			'theme_mode'           => 'auto',
			'launcher_position'    => (string) get_option( 'ncp_launcher_position', 'right' ),
			'persist_history'      => true,
			'enable_markdown'      => true,
			'enable_emoji'         => true,
			'enable_notifications' => true,
			'font_family'          => (string) get_option( 'ncp_font_family', 'Vazirmatn, Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif' ),
			'font_size_heading'    => (string) get_option( 'ncp_font_size_heading', '1.125rem' ),
			'font_size_body'       => (string) get_option( 'ncp_font_size_body', '0.9375rem' ),
			'font_size_caption'    => (string) get_option( 'ncp_font_size_caption', '0.8125rem' ),
			'panel_width'          => (string) get_option( 'ncp_panel_width', '380px' ),
			'panel_height'         => (string) get_option( 'ncp_panel_height', '600px' ),
			'theme_primary'        => (string) get_option( 'ncp_theme_primary', '#b01618' ),
			'theme_primary_hover'  => (string) get_option( 'ncp_theme_primary_hover', '#8c0f11' ),
			'theme_bg_base'        => (string) get_option( 'ncp_theme_bg_base', '#f7f5f2' ),
			'theme_bg_card'        => (string) get_option( 'ncp_theme_bg_card', '#ffffff' ),
			'theme_border'         => (string) get_option( 'ncp_theme_border', '#e6e1da' ),
			'theme_text_base'      => (string) get_option( 'ncp_theme_text_base', '#1c1a18' ),
			'theme_text_muted'     => (string) get_option( 'ncp_theme_text_muted', '#6a625a' ),
			'theme_control_bg'     => (string) get_option( 'ncp_theme_control_bg', '#efe9e2' ),
			'theme_control_hover'  => (string) get_option( 'ncp_theme_control_hover', '#e4ddd6' ),
			'theme_control_text'   => (string) get_option( 'ncp_theme_control_text', '#3c352f' ),
		];

		return array_merge( $defaults, $this->sanitize_overrides( $overrides ) );
	}

	public function render_mount( array $config ): string {
		$json = wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( ! $json ) {
			$json = '{}';
		}

		$is_floating = ! empty( $config['floating_mode'] );
		$classes     = [ 'ncp-mount', $is_floating ? 'ncp-mount-floating' : 'ncp-mount-inline' ];

		return sprintf(
			'<div class="%s" data-ncp-config="%s"></div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $json )
		);
	}

	public function ajax_ncp_chat(): void {
		$this->verify_nonce_or_die();
		$this->apply_rate_limit();

		$message       = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$provider      = sanitize_key( $_POST['provider'] ?? get_option( 'ncp_default_provider', 'avalai' ) );
		$model         = $this->sanitize_model( $_POST['model'] ?? 'gpt-4o-mini' );
		$temperature   = min( 2.0, max( 0.0, (float) ( $_POST['temperature'] ?? 0.7 ) ) );
		$max_tokens    = min( 4096, max( 64, (int) ( $_POST['max_tokens'] ?? 512 ) ) );
		$system_prompt = sanitize_textarea_field( wp_unslash( $_POST['system_prompt'] ?? '' ) );
		$history       = $this->sanitize_history( $_POST['history'] ?? '' );
		$session_id    = $this->sanitize_session( $_POST['session_id'] ?? '' );
		$product       = sanitize_text_field( wp_unslash( $_POST['product'] ?? '' ) );

		if ( '' === $message ) {
			wp_send_json_error( [ 'message' => __( 'Message cannot be empty.', 'nafas-chatbot-pro' ) ], 400 );
		}
		if ( mb_strlen( $message ) > 2000 ) {
			wp_send_json_error( [ 'message' => __( 'Message is too long.', 'nafas-chatbot-pro' ) ], 400 );
		}

		$external = trim( (string) get_option( 'ncp_external_chat_api', '' ) );

		$endpoint = ( '' !== $external ) ? esc_url_raw( $external ) : $this->resolve_endpoint( $provider );
		$api_key  = ( '' !== $external ) ? '' : $this->resolve_api_key( $provider, $_POST['api_key'] ?? '' );

		if ( '' === $endpoint || ( '' === $external && '' === $api_key ) ) {
			$placeholder = (string) get_option( 'ncp_chat_placeholder', 'Thanks for your message. Please configure an API key in the plugin settings.' );
			$reply       = str_replace( '{product}', $product, $placeholder );
			wp_send_json_success( [ 'message' => $reply, 'cached' => false, 'placeholder' => true ] );
		}

		$cache_key = 'ncp_cache_' . md5(
			$provider . '|' . $endpoint . '|' . $message . '|' . $model . '|' . $temperature . '|' . $max_tokens . '|' . $system_prompt . '|' . wp_json_encode( $history )
		);
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			$this->log_chat( $session_id, $provider, $model, $message, (string) $cached, 0, true );
			$this->metric_inc( 'ncp_metric_cache_hits' );
			wp_send_json_success( [ 'message' => (string) $cached, 'cached' => true ] );
		}

		$messages = [];
		if ( '' !== $system_prompt ) {
			$messages[] = [ 'role' => 'system', 'content' => $system_prompt ];
		}
		foreach ( $history as $h ) {
			$messages[] = $h;
		}
		$messages[] = [ 'role' => 'user', 'content' => $message ];

		$payload = [
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		];

		$args = [
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8',
			],
			'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			'timeout' => 45,
		];
		if ( '' === $external ) {
			$args['headers']['Authorization'] = 'Bearer ' . $api_key;
		}

		$response = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $response ) ) {
			$this->metric_inc( 'ncp_metric_api_errors' );
			wp_send_json_error( [ 'message' => __( 'Failed to connect to the AI service.', 'nafas-chatbot-pro' ) ], 502 );
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$body    = (string) wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( $code >= 300 || ! is_array( $decoded ) ) {
			$this->metric_inc( 'ncp_metric_api_errors' );
			$err_msg = ( is_array( $decoded ) && isset( $decoded['error']['message'] ) )
				? sanitize_text_field( (string) $decoded['error']['message'] )
				: __( 'Invalid response from the AI service.', 'nafas-chatbot-pro' );
			wp_send_json_error( [ 'message' => $err_msg ], 502 );
		}

		$reply  = $this->extract_text( $decoded );
		$tokens = (int) ( $decoded['usage']['total_tokens'] ?? 0 );

		if ( '' === $reply ) {
			$this->metric_inc( 'ncp_metric_api_errors' );
			wp_send_json_error( [ 'message' => __( 'No reply received from the model.', 'nafas-chatbot-pro' ) ], 502 );
		}

		$ttl = max( 1, (int) get_option( 'ncp_cache_ttl', 30 ) );
		set_transient( $cache_key, $reply, $ttl * MINUTE_IN_SECONDS );

		$this->log_chat( $session_id, $provider, $model, $message, $reply, $tokens, false );
		$this->metric_inc( 'ncp_metric_api_success' );

		wp_send_json_success( [ 'message' => $reply, 'tokens' => $tokens, 'cached' => false ] );
	}
	public function ajax_ncp_form_submit(): void {
		$this->verify_nonce_or_die();
		$this->apply_rate_limit();

		$type        = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'Unknown' ) );
		$name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$phone       = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$product     = sanitize_text_field( wp_unslash( $_POST['product'] ?? '' ) );

		$name_len = mb_strlen( $name );
		if ( $name_len < 2 || $name_len > 80 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid name.', 'nafas-chatbot-pro' ) ], 400 );
		}

		// Default pattern is Iranian mobile numbers. Customize as needed.
		if ( ! preg_match( '/^(\+98|0)?9\d{9}$/', $phone ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid phone number.', 'nafas-chatbot-pro' ) ], 400 );
		}

		$desc_len = mb_strlen( $description );
		if ( $desc_len < 5 || $desc_len > 2000 ) {
			wp_send_json_error( [ 'message' => __( 'Description must be between 5 and 2000 characters.', 'nafas-chatbot-pro' ) ], 400 );
		}

		$bale_token   = trim( (string) get_option( 'ncp_bale_token', '' ) );
		$bale_chat_id = trim( (string) get_option( 'ncp_bale_chat_id', '' ) );
		if ( '' !== $bale_token && '' !== $bale_chat_id ) {
			$this->send_bale( $bale_token, $bale_chat_id, $type, $name, $phone, $description, $product );
		}

		$tg_token   = trim( (string) get_option( 'ncp_telegram_token', '' ) );
		$tg_chat_id = trim( (string) get_option( 'ncp_telegram_chat_id', '' ) );
		if ( '' !== $tg_token && '' !== $tg_chat_id ) {
			$this->send_telegram( $tg_token, $tg_chat_id, $type, $name, $phone, $description, $product );
		}

		$ext = trim( (string) get_option( 'ncp_external_submit_api', '' ) );
		if ( '' !== $ext ) {
			wp_remote_post(
				esc_url_raw( $ext ),
				[
					'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
					'body'    => wp_json_encode( compact( 'type', 'name', 'phone', 'description', 'product' ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
					'timeout' => 10,
				]
			);
		}

		wp_send_json_success( [ 'message' => __( 'Submitted successfully.', 'nafas-chatbot-pro' ) ] );
	}
	public function ajax_ncp_export_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden', 403 );
		}
		check_admin_referer( NCP_NONCE );

		global $wpdb;
		$table  = $wpdb->prefix . NCP_TABLE;
		$format = sanitize_key( $_REQUEST['format'] ?? 'json' );
		$rows   = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 5000", ARRAY_A );

		nocache_headers();

		if ( 'csv' === $format ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=ncp-chat-log.csv' );

			$out = fopen( 'php://output', 'w' );
			if ( $out ) {
				if ( ! empty( $rows ) ) {
					fputcsv( $out, array_keys( $rows[0] ) );
					foreach ( $rows as $row ) {
						fputcsv( $out, $row );
					}
				}
				fclose( $out );
			}
			exit;
		}

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=ncp-chat-log.json' );
		echo wp_json_encode( $rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		exit;
	}

	public function ajax_ncp_clear_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		check_ajax_referer( NCP_NONCE, 'nonce' );

		global $wpdb;
		$table = $wpdb->prefix . NCP_TABLE;

		$wpdb->query( "TRUNCATE TABLE {$table}" );

		delete_option( 'ncp_metric_cache_hits' );
		delete_option( 'ncp_metric_api_success' );
		delete_option( 'ncp_metric_api_errors' );

		wp_send_json_success( [ 'message' => 'Log cleared.' ] );
	}

	public function register_elementor_widget( $manager ): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		require_once NCP_DIR . 'includes/elementor-widget.php';
		if ( class_exists( 'NCP_Elementor_Widget' ) ) {
			$manager->register( new NCP_Elementor_Widget() );
		}
	}

	public function register_admin_menu(): void {
		add_menu_page(
			__( 'Nafas Chatbot Pro', 'nafas-chatbot-pro' ),
			__( 'Nafas Chatbot', 'nafas-chatbot-pro' ),
			'manage_options',
			NCP_MENU_SLUG,
			[ $this, 'render_admin_page' ],
			'dashicons-format-chat',
			57
		);

		add_submenu_page( NCP_MENU_SLUG, __( 'Settings', 'nafas-chatbot-pro' ), __( 'Settings', 'nafas-chatbot-pro' ), 'manage_options', NCP_MENU_SLUG, [ $this, 'render_admin_page' ] );
		add_submenu_page( NCP_MENU_SLUG, __( 'Chat Log', 'nafas-chatbot-pro' ), __( 'Chat Log', 'nafas-chatbot-pro' ), 'manage_options', NCP_MENU_SLUG . '-logs', [ $this, 'render_logs_page' ] );
	}

	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once NCP_DIR . 'includes/admin-settings.php';
		ncp_render_settings_page();
	}

	public function render_logs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once NCP_DIR . 'includes/admin-logs.php';
		ncp_render_logs_page();
	}

	public function register_settings(): void {
		$text_fields = [
			'ncp_company_name',
			'ncp_company_id',
			'ncp_system_prompt',
			'ncp_chat_placeholder',
			'ncp_bale_token',
			'ncp_bale_chat_id',
			'ncp_telegram_token',
			'ncp_telegram_chat_id',
			'ncp_font_family',
			'ncp_default_provider',
			'ncp_launcher_position',
		];
		foreach ( $text_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}

		$url_fields = [
			'ncp_avalai_endpoint',
			'ncp_openai_endpoint',
			'ncp_custom_endpoint',
			'ncp_external_submit_api',
			'ncp_external_chat_api',
		];
		foreach ( $url_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => 'esc_url_raw' ] );
		}

		$pass_fields = [ 'ncp_avalai_api_key', 'ncp_openai_api_key', 'ncp_custom_api_key' ];
		foreach ( $pass_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}

		$int_fields = [ 'ncp_rate_per_minute', 'ncp_rate_per_hour', 'ncp_cache_ttl' ];
		foreach ( $int_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => 'absint' ] );
		}

		$bool_fields = [ 'ncp_show_launcher', 'ncp_show_company', 'ncp_show_products', 'ncp_show_adr', 'ncp_show_consult', 'ncp_log_enabled' ];
		foreach ( $bool_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => [ $this, 'sanitize_bool' ] ] );
		}

		$css_fields = [ 'ncp_font_size_heading', 'ncp_font_size_body', 'ncp_font_size_caption', 'ncp_panel_width', 'ncp_panel_height' ];
		foreach ( $css_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => [ $this, 'sanitize_css_size' ] ] );
		}

		$color_fields = [ 'ncp_theme_primary', 'ncp_theme_primary_hover', 'ncp_theme_bg_base', 'ncp_theme_bg_card', 'ncp_theme_border', 'ncp_theme_text_base', 'ncp_theme_text_muted', 'ncp_theme_control_bg', 'ncp_theme_control_hover', 'ncp_theme_control_text' ];
		foreach ( $color_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => 'sanitize_hex_color' ] );
		}

		register_setting( NCP_OPT_GROUP, 'ncp_products_json', [ 'sanitize_callback' => [ $this, 'sanitize_products_json' ] ] );
	}

	public function sanitize_bool( $value ): string {
		return in_array( strtolower( (string) $value ), [ '1', 'true', 'yes', 'on' ], true ) ? '1' : '0';
	}

	public function sanitize_css_size( $value ): string {
		$v = trim( (string) $value );
		return preg_match( '/^-?[0-9]*\.?[0-9]+(px|rem|em|vw|vh|%)$/', $v ) ? $v : '';
	}

	public function sanitize_products_json( $value ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		$parsed = json_decode( $value, true );
		if ( ! is_array( $parsed ) ) {
			return (string) get_option( 'ncp_products_json', $this->default_products_json() );
		}

		$clean = [];
		foreach ( $parsed as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id   = sanitize_key( (string) ( $item['id'] ?? '' ) );
			$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
			if ( '' !== $id && '' !== $name ) {
				$clean[] = compact( 'id', 'name' );
			}
		}

		return wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) ?: '[]';
	}

	private function sanitize_overrides( array $o ): array {
		$clean    = [];
		$str_keys = [ 'company_name', 'company_id', 'font_family', 'launcher_position', 'theme_mode', 'text_direction', 'provider', 'model', 'system_prompt' ];
		foreach ( $str_keys as $k ) {
			if ( isset( $o[ $k ] ) ) {
				$clean[ $k ] = sanitize_text_field( (string) $o[ $k ] );
			}
		}

		$bool_keys = [ 'show_launcher', 'show_company', 'show_products', 'show_adr', 'show_consult', 'floating_mode', 'open_by_default', 'persist_history', 'enable_markdown', 'enable_emoji', 'enable_notifications' ];
		foreach ( $bool_keys as $k ) {
			if ( array_key_exists( $k, $o ) ) {
				$clean[ $k ] = $this->to_bool( $o[ $k ] );
			}
		}

		$css_keys = [ 'font_size_heading', 'font_size_body', 'font_size_caption', 'panel_width', 'panel_height' ];
		foreach ( $css_keys as $k ) {
			if ( isset( $o[ $k ] ) ) {
				$v = $this->sanitize_css_size( $o[ $k ] );
				if ( '' !== $v ) {
					$clean[ $k ] = $v;
				}
			}
		}

		$color_keys = [ 'theme_primary', 'theme_primary_hover', 'theme_bg_base', 'theme_bg_card', 'theme_border', 'theme_text_base', 'theme_text_muted', 'theme_control_bg', 'theme_control_hover', 'theme_control_text' ];
		foreach ( $color_keys as $k ) {
			if ( isset( $o[ $k ] ) ) {
				$v = sanitize_hex_color( (string) $o[ $k ] );
				if ( null !== $v ) {
					$clean[ $k ] = $v;
				}
			}
		}

		if ( isset( $o['temperature'] ) ) {
			$clean['temperature'] = min( 2.0, max( 0.0, (float) $o['temperature'] ) );
		}
		if ( isset( $o['max_tokens'] ) ) {
			$clean['max_tokens'] = min( 4096, max( 64, (int) $o['max_tokens'] ) );
		}
		if ( isset( $o['history_length'] ) ) {
			$clean['history_length'] = min( 12, max( 0, (int) $o['history_length'] ) );
		}
		if ( isset( $o['products_json'] ) ) {
			$clean['products_json'] = $this->sanitize_products_json( $o['products_json'] );
		}

		return $clean;
	}

	private function sanitize_history( $raw ): array {
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$out = [];
		foreach ( $decoded as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$role    = in_array( $item['role'] ?? '', [ 'user', 'assistant' ], true ) ? $item['role'] : '';
			$content = sanitize_textarea_field( (string) ( $item['content'] ?? '' ) );
			if ( '' !== $role && '' !== $content ) {
				$out[] = compact( 'role', 'content' );
			}
		}

		return array_slice( $out, -24 );
	}

	private function sanitize_session( $raw ): string {
		$raw = sanitize_text_field( (string) $raw );
		return '' === $raw ? wp_generate_uuid4() : substr( $raw, 0, 128 );
	}

	private function sanitize_model( $raw ): string {
		$raw = sanitize_text_field( (string) $raw );
		return preg_match( '/^[a-zA-Z0-9._:\-]+$/', $raw ) ? $raw : 'gpt-4o-mini';
	}

	private function verify_nonce_or_die(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, NCP_NONCE ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', 'nafas-chatbot-pro' ) ], 403 );
		}
	}

	private function apply_rate_limit(): void {
		$ip       = $this->user_ip();
		$hash     = md5( $ip );
		$k_min    = 'ncp_rm_' . $hash;
		$k_hour   = 'ncp_rh_' . $hash;
		$per_min  = max( 1, (int) get_option( 'ncp_rate_per_minute', 10 ) );
		$per_hour = max( 1, (int) get_option( 'ncp_rate_per_hour', 100 ) );

		if ( (int) get_transient( $k_min ) >= $per_min || (int) get_transient( $k_hour ) >= $per_hour ) {
			wp_send_json_error( [ 'message' => __( 'Too many requests. Please try again later.', 'nafas-chatbot-pro' ) ], 429 );
		}

		set_transient( $k_min, (int) get_transient( $k_min ) + 1, MINUTE_IN_SECONDS );
		set_transient( $k_hour, (int) get_transient( $k_hour ) + 1, HOUR_IN_SECONDS );
	}

	private function resolve_endpoint( string $provider ): string {
		return match ( $provider ) {
			'openai' => esc_url_raw( (string) get_option( 'ncp_openai_endpoint', 'https://api.openai.com/v1/chat/completions' ) ),
			'custom' => esc_url_raw( (string) get_option( 'ncp_custom_endpoint', '' ) ),
			default  => esc_url_raw( (string) get_option( 'ncp_avalai_endpoint', 'https://api.avalai.ir/v1/chat/completions' ) ),
		};
	}

	private function resolve_api_key( string $provider, $posted_key ): string {
		$server_key = match ( $provider ) {
			'openai' => (string) get_option( 'ncp_openai_api_key', '' ),
			'custom' => (string) get_option( 'ncp_custom_api_key', '' ),
			default  => (string) get_option( 'ncp_avalai_api_key', '' ),
		};
		return trim( $server_key ?: sanitize_text_field( (string) $posted_key ) );
	}

	private function extract_text( array $body ): string {
		if ( isset( $body['choices'][0]['message']['content'] ) ) {
			return trim( (string) $body['choices'][0]['message']['content'] );
		}
		if ( isset( $body['choices'][0]['text'] ) ) {
			return trim( (string) $body['choices'][0]['text'] );
		}
		if ( isset( $body['output_text'] ) ) {
			return trim( (string) $body['output_text'] );
		}
		if ( isset( $body['message'] ) && is_string( $body['message'] ) ) {
			return trim( $body['message'] );
		}
		return '';
	}

	private function user_ip(): string {
		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $src ) {
			if ( empty( $_SERVER[ $src ] ) ) {
				continue;
			}
			$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $src ] ) ) )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '0.0.0.0';
	}

	private function send_bale( string $token, string $chat_id, string $type, string $name, string $phone, string $description, string $product ): void {
		$text = implode( "\n", array_filter( [
			'New request from Nafas Chatbot',
			'',
			"Type: {$type}",
			"Name: {$name}",
			"Phone: {$phone}",
			$product ? "Product: {$product}" : '',
			'',
			"Description:\n{$description}",
			'',
			'At: ' . wp_date( 'H:i - Y/m/d' ),
		] ) );

		wp_remote_get(
			'https://tapi.bale.ai/bot' . rawurlencode( $token ) . '/sendMessage?' . http_build_query( [ 'chat_id' => $chat_id, 'text' => $text ] ),
			[ 'timeout' => 8 ]
		);
	}

	private function send_telegram( string $token, string $chat_id, string $type, string $name, string $phone, string $description, string $product ): void {
		$text = implode( "\n", array_filter( [
			'<b>New request from Nafas Chatbot</b>',
			'',
			"<b>Type:</b> {$type}",
			"<b>Name:</b> {$name}",
			"<b>Phone:</b> {$phone}",
			$product ? "<b>Product:</b> {$product}" : '',
			'',
			"<b>Description:</b>\n{$description}",
			'',
			'At: ' . wp_date( 'H:i - Y/m/d' ),
		] ) );

		wp_remote_post(
			"https://api.telegram.org/bot{$token}/sendMessage",
			[
				'body'    => [ 'chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML' ],
				'timeout' => 8,
			]
		);
	}

	private function log_chat( string $session_id, string $provider, string $model, string $message, string $response, int $tokens, bool $cached ): void {
		if ( ! $this->bool_opt( 'ncp_log_enabled', true ) ) {
			return;
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . NCP_TABLE,
			[
				'session_id'  => $session_id,
				'user_ip'     => $this->user_ip(),
				'provider'    => $provider,
				'model'       => $model,
				'message'     => $message,
				'response'    => $response,
				'tokens_used' => $tokens,
				'cached'      => $cached ? 1 : 0,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' ]
		);
	}

	private function metric_inc( string $key ): void {
		update_option( $key, (int) get_option( $key, 0 ) + 1, false );
	}

	private function bool_opt( string $key, bool $default ): bool {
		return $this->to_bool( get_option( $key, $default ? '1' : '0' ) );
	}

	private function to_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( trim( (string) $value ) ), [ '1', 'true', 'yes', 'on' ], true );
	}

	public function default_products(): array { return []; }
	public function default_products_json(): string { return '[]'; }
	private function default_shortcode_atts(): array { return []; }
	public function default_i18n(): array { return []; }

	public function get_theme_presets(): array { return []; }
	public function ajax_ncp_apply_preset(): void {}
	public function get_admin_i18n(): array { return []; }
}

Nafas_Chatbot_Pro::instance();
