<?php
/*
Plugin Name: Gravity Framework
Plugin URI: http://wpthemers.net
Description: Framework required for all WP Themers plug-ins, themes and CMS features.
Version: 1.0.0
Author: Soumyajit Saha
Author Email: soumyajit@wpthemers.net
License: GPLv2
*/

/*--------------------------------------------*
 * Constants
 *--------------------------------------------*/
define('GRAVITY_NAME', 'Gravity Framework');
define('GRAVITY_SLUG', 'gravity_framework');

/*--------------------------------------------*
 * Include Libraries
 *--------------------------------------------*/
include_once( 'lib/gravity-fields/gravity-fields.php' );
include_once( 'lib/BFI_Thumb.php' );

/*--------------------------------------------*
 * Main Class
 *--------------------------------------------*/
class GravityFramework {
	function __construct() {
		add_action( 'init', array( &$this, 'init_gravity_framework' ));
		add_action( 'init', array( $this, 'register_scripts_and_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_script' ));
		//load_plugin_textdomain( 'gravity_framwork', false, GF_DIR . 'languages/' );
	}	
	
	function init_gravity_framework() {

	}
	function register_scripts_and_styles() {
		/*wp_deregister_script( 'wp-mediaelement' );
		wp_deregister_style( 'wp-mediaelement' );*/
		
		wp_register_style( 'gravity_admin_css', plugins_url('asset/css/admin.css', __FILE__), array('gravityfields-css'));
		wp_register_style( 'gravity_reset_css', plugins_url('asset/css/reset.css', __FILE__));
		wp_register_style( 'gravity_grids_css', plugins_url('asset/css/grids.css', __FILE__));
		wp_register_style( 'gravity_icons_css', plugins_url('fonts/icons/icons.css', __FILE__));
		wp_register_style('gravity_mediaelement', plugins_url('asset/mejs/mediaelementplayer.css', __FILE__));
		// Register jQuery Plugins
		wp_register_script('gravity_plug_modernizr', plugins_url('asset/js/modernizr.custom.89443.js', __FILE__));
		wp_register_script('gravity_plug_fitvids', plugins_url('asset/js/jquery.fitvids.js', __FILE__), array('jquery'), '1.0.3');
		wp_register_script('gravity_plug_overlayer', plugins_url('asset/js/jquery.overlayer.min.js', __FILE__), array('jquery'), '1.0');
		wp_register_script('gravity_plug_easing', plugins_url('asset/js/jquery.easing.min.js', __FILE__), array('jquery'), '1.3');
		wp_register_script('gravity_plug_mousewheel', plugins_url('asset/js/jquery.mousewheel.min.js', __FILE__), array('jquery'), '3.1.8');
		wp_register_script('gravity_plug_waitforimages', plugins_url('asset/js/jquery.waitforimages.min.js', __FILE__), array('jquery'), '1.0.0');
		wp_register_script('gravity_plug_throttledresize', plugins_url('asset/js/jquery.throttledresize.min.js', __FILE__), array('jquery'), '1.0.0');
		wp_register_script('gravity_plug_actual', plugins_url('asset/js/jquery.actual.min.js', __FILE__), array('jquery'), '1.0.15');
		wp_register_script('gravity_plug_transit', plugins_url('asset/js/jquery.transit.min.js', __FILE__), array('jquery'), '0.9.9');
		// Register jQuery Scripts
		wp_register_script('gravity_mediaelement', plugins_url('asset/mejs/mediaelement-and-player.min.js', __FILE__), array('jquery'), '2.13.0');
		wp_register_script('gravity_sgallery', plugins_url('asset/js/jquery.sgallery.min.js', __FILE__), array('jquery'), '1.0.0');
		wp_register_script('gravity_flexslider', plugins_url('asset/js/jquery.flexslider.min.js', __FILE__), array('jquery'), '2.2.0');
	}
	function load_admin_script() {
		wp_enqueue_style('gravity_admin_css');
	}
}

new GravityFramework;