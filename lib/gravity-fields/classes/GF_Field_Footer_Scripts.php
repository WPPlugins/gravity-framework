<?php
/**
 * Enables custom scripts that are placed in the foooter
 */
GF_Field::add_field( 'footer_scripts',__( 'Footer Scripts', 'gf' ) );
class GF_Field_Footer_Scripts extends GF_Field_Textarea {
	protected $rows = 3;

	function output_content() {
		if( $this->value ) {
			echo $this->value;			
		}
	}

	function after_constructor() {
		add_action( 'wp_footer', array( $this, 'output_content' ) );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a textarea whose content is automatically displayed in wp_footer().', 'gf' );
	}
}