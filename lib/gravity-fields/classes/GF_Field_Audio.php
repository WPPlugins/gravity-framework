<?php
/**
 * Displays a media select button using the WP 3.5 Gallery
 */
GF_Field::add_field( 'audio', __( 'Audio', 'gf' ) );

class GF_Field_Audio extends GF_Field_Image {
	/**
	 * Enqueue the media element js of WordPress
	 */
	function after_constructor() {
		wp_enqueue_script( 'wp-mediaelement' );
		wp_enqueue_style( 'wp-mediaelement' );
	
		parent::after_constructor();
	}

	/**
	 * Displays a preview of the chosen image if one is set or
	 * a placeholder image for that preview.
	 */
	function display_preview() {
		if( $this->value ) {
			# Prepares values			
			$src       = wp_get_attachment_url( $this->value );
			$player    = wp_audio_shortcode( compact( 'src' ) );
			$text      = get_the_title( $this->value );
			$edit_link = get_edit_post_link( $this->value );

			// The preview should be visible
			$style = 'display:block';
		} else {
			// Blank title, link and edit link
			$src       = '';
			$player    = '';
			$text      = '';
			$edit_link = '';

			// The preview should be hidden
			$style = 'display:none';
		}

		# Display the preview itself
		echo '<div class="gf-file-preview gf-audio-preview" style="' . $style . '"">';
			// Show the file name + a blank space to let the text breathe
			echo '<span class="file-title">' . $text . '</span> ';

			// This button links to the file's edit screen
			echo '<a href="' . $edit_link . '" target="_blank" class="button-secondary edit-link">' . __( 'Edit', 'gf' ) . '</a>';

			echo '<div class="player">' . $player . '</div>';
		echo '</div>';

		// make sure there is an audio element in ie
		?><!--[if lt IE 9]><script>document.createElement('audio');</script><![endif]--><?php
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Enables uploading and chosing an audio through the WordPress uploader.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			GF_Field::factory( 'select', 'output_type', __( 'Output Type', 'gf' ) )
				->add_options( array(
					'player' => __( 'An audio player', 'gf' ),
					'link'   => __( 'A link to the file', 'gf' ),
					'url'    => __( 'The URL of the file', 'gf' ),
					'id'     => __( 'The ID of the attachment', 'gf' ),
				) )
		);
	}

	/**
	 * Process the value based on the settings in the admin
	 * 
	 * @param int $value The ID of the image
	 * @param mixed $data The settings of the field
	 */
	public function process_value( $value, $data ) {
		if( ! isset( $data[ 'output_type' ] ) ) {
			$data[ 'output_type' ] = 'url';
		}

		switch( $data[ 'output_type' ] ) {
			case 'player':
				$src   = wp_get_attachment_url( $value );
				$value = wp_audio_shortcode( compact( 'src' ) );
				break;
			case 'link':
				$value = wp_get_attachment_link( $value );
				break;
			case 'url':
				$value = wp_get_attachment_url( $value );
				break;
		}

		return $value;
	}	
}