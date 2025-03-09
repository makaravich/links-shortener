<?php

/**
 * Processing redirects for the plugin
 */

class LinkSh_Redirects {
	public function __construct() {
		// Process the URL and redirect
		add_action( 'parse_request', [ $this, 'redirect_based_on_custom_meta' ] );
	}

	/**
	 * Update post-meta field with redirects count
	 *
	 * @param $redirect_id
	 *
	 * @return void
	 */
	private function update_redirects_count( $redirect_id ): void {
		global $wpdb;

		// Get the table name
		$table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

		// Cache key for redirects count
		$cache_key       = "redirects_count_{$redirect_id}";
		$redirects_count = wp_cache_get( $cache_key, 'linksh_plugin' );

		if ( $redirects_count === false ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$redirects_count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE redirect_id = %d",
				[ $table_name, $redirect_id ]
			) );

			// Save the result in cache for 5 minutes (300 seconds)
			wp_cache_set( $cache_key, $redirects_count, 'linksh_plugin', 30 );
		}

		update_post_meta( $redirect_id, LINKSH_REDIRECT_COUNT_META_NAME, $redirects_count );
	}

	/**
	 * Update post-meta field with extended log
	 *
	 * @param $redirect_id
	 *
	 * @return void
	 */
	private function update_extended_log( $redirect_id ): void {
		global $wpdb;

		// Get the target URL from the Redirection Post
		$target_url = get_post_meta( $redirect_id, LINKSH_LONG_URL_META_NAME, true );

		// Get the table name
		$table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

		// Automatically populate variables
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		} else {
			$ip_address = 'Unknown';
		}

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$referrer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		} else {
			$referrer = 'Direct';
		}

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		} else {
			$user_agent = 'Unknown';
		}

		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$accept_language = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );
		} else {
			$accept_language = 'Unknown';
		}

		// Extract OS and device type from User-Agent
		$os          = $this->get_os( $user_agent );
		$device_type = $this->get_device_type( $user_agent );

		// Insert the record into the database
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'redirect_id'     => $redirect_id,
				'datetime'        => current_time( 'mysql' ),
				'target_url'      => $target_url,
				'ip_address'      => $ip_address,
				'referrer'        => $referrer,
				'user_agent'      => $user_agent,
				'accept_language' => mb_substr( $accept_language, 0, 127, 'UTF-8' ),
				'os'              => $os,
				'device_type'     => $device_type,
			]
		);

		$this->update_redirects_count( $redirect_id );
	}

	/**
	 * Get OS from User Agent
	 *
	 * @param $user_agent
	 *
	 * @return string
	 */
	private function get_os( $user_agent ): string {
		if ( str_contains( $user_agent, 'Windows' ) ) {
			return 'Windows';
		}
		if ( str_contains( $user_agent, 'Mac' ) ) {
			return 'MacOS';
		}
		if ( str_contains( $user_agent, 'Linux' ) ) {
			return 'Linux';
		}
		if ( str_contains( $user_agent, 'Android' ) ) {
			return 'Android';
		}
		if ( str_contains( $user_agent, 'iPhone' ) ) {
			return 'iOS';
		}

		return 'Unknown';
	}

	/**
	 * Get Device type by User Agent
	 *
	 * @param $user_agent
	 *
	 * @return string
	 */
	private function get_device_type( $user_agent ): string {
		if ( preg_match( '/mobile|android|iphone|ipad/i', $user_agent ) ) {
			return 'Mobile';
		}

		return 'Desktop';
	}


	/**
	 * Redirects using existing redirections
	 *
	 * @param $wp
	 *
	 * @return void
	 */
	function redirect_based_on_custom_meta( $wp ): void {
		// Get the current URL path
		$request_path = trim( $wp->request, '/' );

		// Check if the request path is not empty and does not match any existing page
		if ( ! empty( $request_path ) ) {

			// WP_Query parameters
			$args = array(
				'post_type'      => LINKSH_POST_TYPE,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => LINKSH_SHORT_URL_META_NAME,
						'value'   => $request_path,
						'compare' => '='
					)
				),
				'posts_per_page' => 1
			);

			// Execute the query
			$query = new WP_Query( $args );

			// Check if any posts were found
			if ( $query->have_posts() ) {
				$query->the_post();

				// Get the value of the long_url meta-field
				$long_url = get_post_meta( get_the_ID(), LINKSH_LONG_URL_META_NAME, true );

				if ( ! empty( $long_url ) ) {
					$this->update_extended_log( get_the_ID() );

					// Perform the redirect
					wp_redirect( $long_url );
					exit;
				}
			}
			wp_reset_postdata();
		}
	}
}

new LinkSh_Redirects ();