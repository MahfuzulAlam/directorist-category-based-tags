<?php
/**
 * Plugin Name:       Directorist - Category Based Tags
 * Plugin URI:        https://wpxplore.com/tools/directorist-category-based-tags/
 * Description:       Filters Directorist tags by selected categories on listing and search forms.
 * Version:           2.0.0
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            WpXplore
 * Author URI:        https://wpxplore.com/tools/directorist-category-based-tags/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       directorist-category-based-tags
 * Domain Path:       /languages
 *
 * @package Directorist_Category_Based_Tags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DIRECTORIST_CATEGORY_BASED_TAGS_VERSION' ) ) {
	define( 'DIRECTORIST_CATEGORY_BASED_TAGS_VERSION', '2.0.0' );
}

if ( ! defined( 'DIRECTORIST_CATEGORY_BASED_TAGS_FILE' ) ) {
	define( 'DIRECTORIST_CATEGORY_BASED_TAGS_FILE', __FILE__ );
}

if ( ! defined( 'DIRECTORIST_CATEGORY_BASED_TAGS_DIR' ) ) {
	define( 'DIRECTORIST_CATEGORY_BASED_TAGS_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DIRECTORIST_CATEGORY_BASED_TAGS_URL' ) ) {
	define( 'DIRECTORIST_CATEGORY_BASED_TAGS_URL', plugin_dir_url( __FILE__ ) );
}

require_once DIRECTORIST_CATEGORY_BASED_TAGS_DIR . 'inc/class-directorist-category-based-tags-tag-field.php';
require_once DIRECTORIST_CATEGORY_BASED_TAGS_DIR . 'inc/class-directorist-category-based-tags-manager.php';

/**
 * Bootstrap the plugin after Directorist has loaded.
 *
 * @return void
 */
function directorist_category_based_tags_init() {
	if ( ! defined( 'ATBDP_TAGS' ) || ! defined( 'ATBDP_CATEGORY' ) || ! defined( 'ATBDP_POST_TYPE' ) || ! function_exists( 'get_directorist_option' ) ) {
		return;
	}

	load_plugin_textdomain(
		'directorist-category-based-tags',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	Directorist_Category_Based_Tags_Tag_Field::init();
	Directorist_Category_Based_Tags_Manager::init();
}

add_action( 'plugins_loaded', 'directorist_category_based_tags_init', 20 );
