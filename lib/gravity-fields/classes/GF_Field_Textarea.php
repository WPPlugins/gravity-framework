<?php
GF_Field::add_field( 'textarea',__( 'Textarea', 'gf' ) );
class GF_Field_Textarea extends GF_Field {
	protected $rows = 5,
			$multilingual_support = true;

	public function display_input() {
		echo '<textarea name="' . $this->input_id . '" id="' . $this->input_id . '" rows="' . $this->rows . '">' . htmlspecialchars( stripslashes( $this->value ) ) . '</textarea>';
	}

	public function set_rows($rows) {
		$this->rows = $rows;
		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a basic text area.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'number', 'rows', __( 'Rows', 'gf' ) )
				->set_default_value( 5 )
				->slider( 1, 20 ),
			GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			GF_Field::factory( 'checkbox', 'output_add_paragraphs', __( 'Add Paragraphs', 'gf' ) )
				->set_text( __( 'Automatically add paragraphs and new lines.', 'gf' ) ),
			GF_Field::factory( 'checkbox', 'output_apply_shortcodes', __( 'Apply Shortcodes', 'gf' ) )
				->set_text( __( 'Apply', 'gf' ) )
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
		if( isset( $data[ 'output_apply_shortcodes' ] ) && $data[ 'output_apply_shortcodes' ] ) {
			$value = do_shortcode( $value );
		}

		if( isset( $data[ 'output_add_paragraphs' ] ) && $data[ 'output_add_paragraphs' ] ) {
			$value = wpautop( $value );
		}

		return $value;
	}
}