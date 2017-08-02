<?php
/**
 * Creates a tag-style input, which extends set, but allows multiple tags
 */
GF_Field::add_field( 'tags',__( 'Tags', 'gf' ) );
class GF_Field_Tags extends GF_Field_Select {
	/**
	 * Displays the input of the fields
	 */
	function display_input() {
		if( ! is_array( $this->value ) ) {
			$this->value = array();
		}

		echo '<select name="' . esc_attr( $this->input_id ) . '[]" multiple="multiple" class="input" style="width: 100%">';
		foreach( $this->options as $key => $option ) {
			$checked = is_array( $this->value ) && in_array( $key, $this->value ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( $key ) . '"' . $checked . '>' . esc_html( $option ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Filter the value whens saving
	 * 
	 * @param mixed[] $value The value
	 * @return mixed[] The right values
	 */
	public function filter_value( $value ) {
		return $value;
	}


	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a multiple-choise select, similar to tags.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		$post_types = array();
		$excluded = apply_filters( 'gf_excluded_post_types', array( 'attachment', 'gravityfields' ) );
		$raw = get_post_types( array(
			'show_ui' => true
		), 'objects' );
		foreach( $raw as $id => $post_type ) {
			if( in_array( $id, $excluded ) ) {
				continue;
			}

			$post_types[ $id ] = $post_type->labels->name;
		}

		return array(
			'values_source' => GF_Field::factory( 'select', 'values_source', __( 'Values Source', 'gf' ) )
				->add_options(array(
					'textarea' => __( 'Manually Entered', 'gf' ),
					'posttype' => __( 'Automatically add pages/posts', 'gf' )
				)),
			'options' => GF_Field::factory( 'repeater', 'options', __( 'Options', 'gf' ) )
				->set_dependency( 'values_source', 'textarea' )
				->add_fields( 'option', __( 'Option', 'gf' ), array(
					GF_Field::factory( 'text', 'value', __( 'Value', 'gf' ) ),
					GF_Field::factory( 'text', 'key', __( 'Key', 'gf' ) )
				)),
			'post_type' => GF_Field::factory( 'select', 'post_type', __( 'Post Type', 'gf' ) )
				->add_options( $post_types )
				->set_description( __( 'If you&apos;ve choosen &quot;Automatically add pages/posts&quot; above, please choose the required post type.', 'gf' ) )
				->set_dependency( 'values_source', 'posttype' ),

			'output_data_separator' => GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			'output_data_type' => GF_Field::factory( 'select', 'output_data_type', __( 'Output Item', 'gf' ) )
				->add_options( array(
					'value' => __( 'Output the value of the tag, the way it is saved', 'gf' ),
					'text'  => __( 'Output the label of the selected tag', 'gf' )
				) )
		);
	}

	/**
	 * Process the value based on the settings in the admin.
	 * 
	 * @param int[] $values The value of the field
	 * @param mixed $data The settings of the field, added through the Gravity Fields section
	 * 
	 * @return string The content to be shown in the editor.
	 */
	public function process_value( $values, $data ) {
		$output = array();

		foreach( $values as $value ) {
			if( ! isset( $data[ 'output_data_type' ] ) || $data[ 'output_data_type' ] == 'value' ) {
				$output[] = $value;
			} else {
				$output[] = $this->options[ $value ];
			}
		}

		return implode( ', ', $output );
	}		
}