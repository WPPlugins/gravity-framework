<?php
/**
 * Registers all scripts that might be used throughout the plugin.
 * Based on SCRIPT_DEBUG, either a minified or a normal version will be used.
 */
function gf_register_scripts() {
	$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || WP_DEBUG;

	# Prepare ui style for fields
	wp_register_style( 'jquery-ui', GF_URL . 'templates/css/smoothness/jquery-ui-1.8.18.custom.css' );
	wp_register_style( 'chosen-style', GF_URL . 'templates/css/select2.css', array(), GF_VER );
	wp_register_style( 'gravityfields-css', GF_URL . 'templates/css/gravity-fields.css', array( 'jquery-ui', 'chosen-style' ), '3.3.2' );

	# UI Internationalization kit
	wp_register_script( 'jquery-ui-i18n', GF_URL . 'js/jquery-ui-i18n.js', array( 'jquery' ), null, true);
	
	# Register all scripts and enqueue needed ones
	wp_register_script( 'chosen-script', GF_URL . 'js/select2.min.js', array( 'jquery' ), '3.3.2', true );
	wp_register_script( 'jquery-ui-timepicker', GF_URL . 'js/jquery-ui-timepicker-addon.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.2', true );
	wp_register_script( 'google-maps-api', 'http://maps.googleapis.com/maps/api/js?sensor=false' );
	wp_register_script( 'gravity-fields', GF_URL . 'js/gravity-fields.js', array( 'jquery', 'underscore', 'chosen-script', 'jquery-ui-timepicker' ), GF_VER, true );
	wp_register_script( 'gravity-fields-site', GF_URL . 'js/gravity-fields-site.js', array( 'jquery' ), GF_VER, true );
}