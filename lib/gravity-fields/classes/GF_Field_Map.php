<?php
/**
 * Displays a Google Map and lets you select a location
 */
GF_Field::add_field( 'map',__( 'Map', 'gf' ) );
class GF_Field_Map extends GF_Field {
	protected $width  = '100%',
			  $height = '400px',
			  $default_value   = '38.8865,-77.0969,8',
			  $display_locator = false,
			  $locator_text,
			  $no_google_scripts_text,
			  $multilingual_support = true;

	public function after_constructor() {
		if( !is_admin() )
			return;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues scripts for Google Maps
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'google-maps-api' );
	}

	public function set_width($w) {
		$this->width = strpos($w, '%') === false ? $w . 'px' : $w;
		return $this;
	}

	public function set_height($h) {
		$this->height = strpos($h, '%') === false ? $h . 'px' : $h;
		return $this;
	}

	public function show_locator($toggle = true) {
		$this->display_locator = $toggle;
		return $this;
	}

	public function display_input() {
		# Set help text if not already set
		if( !isset($this->help_text) )
			$this->help_text = __( 'Double click on the map to move the marker.', 'gf' );

		if( !isset($this->no_google_scripts_text) ) {
			$this->no_google_scripts_text = __( 'The Google Maps script could not be loaded. Please check if you have network connection and contact the site&apos;s administrator if you do.', 'gf' );
		}

		# Display wrapping div start
		echo '<div class="gf-map-wrap" style="width:' . $this->width . ';">';

		# Replace values and display a hidden field
		echo '<input type="hidden" value="' . esc_attr($this->value) . '" id="' . $this->input_id . '" name="' . $this->input_id . '" />';

		# Display the div that's going to be responsible for the map
		echo '<div id="' . $this->input_id . '-map" class="gf-map" style="width:' . $this->width . '; height:' . $this->height . ';"></div>';

		if( $this->display_locator ) {
			$locate_btn_text = $this->locator_text ? $this->locator_text : __( 'Locate on map', 'gf' );
			?>
			<div class="gf-map-locator">
				<input type="text" class="gf-map-search" placeholder="<?php _e('Enter address and click the find button...', 'gf') ?>" />
				<input type="button" value="<?php echo $locate_btn_text ?>" class="gf-map-submit" />
			</div>
			<?php
		}

		# Display wrapping div end
		echo '</div>';

		# Display a message if Google maps could not be loaded
		echo '<div class="no-maps" style="display:none">' . $this->no_google_scripts_text . '</div>';
	}

	public function set_locator_text($text) {
		$this->locator_text = $text;
		return $this;
	}

	public function set_noscripts_text($text) {
		$this->no_google_scripts_text = $text;
		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a Google Maps map with the ability to specify location. Search is available.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'number', 'height', __( 'Height', 'gf' ) )
				->slider( 50, 1000 ),
			GF_Field::factory( 'checkbox', 'show_locator', __( 'Show Locator', 'gf' ) )
				->set_text( 'Show' )
				->set_default_value(true),
			GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			GF_Field::factory( 'number', 'output_width', __( 'Width', 'gf' ) )
				->slider( 0, 1000 )
				->set_description( __( 'Leave 0 to set the width to 100%.', 'gf' ) ),
			GF_Field::factory( 'number', 'output_height', __( 'Height', 'gf' ) )
				->slider( 50, 1000 )
				->set_default_value( 50 ),
		);
	}

	/**
	 * Process the value based on the settings in the admin
	 * 
	 * @param int $value The coordinates
	 * @param mixed $data The settings of the field
	 */
	public function process_value( $value, $data ) {
		if( ! $value ) {
			return '';
		}

		extract( $data );

		# Add the Google Maps API
		wp_enqueue_script( 'google-maps-api' );

		# Enqueue the Gravity Fields front-end scripts
		wp_enqueue_script( 'gravity-fields-site' );

		return sprintf(
			'<div class="gf-map" data-value="%s" style="width:%s;height:%s"></div>',
			esc_attr( $value ),
			$output_width ? $output_width . 'px' : '100%',
			$output_height . 'px'
		);
	}
}