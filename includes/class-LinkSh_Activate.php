<?php

class LinkSh_Activate {

    public static function my_plugin_activate(): void {
        global $wpdb;

        // Table name
        $table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

        // Check if the table already exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            // SQL query to create the table
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                redirect_id BIGINT(20) UNSIGNED NOT NULL,
                datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                target_url VARCHAR(256) NOT NULL,
                ip_address VARCHAR(16) NOT NULL,
                referrer VARCHAR(256) NOT NULL,
                PRIMARY KEY (id),
                INDEX redirect_id (redirect_id),
                INDEX datetime (datetime)
            ) {$charset_collate};";


            // Include WordPress library to execute SQL
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }
    }
}