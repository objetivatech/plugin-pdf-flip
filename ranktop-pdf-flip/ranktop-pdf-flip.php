<?php
/**
 * Plugin Name:       Ranktop PDF Flip
 * Plugin URI:        https://github.com/objetivatech/plugin-pdf-flip
 * Description:       Gerencia edições periódicas (jornal/revista) em PDF com um leitor flipbook standalone, sem dependências externas.
 * Version:           1.1.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Ranktop
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ranktop-pdf-flip
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RPF_VERSION', '1.1.0' );
define( 'RPF_FILE', __FILE__ );
define( 'RPF_PATH', plugin_dir_path( __FILE__ ) );
define( 'RPF_URL', plugin_dir_url( __FILE__ ) );
define( 'RPF_CPT', 'rpf_edicao' );

require_once RPF_PATH . 'includes/class-rpf-helpers.php';
require_once RPF_PATH . 'includes/class-rpf-cpt.php';
require_once RPF_PATH . 'includes/class-rpf-admin.php';
require_once RPF_PATH . 'includes/class-rpf-assets.php';
require_once RPF_PATH . 'includes/class-rpf-frontend.php';
require_once RPF_PATH . 'includes/class-rpf-settings.php';
require_once RPF_PATH . 'includes/class-rpf-shortcodes.php';

/**
 * Boot the plugin.
 */
function rpf_init() {
	load_plugin_textdomain( 'ranktop-pdf-flip', false, dirname( plugin_basename( RPF_FILE ) ) . '/languages' );

	RPF_CPT::instance();
	RPF_Admin::instance();
	RPF_Assets::instance();
	RPF_Frontend::instance();
	RPF_Settings::instance();
	RPF_Shortcodes::instance();
}
add_action( 'plugins_loaded', 'rpf_init' );

/**
 * Register CPT + rewrite rules on activation, then flush.
 */
function rpf_activate() {
	require_once RPF_PATH . 'includes/class-rpf-cpt.php';
	RPF_CPT::instance()->register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( RPF_FILE, 'rpf_activate' );

/**
 * Flush rewrite rules on deactivation.
 */
function rpf_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( RPF_FILE, 'rpf_deactivate' );
