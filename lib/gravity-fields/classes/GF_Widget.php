<?php 
# Global hooks
add_action('init', array('GF_Widget', 'trigger_external_register'), 0);
add_action('widgets_init', array('GF_Widget', 'register_widgets'));
add_action('widgets_admin_page', array('GF_Widget', 'after_container'));

class GF_Widget extends WP_Widget implements GF_Datastore {
	private static $widgets = array();

	/** @type mixed[] Hold the data of the currently displayed widget */
	protected static $current_widget_data = array();

	private $gf_id,
			$title,
			$description,
			$base_fields      = array(),
			$fields           = array(),
			$values           = array(),
		    $_control_options = array(),
		    $_widget_options  = array(),
		    $field_keys       = array(),
		    $css_classes      = array('gf_widget');

	/**=====================================================
	 * Static functions used to register widgets without
	 * having to create additional function for each widget
	 *=====================================================*/
	public static function register_widgets() {
		foreach(GF_Widget::$widgets as $class) {
			register_widget($class);			
		}
	}

	public static function register_widget($class) {
		GF_Widget::$widgets[] = $class;
	}

	public static function after_container() {
		do_action('gf_after_container');
	}

	public static function trigger_external_register() {
		do_action('gf_register_widgets');
	}

	/**
	 * Get some value based on the currently displayed widget
	 * 
	 * @param string $key The key of the field
	 * @return mixed|string The value if there is a widget or an empty string otherwise
	 */ 
	public static function get_current_item_value( $key ) {
		return isset( self::$current_widget_data[ $key ] ) ? self::$current_widget_data[ $key ] : '';
	}


	/**=====================================================
	 * Main WP_Widget interface
	 *=====================================================*/
	public function __construct() {
		if(!$this->gf_id) {
			$this->gf_id    = sanitize_title(get_class($this));
		}

		if(!$this->title) {
			$name_parts = explode('_', get_class($this));
			$this->title = ucwords(implode(' ', $name_parts));
		}

		$this->_widget_options['classname'] = implode(' ', $this->css_classes);

		add_action('init', array($this, 'init'));

		# Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		parent::__construct($this->gf_id, $this->title, $this->_widget_options, $this->_control_options);
	}

 	public function form($instance) {
 		global $gravityfields;
 		
 		$this->set_datastore($instance);
 		
 		include( $gravityfields->themes->path( 'widget-form' ) );
		
		$this->output_dependencies();
	}

	public function update($new_instance, $old_instance) {
		$this->set_datastore(array());

		foreach($this->fields as $field) {
			if( is_a( $field, 'GF_Field' ) )
				$field->save($new_instance);
		}

		return $this->values;
	}

	public function widget($args, $instance) {
		extract($args);

		if(method_exists($this, 'skip_display') && $this->skip_display()) {
			return;
		} else {
			# Get in the right mood
			self::$current_widget_data = $instance;

			# Actually display the widget
			echo $before_widget;
			$this->display($args, $instance);
			echo $after_widget;

			# Clean up traces
			self::$current_widget_data = array();
		}
	}

	/*abstract*/ protected function display($args, $instance){}


	/**=====================================================
	 * Datastore functions
	 *=====================================================*/
	public function get_value($key) {
		if(isset($this->values[$key])) {
			return $this->values[$key];
		} else {
			return false;
		}
	}

	public function get_multiple($key) {
		return $this->get_value($key);
	}

	public function save_value($key, $value) {
		$this->values[$key] = $value;
	}

	public function save_multiple($key, $values = array()) {
		$this->save_value($key, $values);
	}

	public function delete_value($key) {
		unset($this->value[$key]);
	}

	/**=====================================================
	 * Widget preparation settings
	 *=====================================================*/
	protected function set_title($title) {
		$this->title = $title;
	}

	protected function set_id($id) {
		$this->gf_id = sanitize_title($id);
	}

	protected function set_width($width) {
		$this->_control_options['width'] = $width;
	}

	protected function set_description($description) {
		$this->_widget_options['description'] = $description;
	}

	protected function add_css_class($classname) {
		$this->css_classes[] = $classname;
	}

	private function add_field(GF_Field $field) {
		$this->base_fields[] = $field;
	}

	protected function add_fields(array $fields) {
		foreach($fields as $field) {
			$this->add_field($field);
		}
	}

	private function set_datastore($values) {
		$this->field_keys = array();
		$this->values = $values;

		# Clone a set of "base" fields, because there's only one instance of this class
		$this->fields = array();
		foreach($this->base_fields as $field) {
			$this->fields[] = clone $field;
		}

		foreach($this->fields as $field) {
			if( $this->check_field_id( $field->get_id() ) ) {
				$field->set_input_id($this->get_field_name($field->get_id()));
				$field->set_datastore($this);
			}
		}
	}

	/**
	 * Enqueues scripts in the admin
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'gravity-fields' );
		wp_enqueue_style( 'gravityfields-css' );
	}

	/**
	 * Check if a field with the same key has been registered
	 *
	 * @param string $key The key of the field
	 */
	protected function check_field_id( $key ) {
		if( isset( $this->field_keys[ $key] ) ) {
			gf_die( sprintf( __( 'Error: Trying to register a widget field with the %s key twice!', 'gf' ), $key ) );
			return false;
		} else {
			$this->field_keys[ $key ] = 1;
			return true;
		}
	}

	public function init() {
		if(is_admin()) {
			wp_enqueue_script( 'gravity-fields' );
			wp_enqueue_style( 'gravityfields-css' );
		}
	}
	/**
	 * Outputs the dependencies of the options page
	 * 
	 * @param boolean $output Output the dependencies or return an array
	 */
	public function output_dependencies( $output = true ) {
		$deps = array();

		foreach( $this->base_fields as $field ) {
			if( ! is_a( $field, 'GF_Field' ) ) {
				continue;
			}

			$field_deps = $field->get_dependencies();

			if( ! empty( $field_deps ) ) {
				$deps[ $field->get_id() ] = $field_deps;
			}

			if( method_exists( $field, 'get_inner_dependencies' ) ) {
				if( count( $inner = $field->get_inner_dependencies() ) ) {
					$deps[ $field->get_id() . '__inner' ] = $inner;
				}
			}
		}

		$deps = apply_filters( 'gf_options_dependencies', apply_filters( 'gf_dependencies', $deps, $this ), $this );

		if( empty( $deps ) ) {
			return array();
		}

		if( $output ) { ?>
			<script type="text/javascript">
			if( typeof(GF_Dependencies) == 'undefined' ) {
				GF_Dependencies = {};
			}

			GF_Dependencies[ '<?php echo $this->gf_id ?>' ] = jQuery.parseJSON('<?php echo json_encode($deps) ?>');
			</script>
		<?php } else {
			return $deps;
		}
	}
}