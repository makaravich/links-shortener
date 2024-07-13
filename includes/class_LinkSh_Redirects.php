<?php

class LinkSh_Redirects {
	public function __construct() {
		add_action( 'parse_request', [ $this, 'redirect_based_on_custom_meta' ] );
	}

	function redirect_based_on_custom_meta($wp): void {
		// Get the current URL path
		$request_path = trim($wp->request, '/');

		// Check if the request path is not empty and does not match any existing page
		if (!empty($request_path)) {
			// Define the post type
			$post_type = LINKSH_POST_TYPE; // Replace with your LINKSH_POST_TYPE value

			// WP_Query parameters
			$args = array(
				'post_type' => $post_type,
				'meta_query' => array(
					array(
						'key' => 'short_url_slug',
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
				// Get the value of the long_url meta field
				$long_url = get_post_meta(get_the_ID(), 'long_url', true);

				if (!empty($long_url)) {
					// Update redirects count
					$redirects_count = get_post_meta( get_the_ID(), REDIRECT_COUNT_META_NAME, true ) ?? 0;
					$redirects_count ++;
					update_post_meta(get_the_ID(), REDIRECT_COUNT_META_NAME, $redirects_count);

					// Perform the redirect
					wp_redirect($long_url, 301);
					exit;
				}
			}
			wp_reset_postdata();
		}
	}
}

new LinkSh_Redirects ();