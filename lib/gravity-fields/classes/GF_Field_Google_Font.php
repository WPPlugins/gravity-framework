<?php
GF_Field::add_field( 'google_font',__( 'Google Font', 'gf' ) );
class GF_Field_Google_Font extends GF_Field_Select {
	protected $error_type = null;

	function after_constructor() {
		if(!is_admin()) {
			return;
		}
		
		$this->option = get_option('gf__google_fonts');
		if( is_array($this->option) && isset($this->option['expires']) && $this->option['expires'] > time() ) {
			foreach($this->option['data'] as $item) {
				$this->options[$item->family] = $item->family;
			}
		}

		add_action( 'wp_ajax_gf_font_preview', array( $this, 'output_preview' ) );
	}

	function set_api_key($key) {
		if(count($this->options)) {
			return $this;
		}

		$get_args = array( 'sslverify' => false );
		$data = wp_remote_get('https://www.googleapis.com/webfonts/v1/webfonts?key=' . $key, $get_args);

		if(is_a($data, 'WP_Error')) {
			foreach( $data->errors as $err ) {
				$this->error_type = $err[0];

				if( $err[0] == 'There are no HTTP transports available which can complete the requested request.' ) {
					$this->error_type = __( 'The list of fonts could not be loaded. Please verify that your server&apos;s PHP settings allow fetching data through the HTTPS protocol.', 'gf' );
				}
			}

			return $this;
		}

		$items = json_decode($data['body']);
		if( !isset( $items->items ) ) {
			return $this;
		}

		$items = $items->items;

		$this->option = array(
			'expires' => time() + 3600,
			'data'    => $items
		);

		update_option('gf__google_fonts', $this->option);

		foreach($items as $item) {
			$this->options[$item->family] = $item->family;
		}

		return $this;
	}

	public function display_input() {
		if( ! $this->check_options( $this->error_type ? $this->error_type : __('You need to set a Google API Key for this field in order to load the list of fonts.', 'gf') ) ) {
			return;
		}

		$options = '';
		foreach($this->options as $key => $option) {
			$selected = $this->value == $key ? ' selected="selected"' : '';
			$options .= '<option value="' . esc_attr($key) . '"' . $selected  . '>' . $option . '</option>';
		}

		$preview_text = __('Preview this font', 'gf');

		echo '<div>
			<select name="' . esc_attr( $this->input_id ) . '" id="' . esc_attr($this->input_id) . '">
				' . $options . '
			</select>
			<a href="?action=gf_font_preview&ff=" class="button-primary gf-font-preview">' . $preview_text . '</a>
			<span class="loader"></span>
			<iframe style="height:1px; overflow:hidden" width="100%" class="gf-font-previewer no-content"></iframe>
		</div>';
	}

	/**
	 * Ouput a font's preview.
	 */
	public function output_preview() {
		# In case there is no font face argument
		if( ! isset( $_GET[ 'ff' ] ) ) {
			return;
		}

		# Prepare the name of the font family and check for it
		$font_exists = false;

		$f = strtolower( $_GET[ 'ff' ] );

		$fonts = get_option( 'gf__google_fonts' );
		foreach( $fonts[ 'data' ] as $font ) {
			if( strtolower( $font->family ) == $f ) {
				$font_exists = true;
				break;
			}
		}

		if( ! $font_exists ) {
			return;
		}

		include( GF_DIR . 'common/font-preview.php' );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a drop-down with fonts from the Google Fonts Directory. Preview available.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'text', 'api_key', __( 'Google Fonts API Key', 'gf' ) )
				->set_description( __( '<b>Important:</b> Without an API key, GF Fields cannot retrive remote fonts!', 'gf' ) )
		);
	}
}