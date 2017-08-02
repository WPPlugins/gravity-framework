<?php
GF_Field::add_field( 'select_page',__( 'Select Page', 'gf' ) );
class GF_Field_Select_Page extends GF_Field {
	public $multilingual_support = true;

	private $post_type = 'page';

	public function display_input() {
		wp_dropdown_pages(array(
			'selected'  => $this->value,
			'name'      => $this->input_id,
			'post_type' => $this->post_type
		));
	}

	/**
	 * Set a specific post type for the dropdown
	 * 
	 * WARNING: If the provided post type is not hierarchical, it will be silently reverted to page
	 *
	 * @param string $post_type The post type for the dropdown
	 * @return GF_Field The instance of the field
	 */
	public function set_post_type( $post_type ) {
		$this->post_type = apply_filters( 'gf_field_select_page_pt' , $post_type, $this );

		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a hierarchical drop-down with pages.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings page (editing a Gravity Fields container)
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			GF_Field::factory( 'radio', 'output_type', __( 'Output Type', 'gf' ) )
				->add_options( array(
					'page_id'    => __( 'Show the page ID, which is the value of the field.', 'gf' ),
					'page_title' => __( 'Show the title of the page.', 'gf' ),
					'page_link'  => __( 'Show a link to the page.', 'gf' ),
					'page_url'   => __( 'Show the URL of the page.', 'gf' )
				) )
		);
	}

	/**
	 * Process the value based on the settings in the admin.
	 * 
	 * @param int $value The value of the field
	 * @param mixed $data The settings of the field, added through the Gravity Fields section
	 * 
	 * @return string The content to be shown in the editor.
	 */
	public function process_value( $value, $data ) {
		if( isset( $data[ 'output_type' ] ) ) {
			switch( $data[ 'output_type' ] ) {
				case 'page_title':
					$value = get_the_title( $value );
					break;
				case 'page_link':
					$value = '<a href="' . esc_attr( get_permalink( $value ) ) . '">' . get_the_title( $value ) . '</a>';
					break;
				case 'page_url':
					$value = get_permalink( $value );
					break;
			}
		}

		return $value;
	}
}