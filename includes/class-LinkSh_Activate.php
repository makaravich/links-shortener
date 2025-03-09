<?php

class LinkSh_Activate {

	public static function my_plugin_activate(): void {
		global $wpdb;

		// Table name
		$table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

		// Check if the table exists in the cache
		$cache_key    = "table_exists_{$table_name}";
		$table_exists = wp_cache_get( $cache_key, 'linksh_plugin' );

		if ( $table_exists === false ) {
			// Table not found in cache, checking the database
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %i", $table_name ) ) === $table_name;

			// Save the result in cache for 5 minutes (300 seconds)
			wp_cache_set( $cache_key, $table_exists, 'linksh_plugin', 30 );
		}

		if ( ! $table_exists ) {
			// Table does not exist, creating it
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} (
	        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	        redirect_id BIGINT(20) UNSIGNED NOT NULL,
	        datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	        target_url VARCHAR(256) NOT NULL,
	        ip_address VARCHAR(45) NOT NULL,
	        referrer VARCHAR(256) NOT NULL,
	        user_agent TEXT NOT NULL,
	        accept_language VARCHAR(128) NOT NULL,
	        os VARCHAR(32) NOT NULL,
	        device_type VARCHAR(32) NOT NULL,
	        PRIMARY KEY (id),
	        INDEX redirect_id (redirect_id),
	        INDEX datetime (datetime)
    		) {$charset_collate};";

			// Include the upgrade functions for creating or updating tables
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			dbDelta( $sql ); // Execute the SQL query to create the table
		}

		// Include WordPress library to execute SQL
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}