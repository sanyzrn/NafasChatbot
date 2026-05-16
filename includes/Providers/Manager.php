<?php
/**
 * AI Providers System
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base AI Provider Interface
 */
interface NCP_Provider_Interface {
	public function send_message( array $messages, array $options ): array;
	public function validate_config(): bool;
	public function get_name(): string;
	public function get_models(): array;
}

/**
 * Provider Manager
 */
class NCP_Provider_Manager {
	private static ?self $instance = null;
	private array $providers = [];

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register_default_providers();
		}
		return self::$instance;
	}

	private function register_default_providers(): void {
		$this->register( 'avalai', new NCP_Provider_AvalAI() );
		$this->register( 'openai', new NCP_Provider_OpenAI() );
		$this->register( 'custom', new NCP_Provider_Custom() );
	}

	/**
	 * Register a provider
	 */
	public function register( string $id, NCP_Provider_Interface $provider ): void {
		$this->providers[ $id ] = $provider;
	}

	/**
	 * Get provider instance
	 */
	public function get( string $provider_id ): ?NCP_Provider_Interface {
		return $this->providers[ $provider_id ] ?? null;
	}

	/**
	 * Get all providers
	 */
	public function get_all(): array {
		return $this->providers;
	}

	/**
	 * Check if provider exists
	 */
	public function exists( string $provider_id ): bool {
		return isset( $this->providers[ $provider_id ] );
	}
}

/**
 * AvalAI Provider
 */
class NCP_Provider_AvalAI implements NCP_Provider_Interface {

	public function send_message( array $messages, array $options ): array {
		$config = NCP_Configuration::instance();
		$endpoint = $config->get( 'ncp_avalai_endpoint' );
		$api_key = $config->get( 'ncp_avalai_api_key' );
		
		if ( ! $endpoint || ! $api_key ) {
			throw new Exception( __( 'AvalAI endpoint or API key not configured.', 'nafas-chatbot-pro' ) );
		}
		
		$payload = [
			'model'       => $options['model'] ?? 'gpt-4o-mini',
			'messages'    => $messages,
			'temperature' => $options['temperature'] ?? 0.7,
			'max_tokens'  => $options['max_tokens'] ?? 512,
		];
		
		return $this->make_request( $endpoint, $api_key, $payload );
	}

	private function make_request( string $endpoint, string $api_key, array $payload ): array {
		$response = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'Content-Type'  => 'application/json; charset=utf-8',
					'Authorization' => 'Bearer ' . $api_key,
				],
				'body'    => NCP_Security::json_encode( $payload ),
				'timeout' => NCP_API_TIMEOUT,
			]
		);
		
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		
		if ( $code >= 300 ) {
			$error_msg = $decoded['error']['message'] ?? 'Unknown error';
			throw new Exception( $error_msg );
		}
		
		return $decoded ?: [];
	}

	public function validate_config(): bool {
		$config = NCP_Configuration::instance();
		return ! empty( $config->get( 'ncp_avalai_api_key' ) );
	}

	public function get_name(): string {
		return 'AvalAI';
	}

	public function get_models(): array {
		return [
			'gpt-4o-mini'   => 'GPT-4o Mini',
			'gpt-4o'        => 'GPT-4o',
			'gpt-4-turbo'   => 'GPT-4 Turbo',
		];
	}
}

/**
 * OpenAI Provider
 */
class NCP_Provider_OpenAI implements NCP_Provider_Interface {

	public function send_message( array $messages, array $options ): array {
		$config = NCP_Configuration::instance();
		$endpoint = $config->get( 'ncp_openai_endpoint' );
		$api_key = $config->get( 'ncp_openai_api_key' );
		
		if ( ! $endpoint || ! $api_key ) {
			throw new Exception( __( 'OpenAI endpoint or API key not configured.', 'nafas-chatbot-pro' ) );
		}
		
		$payload = [
			'model'       => $options['model'] ?? 'gpt-4o-mini',
			'messages'    => $messages,
			'temperature' => $options['temperature'] ?? 0.7,
			'max_tokens'  => $options['max_tokens'] ?? 512,
		];
		
		return $this->make_request( $endpoint, $api_key, $payload );
	}

	private function make_request( string $endpoint, string $api_key, array $payload ): array {
		$response = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'Content-Type'  => 'application/json; charset=utf-8',
					'Authorization' => 'Bearer ' . $api_key,
				],
				'body'    => NCP_Security::json_encode( $payload ),
				'timeout' => NCP_API_TIMEOUT,
			]
		);
		
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		
		if ( $code >= 300 ) {
			$error_msg = $decoded['error']['message'] ?? 'Unknown error';
			throw new Exception( $error_msg );
		}
		
		return $decoded ?: [];
	}

	public function validate_config(): bool {
		$config = NCP_Configuration::instance();
		return ! empty( $config->get( 'ncp_openai_api_key' ) );
	}

	public function get_name(): string {
		return 'OpenAI';
	}

	public function get_models(): array {
		return [
			'gpt-4o-mini'   => 'GPT-4o Mini',
			'gpt-4o'        => 'GPT-4o',
			'gpt-4-turbo'   => 'GPT-4 Turbo',
			'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
		];
	}
}

/**
 * Custom API Provider
 */
class NCP_Provider_Custom implements NCP_Provider_Interface {

	public function send_message( array $messages, array $options ): array {
		$config = NCP_Configuration::instance();
		$endpoint = $config->get( 'ncp_custom_endpoint' );
		$api_key = $config->get( 'ncp_custom_api_key' );
		
		if ( ! $endpoint ) {
			throw new Exception( __( 'Custom endpoint not configured.', 'nafas-chatbot-pro' ) );
		}
		
		$payload = [
			'model'       => $options['model'] ?? 'default',
			'messages'    => $messages,
			'temperature' => $options['temperature'] ?? 0.7,
			'max_tokens'  => $options['max_tokens'] ?? 512,
		];
		
		$headers = [
			'Content-Type' => 'application/json; charset=utf-8',
		];
		
		if ( $api_key ) {
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}
		
		$response = wp_remote_post(
			$endpoint,
			[
				'headers' => $headers,
				'body'    => NCP_Security::json_encode( $payload ),
				'timeout' => NCP_API_TIMEOUT,
			]
		);
		
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		
		if ( $code >= 300 ) {
			throw new Exception( __( 'Custom API error occurred.', 'nafas-chatbot-pro' ) );
		}
		
		return $decoded ?: [];
	}

	public function validate_config(): bool {
		$config = NCP_Configuration::instance();
		return ! empty( $config->get( 'ncp_custom_endpoint' ) );
	}

	public function get_name(): string {
		return 'Custom API';
	}

	public function get_models(): array {
		return [
			'default' => 'Default Model',
		];
	}
}

/**
 * Extract text from provider response
 */
class NCP_Provider_Response {
	public static function extract_text( array $body ): string {
		// OpenAI/AvalAI format
		if ( isset( $body['choices'][0]['message']['content'] ) ) {
			return trim( (string) $body['choices'][0]['message']['content'] );
		}
		
		// Alternative text format
		if ( isset( $body['choices'][0]['text'] ) ) {
			return trim( (string) $body['choices'][0]['text'] );
		}
		
		// Custom format
		if ( isset( $body['output_text'] ) ) {
			return trim( (string) $body['output_text'] );
		}
		
		return '';
	}

	public static function extract_tokens( array $body ): int {
		return (int) ( $body['usage']['total_tokens'] ?? 0 );
	}
}
