<?php

/**
 * Processing AJAX requests for the plugin
 */

class LinkSh_Ajax {
	private string $security_str = 'LinksShortener2025';

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
            <h2><?php esc_html_e( 'Short your link', 'links-shortener' ); ?></h2>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php wp_nonce_field( $this->security_str, 'links-shortener-new-link-nonce' ); ?>
                <input type="hidden" name="linksh_source_page" value="<?php
				echo isset( $_POST['linksh_page'] ) ? esc_url( sanitize_text_field( wp_unslash( $_POST['linksh_page'] ) ) ) : '';
				?>">


                <input type="hidden" name="action" value="process_link_shortener">

                <label for="long_url"><?php esc_html_e( 'Full URL of the long link', 'links-shortener' ); ?></label>
                <input type="url" id="long_url" name="long_url" placeholder="https://" required>
                <p class="description"><?php esc_html_e( 'Full URL, with https:// ', 'links-shortener' ); ?></p>
                <br>

                <label for="short_url"><?php esc_html_e( 'Short link slug', 'links-shortener' ); ?></label>
                <input type="text" id="short_url" name="short_url" pattern="[a-z0-9]*">
                <p class="description"><?php printf( '%s.<br>%s %s/%s. <br> %s',
						esc_html__( 'Optional', 'links-shortener' ),
						esc_html__( 'URL-friendly slug only. Your URL will be generated in the form', 'links-shortener' ),
						esc_url( home_url() ),
						esc_html__( 'your_slug', 'links-shortener' ),
						esc_html__( 'You can use letters a-z and digits 0-9 ', 'links-shortener' ) ); ?>
                </p>
                <br>

                <button class='button-primary button'
                        type="submit"><?php esc_html_e( 'Short the link', 'links-shortener' ); ?></button>
            </form>
        </div>

		<?php
		$form_content = ob_get_clean();
		wp_send_json_success( [ 'formContent' => $form_content ] );
	}
}

new LinkSh_Ajax();