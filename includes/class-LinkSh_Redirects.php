<?php

/**
 * Processing redirects for the plugin
 */

class LinkSh_Redirects {
    public function __construct() {
        // Process the URL and redirect
        add_action('parse_request', [$this, 'redirect_based_on_custom_meta']);
    }

    /**
     * Update post-meta field with redirects count
     *
     * @param $redirect_id
     * @return void
     */
    private function update_redirects_count($redirect_id): void {
        global $wpdb;

        // Get the table name
        $table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

        // Prepare the SQL query to count the records
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE redirect_id = %d",
            $redirect_id
        );

        $redirects_count = (int)$wpdb->get_var($query);

        update_post_meta($redirect_id, LINKSH_REDIRECT_COUNT_META_NAME, $redirects_count);
    }

    /**
     * Update post-meta field with extended log
     *
     * @param $redirect_id
     * @return void
     */
    private function update_extended_log($redirect_id): void {
        global $wpdb;

        // Get the target URL from the Redirection Post
        $target_url = get_post_meta($redirect_id, LINKSH_LONG_URL_META_NAME, true);;

        // Get the table name
        $table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

        // Automatically populate variables
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';

        // Insert the record into the database
        $wpdb->insert(
            $table_name,
            [
                'redirect_id' => $redirect_id,
                //'datetime' => current_time('mysql'), // Get current time in MySQL format
                'target_url' => $target_url,
                'ip_address' => $ip_address,
                'referrer' => $referrer,
            ]
        );

        $this->update_redirects_count($redirect_id);
    }

    /**
     * Redirects using existing redirections
     *
     * @param $wp
     *
     * @return void
     */
    function redirect_based_on_custom_meta($wp): void {
        // Get the current URL path
        $request_path = trim($wp->request, '/');

        // Check if the request path is not empty and does not match any existing page
        if (!empty($request_path)) {

            // WP_Query parameters
            $args = array(
                'post_type' => LINKSH_POST_TYPE,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => LINKSH_SHORT_URL_META_NAME,
                        'value' => $request_path,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1
            );

            // Execute the query
            $query = new WP_Query($args);

            // Check if any posts were found
            if ($query->have_posts()) {
                $query->the_post();

                // Get the value of the long_url meta-field
                $long_url = get_post_meta(get_the_ID(), LINKSH_LONG_URL_META_NAME, true);

                if (!empty($long_url)) {
                    $this->update_extended_log(get_the_ID());

                    // Perform the redirect
                    wp_redirect($long_url);
                    exit;
                }
            }
            wp_reset_postdata();
        }
    }
}

new LinkSh_Redirects ();