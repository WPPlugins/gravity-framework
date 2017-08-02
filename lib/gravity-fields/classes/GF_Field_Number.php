<?php
GF_Field::add_field( 'number',__( 'Number', 'gf' ) );
class GF_Field_Number extends GF_Field {
	public $multilingual_support = true;

	public function display_input() {
		echo '<input type="number" name="' . $this->input_id . '" id="' . $this->input_id . '" value="' . esc_attr( stripslashes( $this->value ) ) . '" />';
	}

	public function filter_value($val) {
		if( is_array( $val ) ) {
			$filtered = array();

			foreach($val as $key => $number) {
				$filtered[ $key ] = intval($number);
			}

			return $filtered;
		} else {
			return intval($val);
		}
	}

	public function slider($min, $max, $step = 1) {
		$this->html_attributes = array(
			'data-slider' => '1',
			'data-min'    => $min,
			'data-max'    => $max,
			'data-step'   => $step
		);

		$this->css_classes[] = 'gf-use-ui';
		
		wp_enqueue_script( 'jquery-ui-slider' );

		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a HTML5 number input with support for jQuery UI Slider.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'checkbox', 'enable_slider', __( 'Enable Slider', 'gf' ) )
				->set_description( __( 'If you want to display the field as a UI Slider, check this. You will also need to fill the next fields - minimum and maximum value.', 'gf' ) ),
			GF_Field::factory( 'number', 'slider_minimum', __( 'Slider Min.', 'gf' ) )
				->set_dependency( 'enable_slider' ),
			GF_Field::factory( 'number', 'slider_maxmimum', __( 'Slider Max. Value', 'gf' ) )
				->set_dependency( 'enable_slider' )
		);
	}
}