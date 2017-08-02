<?php
/**
 * Displays a simple text separator
 */
GF_Field::add_field( 'separator',__( 'Heading', 'gf' ) );
class GF_Field_Separator extends GF_Field {
	function display( $location = null ) {
		global $gravityfields;

		include( $gravityfields->themes->path( 'separator', $location ) );
	}

	/**
	 * Get setting fields for the settings page.
	 * Calls static additional_settings() for child classes.
	 * 
	 * @param string $field_type The type of the field.
	 * @return GF_Field[] The fields for the group in the Fields repeater
	 */
	static public function settings_fields( $field_type ) {
		$fields = array(
			GF_Field::factory( 'text', 'title' )
				->multilingual()
				->set_description( 'This title will separate different kinds of content.' )
				->make_required(),
			GF_Field::factory( 'text', 'description' )
				->multilingual()
				->set_description( 'This text will appear under the title and may be used to give users directions what to do.' )
		);

		return apply_filters( 'gf_field_settings_fields', $fields, $field_type );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Separates groups of fields with different purposes and provides instructions.', 'gf' );
	}
}