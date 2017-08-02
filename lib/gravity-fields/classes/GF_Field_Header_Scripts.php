<?php
/**
 * Enables custom scripts that are placed in the header
 */
GF_Field::add_field( 'header_scripts',__( 'Header Scripts', 'gf' ) );
class GF_Field_Header_Scripts extends GF_Field_Textarea {
	protected $rows = 3;

	function output_content() {
		if( $this->value ) {
			echo $this->value;			
		}
	}

	function after_constructor() {
		add_action( 'wp_head', array( $this, 'output_content' ) );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a textarea whose content is automatically displayed in wp_head().', 'gf' );
	}
}