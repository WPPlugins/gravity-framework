<?php
/**
 * Displays a select that lets you choose sidebars
 */
GF_Field::add_field( 'select_sidebar',__( 'Select Sidebar', 'gf' ) );
class GF_Field_Select_Sidebar extends GF_Field {
	/** @type string[] Should contain all registered sidebars */
	protected $wp_sidebars = array(),
	/** @type string[] Should contain sidebar names that should be hidden */
	$blacklists = array(),
	/** @type string[] Should contain sidebars registered through GF */
	$gf_sidebars = array(),

	/** @type int Priority in the init hook. Make sure all sidebars are registered */
	$init_priotity = 1000,

	/** @type boolean Control if sidebars may be manipulated */
	$manipulation = false,

	/** @type boolean Indicate that the field can be multilingual */
	$multilingual_support = true,

	/** @type array Hold register sidebar args */
	$sidebar_args = array(
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget'  => '</li>',
		'before_title'  => '<h2 class="widgettitle">',
		'after_title'   => '</h2>'
	);

	/** @type string[] Holds all registered fields. */
	protected static $registered_fields = array();

	/**
	 * Allow adding new sidebars through the field
	 *
	 * @param boolean|null $allow Allow the functionality
	 *
	 * @return GF_Field_Select_Sidebar The field for chaining
	 */
	function allow_manipulation( $allow = true ) {
		$this->manipulation = $allow;

		if( $allow ) {
			# Some JS helpers
			$this->html_attributes[ 'data-manipulate' ] = true;
		}

		return $this;
	}

	/**
	 * Set sidebar arguments, before/after widget/title are the only available
	 *
	 * @link http://codex.wordpress.org/Function_Reference/register_sidebar See for args reference
	 *
	 * @param array $args Arguments
	 *
	 * @return GF_Field_Select_Sidebar The field for chaining
	 */
	 function set_sidebar_args( $args )	 {
	 	$possible_keys = array( 'before_widget', 'after_widget', 'before_title', 'after_title' );
	 	$args = array_intersect_key( $args, array_flip( $possible_keys) );

	 	if( count( $this->sidebar_args ) != count( $args ) ) {
	 		gf_die( 'Please add all settings for ' . get_class( $this ) . '->set_sidebar_args()!' );
	 	}

	 	$this->sidebar_args = $args;

	 	return $this;
	 }

	/**
	 * Get registered WordPress sidebars
	 *
	 * @return array A list of all registered sidebar's titles
	 */
	function get_registered_sidebars() {
		global $wp_registered_sidebars;

		$gf_sidebars = $this->get_gf_sidebars();
		$other_sidebars = array();

		// Only add non-gf sidebars here
		foreach( $wp_registered_sidebars as $sidebar ) {
			$gf = false;
			
			if(in_array($sidebar['name'], $this->blacklists)) {
				continue;
			}
			foreach( $gf_sidebars as $gf_sidebar ) {
				if( $gf_sidebar[ 'name' ] == $sidebar[ 'name' ] ) {
					$gf = true;
					break;
				}
			}

			if( ! $gf ) {
				$other_sidebars[] = $sidebar;
			}
		}

		return $other_sidebars;
	}

	function set_blacklist($sidebars) {
		$this->blacklists = $sidebars;
		return $this;
	}
	
	/**
	 * Get GF added sidebars
	 *
	 * @return array The sidebars
	 */
	function get_gf_sidebars() {
		$all_sidebars = get_option( '__gf_sidebars' );
		$sidebars = array();

		if( !is_array( $all_sidebars ) ) {
			return array();
		}

		# Filter sidebars that apply to this field's key only
		foreach( $all_sidebars as $sidebar ) {
			if( $sidebar[ 'field' ] == $this->id ) {
				$sidebars[] = $sidebar;
			}
		}

		return is_array( $sidebars ) ? $sidebars: array();
	}

	/**
	 * Adds a sidebar to the queue for registering
	 *
	 * @param array $sidebar - Sidebar info
	 */
	function register_sidebar( $info ) {
		# Start with the default args
		$args = $this->sidebar_args;

		# Additional args
		$additional = array( 'name', 'description' );
		$args += array_intersect_key( $info, array_flip( $additional) );

		# Chnage those settings
		$args = apply_filters( 'gf_custom_sidebar_args', $args, $this );

 		# Register the sidebar
		register_sidebar( $args );
	}

	/**
	 * Check for custom sidebars that are created through this field and register them
	 */
	function register_sidebars() {
		$sidebars = $this->get_gf_sidebars();

		if( ! isset( GF_Field_Select_Sidebar::$registered_fields[ $this->id ] ) ) {
			foreach( $sidebars as $sidebar ) {
				# Also add this sidebar to the queue for registration
				$this->register_sidebar( $sidebar );
			}

			# No more sidebars
			GF_Field_Select_Sidebar::$registered_fields[ $this->id ] = 1;
		}		
	}

	/**
	 * Adds custom sidebars in the queue for registration
	 */
	function after_constructor() {
		# On widgets init, try registering the custom sidebars
		$this->register_sidebars();
	}

	/**
	 * Displays a single select in case the sidebars can't be manipulated
	 */
	function display_select() {
		# Only display WP sidebars
		$sidebars = $this->get_registered_sidebars();

		$options = '';
		foreach( $sidebars as $s ) {
			$selected = $this->value == $s['name'] ? ' selected="selected"' : '';
			$options .= '<option value="' . esc_attr( $s['name'] ) . '"' . $selected . '>' . $s['name'] . '</option>';
		}

		echo '<select name="' . $this->input_id . '" id="' . $this->input_id . '">' . $options . '</select>';
	}

	/**
	 * Displays a table which allows modifications
	 */
	function display_table() {
		global $gravityfields;
		
		# Get WP sidebars
		$sidebars = $this->get_registered_sidebars();

		# Get GF sidebars
		$gf_sidebars = $this->get_gf_sidebars();

		include( $gravityfields->themes->path( 'select-sidebar-table' ) );
	}

	/**
	 * Displays the field's input
	 */
	function display_input() {
		if( $this->manipulation ) {
			# If manipulation is allowed, use advanced layout
			$this->display_table();
		} else {
			# Otherwise, display a standart select
			$this->display_select();
		}
	}

	/**
	 * Filters out old sidebars and adds new ones
	 *
	 * @param array $sidebars
	 * @param array $deleted
	 * @param array $added
	 */
	function modify_sidebars( &$sidebars, $deleted, $added ) {
		$nice = array();

		// Filter deleted sidebars
		foreach( $sidebars as $sidebar ) {
			if( array_search( $sidebar[ 'name' ], $deleted ) === false ) {
				$nice[] = $sidebar;
			}
		}

		// Add new sidebars
		foreach( $added as $sidebar ) {
			$nice[] = array(
				'name'        => stripslashes( $sidebar[ 'name' ] ),
				'description' => stripslashes( $sidebar[ 'description' ] ),
				'field'       => $this->id
			);
		}

		// Save the values
		$sidebars = $nice;
	}

	/**
	 * Save values. Also saves new sidebars and deletes old ones
	 *
	 * @param array $source All the values in the field's container
	 */
	function save( $src ) {
		$gf_sidebars = get_option( '__gf_sidebars' );

		if( ! $gf_sidebars ) {
			$gf_sidebars = array();
		}

		if( $this->is_multilingual ) {
			# Do actions for each language
			$languages = GF_ML::get();

			foreach( $languages as $language ) {
				$code   = $language[ 'code' ];
				$added  = array();
				$deleted = array();

				# Get deleted sidebars
				if( isset( $src[ 'deleted_' . $this->id ] ) && isset( $src[ 'deleted_' . $this->id ][ $code ] ) ) {
					$deleted = stripslashes_deep( $src[ 'deleted_' . $this->id ][ $code ] );
				}

				# Get added sidebars
				if( isset( $src[ 'new_' . $this->id ] ) && isset( $src[ 'new_' . $this->id ][ $code ] ) ) {
					$added = stripslashes_deep( $src[ 'new_' . $this->id ][ $code ] );
				}

				$this->modify_sidebars( $gf_sidebars, $deleted, $added );
			}
		} else {
			# Modify sidebars for single language
			$deleted = isset( $src[ 'deleted_' . $this->id ] ) ? stripslashes_deep( $src[ 'deleted_' . $this->id ] ) : array();
			$added   = isset( $src[ 'new_' . $this->id ] ) ? stripslashes_deep( $src[ 'new_' . $this->id ] ) : array();
			$this->modify_sidebars( $gf_sidebars, $deleted, $added );
		}

		// Update the option
		update_option( '__gf_sidebars', $gf_sidebars );

		parent::save( $src );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Allows selecting and creating sidebars. Automatic registration for custom ones.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			$sidebar_fields[] = GF_Field::factory( 'checkbox', 'allow_adding', __( 'Allow Adding', 'gf' ) )
				->set_default_value( true )
				->set_text( __( 'Allow', 'gf' ) )
		);
	}
}