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
	 * @return void
	 */
	private function update_redirects_count(): void {
		$redirects_count = get_post_meta( get_the_ID(), LINKSH_REDIRECT_COUNT_META_NAME, true ) ?? 0;
		$redirects_count ++;
		update_post_meta( get_the_ID(), LINKSH_REDIRECT_COUNT_META_NAME, $redirects_count );
	}

	/**
	 * Update post-meta field with extended log
	 *
	 * @return void
	 */
	private function update_extended_log(): void {
		// Get the current time, IP address, and referrer
		$current_time = current_time( 'mysql' );
		$ip_address   = $_SERVER['REMOTE_ADDR'];
		$referrer     = $_SERVER['HTTP_REFERER'] ?? 'Direct';

		// Prepare the log entry
		$log_entry = "Date: $current_time\tIP: $ip_address\tReferrer: $referrer\n";

		// Get the existing log
		$extended_log = get_post_meta( get_the_ID(), LINKSH_EXTENDED_LOG_META_NAME, true ) ?? '';

		// Append the new log entry
		$extended_log .= $log_entry;

		// Update the extended log meta field
		update_post_meta( get_the_ID(), LINKSH_EXTENDED_LOG_META_NAME, $extended_log );
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
					$this->update_redirects_count();

					$this->update_extended_log();

					// Perform the redirect
					wp_redirect( $long_url, 301 );
					exit;
				}
			}
			wp_reset_postdata();
		}
	}
}

new LinkSh_Redirects ();