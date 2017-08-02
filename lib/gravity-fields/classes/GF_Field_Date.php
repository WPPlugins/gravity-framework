<?php
/**
 * Displays a standart date chooser
 */
GF_Field::add_field( 'date',__( 'Date Picker', 'gf' ) );
class GF_Field_Date extends GF_Field {
	protected $format = 'yy/mm/dd';
	public $multilingual_support = true;

	function set_format($format) {
		$this->format = $format;
		return $this;
	}

	function display_input() {
		# Get the structure
		echo '<input type="text" class="gf-datepicker" value="' . esc_attr($this->value) . '" id="' . $this->input_id . '" name="' . $this->input_id . '" />';
	}

	function after_constructor() {
		if( ! is_admin() ) {
			return;
		}

		$this->css_classes[] = 'gf-use-ui';
		$this->html_attributes[ 'data-format' ] = $this->format;

		wp_enqueue_style( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-i18n' );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a standard jQuery UI datepicker.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'text', 'date_format', __( 'Date Format', 'gf' ) )
				->set_default_value( 'mm/dd/yy' )
				->set_description( __( 'Enter date format, according to the PHP date function specifications: <a target="_blank" href="http://php.net/manual/en/function.date.php">Date Manual</a>', 'gf' ) )
		);
	}
}