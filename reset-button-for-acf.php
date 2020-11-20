<?php
/**
 * Reset Button for ACF
 *
 * @wordpress-plugin
 * Plugin Name: Reset Button for ACF
 * Plugin URI:  https://github.com/DimChtz/reset-button-for-acf
 * Description: Adds a reset button on ACF options pages, posts, pages and all registered custom post type admin page allowing you to reset custom fields to their default values.
 * Version:     1.0.0
 * Author:      Dimchtz
 * Author URI:  https://github.com/DimChtz
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: acf-reset
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( !defined('WPINC') ) {
	die;
}

define('ACFR_VERSION', '1.0.0');
define('ACFR_ENV', 'PRODUCTION');
define('ACFR_PLUGIN_DIR', dirname(__FILE__));
define('ACFR_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('admin_enqueue_scripts', 'acfr_scripts');
function acfr_scripts() {

	$file_version = ACFR_VERSION;
	if ( defined(ACFR_ENV) && ACFR_ENV == 'DEVELOPMENT' ) {
		$file_version = time();
	}

	$ajax_data = array(
		'_ajax_url' => admin_url('admin-ajax.php'),
		'_nonce' 	=> wp_create_nonce('acfr_reset_nonce')
	);

	wp_register_script(
		'acfr-main',
		trailingslashit(ACFR_PLUGIN_URL) . 'js/main.admin.js',
		['jquery'],
		$file_version,
		true
	);

	wp_localize_script('acfr-main', 'acfr', $ajax_data);

	wp_enqueue_script('acfr-main');

}

add_action('wp_ajax_acfr_reset_options', 'acfr_ajax_reset_options');
function acfr_ajax_reset_options() {

	if (!wp_verify_nonce($_POST['nonce'], 'acfr_reset_nonce')) {
    	echo false;
    	exit;
  	}

	$fields = isset($_POST['fields']) ? array_map('sanitize_text_field', $_POST['fields']) : array();
	$screen = isset($_POST['screen']) ? sanitize_text_field($_POST['screen']) : false;
	$post 	= isset($_POST['post']) ? intval(sanitize_text_field($_POST['post'])) : false;

  	if ( $fields ) {
		if ( strpos($screen, 'acf-options') !== false ) {
			foreach ( $fields as $field ) {
				delete_option("options_{$field}");
				delete_option("_options_{$field}");
			}
		} elseif ( $screen == 'page' || $screen == 'post' ) {
			foreach ( $fields as $field ) {
				delete_post_meta(intval($post), $field);
				delete_post_meta(intval($post), "_{$field}");
			}
		}
	}

  	echo json_encode([
		'done' 		=> true,
		'screen_id' => $screen,
		'post_id' 	=> $post
	]);
  	wp_die();

}

add_action('acf/input/admin_head', function() {

	/**
	 * Display reset metabox on acf options pages, posts, pages
	 * and all registered custom post types
	 */

	$cpt_names = wp_list_pluck(get_post_types([
		'public' 	=> true,
		'_builtin' 	=> false
	], 'OBJECT'),'name');

	add_meta_box(
		'acfr-metabox',
		'ACF Reset',
		'acfr_metabox',
		array_merge(['acf_options_page', 'post', 'page'], $cpt_names),
		'side',
		'high'
	);

}, 0);

function acfr_metabox($post, $args = []) {

	?>
		<p><?php _e('Click "Reset Custom Fields" if you want to reset all ACF custom fields on this page to their default values.', 'acf-reset'); ?></p>
		<p><em><strong><?php _e('Remember!', 'acf-reset'); ?></strong> <?php _e('You can\'t reset the reset :)', 'acf-reset'); ?></em></p><hr>
		<div style="text-align: right; padding-top: 5px;">
			<a href="#" class="button button-primary acfr-reset-link"><?php _e('Reset Custom Fields', 'acf-reset'); ?></a>
		</div>
		<input type="hidden" name="acfr-screen-id" value="<?= get_current_screen()->id; ?>">
		<input type="hidden" name="acfr-post-id" value="<?= get_the_id(); ?>">
	<?php
}
