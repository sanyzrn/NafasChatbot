<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ncp_render_settings_page(): void {
	?>
	<div class="wrap ncp-admin">
		<div class="ncp-admin-header">
			<h1>🤖 Nafas Chatbot Pro <span class="ncp-version">v<?php echo NCP_VERSION; ?></span></h1>
			<p class="ncp-tagline">Enterprise AI Chatbot for WordPress &amp; Elementor</p>
		</div>

		<div class="ncp-admin-body">
			<?php settings_errors( NCP_OPT_GROUP ); ?>

			<form method="post" action="options.php" class="ncp-settings-form">
				<?php settings_fields( NCP_OPT_GROUP ); ?>

				<div class="ncp-tabs">
					<button type="button" class="ncp-tab active" data-target="tab-ai">🤖 <?php esc_html_e( 'AI Settings', 'nafas-chatbot-pro' ); ?></button>
					<button type="button" class="ncp-tab" data-target="tab-content">🏢 <?php esc_html_e( 'Content', 'nafas-chatbot-pro' ); ?></button>
					<button type="button" class="ncp-tab" data-target="tab-features">⚙️ <?php esc_html_e( 'Features', 'nafas-chatbot-pro' ); ?></button>
					<button type="button" class="ncp-tab" data-target="tab-design">🎨 <?php esc_html_e( 'Appearance', 'nafas-chatbot-pro' ); ?></button>
					<button type="button" class="ncp-tab" data-target="tab-notifications">🔔 <?php esc_html_e( 'Notifications', 'nafas-chatbot-pro' ); ?></button>
					<button type="button" class="ncp-tab" data-target="tab-advanced">🔧 <?php esc_html_e( 'Advanced', 'nafas-chatbot-pro' ); ?></button>
				</div>

				<!-- AI Settings -->
				<div id="tab-ai" class="ncp-tab-content active">
					<div class="ncp-card">
						<h2>🤖 <?php esc_html_e( 'AI Configuration', 'nafas-chatbot-pro' ); ?></h2>
						<table class="form-table">
							<tr>
								<th><label for="ncp_default_provider"><?php esc_html_e( 'Default Provider', 'nafas-chatbot-pro' ); ?></label></th>
								<td>
									<select name="ncp_default_provider" id="ncp_default_provider">
										<option value="avalai" <?php selected( get_option( 'ncp_default_provider', 'avalai' ), 'avalai' ); ?>>AvalAI (<?php esc_html_e( 'Iran', 'nafas-chatbot-pro' ); ?>)</option>
										<option value="openai" <?php selected( get_option( 'ncp_default_provider', 'avalai' ), 'openai' ); ?>>OpenAI</option>
										<option value="custom" <?php selected( get_option( 'ncp_default_provider', 'avalai' ), 'custom' ); ?>><?php esc_html_e( 'Custom Endpoint', 'nafas-chatbot-pro' ); ?></option>
									</select>
								</td>
							</tr>
						</table>
					</div>

					<div class="ncp-card">
						<h3>🔑 <?php esc_html_e( 'API Keys', 'nafas-chatbot-pro' ); ?></h3>
						<table class="form-table">
							<tr>
								<th><label for="ncp_avalai_api_key">AvalAI API Key</label></th>
								<td>
									<input name="ncp_avalai_api_key" id="ncp_avalai_api_key" type="password" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_avalai_api_key', '' ) ); ?>" autocomplete="new-password" />
									<p class="description"><?php printf( esc_html__( 'Get yours at %s', 'nafas-chatbot-pro' ), '<a href="https://avalai.ir" target="_blank">avalai.ir</a>' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="ncp_openai_api_key">OpenAI API Key</label></th>
								<td><input name="ncp_openai_api_key" id="ncp_openai_api_key" type="password" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_openai_api_key', '' ) ); ?>" autocomplete="new-password" /></td>
							</tr>
							<tr>
								<th><label for="ncp_custom_api_key"><?php esc_html_e( 'Custom API Key', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_custom_api_key" id="ncp_custom_api_key" type="password" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_custom_api_key', '' ) ); ?>" autocomplete="new-password" /></td>
							</tr>
						</table>
					</div>

					<div class="ncp-card">
						<h3>🔗 Endpoints</h3>
						<table class="form-table">
							<tr>
								<th><label for="ncp_avalai_endpoint">AvalAI Endpoint</label></th>
								<td><input name="ncp_avalai_endpoint" id="ncp_avalai_endpoint" type="url" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_avalai_endpoint', 'https://api.avalai.ir/v1/chat/completions' ) ); ?>" /></td>
							</tr>
							<tr>
								<th><label for="ncp_openai_endpoint">OpenAI Endpoint</label></th>
								<td><input name="ncp_openai_endpoint" id="ncp_openai_endpoint" type="url" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_openai_endpoint', 'https://api.openai.com/v1/chat/completions' ) ); ?>" /></td>
							</tr>
							<tr>
								<th><label for="ncp_custom_endpoint">Custom Endpoint</label></th>
								<td><input name="ncp_custom_endpoint" id="ncp_custom_endpoint" type="url" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_custom_endpoint', '' ) ); ?>" /></td>
							</tr>
						</table>
					</div>

					<div class="ncp-card">
						<h3>🧠 <?php esc_html_e( 'System Prompt', 'nafas-chatbot-pro' ); ?></h3>
						<table class="form-table">
							<tr>
								<th><label for="ncp_system_prompt">System Prompt</label></th>
								<td>
									<textarea name="ncp_system_prompt" id="ncp_system_prompt" class="large-text" rows="5"><?php echo esc_textarea( (string) get_option( 'ncp_system_prompt', '' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'General instructions for the AI assistant.', 'nafas-chatbot-pro' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="ncp_chat_placeholder"><?php esc_html_e( 'Fallback reply (no API)', 'nafas-chatbot-pro' ); ?></label></th>
								<td>
									<textarea name="ncp_chat_placeholder" id="ncp_chat_placeholder" class="large-text" rows="3"><?php echo esc_textarea( (string) get_option( 'ncp_chat_placeholder', 'سپاس از سوال شما. دستیار هوشمند نفس فارمد آماده پاسخگویی است.' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Shown when no API is configured. Use {product} as a placeholder.', 'nafas-chatbot-pro' ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<div class="ncp-card">
						<h3>🚦 <?php esc_html_e( 'Rate Limiting', 'nafas-chatbot-pro' ); ?></h3>
						<table class="form-table">
							<tr>
								<th><label for="ncp_rate_per_minute"><?php esc_html_e( 'Requests per minute (per IP)', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_rate_per_minute" id="ncp_rate_per_minute" type="number" min="1" max="60" value="<?php echo esc_attr( (int) get_option( 'ncp_rate_per_minute', 10 ) ); ?>" /></td>
							</tr>
							<tr>
								<th><label for="ncp_rate_per_hour"><?php esc_html_e( 'Requests per hour (per IP)', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_rate_per_hour" id="ncp_rate_per_hour" type="number" min="1" max="500" value="<?php echo esc_attr( (int) get_option( 'ncp_rate_per_hour', 100 ) ); ?>" /></td>
							</tr>
							<tr>
								<th><label for="ncp_cache_ttl"><?php esc_html_e( 'Cache duration (minutes)', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_cache_ttl" id="ncp_cache_ttl" type="number" min="0" max="1440" value="<?php echo esc_attr( (int) get_option( 'ncp_cache_ttl', 30 ) ); ?>" /></td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Content -->
				<div id="tab-content" class="ncp-tab-content">
					<div class="ncp-card">
						<h2>🏢 <?php esc_html_e( 'Company Information', 'nafas-chatbot-pro' ); ?></h2>
						<table class="form-table">
							<tr>
								<th><label for="ncp_company_name"><?php esc_html_e( 'Company name', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_company_name" id="ncp_company_name" type="text" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_company_name', 'شرکت نفس زیست فارمد' ) ); ?>" /></td>
							</tr>
							<tr>
								<th><label for="ncp_company_id"><?php esc_html_e( 'Company ID', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_company_id" id="ncp_company_id" type="text" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_company_id', 'nafas' ) ); ?>" /></td>
							</tr>
						</table>
					</div>

					<div class="ncp-card">
						<h2>💊 <?php esc_html_e( 'Products', 'nafas-chatbot-pro' ); ?></h2>
						<table class="form-table">
							<tr>
								<th><label for="ncp_products_json"><?php esc_html_e( 'Product list (JSON)', 'nafas-chatbot-pro' ); ?></label></th>
								<td>
									<textarea name="ncp_products_json" id="ncp_products_json" class="large-text code" rows="10"><?php
										echo esc_textarea( (string) get_option( 'ncp_products_json', Nafas_Chatbot_Pro::instance()->default_products_json() ) );
									?></textarea>
									<p class="description"><?php esc_html_e( 'Format:', 'nafas-chatbot-pro' ); ?> <code>[{"id":"capsulizer","name":"Capsulizer"}, ...]</code></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Features -->
				<div id="tab-features" class="ncp-tab-content">
					<div class="ncp-card">
						<h2>⚙️ <?php esc_html_e( 'Default Features', 'nafas-chatbot-pro' ); ?></h2>
						<table class="form-table">
							<?php
							$features = [
								'ncp_show_launcher' => __( 'Show launcher button', 'nafas-chatbot-pro' ),
								'ncp_show_company'  => __( 'Company questions menu', 'nafas-chatbot-pro' ),
								'ncp_show_products' => __( 'Product questions menu', 'nafas-chatbot-pro' ),
								'ncp_show_adr'      => __( 'Adverse drug reaction menu', 'nafas-chatbot-pro' ),
								'ncp_show_consult'  => __( 'Consultation request menu', 'nafas-chatbot-pro' ),
							];
							foreach ( $features as $key => $label ) :
							?>
							<tr>
								<th><?php echo esc_html( $label ); ?></th>
								<td>
									<label>
										<input name="<?php echo esc_attr( $key ); ?>" type="checkbox" value="1" <?php checked( get_option( $key, '1' ), '1' ); ?> />
										<?php esc_html_e( 'Enable', 'nafas-chatbot-pro' ); ?>
									</label>
								</td>
							</tr>
							<?php endforeach; ?>
							<tr>
								<th><label for="ncp_log_enabled"><?php esc_html_e( 'Chat logging', 'nafas-chatbot-pro' ); ?></label></th>
								<td>
									<label>
										<input name="ncp_log_enabled" id="ncp_log_enabled" type="checkbox" value="1" <?php checked( get_option( 'ncp_log_enabled', '1' ), '1' ); ?> />
										<?php esc_html_e( 'Enable (for analytics and quality improvement)', 'nafas-chatbot-pro' ); ?>
									</label>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Design / Appearance -->
				<div id="tab-design" class="ncp-tab-content">

					<!-- ── Theme Presets ── -->
					<div class="ncp-card ncp-presets-card">
						<h2>✨ <?php esc_html_e( 'Theme Presets', 'nafas-chatbot-pro' ); ?></h2>
						<p class="description" style="margin-bottom:1.25rem"><?php esc_html_e( 'One-click signature design packs. Instantly populates all color tokens below — then save to persist.', 'nafas-chatbot-pro' ); ?></p>

						<div class="ncp-presets-grid" id="ncp-presets-grid">
							<?php foreach ( Nafas_Chatbot_Pro::instance()->get_theme_presets() as $preset_id => $preset ) : ?>
							<div class="ncp-preset-card" data-preset="<?php echo esc_attr( $preset_id ); ?>">
								<div class="ncp-preset-swatches">
									<span class="ncp-swatch" style="background:<?php echo esc_attr( $preset['ncp_theme_primary'] ); ?>"></span>
									<span class="ncp-swatch" style="background:<?php echo esc_attr( $preset['ncp_theme_bg_base'] ); ?>"></span>
									<span class="ncp-swatch" style="background:<?php echo esc_attr( $preset['ncp_theme_bg_card'] ); ?>"></span>
									<span class="ncp-swatch" style="background:<?php echo esc_attr( $preset['ncp_theme_text_base'] ); ?>"></span>
								</div>
								<div class="ncp-preset-icon"><?php echo $preset['icon']; ?></div>
								<div class="ncp-preset-name"><?php echo esc_html( $preset['label'] ); ?></div>
								<div class="ncp-preset-desc"><?php echo esc_html( $preset['description'] ); ?></div>
								<button type="button" class="button ncp-apply-preset" data-preset="<?php echo esc_attr( $preset_id ); ?>">
									<?php esc_html_e( 'Apply Preset', 'nafas-chatbot-pro' ); ?>
								</button>
							</div>
							<?php endforeach; ?>
						</div>

						<div id="ncp-preset-notice" class="ncp-preset-notice" style="display:none">
							<span class="dashicons dashicons-yes-alt"></span>
							<strong id="ncp-preset-notice-text"></strong>
							<span class="ncp-preset-save-hint"><?php esc_html_e( '— click Save Settings below to persist.', 'nafas-chatbot-pro' ); ?></span>
						</div>
					</div>

					<!-- ── Dimensions & Font ── -->
					<div class="ncp-card">
						<h2>📐 <?php esc_html_e( 'Dimensions & Font', 'nafas-chatbot-pro' ); ?></h2>
						<table class="form-table">
							<tr>
								<th><label for="ncp_panel_width"><?php esc_html_e( 'Panel width', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_panel_width" id="ncp_panel_width" type="text" class="small-text" value="<?php echo esc_attr( (string) get_option( 'ncp_panel_width', '380px' ) ); ?>" placeholder="380px" /></td>
							</tr>
							<tr>
								<th><label for="ncp_panel_height"><?php esc_html_e( 'Panel height', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_panel_height" id="ncp_panel_height" type="text" class="small-text" value="<?php echo esc_attr( (string) get_option( 'ncp_panel_height', '600px' ) ); ?>" placeholder="600px" /></td>
							</tr>
							<tr>
								<th><label for="ncp_launcher_position"><?php esc_html_e( 'Launcher position', 'nafas-chatbot-pro' ); ?></label></th>
								<td>
									<select name="ncp_launcher_position" id="ncp_launcher_position">
										<option value="right" <?php selected( get_option( 'ncp_launcher_position', 'right' ), 'right' ); ?>><?php esc_html_e( 'Right', 'nafas-chatbot-pro' ); ?></option>
										<option value="left"  <?php selected( get_option( 'ncp_launcher_position', 'right' ), 'left' ); ?>><?php esc_html_e( 'Left', 'nafas-chatbot-pro' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><label for="ncp_font_family"><?php esc_html_e( 'Font family', 'nafas-chatbot-pro' ); ?></label></th>
								<td><input name="ncp_font_family" id="ncp_font_family" type="text" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_font_family', 'Vazirmatn, IRANSansX, Tahoma, sans-serif' ) ); ?>" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Font sizes', 'nafas-chatbot-pro' ); ?></th>
								<td>
									<label><?php esc_html_e( 'Heading:', 'nafas-chatbot-pro' ); ?> <input name="ncp_font_size_heading" type="text" class="small-text" value="<?php echo esc_attr( (string) get_option( 'ncp_font_size_heading', '1.125rem' ) ); ?>" /></label> &nbsp;
									<label><?php esc_html_e( 'Body:', 'nafas-chatbot-pro' ); ?> <input name="ncp_font_size_body" type="text" class="small-text" value="<?php echo esc_attr( (string) get_option( 'ncp_font_size_body', '0.9375rem' ) ); ?>" /></label> &nbsp;
									<label><?php esc_html_e( 'Caption:', 'nafas-chatbot-pro' ); ?> <input name="ncp_font_size_caption" type="text" class="small-text" value="<?php echo esc_attr( (string) get_option( 'ncp_font_size_caption', '0.8125rem' ) ); ?>" /></label>
								</td>
							</tr>
						</table>
					</div>

					<!-- ── Color Tokens ── -->
					<div class="ncp-card">
						<div class="ncp-colors-header">
							<h2>🎨 <?php esc_html_e( 'Color Tokens', 'nafas-chatbot-pro' ); ?></h2>
							<button type="button" id="ncp-toggle-preview" class="button">
								👁 <?php esc_html_e( 'Live Preview', 'nafas-chatbot-pro' ); ?>
							</button>
						</div>

						<table class="form-table" id="ncp-color-table">
							<?php
							$colors = [
								'ncp_theme_primary'       => __( 'Primary color', 'nafas-chatbot-pro' ),
								'ncp_theme_primary_hover' => __( 'Primary hover', 'nafas-chatbot-pro' ),
								'ncp_theme_bg_base'       => __( 'Base background', 'nafas-chatbot-pro' ),
								'ncp_theme_bg_card'       => __( 'Card background', 'nafas-chatbot-pro' ),
								'ncp_theme_border'        => __( 'Border color', 'nafas-chatbot-pro' ),
								'ncp_theme_text_base'     => __( 'Base text color', 'nafas-chatbot-pro' ),
								'ncp_theme_text_muted'    => __( 'Muted text color', 'nafas-chatbot-pro' ),
								'ncp_theme_control_bg'    => __( 'Control background', 'nafas-chatbot-pro' ),
								'ncp_theme_control_hover' => __( 'Control hover', 'nafas-chatbot-pro' ),
								'ncp_theme_control_text'  => __( 'Control text', 'nafas-chatbot-pro' ),
							];
							$defaults = [
								'ncp_theme_primary'       => '#b01618',
								'ncp_theme_primary_hover' => '#8c0f11',
								'ncp_theme_bg_base'       => '#f7f5f2',
								'ncp_theme_bg_card'       => '#ffffff',
								'ncp_theme_border'        => '#e6e1da',
								'ncp_theme_text_base'     => '#1c1a18',
								'ncp_theme_text_muted'    => '#6a625a',
								'ncp_theme_control_bg'    => '#efe9e2',
								'ncp_theme_control_hover' => '#e4ddd6',
								'ncp_theme_control_text'  => '#3c352f',
							];
							foreach ( $colors as $key => $label ) :
							?>
							<tr>
								<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
								<td>
									<input type="color"
										name="<?php echo esc_attr( $key ); ?>"
										id="<?php echo esc_attr( $key ); ?>"
										class="ncp-color-input"
										data-token="<?php echo esc_attr( $key ); ?>"
										value="<?php echo esc_attr( (string) get_option( $key, $defaults[ $key ] ) ); ?>" />
									<input type="text"
										name="<?php echo esc_attr( $key ); ?>_txt"
										class="small-text ncp-color-txt"
										value="<?php echo esc_attr( (string) get_option( $key, $defaults[ $key ] ) ); ?>"
										readonly />
								</td>
							</tr>
							<?php endforeach; ?>
						</table>

						<!-- Live Preview -->
						<div id="ncp-live-preview" class="ncp-live-preview" style="display:none">
							<p class="ncp-preview-label">👁 <?php esc_html_e( 'Live Preview', 'nafas-chatbot-pro' ); ?></p>
							<div class="ncp-preview-panel" id="ncp-preview-panel">
								<div class="ncp-preview-header">
									<span class="ncp-preview-title"><?php esc_html_e( 'AI Assistant', 'nafas-chatbot-pro' ); ?></span>
									<span class="ncp-preview-dot"></span>
									<span class="ncp-preview-online"><?php esc_html_e( 'Online', 'nafas-chatbot-pro' ); ?></span>
								</div>
								<div class="ncp-preview-body">
									<div class="ncp-preview-msg ncp-preview-bot"><?php esc_html_e( 'Hello! How can I help you today?', 'nafas-chatbot-pro' ); ?></div>
									<div class="ncp-preview-msg ncp-preview-user"><?php esc_html_e( 'Tell me about your products.', 'nafas-chatbot-pro' ); ?></div>
									<div class="ncp-preview-msg ncp-preview-bot"><?php esc_html_e( 'Of course! We have several pharmaceutical products available...', 'nafas-chatbot-pro' ); ?></div>
								</div>
								<div class="ncp-preview-footer">
									<span class="ncp-preview-input-area"><?php esc_html_e( 'Type your message…', 'nafas-chatbot-pro' ); ?></span>
									<button class="ncp-preview-send">➤</button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Notifications -->
				<div id="tab-notifications" class="ncp-tab-content">
					<div class="ncp-card">
						<h2>🔔 <?php esc_html_e( 'Bale Notifications', 'nafas-chatbot-pro' ); ?></h2>
						<table class="form-table">
							<tr>
								<th><label for="ncp_bale_token">Bale Bot Token</label></th>
								<td><input name="ncp_bale_token" id="ncp_bale_token" type="password" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_bale_token', '' ) ); ?>" autocomplete="new-password" /></td>
							</tr>
							<tr>
								<th><label for="ncp_bale_chat_id">Bale Chat ID</label></th>
								<td><input name="ncp_bale_chat_id" id="ncp_bale_chat_id" type="text" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_bale_chat_id', '' ) ); ?>" /></td>
							</tr>
						</table>
					</div>

					<div class="ncp-card">
						<h2>📨 <?php esc_html_e( 'Telegram Notifications', 'nafas-chatbot-pro' ); ?></h2>
						<table class="form-table">
							<tr>
								<th><label for="ncp_telegram_token">Telegram Bot Token</label></th>
								<td><input name="ncp_telegram_token" id="ncp_telegram_token" type="password" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_telegram_token', '' ) ); ?>" autocomplete="new-password" /></td>
							</tr>
							<tr>
								<th><label for="ncp_telegram_chat_id">Telegram Chat ID</label></th>
								<td><input name="ncp_telegram_chat_id" id="ncp_telegram_chat_id" type="text" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_telegram_chat_id', '' ) ); ?>" /></td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Advanced -->
				<div id="tab-advanced" class="ncp-tab-content">
					<div class="ncp-card">
						<h2>🔧 <?php esc_html_e( 'External APIs', 'nafas-chatbot-pro' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Connect to external systems by setting the following endpoints.', 'nafas-chatbot-pro' ); ?></p>
						<table class="form-table">
							<tr>
								<th><label for="ncp_external_chat_api">External Chat API</label></th>
								<td>
									<input name="ncp_external_chat_api" id="ncp_external_chat_api" type="url" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_external_chat_api', '' ) ); ?>" />
									<p class="description"><?php esc_html_e( 'If empty, the AI provider above is used directly.', 'nafas-chatbot-pro' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="ncp_external_submit_api">External Form Submit API</label></th>
								<td><input name="ncp_external_submit_api" id="ncp_external_submit_api" type="url" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'ncp_external_submit_api', '' ) ); ?>" /></td>
							</tr>
						</table>
					</div>

					<div class="ncp-card ncp-card-info">
						<h2>📋 <?php esc_html_e( 'Shortcodes', 'nafas-chatbot-pro' ); ?></h2>
						<p><?php esc_html_e( 'Simple usage:', 'nafas-chatbot-pro' ); ?> <code>[nafas_chatbot]</code></p>
						<p><?php esc_html_e( 'With parameters:', 'nafas-chatbot-pro' ); ?> <code>[nafas_chatbot company_name="My Company" theme_primary="#0066cc" show_consult="true"]</code></p>
						<p><?php esc_html_e( 'All Elementor Widget parameters are also supported in shortcodes.', 'nafas-chatbot-pro' ); ?></p>
						<h3><?php esc_html_e( 'Parameters', 'nafas-chatbot-pro' ); ?></h3>
						<table class="widefat striped" style="max-width:600px">
							<thead><tr><th><?php esc_html_e( 'Parameter', 'nafas-chatbot-pro' ); ?></th><th><?php esc_html_e( 'Example', 'nafas-chatbot-pro' ); ?></th></tr></thead>
							<tbody>
								<tr><td>company_name</td><td>"My Company"</td></tr>
								<tr><td>provider</td><td>"avalai" | "openai" | "custom"</td></tr>
								<tr><td>model</td><td>"gpt-4o-mini"</td></tr>
								<tr><td>show_launcher</td><td>"true" | "false"</td></tr>
								<tr><td>theme_primary</td><td>"#b01618"</td></tr>
								<tr><td>panel_width</td><td>"380px"</td></tr>
								<tr><td>text_direction</td><td>"rtl" | "ltr"</td></tr>
								<tr><td>theme_mode</td><td>"auto" | "light" | "dark"</td></tr>
							</tbody>
						</table>
					</div>
				</div>

				<?php submit_button( __( 'Save Settings', 'nafas-chatbot-pro' ), 'primary large', 'submit', true ); ?>
			</form>
		</div>

		<!-- Metrics -->
		<div class="ncp-admin-metrics">
			<h2>📊 <?php esc_html_e( 'Statistics', 'nafas-chatbot-pro' ); ?></h2>
			<div class="ncp-metrics-grid">
				<?php
				global $wpdb;
				$table   = $wpdb->prefix . NCP_TABLE;
				$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
				$today   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", gmdate( 'Y-m-d' ) ) );
				$cached  = (int) get_option( 'ncp_metric_cache_hits', 0 );
				$success = (int) get_option( 'ncp_metric_api_success', 0 );
				$errors  = (int) get_option( 'ncp_metric_api_errors', 0 );
				$metrics = [
					[ __( 'Total chats', 'nafas-chatbot-pro' ), $total,   '💬' ],
					[ __( 'Today',       'nafas-chatbot-pro' ), $today,   '📅' ],
					[ __( 'Cache hits',  'nafas-chatbot-pro' ), $cached,  '⚡' ],
					[ __( 'API success', 'nafas-chatbot-pro' ), $success, '✅' ],
					[ __( 'API errors',  'nafas-chatbot-pro' ), $errors,  '❌' ],
				];
				foreach ( $metrics as [ $label, $value, $icon ] ) :
				?>
				<div class="ncp-metric-card">
					<span class="ncp-metric-icon"><?php echo $icon; ?></span>
					<span class="ncp-metric-value"><?php echo number_format( $value ); ?></span>
					<span class="ncp-metric-label"><?php echo esc_html( $label ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ncp-dashboard-logs' ) ); ?>"><?php esc_html_e( 'View full log', 'nafas-chatbot-pro' ); ?></a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=ncp_export_log&format=csv' ), NCP_NONCE ) ); ?>"><?php esc_html_e( 'Download CSV', 'nafas-chatbot-pro' ); ?></a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=ncp_export_log&format=json' ), NCP_NONCE ) ); ?>"><?php esc_html_e( 'Download JSON', 'nafas-chatbot-pro' ); ?></a>
			</p>
		</div>
	</div>
	<?php
}
