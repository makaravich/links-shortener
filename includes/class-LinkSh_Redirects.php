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

		// Prepare the SQL query to count the records
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE redirect_id = %d",
			$redirect_id
		);

		$redirects_count = (int) $wpdb->get_var( $query );

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
		$ip_address      = $_SERVER['REMOTE_ADDR'];
		$referrer        = $_SERVER['HTTP_REFERER'] ?? 'Direct';
		$user_agent      = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
		$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown';

		// Extract OS and device type from User-Agent
		$os          = $this->get_os( $user_agent );
		$device_type = $this->get_device_type( $user_agent );

		// Get UTM parameters from the referrer or request URL
		$utm_source   = $_GET['utm_source'] ?? null;
		$utm_medium   = $_GET['utm_medium'] ?? null;
		$utm_campaign = $_GET['utm_campaign'] ?? null;

		// Insert the record into the database
		$wpdb->insert(
			$table_name,
			[
				'redirect_id'     => $redirect_id,
				'datetime'        => current_time( 'mysql' ),
				'target_url'      => $target_url,
				'ip_address'      => $ip_address,
				'referrer'        => $referrer,
				'user_agent'      => $user_agent,
				'accept_language' => $accept_language,
				'os'              => $os,
				'device_type'     => $device_type,
				'utm_source'      => $utm_source,
				'utm_medium'      => $utm_medium,
				'utm_campaign'    => $utm_campaign
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