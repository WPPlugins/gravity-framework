<?php
/**
 * Gravity Fields Settings Post Type and Settings pages
 */

# Register the gravityfields post type which is represents containers
include_once( 'interface/post-type.php' );

# Change the icon of the post type
include_once( 'interface/icons.php' );

# Add a page for export
include_once( 'interface/export.php' );

# Add a page for general settings
include_once( 'interface/settings-page.php' );

# Change the edit screen and add neccessary fields
include_once( 'interface/meta.php' );

# Add shortcode buttons & functionality
include_once( 'interface/shortcode.php' );

# Enqueue scripts (and eventually styles) for the settings page
add_action( 'admin_enqueue_scripts', 'gf_settings_enqueue_js' );
function gf_settings_enqueue_js() {
	wp_enqueue_script( 'gf-settings', GF_URL . 'settings/interface/settings.js' );
}