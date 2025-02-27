<?php

class LinkSh_Activate {

	public static function my_plugin_activate(): void {
		global $wpdb;

		// Table name
		$table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

		// Check if the table already exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
			// SQL query to create the table
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            redirect_id BIGINT(20) UNSIGNED NOT NULL,
            datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            target_url VARCHAR(256) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            referrer VARCHAR(256) NOT NULL,
            user_agent TEXT NOT NULL,
            accept_language VARCHAR(64) NOT NULL,
            os VARCHAR(32) NOT NULL,
            device_type VARCHAR(32) NOT NULL,
            PRIMARY KEY (id),
            INDEX redirect_id (redirect_id),
            INDEX datetime (datetime)
        ) {$charset_collate};";

			// Include WordPress library to execute SQL
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		} else {
			// SQL query to update the existing table structure
			$sql = "ALTER TABLE {$table_name}
            ADD COLUMN user_agent TEXT NOT NULL AFTER referrer,
            ADD COLUMN accept_language VARCHAR(128) NOT NULL AFTER user_agent,
            ADD COLUMN os VARCHAR(32) NOT NULL AFTER accept_language,
            ADD COLUMN device_type VARCHAR(32) NOT NULL AFTER os;";

			$wpdb->query( $sql );
		}
	}
}