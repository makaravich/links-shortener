<?php

class class_LinkSh_Main_Page {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
	}

	public function add_menu_page() {
		add_submenu_page(
			'tools.php',
			__( 'Links shortener', 'linkssh' ),
			__( 'Links shortener', 'linkssh' ),
			'manage_options',
			'links_short.php',
			[ $this, 'plugin_settings_page' ]
		);
	}

	public function render_adding_form(): void {
		?>
        <div class="linksh-adding-form-wrapper">
            <h2><?php _e( 'Short your link', 'linkssh' ); ?></h2>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="process_link_shortener">

                <label for="long_url"><?php _e( 'Full URL of the long link', 'linkssh' ); ?></label>
                <input type="url" id="long_url" name="long_url" required>
                <br>

                <label for="short_url"><?php _e( 'Short link address', 'linkssh' ); ?></label>
                <input type="text" id="short_url" name="short_url">
                <br>

                <button type="submit"><?php _e( 'Shorten the link', 'linkssh' ); ?></button>
            </form>
        </div>
		<?php
	}

	public function plugin_settings_page() {
		echo '<div class="wrap"><h1>' . __( 'Links shortener', 'linkssh' ) . '</h1>';
		$this->render_adding_form();
		linksh_render_main_table();
		echo '</div>';
	}
}

new class_LinkSh_Main_Page();