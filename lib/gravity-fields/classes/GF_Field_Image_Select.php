<?php
/**
 * Creates a radio group with images
 */
GF_Field::add_field( 'image_select',__( 'Image Select', 'gf' ) );
class GF_Field_Image_Select extends GF_Field_Select {
	public function display_input() {
		# Prepare checked value - 1st one
		if( !$this->value && key($this->options) ) {
			$this->value = key($this->options);
		}

		?>
		<fieldset class="gf-image-select">
			<?php foreach($this->options as $key => $item):
				$checked = $key == $this->value ? ' checked="checked"' : '';
				$title   = $item['label'];
				$src     = $item['image'];
				?>
				<label>
					<input type="radio" value="<?php echo esc_attr($key) ?>" name="<?php echo $this->input_id ?>" <?php echo $checked ?>/>
					<span><img src="<?php echo $src ?>" alt="<?php echo $title ?>" title="<?php echo $title ?>" /></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays multiple images, only one of which may be selected.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		$image_sizes = get_intermediate_image_sizes();
		$image_sizes = array_combine( $image_sizes, $image_sizes );

		# Add prettier labels to some sizes
		$image_sizes[ 'full' ]      = __( 'Full', 'gf' );
		$image_sizes[ 'thumbnail' ] = __( 'Thumbnail', 'gf' );
		$image_sizes[ 'medium' ]    = __( 'Medium', 'gf' );
		$image_sizes[ 'large' ]     = __( 'Large', 'gf' );

		return array(
			GF_Field::factory( 'repeater', 'options', __( 'Options', 'gf' ) )
				->add_fields( 'Option', array(
					GF_Field::factory( 'text', 'value', __( 'Title' ) ),
					GF_Field::factory( 'image', 'image', __( 'Image', 'gf' ) ),
					GF_Field::factory( 'checkbox', 'use_image_src', __( 'Use image URL as key.', 'gf' ) ),
					GF_Field::factory( 'text', 'key', __( 'Key', 'gf' ) )
						->set_dependency( 'use_image_src', true, '!=' ),
					GF_Field::factory( 'select', 'image_size', __( 'Image Size', 'gf' ) )
						->set_dependency( 'use_image_src', true )
						->set_description( __( 'The key of the field will contain the URL of the image with the given size', 'gf' ) )
						->add_options( $image_sizes )
				))
		);
	}
}