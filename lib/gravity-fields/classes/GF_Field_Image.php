<?php
/**
 * Display a media select button using the new WP 3.5 Gallery
 *
 * @since 1.0
 */
GF_Field::add_field( 'image',__( 'Image', 'gf' ) );
class GF_Field_Image extends GF_Field_File {
	/**
	 * Displays a preview of the chosen image if one is set or
	 * a placeholder image for that preview.
	 */
	protected function display_preview() {
		if( $this->value ) {
			# Prepares values
			$atts = wp_get_attachment_image_src( $this->value, 'thumbnail' );

			$image = '<img src="' . $atts[ 0 ] . '" alt="" />';
			$link      = wp_get_attachment_url( $this->value );
			$edit_link = get_edit_post_link( $this->value );

			// The preview should be visible
			$style = 'display:block';
		} else {
			// Blank title, link and edit link
			$image = '<img src="" alt="" />';
			$link      = '';
			$edit_link = '';

			// The preview should be hidden
			$style = 'display:none';
		}

		# Display the preview itself
		echo '<div class="gf-file-preview gf-image-preview" style="' . $style . '"">';
			// Show the file name + a blank space to let the text breathe
			echo $image;

			// This button links to the file itself
			echo '<a href="' . $link . '" target="_blank" class="button-secondary file-link">' . __( 'View', 'gf' ) . '</a> ';

			// This button links to the file's edit screen
			echo '<a href="' . $edit_link . '" target="_blank" class="button-secondary edit-link">' . __( 'Edit', 'gf' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Enables uploading and chosing an image through the WordPress uploader.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		# Fetch image sizes
		$image_sizes = get_intermediate_image_sizes();
		$image_sizes = array_combine( $image_sizes, $image_sizes );

		# Add prettier labels to some sizes
		$image_sizes[ 'full' ]      = __( 'Full', 'gf' );
		$image_sizes[ 'thumbnail' ] = __( 'Thumbnail', 'gf' );
		$image_sizes[ 'medium' ]    = __( 'Medium', 'gf' );
		$image_sizes[ 'large' ]     = __( 'Large', 'gf' );

		return array(
			GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			GF_Field::factory( 'select', 'output_type', __( 'Output Type', 'gf' ) )
				->add_options( array(
					'image' => __( 'A full image tag', 'gf' ),
					'url'   => __( 'The URL of the image', 'gf' ),
					'id'    => __( 'The ID of the attachment image', 'gf' ),
				) ),
			GF_Field::factory( 'select', 'image_size', __( 'Image Size', 'gf' ) )
				->add_options( $image_sizes )
				->set_description( __( 'Those are the available image sizes. If you want to add additional ones, use the add_image_size() function.', 'gf' ) )
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
			$data[ 'output_type' ] = 'image';
		}

		switch( $data[ 'output_type' ] ) {
			case 'image':
				$value = wp_get_attachment_image( $value, $data[ 'image_size' ] );
				break;

			case 'url':
				$value = wp_get_attachment_image_src( $value, $data[ 'image_size' ] );
				$value = $value[ 0 ];
				break;
		}

		return $value;
	}
}