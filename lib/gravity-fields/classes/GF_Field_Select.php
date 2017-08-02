<?php
GF_Field::add_field( 'select',__( 'Select', 'gf' ) );
class GF_Field_Select extends GF_Field {
	protected $options = array(),
			  $no_options_message,
			  $multilingual_support = true;

	protected function check_options( $default_message ) {
		if(empty($this->options)) {
			if(!$this->no_options_message)
				$this->no_options_message = $default_message;

			echo '<p class="only-child">' . $this->no_options_message . '</p>';
			return false;
		}		

		return true;
	}

	public function display_input() {
		if(!$this->check_options( __('This select has no options.', 'gf') )) {
			return;
		}

		echo '<select name="' . $this->input_id . '" id="' . $this->input_id . '">';
		foreach($this->options as $key => $option) {
			$selected = $this->value == $key ? ' selected="selected"' : '';

			echo '<option value="' . esc_attr($key) . '"' . $selected  . '>' . $option . '</option>';
		}
		echo '</select>';
	}

	public function add_options(array $options) {
		$this->options += $options;
		return $this;
	}

	public function set_no_options_message($message) {
		$this->no_options_message = $message;
		return $this;
	}

	public function add_posts(array $args) {
		$args = array_merge( array(
			'posts_per_page' => -1
		), $args );

		$items = get_posts($args);
		foreach($items as $item) {
			$this->options[$item->ID] = apply_filters('the_title', $item->post_title);
		}
		return $this;
	}

	public function chosen() {
		$this->html_attributes = array(
			'data-chosen' => true
		);

		wp_enqueue_script( 'chosen-script' );
		wp_enqueue_style( 'chosen-style' );

		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a drop-down with predefined options.', 'gf' );
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
			'jquery_plugin' => GF_Field::factory( 'checkbox', 'jquery_plugin', __( 'jQuery Enchanced', 'gf' ) ),

			'output_data_separator' => GF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'gf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;gf&quot; function or shortcode.', 'gf' ) ),
			'output_data_type' => GF_Field::factory( 'select', 'output_data_type', __( 'Output Item', 'gf' ) )
				->add_options( array(
					'value' => __( 'Output the value of the select, the way it is saved', 'gf' ),
					'text'  => __( 'Output the label of the selected value', 'gf' )
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
		if( ! isset( $data[ 'output_data_type' ] ) || $data[ 'output_data_type' ] == 'value' ) {
			return $value;
		} else {
			return $this->options[ $value ];
		}
	}

	/**
	 * Displays a text input for default value
	 *
	 * @return string The type of the field
	 */
	static public function get_default_value_type() {
		return 'text';
	}
}