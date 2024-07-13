<?php
/**
 * Links shortener
 *
 * @author        Dzmitry Makarski
 * @version       0.0.1
 *
 * @wordpress-plugin
 * Plugin Name:   Links shortener
 * Description:   Allows you to add link shortener to your site
 * Version:       0.0.1
 * Author:        Dzmitry Makarski
 * Text Domain:   linkssh
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LINKSH_POST_TYPE', 'links_shrt' );
const REDIRECT_COUNT_META_NAME = 'redirects_count';

define( 'LINKSH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'LINKSH_PLUGIN_BASEPATH', plugin_dir_path( __FILE__ ) );
define( 'LINKSH_PLUGIN_BASEURI', plugin_dir_url( __FILE__ ) );

// Custom post type
require_once 'includes/class_LinkSh_Init.php';
require_once 'includes/class_LinksSh_CPT.php';
require_once 'includes/class_LinkSh_Ajax.php';
require_once 'includes/class_LinkSh_Redirects.php';
