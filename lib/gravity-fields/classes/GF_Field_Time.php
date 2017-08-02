<?php
/**
 * Displays a jQuery timepicker
 */
GF_Field::add_field( 'time', __( 'Time Picker', 'gf' ) );
class GF_Field_Time extends GF_Field_Date {
	protected $format = 'HH:mm';
	
	/**
	 * Enqueue needed scripts and set admin details
	 */
	function after_constructor() {
		if( ! is_admin() ) {
			return;
		}

		# Call the parent function
		parent::after_constructor();

		# Enqueue the date-time picker plugin
		wp_enqueue_script( 'jquery-ui-timepicker' );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a standard jQuery UI timepicker.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'text', 'date_format', __( 'Date Format', 'gf' ) )
				->set_default_value( 'HH:mm' )
				->set_description( __( 'Enter date format, according to the PHP date function specifications: <a target="_blank" href="http://php.net/manual/en/function.date.php">Date Manual</a>', 'gf' ) )
		);
	}
}