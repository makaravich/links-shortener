<?php

/**
 * Processing AJAX requests for the plugin
 */

class LinkSh_Ajax {
	private string $security_str = 'LinksShortener2024';

	public function __construct() {
        // Add custom data to use in JS
		add_action( 'admin_enqueue_scripts', [ $this, 'setup_admin_ajax_data' ] );

        // Processing of AJAX request to add the form
		add_action( 'wp_ajax_get_linksh_adding_form', [ $this, 'get_linksh_adding_form' ] );
	}

	/**
	 * Add AJAX variables to the site code
	 */
	function setup_admin_ajax_data(): void {
		$ajax_data = $this->get_ajax_data();
		wp_add_inline_script( 'linksh-admin-script', 'const LINKSH_AJAX = ' . json_encode( $ajax_data ), 'before' );
	}


	/**
	 * Contains data to transfer in JS
	 * @return array
	 */
	private function get_ajax_data(): array {
		return [
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( $this->security_str ),
			'postType'        => LINKSH_POST_TYPE,
			'plugin_basename' => LINKSH_PLUGIN_BASENAME,
		];
	}

	/**
	 * Renders the form to add custom links
	 *
	 * @return void
	 */
	public function get_linksh_adding_form(): void {
		check_ajax_referer( $this->security_str, 'nonce' );
		ob_start();
		?>
        <div class="adding-form-wrapper">
            <h2><?php _e( 'Short your link', 'linkssh' ); ?></h2>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="process_link_shortener">

                <label for="long_url"><?php _e( 'Full URL of the long link', 'linkssh' ); ?></label>
                <input type="url" id="long_url" name="long_url" placeholder="https://" required>
                <p class="description"><?php _e( 'Full URL, with https:// ' ); ?></p>
                <br>

                <label for="short_url"><?php _e( 'Short link slug', 'linkssh' ); ?></label>
                <input type="text" id="short_url" name="short_url" pattern="[a-z0-9]*">
                <p class="description"><?php printf( __( 'Optional. <br>URL-friendly slug only. Your URL will be generated in the form %s/your_slug. <br>You can use letters a-z and digits 0-9 ' ), home_url() ); ?></p>
                <br>

                <button class='button-primary button' type="submit"><?php _e( 'Short the link', 'linkssh' ); ?></button>
            </form>
        </div>

		<?php
		$form_content = ob_get_clean();
		wp_send_json_success( [ 'formContent' => $form_content ] );
	}
}

new LinkSh_Ajax();