<?php
/**
 * Plugin Name:       Nafas Chatbot Pro
 * Plugin URI:        https://dbsgraphic.ir
 * Description:       Enterprise-grade AI chatbot for WordPress & Elementor. Supports multi-provider AI (AvalAI, OpenAI, custom), RTL/LTR, dark mode, persistent history, rate limiting, Bale notifications, chat logging, and full shortcode + Elementor widget integration.
 * Version:           2.0.0
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

define( 'NCP_VERSION',     '2.0.0' );
define( 'NCP_FILE',        __FILE__ );
define( 'NCP_DIR',         plugin_dir_path( __FILE__ ) );
define( 'NCP_URL',         plugin_dir_url( __FILE__ ) );
define( 'NCP_TABLE',       'ncp_chat_log' );
define( 'NCP_NONCE',       'ncp_security_nonce' );
define( 'NCP_OPT_GROUP',   'ncp_settings_group' );
define( 'NCP_MENU_SLUG',   'ncp-dashboard' );

/* ──────────────────────────────────────────────────────────────
   BOOTSTRAP
────────────────────────────────────────────────────────────── */
final class Nafas_Chatbot_Pro {

	private static ?self $instance = null;

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
		add_action( 'init',           [ $this, 'register_shortcodes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_action( 'admin_menu',     [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init',     [ $this, 'register_settings' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_widget' ] );

		// AJAX handlers
		foreach ( [ 'ncp_chat', 'ncp_form_submit', 'ncp_clear_log' ] as $action ) {
			add_action( "wp_ajax_{$action}",        [ $this, "ajax_{$action}" ] );
			add_action( "wp_ajax_nopriv_{$action}", [ $this, "ajax_{$action}" ] );
		}
		add_action( 'wp_ajax_ncp_export_log',      [ $this, 'ajax_ncp_export_log' ] );
		add_action( 'wp_ajax_ncp_clear_log',       [ $this, 'ajax_ncp_clear_log' ] );
		add_action( 'wp_ajax_ncp_apply_preset',    [ $this, 'ajax_ncp_apply_preset' ] );
	}

	/* ── Activation / Deactivation ─────────────────────────── */

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
			INDEX idx_session  (session_id),
			INDEX idx_ip       (user_ip),
			INDEX idx_created  (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Default options
		$defaults = [
			'ncp_default_provider'   => 'avalai',
			'ncp_avalai_endpoint'    => 'https://api.avalai.ir/v1/chat/completions',
			'ncp_openai_endpoint'    => 'https://api.openai.com/v1/chat/completions',
			'ncp_rate_per_minute'    => 10,
			'ncp_rate_per_hour'      => 100,
			'ncp_company_name'       => 'شرکت نفس زیست فارمد',
			'ncp_company_id'         => 'nafas',
			'ncp_show_launcher'      => '1',
			'ncp_show_company'       => '1',
			'ncp_show_products'      => '1',
			'ncp_show_adr'           => '1',
			'ncp_show_consult'       => '1',
			'ncp_font_family'        => 'Vazirmatn, IRANSansX, IRANSans, Tahoma, sans-serif',
			'ncp_font_size_heading'  => '1.125rem',
			'ncp_font_size_body'     => '0.9375rem',
			'ncp_font_size_caption'  => '0.8125rem',
			'ncp_panel_width'        => '380px',
			'ncp_panel_height'       => '600px',
			'ncp_launcher_position'  => 'right',
			'ncp_theme_primary'      => '#b01618',
			'ncp_theme_primary_hover'=> '#8c0f11',
			'ncp_theme_bg_base'      => '#f7f5f2',
			'ncp_theme_bg_card'      => '#ffffff',
			'ncp_theme_border'       => '#e6e1da',
			'ncp_theme_text_base'    => '#1c1a18',
			'ncp_theme_text_muted'   => '#6a625a',
			'ncp_cache_ttl'          => 30,
			'ncp_log_enabled'        => '1',
		];

		foreach ( $defaults as $key => $value ) {
			add_option( $key, $value );
		}
	}

	public function deactivate(): void {
		// Intentionally left blank. Data is preserved on deactivation.
	}

	/* ── Assets ────────────────────────────────────────────── */

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
		wp_register_style(
			'ncp-chatbot',
			NCP_URL . 'assets/chatbot.css',
			[ 'ncp-vazirmatn' ],
			NCP_VERSION
		);
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

	private bool $assets_loaded = false;

	public function enqueue_frontend_assets( array $config ): void {
    wp_enqueue_style( 'ncp-chatbot' );
    wp_enqueue_script( 'ncp-chatbot' );

    if ( ! $this->assets_loaded ) {
        $global = [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( NCP_NONCE ),
            'i18n'    => $this->default_i18n(),
        ];

        wp_add_inline_script(
            'ncp-chatbot',
            'window.ncpGlobal=' . wp_json_encode( $global, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';',
            'before'
        );

        $this->assets_loaded = true;
    }

    /*
     * Config is now read directly from .ncp-mount[data-ncp-config].
     * Do not push window.ncpInstances here, otherwise the widget may render twice.
     */
}

	/* ── Shortcode ─────────────────────────────────────────── */

	public function register_shortcodes(): void {
		add_shortcode( 'nafas_chatbot', [ $this, 'shortcode_nafas' ] );
		add_shortcode( 'ncp_chatbot',   [ $this, 'shortcode_nafas' ] );
	}

	public function shortcode_nafas( array $atts ): string {
		$atts = shortcode_atts( $this->default_shortcode_atts(), $atts, 'nafas_chatbot' );
		$config = $this->build_config( $atts );
		$this->enqueue_frontend_assets( $config );
		return $this->render_mount( $config );
	}

	/* ── Config Builder ────────────────────────────────────── */

	public function build_config( array $overrides = [] ): array {
		$o  = get_option( 'ncp_avalai_endpoint', 'https://api.avalai.ir/v1/chat/completions' );
		$oe = get_option( 'ncp_openai_endpoint', 'https://api.openai.com/v1/chat/completions' );
		$ce = get_option( 'ncp_custom_endpoint', '' );

		$defaults = [
			/* Identity */
			'company_name'      => (string) get_option( 'ncp_company_name', 'شرکت نفس زیست فارمد' ),
			'company_id'        => (string) get_option( 'ncp_company_id', 'nafas' ),
			'products_json'     => (string) get_option( 'ncp_products_json', $this->default_products_json() ),

			/* AI */
			'provider'          => (string) get_option( 'ncp_default_provider', 'avalai' ),
			'model'             => 'gpt-4o-mini',
			'temperature'       => 0.7,
			'max_tokens'        => 512,
			'history_length'    => 6,
			'system_prompt'     => (string) get_option( 'ncp_system_prompt', '' ),

			/* Endpoints (resolved server-side) */
			'avalai_endpoint'   => $o,
			'openai_endpoint'   => $oe,
			'custom_endpoint'   => $ce,

			/* Form / Bale */
			'submit_api_url'    => '',   // blank = use built-in WP AJAX
			'chat_api_url'      => '',

			/* Features */
			'show_launcher'     => $this->bool_opt( 'ncp_show_launcher', true ),
			'show_company'      => $this->bool_opt( 'ncp_show_company', true ),
			'show_products'     => $this->bool_opt( 'ncp_show_products', true ),
			'show_adr'          => $this->bool_opt( 'ncp_show_adr', true ),
			'show_consult'      => $this->bool_opt( 'ncp_show_consult', true ),

			/* UI */
			'floating_mode'     => true,
			'open_by_default'   => false,
			'text_direction'    => 'rtl',
			'theme_mode'        => 'auto',
			'launcher_position' => (string) get_option( 'ncp_launcher_position', 'right' ),
			'persist_history'   => true,
			'enable_markdown'   => true,
			'enable_emoji'      => true,
			'enable_notifications' => true,

			/* Fonts / Sizes */
			'font_family'       => (string) get_option( 'ncp_font_family', 'Vazirmatn, IRANSansX, IRANSans, Tahoma, sans-serif' ),
			'font_size_heading' => (string) get_option( 'ncp_font_size_heading', '1.125rem' ),
			'font_size_body'    => (string) get_option( 'ncp_font_size_body', '0.9375rem' ),
			'font_size_caption' => (string) get_option( 'ncp_font_size_caption', '0.8125rem' ),
			'panel_width'       => (string) get_option( 'ncp_panel_width', '380px' ),
			'panel_height'      => (string) get_option( 'ncp_panel_height', '600px' ),

			/* Theme Colors */
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

		$merged = array_merge( $defaults, $this->sanitize_overrides( $overrides ) );

		// Strip server-side only fields from JS payload
		unset( $merged['avalai_endpoint'], $merged['openai_endpoint'], $merged['custom_endpoint'] );

		return $merged;
	}

	/* ── Render ────────────────────────────────────────────── */

public function render_mount( array $config ): string {
    $json = wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

    if ( ! $json ) {
        $json = '{}';
    }

    $is_floating = ! empty( $config['floating_mode'] );

    $classes = [
        'ncp-mount',
        $is_floating ? 'ncp-mount-floating' : 'ncp-mount-inline',
    ];

    return sprintf(
        '<div class="%1$s" data-ncp-config="%2$s" aria-live="polite"></div>',
        esc_attr( implode( ' ', $classes ) ),
        esc_attr( $json )
    );
}

	/* ── AJAX: Chat ────────────────────────────────────────── */

	public function ajax_ncp_chat(): void {
		$this->verify_nonce_or_die();
		$this->apply_rate_limit();

		$message      = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$provider     = sanitize_key( $_POST['provider'] ?? get_option( 'ncp_default_provider', 'avalai' ) );
		$model        = $this->sanitize_model( $_POST['model'] ?? 'gpt-4o-mini' );
		$temperature  = min( 2.0, max( 0.0, (float) ( $_POST['temperature'] ?? 0.7 ) ) );
		$max_tokens   = min( 4096, max( 64, (int) ( $_POST['max_tokens'] ?? 512 ) ) );
		$system_prompt= sanitize_textarea_field( wp_unslash( $_POST['system_prompt'] ?? '' ) );
		$history      = $this->sanitize_history( $_POST['history'] ?? '' );
		$session_id   = $this->sanitize_session( $_POST['session_id'] ?? '' );
		$product      = sanitize_text_field( wp_unslash( $_POST['product'] ?? '' ) );

		if ( '' === $message ) {
			wp_send_json_error( [ 'message' => __( 'پیام نمی‌تواند خالی باشد.', 'nafas-chatbot-pro' ) ], 400 );
		}
		if ( mb_strlen( $message ) > 2000 ) {
			wp_send_json_error( [ 'message' => __( 'پیام بیش از حد طولانی است.', 'nafas-chatbot-pro' ) ], 400 );
		}

		// Check if external API is configured or fallback to placeholder
		$endpoint = $this->resolve_endpoint( $provider );
		$api_key  = $this->resolve_api_key( $provider, $_POST['api_key'] ?? '' );

		if ( '' === $api_key || '' === $endpoint ) {
			// Graceful placeholder reply
			$placeholder = (string) get_option( 'ncp_chat_placeholder', 'سپاس از سوال شما. دستیار هوشمند نفس فارمد آماده پاسخگویی است. لطفاً از بخش درخواست مشاوره نیز استفاده کنید.' );
			$reply = str_replace( '{product}', $product, $placeholder );
			wp_send_json_success( [ 'message' => $reply, 'cached' => false, 'placeholder' => true ] );
		}

		// Cache lookup
		$cache_key = 'ncp_cache_' . md5( $provider . $endpoint . $message . $model . $temperature . $max_tokens . $system_prompt . wp_json_encode( $history ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			$this->log_chat( $session_id, $provider, $model, $message, $cached, 0, true );
			$this->metric_inc( 'ncp_metric_cache_hits' );
			wp_send_json_success( [ 'message' => $cached, 'cached' => true ] );
		}

		// Build messages array
		$messages = [];
		if ( '' !== $system_prompt ) {
			$messages[] = [ 'role' => 'system', 'content' => $system_prompt ];
		}
		foreach ( $history as $h ) {
			$messages[] = $h;
		}
		$messages[] = [ 'role' => 'user', 'content' => $message ];

		$response = wp_remote_post( $endpoint, [
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json; charset=utf-8',
			],
			'body'    => wp_json_encode( compact( 'model', 'messages', 'temperature', 'max_tokens' ) ),
			'timeout' => 45,
		] );

		if ( is_wp_error( $response ) ) {
			$this->metric_inc( 'ncp_metric_api_errors' );
			wp_send_json_error( [ 'message' => __( 'اتصال به سرویس هوش مصنوعی برقرار نشد.', 'nafas-chatbot-pro' ) ], 502 );
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$body    = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 || ! is_array( $decoded ) ) {
			$this->metric_inc( 'ncp_metric_api_errors' );
			$err_msg = is_array( $decoded ) && isset( $decoded['error']['message'] )
				? sanitize_text_field( (string) $decoded['error']['message'] )
				: __( 'پاسخ نامعتبر از سرویس هوش مصنوعی.', 'nafas-chatbot-pro' );
			wp_send_json_error( [ 'message' => $err_msg ], 502 );
		}

		$reply  = $this->extract_text( $decoded );
		$tokens = (int) ( $decoded['usage']['total_tokens'] ?? 0 );

		if ( '' === $reply ) {
			$this->metric_inc( 'ncp_metric_api_errors' );
			wp_send_json_error( [ 'message' => __( 'پاسخی از مدل دریافت نشد.', 'nafas-chatbot-pro' ) ], 502 );
		}

		$ttl = max( 1, (int) get_option( 'ncp_cache_ttl', 30 ) );
		set_transient( $cache_key, $reply, $ttl * MINUTE_IN_SECONDS );

		$this->log_chat( $session_id, $provider, $model, $message, $reply, $tokens, false );
		$this->metric_inc( 'ncp_metric_api_success' );

		wp_send_json_success( [ 'message' => $reply, 'tokens' => $tokens, 'cached' => false ] );
	}

	/* ── AJAX: Form Submit ─────────────────────────────────── */

	public function ajax_ncp_form_submit(): void {
		$this->verify_nonce_or_die();

		$type        = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'نامشخص' ) );
		$name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$phone       = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$product     = sanitize_text_field( wp_unslash( $_POST['product'] ?? '' ) );

		// Validation
		if ( mb_strlen( $name ) < 2 || mb_strlen( $name ) > 80 ) {
			wp_send_json_error( [ 'message' => __( 'نام نامعتبر است.', 'nafas-chatbot-pro' ) ], 400 );
		}
		if ( ! preg_match( '/^(\+98|0)?9\d{9}$/', $phone ) ) {
			wp_send_json_error( [ 'message' => __( 'شماره موبایل نامعتبر است. (مثال: 09121234567)', 'nafas-chatbot-pro' ) ], 400 );
		}
		if ( mb_strlen( $description ) < 5 || mb_strlen( $description ) > 2000 ) {
			wp_send_json_error( [ 'message' => __( 'توضیحات باید بین ۵ تا ۲۰۰۰ کاراکتر باشد.', 'nafas-chatbot-pro' ) ], 400 );
		}

		// Send Bale notification
		$bale_token   = trim( (string) get_option( 'ncp_bale_token', '' ) );
		$bale_chat_id = trim( (string) get_option( 'ncp_bale_chat_id', '' ) );
		if ( '' !== $bale_token && '' !== $bale_chat_id ) {
			$this->send_bale( $bale_token, $bale_chat_id, $type, $name, $phone, $description, $product );
		}

		// Send Telegram notification (bonus)
		$tg_token   = trim( (string) get_option( 'ncp_telegram_token', '' ) );
		$tg_chat_id = trim( (string) get_option( 'ncp_telegram_chat_id', '' ) );
		if ( '' !== $tg_token && '' !== $tg_chat_id ) {
			$this->send_telegram( $tg_token, $tg_chat_id, $type, $name, $phone, $description, $product );
		}

		// Save to external submit API if configured
		$ext = trim( (string) get_option( 'ncp_external_submit_api', '' ) );
		if ( '' !== $ext ) {
			wp_remote_post( $ext, [
				'body'    => compact( 'type', 'name', 'phone', 'description', 'product' ),
				'timeout' => 10,
			] );
		}

		wp_send_json_success( [ 'message' => __( 'اطلاعات با موفقیت ثبت شد.', 'nafas-chatbot-pro' ) ] );
	}

	/* ── AJAX: Export / Clear Log ──────────────────────────── */

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
			if ( ! empty( $rows ) ) {
				fputcsv( $out, array_keys( $rows[0] ) );
				foreach ( $rows as $row ) {
					fputcsv( $out, $row );
				}
			}
			fclose( $out );
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
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}" . NCP_TABLE );
		delete_option( 'ncp_metric_cache_hits' );
		delete_option( 'ncp_metric_api_success' );
		delete_option( 'ncp_metric_api_errors' );

		wp_send_json_success( [ 'message' => 'Log cleared.' ] );
	}

	/* ── Elementor Widget Registration ─────────────────────── */

	public function register_elementor_widget( $manager ): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}
		require_once NCP_DIR . 'includes/elementor-widget.php';
		if ( class_exists( 'NCP_Elementor_Widget' ) ) {
			$manager->register( new NCP_Elementor_Widget() );
		}
	}

	/* ── Admin Menu ─────────────────────────────────────────── */

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

	/* ── Settings Registration ──────────────────────────────── */

	public function register_settings(): void {
		$text_fields = [
			'ncp_company_name', 'ncp_company_id', 'ncp_system_prompt',
			'ncp_chat_placeholder', 'ncp_bale_token', 'ncp_bale_chat_id',
			'ncp_telegram_token', 'ncp_telegram_chat_id',
			'ncp_font_family', 'ncp_default_provider', 'ncp_launcher_position',
		];
		foreach ( $text_fields as $key ) {
			register_setting( NCP_OPT_GROUP, $key, [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}

		$url_fields = [
			'ncp_avalai_endpoint', 'ncp_openai_endpoint',
			'ncp_custom_endpoint', 'ncp_external_submit_api', 'ncp_external_chat_api',
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

	/* ── Helpers: Sanitization ──────────────────────────────── */

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
			return get_option( 'ncp_products_json', $this->default_products_json() );
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
		$clean = [];
		$str_keys = [ 'company_name', 'company_id', 'font_family', 'launcher_position', 'theme_mode', 'text_direction', 'provider', 'model', 'system_prompt' ];
		foreach ( $str_keys as $k ) {
			if ( isset( $o[$k] ) ) {
				$clean[$k] = sanitize_text_field( (string) $o[$k] );
			}
		}
		$bool_keys = [ 'show_launcher', 'show_company', 'show_products', 'show_adr', 'show_consult', 'floating_mode', 'open_by_default', 'persist_history', 'enable_markdown', 'enable_emoji', 'enable_notifications' ];
		foreach ( $bool_keys as $k ) {
			if ( array_key_exists( $k, $o ) ) {
				$clean[$k] = $this->to_bool( $o[$k] );
			}
		}
		$css_keys = [ 'font_size_heading', 'font_size_body', 'font_size_caption', 'panel_width', 'panel_height' ];
		foreach ( $css_keys as $k ) {
			if ( isset( $o[$k] ) ) {
				$v = $this->sanitize_css_size( $o[$k] );
				if ( '' !== $v ) {
					$clean[$k] = $v;
				}
			}
		}
		$color_keys = [ 'theme_primary', 'theme_primary_hover', 'theme_bg_base', 'theme_bg_card', 'theme_border', 'theme_text_base', 'theme_text_muted', 'theme_control_bg', 'theme_control_hover', 'theme_control_text' ];
		foreach ( $color_keys as $k ) {
			if ( isset( $o[$k] ) ) {
				$v = sanitize_hex_color( (string) $o[$k] );
				if ( null !== $v ) {
					$clean[$k] = $v;
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

	/* ── Helpers: Network / Auth ───────────────────────────── */

	private function verify_nonce_or_die(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, NCP_NONCE ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر است.', 'nafas-chatbot-pro' ) ], 403 );
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
			wp_send_json_error( [ 'message' => __( 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.', 'nafas-chatbot-pro' ) ], 429 );
		}
		set_transient( $k_min,  (int) get_transient( $k_min )  + 1, MINUTE_IN_SECONDS );
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
		// Never use a client-posted key if server key exists
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
		return '';
	}

	private function user_ip(): string {
		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $src ) {
			if ( empty( $_SERVER[$src] ) ) {
				continue;
			}
			$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[$src] ) ) )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '0.0.0.0';
	}

	/* ── Notifications ─────────────────────────────────────── */

	private function send_bale( string $token, string $chat_id, string $type, string $name, string $phone, string $description, string $product ): void {
		$text = implode( "\n", array_filter( [
			'📩 درخواست جدید از چت‌بات نفس فارمد',
			'',
			"نوع: {$type}",
			"نام: {$name}",
			"تماس: {$phone}",
			$product ? "محصول: {$product}" : '',
			'',
			'توضیحات:',
			$description,
			'',
			'🕐 ' . wp_date( 'H:i — Y/m/d' ),
		] ) );

		wp_remote_get(
			'https://tapi.bale.ai/bot' . rawurlencode( $token ) . '/sendMessage?' . http_build_query( [ 'chat_id' => $chat_id, 'text' => $text ] ),
			[ 'timeout' => 8 ]
		);
	}

	private function send_telegram( string $token, string $chat_id, string $type, string $name, string $phone, string $description, string $product ): void {
		$text = implode( "\n", array_filter( [
			'📩 <b>درخواست جدید از چت‌بات نفس فارمد</b>',
			'',
			"<b>نوع:</b> {$type}",
			"<b>نام:</b> {$name}",
			"<b>تماس:</b> {$phone}",
			$product ? "<b>محصول:</b> {$product}" : '',
			'',
			"<b>توضیحات:</b>\n{$description}",
			'',
			'🕐 ' . wp_date( 'H:i — Y/m/d' ),
		] ) );

		wp_remote_post( "https://api.telegram.org/bot{$token}/sendMessage", [
			'body'    => [ 'chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML' ],
			'timeout' => 8,
		] );
	}

	/* ── Logging ───────────────────────────────────────────── */

	private function log_chat( string $session_id, string $provider, string $model, string $message, string $response, int $tokens, bool $cached ): void {
		if ( ! $this->bool_opt( 'ncp_log_enabled', true ) ) {
			return;
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . NCP_TABLE,
			[
				'session_id' => $session_id,
				'user_ip'    => $this->user_ip(),
				'provider'   => $provider,
				'model'      => $model,
				'message'    => $message,
				'response'   => $response,
				'tokens_used'=> $tokens,
				'cached'     => $cached ? 1 : 0,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' ]
		);
	}

	private function metric_inc( string $key ): void {
		update_option( $key, (int) get_option( $key, 0 ) + 1, false );
	}

	/* ── Helpers: Options ──────────────────────────────────── */

	private function bool_opt( string $key, bool $default ): bool {
		return $this->to_bool( get_option( $key, $default ? '1' : '0' ) );
	}

	private function to_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( trim( (string) $value ) ), [ '1', 'true', 'yes', 'on' ], true );
	}

	/* ── Default Data ──────────────────────────────────────── */

	public function default_products(): array {
		return [
			[ 'id' => 'capsulizer',  'name' => 'کپسولایزر' ],
			[ 'id' => 'coldanese',   'name' => 'کلدانیز پلاس' ],
			[ 'id' => 'folinozit',   'name' => 'فولینوزیت' ],
			[ 'id' => 'meglozek',    'name' => 'مگلوزک' ],
			[ 'id' => 'tiotoriva',   'name' => 'تیوتوریوا' ],
		];
	}

	public function default_products_json(): string {
		return wp_json_encode( $this->default_products(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) ?: '[]';
	}

	private function default_shortcode_atts(): array {
		return [
			'company_name' => '', 'company_id' => '', 'products_json' => '',
			'provider' => '', 'model' => '', 'system_prompt' => '',
			'temperature' => '', 'max_tokens' => '', 'history_length' => '',
			'show_launcher' => '', 'show_company' => '', 'show_products' => '',
			'show_adr' => '', 'show_consult' => '',
			'floating_mode' => '', 'open_by_default' => '', 'text_direction' => '',
			'theme_mode' => '', 'launcher_position' => '',
			'panel_width' => '', 'panel_height' => '',
			'font_family' => '', 'font_size_body' => '',
			'theme_primary' => '',
		];
	}

	public function default_i18n(): array {
		return [
			/* Launcher */
			'launcherOpen'       => 'باز کردن چت',
			'launcherClose'      => 'بستن چت',
			/* Header */
			'headerMenu'         => 'دستیار هوشمند',
			'headerProducts'     => 'انتخاب محصول',
			'headerAdrSelect'    => 'انتخاب دارو',
			'headerAdrForm'      => 'ثبت عوارض',
			'headerConsultForm'  => 'درخواست مشاوره',
			'headerSuccess'      => 'ثبت موفقیت‌آمیز',
			'subHeaderKnowledge' => 'متصل به پایگاه دانش',
			'subHeaderForm'      => 'اطلاعات خود را وارد کنید',
			'subHeaderOnline'    => 'آنلاین',
			/* Menu */
			'menuGreeting'       => 'سلام!',
			'menuGreetingDesc1'  => 'به پورتال پشتیبانی نفس فارمد خوش آمدید.',
			'menuGreetingDesc2'  => 'چطور می‌توانم کمکتان کنم؟',
			'menuNoOption'       => 'هیچ گزینه‌ای برای نمایش فعال نشده است.',
			'menuCompanyTitle'   => 'سوال درباره شرکت',
			'menuCompanyDesc'    => 'تاریخچه، خط مشی و اطلاعات تماس',
			'menuProductsTitle'  => 'سوال درباره محصولات',
			'menuProductsDesc'   => 'اطلاعات دارویی، نحوه مصرف و عوارض',
			'menuAdrTitle'       => 'ثبت عوارض دارویی',
			'menuConsultTitle'   => 'درخواست مشاوره',
			/* Products */
			'productsPrompt'     => 'لطفاً محصولی که درباره آن سوال دارید را انتخاب کنید:',
			'adrPrompt'          => 'لطفاً دارویی که باعث عارضه شده را انتخاب کنید:',
			/* Form */
			'formNameLabel'      => 'نام و نام خانوادگی',
			'formNamePlaceholder'=> 'مثلاً: علی احمدی',
			'formPhoneLabel'     => 'شماره موبایل',
			'formPhonePlaceholder' => '09121234567',
			'formDescAdrLabel'   => 'شرح عارضه مشاهده‌شده',
			'formDescConsultLabel'=> 'موضوع و خلاصه درخواست',
			'formDescAdrPlaceholder'    => 'لطفاً علائم و مشکلاتی که پس از مصرف دارو پیش آمد را با جزئیات بنویسید...',
			'formDescConsultPlaceholder' => 'لطفاً بنویسید که در چه موردی نیاز به مشاوره دارید...',
			'formSubmitAdr'      => 'ثبت گزارش عوارض',
			'formSubmitConsult'  => 'ثبت درخواست مشاوره',
			/* Success */
			'successTitle'       => 'ثبت موفقیت‌آمیز',
			'successDesc1'       => 'اطلاعات شما با موفقیت ثبت شد.',
			'successDesc2'       => 'کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.',
			'successBack'        => 'بازگشت به منوی اصلی',
			/* Chat */
			'chatPlaceholder'    => 'پیام خود را بنویسید...',
			'chatSendAria'       => 'ارسال پیام',
			'chatAiWarning'      => 'هوش مصنوعی ممکن است اشتباه کند.',
			'chatWelcomeCompany' => 'سلام! آماده پاسخگویی به سوالات شما درباره **{company}** هستم.',
			'chatWelcomeProduct' => 'سلام! من دستیار هوشمند **{product}** هستم. هر سوالی دارید بپرسید.',
			/* Errors */
			'errNetwork'         => 'خطا در ارتباط با سرور. اتصال اینترنت خود را بررسی کنید.',
			'errTooLong'         => 'پیام بیش از حد طولانی است.',
			'errFormSubmit'      => 'در ثبت اطلاعات مشکلی پیش آمد. لطفاً دوباره تلاش کنید.',
			'errRateLimit'       => 'تعداد درخواست‌های شما بیش از حد مجاز است.',
			/* Misc */
			'typing'             => 'در حال پاسخ...',
			'clearChat'          => 'پاکسازی',
			'themeToggle'        => 'تغییر تم',
			'closePanel'         => 'بستن',
			'emojiToggle'        => 'ایموجی',
			'backButton'         => 'بازگشت',
			'adrBanner'          => 'گزارش عارضه برای: {product}',
			'formTypeAdr'        => 'گزارش عوارض دارویی',
			'formTypeConsult'    => 'درخواست مشاوره',
		];
	}

	/* ── Theme Presets ─────────────────────────────────────── */

	public function get_theme_presets(): array {
		return [
			'clinical_minimal' => [
				'label'                => __( 'Clinical Minimal', 'nafas-chatbot-pro' ),
				'description'          => __( 'Clean whites & precise typography. Professional medical tone.', 'nafas-chatbot-pro' ),
				'icon'                 => '🏥',
				'ncp_theme_primary'       => '#1a73e8',
				'ncp_theme_primary_hover' => '#1558b0',
				'ncp_theme_bg_base'       => '#f8f9fa',
				'ncp_theme_bg_card'       => '#ffffff',
				'ncp_theme_border'        => '#dadce0',
				'ncp_theme_text_base'     => '#202124',
				'ncp_theme_text_muted'    => '#5f6368',
				'ncp_theme_control_bg'    => '#f1f3f4',
				'ncp_theme_control_hover' => '#e8eaed',
				'ncp_theme_control_text'  => '#3c4043',
				'ncp_font_family'         => 'Inter, Segoe UI, Arial, sans-serif',
			],
			'premium_glass' => [
				'label'                => __( 'Premium Glass', 'nafas-chatbot-pro' ),
				'description'          => __( 'Dark glass morphism with luminous accents. Premium feel.', 'nafas-chatbot-pro' ),
				'icon'                 => '💎',
				'ncp_theme_primary'       => '#a78bfa',
				'ncp_theme_primary_hover' => '#7c3aed',
				'ncp_theme_bg_base'       => '#0f0f1a',
				'ncp_theme_bg_card'       => '#1a1a2e',
				'ncp_theme_border'        => '#2d2d4e',
				'ncp_theme_text_base'     => '#e2e8f0',
				'ncp_theme_text_muted'    => '#94a3b8',
				'ncp_theme_control_bg'    => '#16213e',
				'ncp_theme_control_hover' => '#1e2a4a',
				'ncp_theme_control_text'  => '#cbd5e1',
				'ncp_font_family'         => 'Vazirmatn, Inter, Segoe UI, sans-serif',
			],
			'editorial_bold' => [
				'label'                => __( 'Editorial Bold', 'nafas-chatbot-pro' ),
				'description'          => __( 'High-contrast editorial style with strong typographic hierarchy.', 'nafas-chatbot-pro' ),
				'icon'                 => '✏️',
				'ncp_theme_primary'       => '#e63946',
				'ncp_theme_primary_hover' => '#c1121f',
				'ncp_theme_bg_base'       => '#fffcf7',
				'ncp_theme_bg_card'       => '#ffffff',
				'ncp_theme_border'        => '#000000',
				'ncp_theme_text_base'     => '#000000',
				'ncp_theme_text_muted'    => '#4a4a4a',
				'ncp_theme_control_bg'    => '#f5f5f5',
				'ncp_theme_control_hover' => '#eeeeee',
				'ncp_theme_control_text'  => '#111111',
				'ncp_font_family'         => 'Georgia, "Times New Roman", serif',
			],
		];
	}

	public function ajax_ncp_apply_preset(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}
		check_ajax_referer( NCP_NONCE, 'nonce' );

		$preset_id = sanitize_key( $_POST['preset'] ?? '' );
		$presets   = $this->get_theme_presets();

		if ( ! isset( $presets[ $preset_id ] ) ) {
			wp_send_json_error( [ 'message' => 'Invalid preset.' ], 400 );
		}

		$preset = $presets[ $preset_id ];
		$color_keys = [ 'ncp_theme_primary', 'ncp_theme_primary_hover', 'ncp_theme_bg_base', 'ncp_theme_bg_card', 'ncp_theme_border', 'ncp_theme_text_base', 'ncp_theme_text_muted', 'ncp_theme_control_bg', 'ncp_theme_control_hover', 'ncp_theme_control_text' ];

		foreach ( $color_keys as $key ) {
			if ( isset( $preset[ $key ] ) ) {
				update_option( $key, sanitize_hex_color( $preset[ $key ] ) );
			}
		}
		if ( isset( $preset['ncp_font_family'] ) ) {
			update_option( 'ncp_font_family', sanitize_text_field( $preset['ncp_font_family'] ) );
		}

		wp_send_json_success( [ 'preset' => $preset, 'message' => __( 'Theme preset applied.', 'nafas-chatbot-pro' ) ] );
	}

	/* ── Admin i18n ─────────────────────────────────────────── */

	public function get_admin_i18n(): array {
		return [
			'presetApplied'   => __( 'Theme preset applied successfully!', 'nafas-chatbot-pro' ),
			'presetApplying'  => __( 'Applying...', 'nafas-chatbot-pro' ),
			'applyPreset'     => __( 'Apply Preset', 'nafas-chatbot-pro' ),
			'livePreview'     => __( 'Live Preview', 'nafas-chatbot-pro' ),
			'themePresets'    => __( 'Theme Presets', 'nafas-chatbot-pro' ),
			'presetsDesc'     => __( 'One-click signature design packs. Instantly changes all color tokens.', 'nafas-chatbot-pro' ),
			'saveNote'        => __( 'After applying a preset, save your settings to persist the changes.', 'nafas-chatbot-pro' ),
		];
	}

} // end class Nafas_Chatbot_Pro

Nafas_Chatbot_Pro::instance();
