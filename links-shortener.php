<?php
/**
 * Links shortener
 *
 * @author        Dzmitry Makarski
 * @version       0.0.1
 *
 * @wordpress-plugin
 * Plugin Name:       Links shortener
 * Description:       Allows you to add link shortener to your site
 * Version:           0.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Dzmitry Makarski
 * Text Domain:       linkssh
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LINKSH_POST_TYPE                = 'links_shrt';
const LINKSH_LONG_URL_META_NAME       = 'long_url';
const LINKSH_SHORT_URL_META_NAME      = 'short_url_slug';
const LINKSH_REDIRECT_COUNT_META_NAME = 'redirects_count';
const LINKSH_EXTENDED_LOG_META_NAME   = 'linkssh_extended_log';


define( 'LINKSH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'LINKSH_PLUGIN_BASEPATH', plugin_dir_path( __FILE__ ) );
define( 'LINKSH_PLUGIN_BASEURI', plugin_dir_url( __FILE__ ) );

// Initialization
require_once 'includes/class-LinkSh_Init.php';
// Main plugin functionality
require_once 'includes/class-LinkSh_Core.php';
// Custom post type
require_once 'includes/class-LinksSh_CPT.php';
// AJAX processing
require_once 'includes/class-LinkSh_Ajax.php';
// Redirects processing
require_once 'includes/class-LinkSh_Redirects.php';