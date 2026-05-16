<?php
/**
 * API Handler - AJAX Endpoints
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX API Handler
 */
class NCP_API {

	/**
	 * Register AJAX endpoints
	 */
	public static function register_endpoints(): void {
		add_action( 'wp_ajax_ncp_chat', [ self::class, 'handle_chat' ] );
		add_action( 'wp_ajax_nopriv_ncp_chat', [ self::class, 'handle_chat' ] );
		
		add_action( 'wp_ajax_ncp_form_submit', [ self::class, 'handle_form_submit' ] );
		add_action( 'wp_ajax_nopriv_ncp_form_submit', [ self::class, 'handle_form_submit' ] );
		
		add_action( 'wp_ajax_ncp_feedback', [ self::class, 'handle_feedback' ] );
		add_action( 'wp_ajax_nopriv_ncp_feedback', [ self::class, 'handle_feedback' ] );
		
		// Admin endpoints
		add_action( 'wp_ajax_ncp_export_log', [ self::class, 'admin_export_log' ] );
		add_action( 'wp_ajax_ncp_clear_log', [ self::class, 'admin_clear_log' ] );
		add_action( 'wp_ajax_ncp_analytics', [ self::class, 'admin_get_analytics' ] );
	}

	/**
	 * Handle chat message
	 */
	public static function handle_chat(): void {
		try {
			// Security checks
			NCP_Security::verify_nonce( NCP_NONCE_CHAT );
			NCP_Security::apply_rate_limit();
			
			// Sanitize inputs
			$message       = NCP_Security::sanitize_message( $_POST['message'] ?? '' );
			$provider      = NCP_Security::sanitize_provider( $_POST['provider'] ?? '' );
			$model         = NCP_Security::sanitize_model( $_POST['model'] ?? '' );
			$temperature   = NCP_Security::sanitize_temperature( $_POST['temperature'] ?? 0.7 );
			$max_tokens    = NCP_Security::sanitize_max_tokens( $_POST['max_tokens'] ?? 512 );
			$system_prompt = sanitize_textarea_field( wp_unslash( $_POST['system_prompt'] ?? '' ) );
			$history       = NCP_Security::sanitize_history( $_POST['history'] ?? '' );
			$session_id    = NCP_Security::sanitize_session( $_POST['session_id'] ?? '' );
			
			// Get provider
			$provider_manager = NCP_Provider_Manager::instance();
			$ai_provider = $provider_manager->get( $provider );
			
			if ( ! $ai_provider ) {
				throw new Exception( __( 'Provider not found.', 'nafas-chatbot-pro' ) );
			}
			
			if ( ! $ai_provider->validate_config() ) {
				$placeholder = NCP_Configuration::instance()->get( 'ncp_chat_placeholder' );
				wp_send_json_success( [ 
					'message'     => $placeholder,
					'cached'      => false,
					'placeholder' => true,
				] );
			}
			
			// Check cache
			$cache_enabled = NCP_ENABLE_CACHING;
			$cache_key = $cache_enabled ? NCP_CACHE_KEY_PREFIX . 'response_' . md5(
				$provider . $message . $model . $temperature . $max_tokens . $system_prompt
			) : '';
			
			if ( $cache_enabled && $cache_key ) {
				$cached = get_transient( $cache_key );
				if ( false !== $cached ) {
					$cache_ttl = (int) NCP_Configuration::instance()->get( 'ncp_cache_ttl', 30 );
					NCP_Database::log_chat( $session_id, $provider, $model, $message, $cached, 0, 0, true );
					wp_send_json_success( [ 
						'message' => $cached,
						'cached'  => true,
					] );
				}
			}
			
			// Build message array
			$messages = [];
			if ( $system_prompt ) {
				$messages[] = [ 'role' => 'system', 'content' => $system_prompt ];
			}
			foreach ( $history as $h ) {
				$messages[] = $h;
			}
			$messages[] = [ 'role' => 'user', 'content' => $message ];
			
			// Call AI provider
			$response_data = $ai_provider->send_message( $messages, compact( 'model', 'temperature', 'max_tokens' ) );
			
			if ( empty( $response_data ) ) {
				throw new Exception( __( 'No response from AI service.', 'nafas-chatbot-pro' ) );
			}
			
			// Extract response
			$reply  = NCP_Provider_Response::extract_text( $response_data );
			$tokens = NCP_Provider_Response::extract_tokens( $response_data );
			
			if ( ! $reply ) {
				throw new Exception( __( 'Empty response from AI service.', 'nafas-chatbot-pro' ) );
			}
			
			// Cache response
			if ( $cache_enabled && $cache_key ) {
				$cache_ttl = (int) NCP_Configuration::instance()->get( 'ncp_cache_ttl', 30 );
				set_transient( $cache_key, $reply, $cache_ttl * MINUTE_IN_SECONDS );
			}
			
			// Log chat
			NCP_Database::log_chat( $session_id, $provider, $model, $message, $reply, $tokens, 0, false );
			
			// Send response
			wp_send_json_success( [ 
				'message' => $reply,
				'tokens'  => $tokens,
				'cached'  => false,
			] );
			
		} catch ( Exception $e ) {
			wp_send_json_error( [ 
				'message' => $e->getMessage(),
			], 500 );
		}
	}

	/**
	 * Handle form submission
	 */
	public static function handle_form_submit(): void {
		try {
			NCP_Security::verify_nonce( NCP_NONCE_CHAT );
			NCP_Security::apply_rate_limit();
			
			// Check honeypot
			if ( ! NCP_Security::check_honeypot() ) {
				throw new Exception( __( 'Form validation failed.', 'nafas-chatbot-pro' ) );
			}
			
			// Sanitize form data
			$type        = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'Unknown' ) );
			$name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			$phone       = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
			$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
			$product     = sanitize_text_field( wp_unslash( $_POST['product'] ?? '' ) );
			
			// Validate
			if ( mb_strlen( $name ) < 2 || mb_strlen( $name ) > 80 ) {
				throw new Exception( __( 'Invalid name.', 'nafas-chatbot-pro' ) );
			}
			
			if ( ! NCP_Security::validate_phone( $phone ) ) {
				throw new Exception( __( 'Invalid phone number.', 'nafas-chatbot-pro' ) );
			}
			
			$desc_len = mb_strlen( $description );
			if ( $desc_len < 5 || $desc_len > 2000 ) {
				throw new Exception( __( 'Description must be between 5 and 2000 characters.', 'nafas-chatbot-pro' ) );
			}
			
			// Send to notification services
			$config = NCP_Configuration::instance();
			
			if ( $config->get( 'ncp_bale_token' ) && $config->get( 'ncp_bale_chat_id' ) ) {
				self::send_bale_notification( $type, $name, $phone, $description, $product );
			}
			
			if ( $config->get( 'ncp_telegram_token' ) && $config->get( 'ncp_telegram_chat_id' ) ) {
				self::send_telegram_notification( $type, $name, $phone, $description, $product );
			}
			
			// Send to external API
			if ( $config->get( 'ncp_external_submit_api' ) ) {
				self::send_external_notification( $type, $name, $phone, $description, $product );
			}
			
			wp_send_json_success( [ 
				'message' => __( 'Submitted successfully.', 'nafas-chatbot-pro' ),
			] );
			
		} catch ( Exception $e ) {
			wp_send_json_error( [ 
				'message' => $e->getMessage(),
			], 400 );
		}
	}

	/**
	 * Handle user feedback
	 */
	public static function handle_feedback(): void {
		try {
			NCP_Security::verify_nonce( NCP_NONCE_CHAT );
			
			$session_id = NCP_Security::sanitize_session( $_POST['session_id'] ?? '' );
			$rating     = (int) ( $_POST['rating'] ?? 0 );
			$comment    = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
			
			if ( $rating < 1 || $rating > 5 ) {
				throw new Exception( __( 'Invalid rating.', 'nafas-chatbot-pro' ) );
			}
			
			NCP_Database::record_feedback( $session_id, $rating, $comment );
			
			wp_send_json_success( [ 
				'message' => __( 'Thank you for your feedback!', 'nafas-chatbot-pro' ),
			] );
			
		} catch ( Exception $e ) {
			wp_send_json_error( [ 
				'message' => $e->getMessage(),
			], 400 );
		}
	}

	/**
	 * Admin: Export logs
	 */
	public static function admin_export_log(): void {
		NCP_Security::verify_capability( 'manage_options' );
		NCP_Security::verify_nonce( NCP_NONCE_EXPORT, 'nonce' );
		
		try {
			$logs = NCP_Database::export_logs();
			
			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=ncp-chat-log.json' );
			
			echo NCP_Security::json_encode( $logs );
			exit;
			
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Admin: Clear logs
	 */
	public static function admin_clear_log(): void {
		NCP_Security::verify_capability( 'manage_options' );
		NCP_Security::verify_nonce( NCP_NONCE_SETTINGS, 'nonce' );
		
		try {
			NCP_Database::clear_logs();
			wp_send_json_success( [ 'message' => __( 'Logs cleared successfully.', 'nafas-chatbot-pro' ) ] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Admin: Get analytics
	 */
	public static function admin_get_analytics(): void {
		NCP_Security::verify_capability( 'manage_options' );
		NCP_Security::verify_nonce( NCP_NONCE_SETTINGS, 'nonce' );
		
		try {
			$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
			$date_to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
			
			$analytics = NCP_Database::get_analytics( $date_from, $date_to );
			$by_provider = NCP_Database::get_analytics_by_provider();
			
			wp_send_json_success( [ 
				'analytics'      => $analytics,
				'by_provider'    => $by_provider,
			] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	private static function send_bale_notification( string $type, string $name, string $phone, string $description, string $product ): void {
		$config = NCP_Configuration::instance();
		$token  = $config->get( 'ncp_bale_token' );
		$chat_id = $config->get( 'ncp_bale_chat_id' );
		
		$text = sprintf(
			"*New request from Nafas Chatbot*\n\n*Type:* %s\n*Name:* %s\n*Phone:* %s\n*Product:* %s\n\n*Description:*\n%s\n\n_At: %s_",
			$type,
			$name,
			$phone,
			$product ?: 'N/A',
			$description,
			wp_date( 'H:i - Y/m/d' )
		);
		
		wp_remote_get(
			'https://tapi.bale.ai/bot' . rawurlencode( $token ) . '/sendMessage?' . http_build_query( [
				'chat_id' => $chat_id,
				'text'    => $text,
			] ),
			[ 'timeout' => NCP_EXTERNAL_API_TIMEOUT ]
		);
	}

	private static function send_telegram_notification( string $type, string $name, string $phone, string $description, string $product ): void {
		$config = NCP_Configuration::instance();
		$token  = $config->get( 'ncp_telegram_token' );
		$chat_id = $config->get( 'ncp_telegram_chat_id' );
		
		$text = sprintf(
			"<b>New request from Nafas Chatbot</b>\n\n<b>Type:</b> %s\n<b>Name:</b> %s\n<b>Phone:</b> %s\n<b>Product:</b> %s\n\n<b>Description:</b>\n%s\n\n<i>At: %s</i>",
			$type,
			$name,
			$phone,
			$product ?: 'N/A',
			$description,
			wp_date( 'H:i - Y/m/d' )
		);
		
		wp_remote_post(
			"https://api.telegram.org/bot{$token}/sendMessage",
			[
				'body'    => [ 'chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML' ],
				'timeout' => NCP_EXTERNAL_API_TIMEOUT,
			]
		);
	}

	private static function send_external_notification( string $type, string $name, string $phone, string $description, string $product ): void {
		$config = NCP_Configuration::instance();
		$endpoint = $config->get( 'ncp_external_submit_api' );
		
		wp_remote_post(
			NCP_Security::esc_url( $endpoint ),
			[
				'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
				'body'    => NCP_Security::json_encode( compact( 'type', 'name', 'phone', 'description', 'product' ) ),
				'timeout' => NCP_EXTERNAL_API_TIMEOUT,
			]
		);
	}
}
