<?php
/**
 * Colorpicker
 */
GF_Field::add_field( 'color',__( 'Color Picker', 'gf' ) );
class GF_Field_Color extends GF_Field {
	public $multilingual_support = true;
	protected $default_value = '#000000';

	function display_input() {
		echo '<input type="text" value="' . esc_attr($this->value) . '" id="' . $this->input_id .'" name="' . $this->input_id .'" />';
	}	

	function after_constructor() {
		if(!is_admin()) {
			return;
		}

		wp_enqueue_style( 'iris' );
		wp_enqueue_script( 'iris' );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a jQuery color picker with automatic toggling on hover.', 'gf' );
	}
}