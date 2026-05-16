<?php
/**
 * Database Management Layer
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Database
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Manager
 */
class NCP_Database {

	/**
	 * Initialize database tables
	 */
	public static function init_tables(): void {
		global $wpdb;
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$charset = $wpdb->get_charset_collate();
		
		// Chat Logs Table (Enhanced)
		$table_chats = $wpdb->prefix . NCP_TABLE_CHATS;
		$sql_chats = "CREATE TABLE {$table_chats} (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id      VARCHAR(128)    NOT NULL DEFAULT '',
			user_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_ip         VARCHAR(45)     NOT NULL DEFAULT '',
			provider        VARCHAR(32)     NOT NULL DEFAULT '',
			model           VARCHAR(100)    NOT NULL DEFAULT '',
			message         LONGTEXT        NOT NULL,
			response        LONGTEXT        NOT NULL,
			tokens_used     INT UNSIGNED    NOT NULL DEFAULT 0,
			cost            DECIMAL(10, 6)  NOT NULL DEFAULT 0,
			cached          TINYINT(1)      NOT NULL DEFAULT 0,
			user_rating     TINYINT(1)      DEFAULT NULL,
			feedback        TEXT            DEFAULT NULL,
			created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_session (session_id),
			KEY idx_user_id (user_id),
			KEY idx_ip (user_ip),
			KEY idx_provider (provider),
			KEY idx_created (created_at),
			KEY idx_rating (user_rating)
		) {$charset};";
		
		dbDelta( $sql_chats );
		
		// Sessions Table
		$table_sessions = $wpdb->prefix . NCP_TABLE_SESSIONS;
		$sql_sessions = "CREATE TABLE {$table_sessions} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id  VARCHAR(128)    NOT NULL UNIQUE,
			user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_ip     VARCHAR(45)     NOT NULL DEFAULT '',
			provider    VARCHAR(32)     NOT NULL DEFAULT '',
			message_count INT UNSIGNED  NOT NULL DEFAULT 0,
			total_tokens INT UNSIGNED   NOT NULL DEFAULT 0,
			total_cost  DECIMAL(10, 6)  NOT NULL DEFAULT 0,
			started_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			ended_at    DATETIME        DEFAULT NULL,
			is_active   TINYINT(1)      NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY idx_session_id (session_id),
			KEY idx_user_id (user_id),
			KEY idx_active (is_active)
		) {$charset};";
		
		dbDelta( $sql_sessions );
		
		// Analytics Table
		$table_analytics = $wpdb->prefix . NCP_TABLE_ANALYTICS;
		$sql_analytics = "CREATE TABLE {$table_analytics} (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			date_key        VARCHAR(10)     NOT NULL DEFAULT '',
			total_sessions  INT UNSIGNED    NOT NULL DEFAULT 0,
			total_messages  INT UNSIGNED    NOT NULL DEFAULT 0,
			total_tokens    INT UNSIGNED    NOT NULL DEFAULT 0,
			total_cost      DECIMAL(10, 6)  NOT NULL DEFAULT 0,
			avg_response_time FLOAT         NOT NULL DEFAULT 0,
			success_rate    FLOAT           NOT NULL DEFAULT 0,
			error_count     INT UNSIGNED    NOT NULL DEFAULT 0,
			cache_hits      INT UNSIGNED    NOT NULL DEFAULT 0,
			created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_date (date_key)
		) {$charset};";
		
		dbDelta( $sql_analytics );
		
		// Feedback Table
		$table_feedback = $wpdb->prefix . NCP_TABLE_FEEDBACK;
		$sql_feedback = "CREATE TABLE {$table_feedback} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id  VARCHAR(128)    NOT NULL DEFAULT '',
			rating      TINYINT(1)      NOT NULL DEFAULT 0,
			comment     TEXT            DEFAULT NULL,
			email       VARCHAR(100)    DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_session (session_id),
			KEY idx_rating (rating)
		) {$charset};";
		
		dbDelta( $sql_feedback );
	}

	/**
	 * Log chat message
	 */
	public static function log_chat( 
		string $session_id,
		string $provider,
		string $model,
		string $message,
		string $response,
		int $tokens = 0,
		float $cost = 0,
		bool $cached = false
	): bool {
		global $wpdb;
		
		if ( ! NCP_Configuration::instance()->get( 'ncp_log_enabled' ) ) {
			return false;
		}
		
		$result = $wpdb->insert(
			$wpdb->prefix . NCP_TABLE_CHATS,
			[
				'session_id'  => $session_id,
				'user_id'     => get_current_user_id(),
				'user_ip'     => NCP_Security::get_user_ip(),
				'provider'    => $provider,
				'model'       => $model,
				'message'     => $message,
				'response'    => $response,
				'tokens_used' => $tokens,
				'cost'        => $cost,
				'cached'      => $cached ? 1 : 0,
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d' ]
		);
		
		return false !== $result;
	}

	/**
	 * Record user feedback
	 */
	public static function record_feedback( 
		string $session_id,
		int $rating,
		string $comment = '',
		string $email = ''
	): bool {
		global $wpdb;
		
		$result = $wpdb->insert(
			$wpdb->prefix . NCP_TABLE_FEEDBACK,
			[
				'session_id' => $session_id,
				'rating'     => max( 1, min( 5, $rating ) ),
				'comment'    => $comment,
				'email'      => $email,
			],
			[ '%s', '%d', '%s', '%s' ]
		);
		
		return false !== $result;
	}

	/**
	 * Get chat logs with filtering and pagination
	 */
	public static function get_logs( 
		int $limit = 100,
		int $offset = 0,
		array $filters = []
	): array {
		global $wpdb;
		
		$table = $wpdb->prefix . NCP_TABLE_CHATS;
		$query = "SELECT * FROM {$table} WHERE 1=1";
		
		if ( ! empty( $filters['provider'] ) ) {
			$query .= $wpdb->prepare( ' AND provider = %s', $filters['provider'] );
		}
		
		if ( ! empty( $filters['user_ip'] ) ) {
			$query .= $wpdb->prepare( ' AND user_ip = %s', $filters['user_ip'] );
		}
		
		if ( ! empty( $filters['date_from'] ) ) {
			$query .= $wpdb->prepare( ' AND created_at >= %s', $filters['date_from'] );
		}
		
		if ( ! empty( $filters['date_to'] ) ) {
			$query .= $wpdb->prepare( ' AND created_at <= %s', $filters['date_to'] );
		}
		
		$query .= " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
		
		$rows = $wpdb->get_results( $query, ARRAY_A );
		return $rows ?: [];
	}

	/**
	 * Get analytics data
	 */
	public static function get_analytics( string $date_from = '', string $date_to = '' ): array {
		global $wpdb;
		
		$table = $wpdb->prefix . NCP_TABLE_CHATS;
		
		$query = "SELECT 
			COUNT(DISTINCT session_id) as total_sessions,
			COUNT(*) as total_messages,
			SUM(tokens_used) as total_tokens,
			SUM(cost) as total_cost,
			SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as cache_hits,
			AVG(CASE WHEN user_rating IS NOT NULL THEN user_rating ELSE NULL END) as avg_rating
		FROM {$table}
		WHERE 1=1";
		
		if ( $date_from ) {
			$query .= $wpdb->prepare( ' AND created_at >= %s', $date_from );
		}
		
		if ( $date_to ) {
			$query .= $wpdb->prepare( ' AND created_at <= %s', $date_to );
		}
		
		$result = $wpdb->get_row( $query, ARRAY_A );
		
		return $result ?: [
			'total_sessions' => 0,
			'total_messages' => 0,
			'total_tokens'   => 0,
			'total_cost'     => 0,
			'cache_hits'     => 0,
			'avg_rating'     => 0,
		];
	}

	/**
	 * Clear chat logs
	 */
	public static function clear_logs(): bool {
		global $wpdb;
		
		$table = $wpdb->prefix . NCP_TABLE_CHATS;
		$result = $wpdb->query( "TRUNCATE TABLE {$table}" );
		
		return false !== $result;
	}

	/**
	 * Export logs as array
	 */
	public static function export_logs( array $filters = [] ): array {
		return self::get_logs( 5000, 0, $filters );
	}

	/**
	 * Get analytics by provider
	 */
	public static function get_analytics_by_provider(): array {
		global $wpdb;
		
		$table = $wpdb->prefix . NCP_TABLE_CHATS;
		
		$query = "SELECT 
			provider,
			COUNT(*) as count,
			SUM(tokens_used) as tokens,
			AVG(CASE WHEN user_rating IS NOT NULL THEN user_rating ELSE NULL END) as rating
		FROM {$table}
		GROUP BY provider
		ORDER BY count DESC";
		
		return $wpdb->get_results( $query, ARRAY_A ) ?: [];
	}
}
