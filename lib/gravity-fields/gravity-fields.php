<?php
# Setup the plugin - check plugin location, add right hooks and run filters
if(!function_exists('gf_load') && is_admin()) {
	require_once( 'common/startup.php' );
	gf_startup( __FILE__ );

	/**
	 * Includes core files and adds the init action.
	 * 
	 * This function is executed on after_setup_theme, so you can add all the hooks you need
	 * in functions.php, which is included before the after_setup_theme action is performed.
	 */
	function gf_load() {
		global $gravityfields;
	
		# Most classes will somehow be saved to $gravityfields
		$gravityfields = new stdClass();
	
		# Common functionality that's used accross the framework
		include_once( 'common/common.php' );
	
		# Include classes and functions
		include_once( 'includes.php' );
	
		# Add GF options pages which provide admin interface for fields
		include_once( 'settings/settings.php' );
		
		# Indicate that the plugin is present so themes could check it.
		define( 'GF', true);
	
		# Fields are set up on init, but only in the admin, with priority 12
		add_action( 'init', 'gf_init', 12 );
	}
	
	/**
	 * Enqueues scripts, allows adding additional classes and sets up fields.
	 */
	function gf_init() {
		# Register available scripts and styles
		gf_register_scripts();
	
		# Register additional fields, templates, etc.
		do_action( 'gf_extend' );
		
		# Init fields through themes and other plugins
		do_action( 'gf_setup' );
	
		# Now that the fields are defined, save options and stuff
		do_action( 'gf_save' );
	}
}
