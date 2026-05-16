<?php
/**
 * Admin Dashboard & Settings
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Manager
 */
class NCP_Admin {

	/**
	 * Register admin menu and settings
	 */
	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ] );
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
	}

	/**
	 * Register admin menu
	 */
	public static function register_menu(): void {
		add_menu_page(
			__( 'Nafas Chatbot Pro', 'nafas-chatbot-pro' ),
			__( 'Nafas Chatbot', 'nafas-chatbot-pro' ),
			NCP_MENU_CAP,
			NCP_MENU_SLUG,
			[ self::class, 'render_dashboard' ],
			NCP_MENU_ICON,
			NCP_MENU_POSITION
		);
		
		add_submenu_page(
			NCP_MENU_SLUG,
			__( 'Settings', 'nafas-chatbot-pro' ),
			__( 'Settings', 'nafas-chatbot-pro' ),
			NCP_MENU_CAP,
			NCP_MENU_SLUG,
			[ self::class, 'render_dashboard' ]
		);
		
		add_submenu_page(
			NCP_MENU_SLUG,
			__( 'Chat Logs', 'nafas-chatbot-pro' ),
			__( 'Chat Logs', 'nafas-chatbot-pro' ),
			NCP_MENU_CAP,
			NCP_MENU_SLUG . '-logs',
			[ self::class, 'render_logs_page' ]
		);
		
		add_submenu_page(
			NCP_MENU_SLUG,
			__( 'Analytics', 'nafas-chatbot-pro' ),
			__( 'Analytics', 'nafas-chatbot-pro' ),
			NCP_MENU_CAP,
			NCP_MENU_SLUG . '-analytics',
			[ self::class, 'render_analytics_page' ]
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, NCP_MENU_SLUG ) ) {
			return;
		}
		
		wp_enqueue_style( 'ncp-admin', NCP_ASSETS_URL . 'css/admin.css', [], NCP_VERSION );
		wp_enqueue_script( 'ncp-admin', NCP_ASSETS_URL . 'js/admin.js', [ 'jquery' ], NCP_VERSION, true );
		
		wp_localize_script( 'ncp-admin', 'ncpAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => NCP_Security::get_nonce( NCP_NONCE_SETTINGS ),
			'i18n'    => self::get_admin_i18n(),
		] );
	}

	/**
	 * Register settings
	 */
	public static function register_settings(): void {
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
			register_setting( NCP_SETTINGS_GROUP, $key, [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}
		
		$url_fields = [
			'ncp_avalai_endpoint',
			'ncp_openai_endpoint',
			'ncp_custom_endpoint',
			'ncp_external_submit_api',
			'ncp_external_chat_api',
		];
		
		foreach ( $url_fields as $key ) {
			register_setting( NCP_SETTINGS_GROUP, $key, [ 'sanitize_callback' => 'esc_url_raw' ] );
		}
		
		$pass_fields = [ 'ncp_avalai_api_key', 'ncp_openai_api_key', 'ncp_custom_api_key' ];
		foreach ( $pass_fields as $key ) {
			register_setting( NCP_SETTINGS_GROUP, $key, [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}
		
		$int_fields = [ 'ncp_rate_per_minute', 'ncp_rate_per_hour', 'ncp_rate_per_day', 'ncp_cache_ttl' ];
		foreach ( $int_fields as $key ) {
			register_setting( NCP_SETTINGS_GROUP, $key, [ 'sanitize_callback' => 'absint' ] );
		}
		
		$bool_fields = [ 'ncp_show_launcher', 'ncp_show_company', 'ncp_show_products', 'ncp_show_adr', 'ncp_show_consult', 'ncp_log_enabled' ];
		foreach ( $bool_fields as $key ) {
			register_setting( NCP_SETTINGS_GROUP, $key, [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}
		
		$css_fields = [ 'ncp_font_size_heading', 'ncp_font_size_body', 'ncp_font_size_caption', 'ncp_panel_width', 'ncp_panel_height' ];
		foreach ( $css_fields as $key ) {
			register_setting( NCP_SETTINGS_GROUP, $key, [ 'sanitize_callback' => [ self::class, 'sanitize_css_size' ] ] );
		}
		
		$color_fields = [ 'ncp_theme_primary', 'ncp_theme_primary_hover', 'ncp_theme_bg_base', 'ncp_theme_bg_card', 'ncp_theme_border', 'ncp_theme_text_base', 'ncp_theme_text_muted', 'ncp_theme_control_bg', 'ncp_theme_control_hover', 'ncp_theme_control_text' ];
		foreach ( $color_fields as $key ) {
			register_setting( NCP_SETTINGS_GROUP, $key, [ 'sanitize_callback' => 'sanitize_hex_color' ] );
		}
	}

	/**
	 * Render main dashboard
	 */
	public static function render_dashboard(): void {
		if ( ! current_user_can( NCP_MENU_CAP ) ) {
			wp_die( __( 'Access denied.', 'nafas-chatbot-pro' ) );
		}
		
		?>
		<div class="wrap ncp-admin-page">
			<h1><?php echo esc_html( __( 'Nafas Chatbot Pro - Settings', 'nafas-chatbot-pro' ) ); ?></h1>
			
			<form method="post" action="options.php">
				<?php
					settings_fields( NCP_SETTINGS_GROUP );
					do_settings_sections( NCP_SETTINGS_GROUP );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render logs page
	 */
	public static function render_logs_page(): void {
		if ( ! current_user_can( NCP_MENU_CAP ) ) {
			wp_die( __( 'Access denied.', 'nafas-chatbot-pro' ) );
		}
		
		$logs = NCP_Database::get_logs( 100 );
		
		?>
		<div class="wrap ncp-admin-page">
			<h1><?php echo esc_html( __( 'Chat Logs', 'nafas-chatbot-pro' ) ); ?></h1>
			
			<div class="ncp-admin-actions">
				<button class="button button-secondary" id="ncp-export-logs">
					<?php echo esc_html( __( 'Export as JSON', 'nafas-chatbot-pro' ) ); ?>
				</button>
				<button class="button button-danger" id="ncp-clear-logs" onclick="return confirm('<?php echo esc_attr( __( 'Are you sure?', 'nafas-chatbot-pro' ) ); ?>')">
					<?php echo esc_html( __( 'Clear All Logs', 'nafas-chatbot-pro' ) ); ?>
				</button>
			</div>
			
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html( __( 'ID', 'nafas-chatbot-pro' ) ); ?></th>
						<th><?php echo esc_html( __( 'Session', 'nafas-chatbot-pro' ) ); ?></th>
						<th><?php echo esc_html( __( 'Provider', 'nafas-chatbot-pro' ) ); ?></th>
						<th><?php echo esc_html( __( 'Message', 'nafas-chatbot-pro' ) ); ?></th>
						<th><?php echo esc_html( __( 'Created', 'nafas-chatbot-pro' ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $logs ) ) {
						foreach ( $logs as $log ) {
							?>
							<tr>
								<td><?php echo esc_html( $log['id'] ); ?></td>
								<td><code><?php echo esc_html( substr( $log['session_id'], 0, 12 ) ); ?></code></td>
								<td><?php echo esc_html( $log['provider'] ); ?></td>
								<td><?php echo esc_html( substr( $log['message'], 0, 50 ) ); ?>...</td>
								<td><?php echo esc_html( $log['created_at'] ); ?></td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td colspan="5"><?php echo esc_html( __( 'No logs found.', 'nafas-chatbot-pro' ) ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render analytics page
	 */
	public static function render_analytics_page(): void {
		if ( ! current_user_can( NCP_MENU_CAP ) ) {
			wp_die( __( 'Access denied.', 'nafas-chatbot-pro' ) );
		}
		
		$analytics = NCP_Database::get_analytics();
		$by_provider = NCP_Database::get_analytics_by_provider();
		
		?>
		<div class="wrap ncp-admin-page">
			<h1><?php echo esc_html( __( 'Analytics', 'nafas-chatbot-pro' ) ); ?></h1>
			
			<div class="ncp-analytics-grid">
				<div class="ncp-analytics-card">
					<h3><?php echo esc_html( __( 'Total Sessions', 'nafas-chatbot-pro' ) ); ?></h3>
					<p class="ncp-stat-value"><?php echo esc_html( $analytics['total_sessions'] ?? 0 ); ?></p>
				</div>
				
				<div class="ncp-analytics-card">
					<h3><?php echo esc_html( __( 'Total Messages', 'nafas-chatbot-pro' ) ); ?></h3>
					<p class="ncp-stat-value"><?php echo esc_html( $analytics['total_messages'] ?? 0 ); ?></p>
				</div>
				
				<div class="ncp-analytics-card">
					<h3><?php echo esc_html( __( 'Total Tokens Used', 'nafas-chatbot-pro' ) ); ?></h3>
					<p class="ncp-stat-value"><?php echo esc_html( number_format( $analytics['total_tokens'] ?? 0 ) ); ?></p>
				</div>
				
				<div class="ncp-analytics-card">
					<h3><?php echo esc_html( __( 'Cache Hits', 'nafas-chatbot-pro' ) ); ?></h3>
					<p class="ncp-stat-value"><?php echo esc_html( number_format( $analytics['cache_hits'] ?? 0 ) ); ?></p>
				</div>
			</div>
			
			<?php if ( ! empty( $by_provider ) ) { ?>
				<h2><?php echo esc_html( __( 'By Provider', 'nafas-chatbot-pro' ) ); ?></h2>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th><?php echo esc_html( __( 'Provider', 'nafas-chatbot-pro' ) ); ?></th>
							<th><?php echo esc_html( __( 'Messages', 'nafas-chatbot-pro' ) ); ?></th>
							<th><?php echo esc_html( __( 'Tokens', 'nafas-chatbot-pro' ) ); ?></th>
							<th><?php echo esc_html( __( 'Avg Rating', 'nafas-chatbot-pro' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $by_provider as $provider ) {
							?>
							<tr>
								<td><?php echo esc_html( $provider['provider'] ); ?></td>
								<td><?php echo esc_html( number_format( $provider['count'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( number_format( $provider['tokens'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( number_format( $provider['rating'] ?? 0, 2 ) ); ?>/5</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Sanitize CSS size field
	 */
	public static function sanitize_css_size( $value ): string {
		$v = trim( (string) $value );
		return preg_match( '/^-?[0-9]*\.?[0-9]+(px|rem|em|vw|vh|%)$/', $v ) ? $v : '';
	}

	/**
	 * Get admin i18n
	 */
	private static function get_admin_i18n(): array {
		return [
			'confirm_delete' => __( 'Are you sure?', 'nafas-chatbot-pro' ),
			'success'        => __( 'Operation completed successfully.', 'nafas-chatbot-pro' ),
			'error'          => __( 'An error occurred.', 'nafas-chatbot-pro' ),
			'loading'        => __( 'Loading...', 'nafas-chatbot-pro' ),
		];
	}
}
