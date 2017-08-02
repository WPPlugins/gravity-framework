<?php
GF_Field::add_field( 'text',__( 'Text', 'gf' ) );
class GF_Field_Text extends GF_Field {
	protected $autocomplete = array(),
			$multilingual_support = true;

	public function display_input() {
		echo '<input type="text" name="' . $this->input_id . '" id="' . $this->input_id . '" value="' . esc_attr( stripslashes( $this->value ) ) . '" />';

		if(count($this->autocomplete)) {
			echo '<div style="display:none;" class="gf-autocompletes">' . json_encode($this->autocomplete) . '</div>';
		}
	}

	public function add_suggestions(array $suggestions) {
		$this->autocomplete += $suggestions;

		wp_enqueue_script( 'jquery-ui-autocomplete' );

		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a text input. Optional jQuery UI Autocomplete suggestions.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'textarea', 'autocomplete_suggestions', __( 'Autocomplete Suggestions', 'gf' ) )
				->set_description( __( 'You may list predefined values here. One value per row.', 'gf' ) ),

			GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			GF_Field::factory( 'select', 'output_format_value', __( 'Format Value', 'gf' ) )
				->add_options( array(
					'none' => __( 'None', 'gf' ),
					'html' => __( 'HTML Entities', 'gf' )
				) )
		);
	}

	/**
	 * Process the value based on the settings in the admin.
	 * 
	 * @param int $value The value of the field
	 * @param mixed $data The settings of the field, added through the Gravity Fields section
	 * 
	 * @return string The content to be shown in the editor.
	 */
	public function process_value( $value, $data ) {
		if( isset( $data[ 'output_format_value' ] ) && $data[ 'output_format_value' ] ) {
			return esc_html( $value );
		}	

		return $value;
	}
}