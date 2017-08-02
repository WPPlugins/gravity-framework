<?php
/**
 * Displays a field, which lets the user upload files.
 *
 * The field is using the media uploader that is used by WordPress 3.5, but has
 * a fallback to the media gallery, that was used in the verions before that.
 *
 * @since 1.3
 * @package gravityfields
 */
GF_Field::add_field( 'file', __( 'File', 'gf' ) );
class GF_Field_File extends GF_Field {
	/** @type boolean Indicates if the field supports multilingual values */
	protected $multilingual_support = true;

	/**
	 * Prepares strings & classes + enqueueing media
	 */
	protected function after_constructor() {		
		# All of the settings above apply only in the admin
		if( ! is_admin() )
			return;

		# Prepare texts
		$this->strings = apply_filters( 'gf_file_field_texts', array(
			'btn_text'     => __( 'Select', 'gf' ),
			'delete_text'  => __( 'Remove', 'gf' ),
			'confirm_text' => __( 'Are you sure you want to remove this file?', 'gf' ),
			'use_text'     => __( 'Save & Use', 'gf' )
		), $this );

		# Add some data to the HTML elements
		$this->html_attributes = array(
			'data-confirm-deletion' => $this->strings[ 'confirm_text' ]
		);

		# Add the proper strings to the uploader window
		//add_filter( 'media_view_strings' , array( $this, 'change_insert_button' ), 10, 2 );

		# Enqueue media scripts on admin_init
		remove_action( 'admin_enqueue_scripts', array( 'GF_Field_File', 'enqueue_media' ) );
		add_action( 'admin_enqueue_scripts', array( 'GF_Field_File', 'enqueue_media' ) );
	}

	/**
	 * Makes sure that the needed media scripts are required.
	 */
	static public function enqueue_media() {		
		# Enqueue custom scripts
		wp_enqueue_media();
	}

	/**
	 * Modifies the strings that are displayed in the Insert button of the media popup.
	 * 
	 * @param string[] $strigs The strings that are used
	 * @param WP_Post $post The post where the button is displayed
	 * @return string[] The modified strings
	 */
	function change_insert_button( $strings, $post ) {
		# There will be a different string for posts/pages
		$hier = $post && is_post_type_hierarchical( $post->post_type );

		# Save the default string
		$str = $hier ? __( 'Insert into page' ) :
					   __( 'Insert into post' );

		# Replace the right strings
		$strings[ 'insertIntoPost' ] = '<span class="def">' . $str . '</span>';
		$strings[ 'insertIntoPost' ] .= '<span class="gf-use-button">' . $this->strings[ 'use_text' ] . '</span>';

		return $strings;
	}

	/**
	 * Displays a preview and a file chooser
	 */
	function display_input() {
		echo '<div class="gf-file-wrap">';
		
		# Display the preview, this is different for each file type - generic, image and audio.
		$this->display_preview();

		# Add button
		echo '<span class="buttons">';
			# Select button
			echo '<a href="#" class="button-primary">' . $this->strings[ 'btn_text' ] . '</a>';

			# Remove button
			$style = $this->value ? '' : ' style="display:none"';
			echo '<a href="#" class="button-secondary gf-remove-file"' . $style . '>'
				. $this->strings[ 'delete_text' ] .
				'</a>';
		echo '</span>';

		# Hidden ID input
		printf(
			'<input type="hidden" id="%s" name="%s" value="%s" />',
			esc_attr( $this->input_id ),
			esc_attr( $this->input_id ),
			esc_attr( $this->value )
		);

		echo '</div>';
	}

	/**
	 * Displays a preview of the chosen file if one is set or
	 * a placeholder for that preview.
	 */
	protected function display_preview() {
		if( $this->value ) {
			# Prepares values
			$link      = wp_get_attachment_url( $this->value );
			$text      = get_the_title( $this->value );
			$edit_link = get_edit_post_link( $this->value );
			$icon      = wp_get_attachment_image( $this->value, '', true );

			// The preview should be visible
			$style = 'display:block';
		} else {
			// Blank title, link and edit link
			$text      = '';
			$link      = '';
			$edit_link = '';
			$icon      = '';

			// The preview should be hidden
			$style = 'display:none';
		}

		# Display the preview itself
		echo '<div class="gf-file-preview" style="' . $style . '"">';
			# Output the file's icon
			echo $icon;

			// Show the file name + a blank space to let the text breathe
			echo '<span class="file-title">' . $text . '</span> ';

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
		return __( 'Enables uploading and chosing any file through the WordPress uploader.', 'gf' );
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
					'link' => __( 'A link to the file', 'gf' ),
					'url'  => __( 'The URL of the file', 'gf' ),
					'id'   => __( 'The ID of the file', 'gf' ),
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
			$data[ 'output_type' ] = 'link';
		}

		switch( $data[ 'output_type' ] ) {
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