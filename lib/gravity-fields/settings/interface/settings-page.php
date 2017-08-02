<?php
/**
 * Add a settings page
 */
global $gravityfields;

$fields = array();

# This will control the multilingual functionality
$fields[] = GF_Field::factory( 'checkbox', 'gf_multilingual', __( 'Multilingual Fields', 'gf' ) )
	->set_text( __( 'Enable', 'gf' ) )
	->set_description( __( 'This plugin is compatible with qTranslate, which is a WordPress multilingual plugin. If you enable the multilingual fields and you use qTranslate, we will automatically get the available languages and display two field versions for each language. Please remember that each field will have it&apos;s own setting and you will need to use it in order to make it multilingual.', 'gf' ) );

# Add a theme chooser field
$themes = $gravityfields->themes->get();
if( true || $themes ) {
	# Start with the default theme
	$themes = array_merge( array(
		array(
			'id'    => 'default',
			'title' => __( 'Default Theme', 'gf' ),
			'dir'   => GF_DIR . 'templates',
			'url'   => GF_URL . 'templates',
			'image' => GF_URL . 'templates/screenshot.png'
		)
	), $themes );

	foreach( $themes as $theme ) {
		$options[] = array(
			'label' => $theme[ 'title' ],
			'image' => $theme[ 'image' ],
			'value' => $theme[ 'id' ]
		);
	}

	# Add the field
	$fields[] = GF_Field::factory( 'image_select', 'gf_theme', __( 'Theme', 'gf' ) )->add_options( $options );
}

# A field that controls the Google Maps API Key
$fields[] = GF_Field::factory( 'text', 'gf_google_fonts_api_key', __( 'Google Fonts API Key', 'gf' ) )
	->set_description( __( 'In order to enable the Google Font field, you need to add your API key here. One can be generated at <a href="https://code.google.com/apis/console/" target="_blank">Google APIs Console</a>.', 'gf' ) );

# Create the page
GF_Options::page( 'Settings', array(
	'parent' => 'edit.php?post_type=gravityfields',
	'title' => __( 'Settings', 'gf' ),
	'description' => __( "Welcome to Gravity Fields. This plugin will allow you to add fields to most places in the WordPress admin and this is the place where you set them up.", 'gf' ),
))->add_fields( $fields );