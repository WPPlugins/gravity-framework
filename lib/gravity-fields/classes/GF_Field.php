<?php
/**
 * Base class for each field
 */
class GF_Field {
	protected $id,
			  $input_id,
			  $title,
			  $datastore,
			  $value,
			  $default_value,
			  $description,
			  $help_text,
			  $is_required = false,
			  $validation_rule = '/^.+$/',
			  $dependencies = array(),
			  $dependencies_relation = 'AND',
			  $custom_template,
			  $css_classes = array(),
			  $is_multilingual = false,
			  $multilingual_support = false;

	function __construct($id, $title = null) {
		$this->id       = sanitize_title($id);
		$this->input_id = $this->id;

		if( method_exists($this, 'before_constructor') )
			$this->before_constructor();

		if($title) {
			$this->title = $title;
		} else {
			$this->title = ucwords( trim( preg_replace('~[\-_]~', ' ', $id) ) );
		}

		if( method_exists($this, 'after_constructor') )
			$this->after_constructor();
	}

	public function get_datastore() {
		return $this->datastore ? $this->datastore : false;
	}

	public function set_datastore(GF_Datastore $datastore, $optional = false) {
		global $gf_processors;

		if( ($optional && !$this->datastore) || !$optional ) {
			$this->datastore = $datastore;

			$value = $this->datastore->get_value($this->id);
			if( $value ) {
				$this->set_value( $value );
			}
		}

		return $this;
	}

	public function prepare_value( $value ) {
		if( method_exists($this, 'filter_value') ) {
			return $this->filter_value($value);
		} elseif(is_string($value)) {
			return stripslashes($value);
		} else {
			return $value;
		}		
	}

	public function save($src) {
		if( ! isset( $src[$this->id] ) ) {
			return;
		}

		$data = $src[ $this->id ];

		if( $this->is_multilingual ) {
			$value     = array();
			$languages = GF_ML::get();

			foreach( $languages as $language) {
				extract($language);

				if( isset( $data[$code] ) ) {
					$value[ $code ] = $this->prepare_value( $data[$code] );
				} else {
					$value[ $code ] = false;
				}
			}

			$this->value = GF_ML::join( $value );
		} else {
			$this->value = $this->prepare_value( $data );
		}

		if( ! method_exists($this->datastore, 'save_value')) {
			return;
		}

		$this->datastore->save_value($this->id, $this->value);
	}

	private function display_language_switcher() {
		$buttons = array();

		$languages = GF_ML::get();
		foreach( $languages as $language ) {
			extract($language);

			$buttons[] = sprintf(
				'<a href="#" class="lang-btn lang-btn-%s" data-language="%s">
					<img src="%s" alt="%s" title="%s" />
				</a>',
				$code,
				$code,
				$flag,
				$name,
				$name
			);
		}

		echo '<ul class="gf-lang-switch"><li>' . implode( "</li><li>", $buttons ) . '</li></ul>';
	}

	private function base_display_input() {
		if( ! $this->is_multilingual ) {
			$this->display_input();
			return;
		}

		$languages = GF_ML::get();

		if( count( $languages ) > 1 ) {
			# Backup vars
			$id       = $this->input_id;
			$value    = $this->value;
			$width    = 25;

			echo '<div class="gf-lang-wrap" style="padding-right:' . ( $width * count( $languages ) ) . 'px;">';

				$this->display_language_switcher();

				# Display inputs
				foreach( $languages as $language ) {
					$this->language = $language['code'];
					$this->input_id = $id . '[' . $language['code'] . ']';
					$this->value = GF_ML::split($value, $language['code']);

					echo '<div class="lang-input lang-input-' . $language['code'] . '">';
						$this->display_input();
					echo '</div>';
				}

			echo '</div>';

			# Restore vars
			$this->language = null;
			$this->input_id = $id;
			$this->value    = $value;
		} else {
			$this->display_input();
		}
	}

	public function display( $location = null ) {
		global $gravityfields;
		
		$base_template = 'field';

		if( $this->custom_template ) {
			$base_template = $this->custom_template;
		}

		include( $gravityfields->themes->path( $base_template, $location ) );
	}

	# Resets a field in case it's in a repeater or something similar
	public function reset() {
		if($this->default_value) {
			$this->value = $this->default_value;
		} else {
			$this->value = false;
		}
	}

	public function set_input_id($id) {
		$this->input_id = $id;
		return $this;
	}

	public function get_input_id() {
		return $this->input_id;
	}

	public function set_help_text($text) {
		$this->help_text = $text;
		return $this;
	}

	public function get_help_text($text) {
		return $this->help_text;
	}

	public function set_description($text) {
		$this->description = $text;
		return $this;
	}

	public function get_description() {
		return $this->description;
	}

	public function set_id($id) {
		$this->id = $id;
		return $this;
	}

	public function get_id() {
		return $this->id;
	}

	public function set_title($title) {
		$this->title = $title;
		return $this;
	}

	public function get_title() {
		return $this->title;
	}

	public function set_value($value) {
		if($value !== false) {
			$this->value = is_string($value) ? stripslashes($value) : $value;
		} else {
			$this->value = $this->default_value;
		}
	}

	public function set_default_value($value) {
		if(!isset($this->value))
			$this->value = $value;

		$this->default_value = $value;
		return $this;
	}

	public function get_default_value() {
		return $this->default_value;
	}

	public function make_required( $expression = '' ) {
		$this->is_required = true;

		if( $expression ) {
			$this->validation_rule = $expression;
		}

		return $this;
	}

	public function field_atts( $additional_classes = null ) {
		# Custom CSS classes
		$classes = ' ' . implode(' ', $this->css_classes);

		if( $additional_classes ) {
			$classes .= " $additional_classes";
		}
		
		# Defaults
		$atts = array(
			'class'       => 'gf-field ' . strtolower(str_replace('_', '-', get_class($this))) . $classes,
			'data-id' => $this->get_id()
		);

		# Custom field atts
		if( isset($this->html_attributes) && is_array($this->html_attributes) ) {
			$atts += $this->html_attributes;
		}

		if( $this->is_required ) {
			$atts['data-regex'] = $this->validation_rule;
			$atts['class'] .= ' required';
		}

		$atts = apply_filters('gf_field_attributes', $atts, $this);

		$atts_str = '';
		foreach($atts as $att => $val) {
			$atts_str .= ' ' . $att . '="' . esc_attr($val) . '"';
		}

		echo $atts_str;
	}

	/**
	 * Add a dependency on other field
	 * 
	 * @param string|GF_Field $field Field key or a GF_Field object
	 * @param string $value A comparable value. If left null, any non-empty value will be valid
	 * @param string $rule Boolean operator
	 */
	public function set_dependency( $field, $value = null, $rule = null ) {
		if( ! is_a( $field, 'GF_Field' ) && ! is_string( $field ) ) {
			gf_die('You need to pass either a field or it&apos;s key to set_dependency()!');
		}

		$dependecy_key = is_a( $field, 'GF_Field' ) ? $field->get_id() : $field;
		$dependency_data = array();

		if( $value ) {
			$dependency_data[ 'value' ] = $value;
			$dependency_data[ 'compare' ]  = $rule ? $rule : '=';
		} else {
			$dependency_data[ 'compare' ] = 'NOT_NULL';
		}

		$this->dependencies[ $dependecy_key ] = $dependency_data;

		return $this;
	}

	/**
	 * Sets the relation between field dependencies
	 * 
	 * @param string $relation Boolean operator
	 */
	public function set_dependencies_relation(string $relation) {
		$possible_relations = array( 'AND', 'OR', 'XOR' );
		$possible_relations = apply_filters( 'gf_dependency_relations', $possible_relations );

		if( ! in_array( strtoupper( $relation ), $possible_relations ) ) {
			gf_die( sprintf(
				"Trying to set an invalid relation (%s) between fields! Available relations: %s",
				$relation,
				implode( ', ', $possible_relations )
			) );
		}

		$this->dependencies_relation = $relation;

		return $this;
	}

	/**
	 * Returns field dependencies
	 * 
	 * @return array() || false
	 */
	function get_dependencies() {
		if( empty( $this->dependencies ) ) {
			return array();
		}

		$dependencies = array(
			'relationship' => $this->dependencies_relation,
			'targets'      => $this->dependencies
		);

		return apply_filters( 'gf_field_dependencies', $dependencies, $this );
	}

	/**
	 * Sets a custom template for the field display in the admin
	 *
	 * @param $template String - The custom template
	 */
	function set_custom_template($template) {
		$this->custom_template = $template;

		$this->css_classes[] = $template;

		return $this;
	}

	/**
	 * Enable multilingual content for this field
	 */
	function multilingual() {
		if( ! $this->multilingual_support ) {
			$class_name = get_class( $this );
			gf_die("Class <strong>$class_name</strong> cannot be multilingual!");
		}

		if( GF_ML::check() ) {
			$this->is_multilingual = true;

			$this->css_classes[] = 'multilingual';
		}

		return $this;
	}

	/**
	 * Destroys the field by cleaning up actions and filters.
	 * 
	 * The default field does not have any, but sub-classes may have scripts or actions
	 */
	public function destroy() {
		// Nothing to remove yet
	}

	##########################################################################################
	############################### STATIC FUNCTIONALITY #####################################
	##########################################################################################

	/** @type string[] Holds the types of all registered fields */
	static private $available_fields = array();

	/**
	 * Convert field type to classname
	 * 
	 * @param string $type The type to be appended to GF_Field
	 * @return string $classname The full classname
	 */
	static protected function get_class( $type ) {
		$class_name = ucwords( str_replace( '_', ' ', $type ) );
		$class_name = 'GF_Field_' . str_replace( ' ', '_', $class_name );
		return apply_filters( 'gf_field_class', $class_name, $type );
	}

	/**
	 * Mark a field type as added
	 * 
	 * @param string $type The type of the field. NOT it's classname
	 * @param string $title The title of the type
	 */
	static public function add_field( $type, $title ) {
		self::$available_fields[ $type ] = $title;
	}

	/**
	 * Creates a specific field and returns it
	 * 
	 * @param string $type The type of the field, like text or google_font
	 * @param string $id The ID of the field. Containers of single types cannot have identical field IDs
	 * @param string $title The title that's to be assigned to the field. If skipped, it's generated from the id
	 * 
	 * @return GF_Field the newly created field, ready for chaining
	 */
	public static function factory( $type, $id, $title='' ) {
		$class_name = self::get_class( $type );

		if( ! class_exists( $class_name ) || ! is_subclass_of( $class_name, 'GF_Field' ) ) {
			gf_die( "Class <strong>$class_name</strong> does not exist or is not a GF_Field!" );
		}


		return new $class_name( $id, $title );
	}

	/**
	 * Get setting fields for the settings page.
	 * Calls static additional_settings() for child classes.
	 * 
	 * @param string $field_type The type of the field.
	 * @return GF_Field[] The fields for the group in the Fields repeater
	 */
	static public function settings_fields( $field_type ) {
		$default_value_description = __( 'This value will be automatically populated when the field is initially shown.', 'gf' );
		$class_name = self::get_class( $field_type );

		# Optionally, set a specific type for the default value
		$default_field_type = $field_type;
		if( method_exists( $class_name, 'get_default_value_type' ) ) {
			$default_field_type = call_user_func( array( $class_name, 'get_default_value_type' ) );
		}

		# Generic fields, which are available for each field
		$fields = array(
			# A title
			'field_title' => GF_Field::factory( 'text', 'field_title', __( 'Field Title', 'gf' ) )
				->set_description( __( 'This name will appear as a label next to the field.', 'gf' ) )
				->make_required()
				->multilingual(),

			# The main ID of the field
			'field_id' => GF_Field::factory( 'text', 'field_id', __( 'Field ID (key)', 'gf' ) )
				->set_description( __( 'You will be able to get the field\'s value using this ID', 'gf' ) )
				->make_required( '/[a-z0-9_]+/' ),

			# A description for the field
			'description' => GF_Field::factory( 'text', 'description', __( 'Description', 'gf' ) )
				->multilingual()
				->set_description( __( 'The description would appear after the field, just like this one.', 'gf' ) ),

			# Help text
			// 'help_text' => GF_Field::factory( 'text', 'help_text', __( 'Help Text', 'gf' ) )
			// 	->multilingual()
			// 	->set_description( __( 'This is only visible on hover, but might be useful if you want to guide the user on how to use the field.', 'gf' ) )
		);

		# A checkbox for enabling multilingual functionality
		if( GF_ML::check() && $field_type != 'repeater' ) {
			$fields[ 'multilingual' ] = GF_Field::factory( 'checkbox', 'multilingual', __( 'Multilingual', 'gf' ) )
				->set_text( __( 'Enable multilingual functionality', 'gf' ) )
				->set_description( __( 'If enabled, Gravity Fields will automatically display fields for each language.', 'gf' ) );
		}

		if( $field_type != 'repeater' ) {
			# The default value, which is a field of the same type. This one is not multilingual
			$fields[ 'default_value' ] = GF_Field::factory( $default_field_type, 'default_value', __( 'Default Value', 'gf' ) )
				->set_description( $default_value_description );
		}

		# The site is multilingual
		if( GF_ML::check() && $field_type != 'repeater' ) {
			$fields[ 'default_value' ]->set_dependency( 'multilingual', true, '!=' );
			$fields[ 'default_value_multilingual' ] = GF_Field::factory( $default_field_type, 'default_value_ml', __( 'Default Value', 'gf' ) )
				->multilingual()
				->set_description( $default_value_description )
				->set_dependency( 'multilingual' );
		}

		# Provide a way to change those fields
		$fields = apply_filters( 'gf_default_field_settings_fields', $fields, $field_type );

		# Get additional field settings
		if( method_exists( $class_name, 'additional_settings' ) ) {
			$fields = array_merge( $fields, call_user_func( array( $class_name, 'additional_settings' ) ) );
		}

		return apply_filters( 'gf_field_settings_fields', $fields, $field_type );
	}

	/**
	 * Return a repeater with all available fields inside
	 * 
	 * @param GF_Field_Repeater $repeater The repeater to add fields to
	 * @return GF_Field_Repeater The populated repeater
	 */
	static public function get_fields( GF_Field_Repeater $repeater ) {
		foreach( self::$available_fields as $type => $name ) {
			if( $type == 'repeater' ) {
				continue;
			}
			
			$class_name = self::get_class( $type );

			$args = array(
				'title' => $name
			);

			if( method_exists( $class_name, 'settings_description' ) ) {
				$args[ 'description' ] = call_user_func( array( $class_name, 'settings_description' ) );
			}

			$repeater->add_fields( $type, $args, call_user_func( array( $class_name, 'settings_fields' ), $type ) );
		}
	}
}