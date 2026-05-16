<?php
/**
 * Security Module - Comprehensive Security Features
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security Manager
 */
class NCP_Security {

	/**
	 * Verify nonce and die if invalid
	 */
	public static function verify_nonce( string $action = '', string $param_name = 'nonce' ): void {
		$action = $action ?: NCP_NONCE_CHAT;
		$nonce = sanitize_text_field( wp_unslash( $_POST[ $param_name ] ?? $_GET[ $param_name ] ?? '' ) );
		
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( 
				[ 'message' => __( 'Security verification failed. Invalid nonce.', 'nafas-chatbot-pro' ) ], 
				403 
			);
		}
	}

	/**
	 * Verify current user can perform action
	 */
	public static function verify_capability( string $cap = 'manage_options' ): void {
		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error( 
				[ 'message' => __( 'You do not have permission to perform this action.', 'nafas-chatbot-pro' ) ], 
				403 
			);
		}
	}

	/**
	 * Sanitize message input with rate limiting
	 */
	public static function sanitize_message( string $message ): string {
		$message = sanitize_textarea_field( wp_unslash( $message ) );
		$message = trim( $message );
		
		// Remove potential XSS vectors
		$message = wp_kses_post( $message );
		
		// Check length
		if ( mb_strlen( $message ) < NCP_MIN_MESSAGE_LENGTH ) {
			throw new Exception( __( 'Message is too short.', 'nafas-chatbot-pro' ) );
		}
		
		if ( mb_strlen( $message ) > NCP_MAX_MESSAGE_LENGTH ) {
			throw new Exception( 
				sprintf( __( 'Message exceeds maximum length of %d characters.', 'nafas-chatbot-pro' ), NCP_MAX_MESSAGE_LENGTH )
			);
		}
		
		return $message;
	}

	/**
	 * Sanitize and validate provider
	 */
	public static function sanitize_provider( string $provider ): string {
		$allowed = [ 'avalai', 'openai', 'custom' ];
		$provider = sanitize_key( $provider );
		
		return in_array( $provider, $allowed, true ) ? $provider : 'avalai';
	}

	/**
	 * Sanitize model name
	 */
	public static function sanitize_model( string $model ): string {
		$model = sanitize_text_field( $model );
		
		// Allow alphanumeric, dots, hyphens, colons, underscores
		if ( ! preg_match( '/^[a-zA-Z0-9._:\-]+$/', $model ) ) {
			return 'gpt-4o-mini';
		}
		
		return substr( $model, 0, 100 );
	}

	/**
	 * Sanitize and validate session ID
	 */
	public static function sanitize_session( string $session ): string {
		$session = sanitize_text_field( $session );
		
		if ( '' === $session ) {
			return wp_generate_uuid4();
		}
		
		return substr( $session, 0, 128 );
	}

	/**
	 * Sanitize history array
	 */
	public static function sanitize_history( $raw ): array {
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
			
			$role    = in_array( $item['role'] ?? '', [ 'user', 'assistant', 'system' ], true ) ? $item['role'] : '';
			$content = sanitize_textarea_field( (string) ( $item['content'] ?? '' ) );
			$content = wp_kses_post( $content );
			
			if ( '' !== $role && '' !== $content && mb_strlen( $content ) <= NCP_MAX_MESSAGE_LENGTH ) {
				$out[] = compact( 'role', 'content' );
			}
		}
		
		// Limit history length
		return array_slice( $out, -NCP_MAX_HISTORY_LENGTH );
	}

	/**
	 * Sanitize API key
	 */
	public static function sanitize_api_key( string $key ): string {
		$key = sanitize_text_field( $key );
		
		// Remove whitespace
		$key = trim( $key );
		
		// Allow alphanumeric, hyphens, underscores, and dots
		if ( ! preg_match( '/^[a-zA-Z0-9\-_.]+$/', $key ) ) {
			return '';
		}
		
		return substr( $key, 0, 500 );
	}

	/**
	 * Sanitize temperature (0.0 - 2.0)
	 */
	public static function sanitize_temperature( $value ): float {
		$temp = (float) $value;
		return min( 2.0, max( 0.0, $temp ) );
	}

	/**
	 * Sanitize max tokens
	 */
	public static function sanitize_max_tokens( $value ): int {
		$tokens = (int) $value;
		return min( NCP_MAX_TOKENS, max( NCP_MIN_TOKENS, $tokens ) );
	}

	/**
	 * Get user IP address (with spoofing protection)
	 */
	public static function get_user_ip(): string {
		$ip_sources = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
		
		foreach ( $ip_sources as $source ) {
			if ( empty( $_SERVER[ $source ] ) ) {
				continue;
			}
			
			// Handle multiple IPs (take first one)
			$ips = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $source ] ) ) ) );
			$ip  = $ips[0] ?? '0.0.0.0';
			
			// Validate IP
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		
		return '0.0.0.0';
	}

	/**
	 * IP-based rate limiting
	 */
	public static function apply_rate_limit(): void {
		$ip    = self::get_user_ip();
		$hash  = md5( $ip );
		
		$config = NCP_Configuration::instance();
		$limits = $config->get_rate_limits();
		
		$k_min  = NCP_CACHE_KEY_PREFIX . 'rm_' . $hash;
		$k_hour = NCP_CACHE_KEY_PREFIX . 'rh_' . $hash;
		
		$count_min  = (int) get_transient( $k_min );
		$count_hour = (int) get_transient( $k_hour );
		
		if ( $count_min >= $limits['per_minute'] || $count_hour >= $limits['per_hour'] ) {
			wp_send_json_error( 
				[ 'message' => __( 'Too many requests. Please try again later.', 'nafas-chatbot-pro' ) ], 
				429 
			);
		}
		
		set_transient( $k_min, $count_min + 1, MINUTE_IN_SECONDS );
		set_transient( $k_hour, $count_hour + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Output escaping for HTML context
	 */
	public static function esc_html( $text ): string {
		return wp_kses_post( $text );
	}

	/**
	 * Output escaping for attributes
	 */
	public static function esc_attr( $text ): string {
		return esc_attr( $text );
	}

	/**
	 * Output escaping for URLs
	 */
	public static function esc_url( $url ): string {
		return esc_url_raw( $url );
	}

	/**
	 * Validate JSON string
	 */
	public static function validate_json( $json ): bool {
		if ( ! is_string( $json ) ) {
			return false;
		}
		
		$decoded = json_decode( $json, true );
		return is_array( $decoded );
	}

	/**
	 * Escape JSON for JavaScript
	 */
	public static function json_encode( $data ): string {
		return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Create CSRF token
	 */
	public static function create_nonce_field( string $action = '' ): string {
		$action = $action ?: NCP_NONCE_CHAT;
		return wp_nonce_field( $action, 'nonce', true, false );
	}

	/**
	 * Get CSRF token value
	 */
	public static function get_nonce( string $action = '' ): string {
		$action = $action ?: NCP_NONCE_CHAT;
		return wp_create_nonce( $action );
	}

	/**
	 * Honeypot protection for forms
	 */
	public static function create_honeypot(): string {
		return '<input type="text" name="website" style="display:none;" autocomplete="off" tabindex="-1">';
	}

	/**
	 * Check honeypot field
	 */
	public static function check_honeypot(): bool {
		$website = sanitize_text_field( wp_unslash( $_POST['website'] ?? '' ) );
		return '' === $website;
	}

	/**
	 * Validate email
	 */
	public static function validate_email( string $email ): bool {
		return is_email( $email );
	}

	/**
	 * Validate phone number (Iranian format)
	 */
	public static function validate_phone( string $phone ): bool {
		// Accept Iranian phone numbers: +989XXXXXXXXX, 09XXXXXXXXX, or 989XXXXXXXXX
		$pattern = '/^(?:\+98|0)?9\d{9}$/';
		return (bool) preg_match( $pattern, $phone );
	}
}
