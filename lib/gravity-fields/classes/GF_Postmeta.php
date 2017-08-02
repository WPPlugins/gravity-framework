<?php
/**
 * Creates a post meta box on post(type) edit screens.
 * 
 * As it's name suggests, it works with the WordPress post meta API
 * 
 * @see http://codex.wordpress.org/Function_Reference/add_meta_box
 */
class GF_Postmeta extends GF_Container_Base implements GF_Container {
	/** @type string The title of the box, as it will appear in it's title bar */
	protected $title;

	/** @type int The ID of the box, should be unique. Used internally only */
	protected $id;

	/** @type string A description that will appear before the fields in the box */
	protected $description;

	/** @type string|string[] Edit screens this should appear on */
	protected $post_types = array();

	/** @type GF_Field[] The fields that will reside in the box */
	protected $fields = array();

	/** @type string $content The column the box will appear in. Either normal or side */
	protected $context = 'normal';

	/** @type The priority of the box. default, high or low */
	protected $priority = 'default';

	/** @type GF_Datastore The datastore that will be spread through the fields */
	protected $datastore;

	/** @type int[] Hierarchy levels the box will appear on */
	protected $levels = array();

	/** @type int[] Hieararchy levels the box will disappear from */
	protected $levels_hidden = array();

	/** @type string[] Spefici templates which the box will be used for */
	protected $templates = array();

	/** @type string[] Specific templates which the box will not be used for */
	protected $templates_hidden = array();

	/** @type mixed[] IDs, Slugs or Terms the box will only be visible for */
	protected $terms = array();

	/** @type mixed[] IDs, Slugs or Terms the box will not be visible for */
	protected $terms_hidden = array();

	/** @type boolean Indicates if there is a tab that's currently being open */
	protected $tab_open = false;

	/** @type boolean Indicates if there is a tab group currently being open */
	protected $tabs_open = false;

	/** @type mixed[] Tabs that will appear in the nav */
	protected $tabs = array();

	/** @type int The iterator of the current tab group */
	protected $current_tab_group = -1;

	/** @type string Positioning of the tabs */
	protected $tabs_align = "top";

	/**
	 * Creates a box by setting attributes and adding actions.
	 * The third parameter accepts arguments that will be passed to setters
	 * ex. array( 'priority' => 10 ) will call ->set_priority( 10 )
	 * 
	 * @param string $title The title of the box. Used for ID
	 * @param string|string[] $post_type The post type(s) of the page
	 * @param mixed[] $args Arguments that are passed to setters.
	 */
	function __construct( $title, $post_type, $args = null ) {
		# Process title
		$this->set_title( $title);

		# Add the post type
		$this->add_post_type( $post_type);

		# Prepare default datastore
		$this->datastore = new GF_Datastore_Postmeta();

		# Process args
		if( $args ) {
			if( is_array( $args) ) {
				foreach( $args as $property => $value ) {
					if( method_exists( $this, 'set_' . $property) ) {
						call_user_func( array( $this, 'set_' . $property ) , $value );
					} else {
						gf_die( '<strong>GF_Postmeta</strong>: ' . $property . ' is not a valid argument!' );
					}
				}
			} else {
				gf_die( '<strong>GF_Postmeta</strong>: Only arrays may be passed as options to the container!' );
			}
		}

		# This will attach the box
		add_action( 'add_meta_boxes', array( $this, 'attach_to_wp' ) );

		# This will save the box's values
		add_action( 'save_post',   array( $this, 'save' ) );

		# This will output the required scripts in the footer
		add_action( 'admin_footer', array( $this, 'output_scripts' ) );

		# This will output notices if needed
		add_action( 'admin_notices', array( $this, 'display_notice' ) );

		# Enqueue required scripts and styles in admin
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues GF's scripts and styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'gravity-fields' );
		wp_enqueue_style( 'gravityfields-css' );
	}

	/**
	 * Adds a single meta box for each post type
	 */
	public function attach_to_wp( ) {
		foreach( $this->post_types as $post_type ) {
			add_meta_box( $this->id, $this->title, array( $this, 'display' ), $post_type, $this->context, $this->priority);
		}
	}

	/**
	 * Displays the content of the meta box
	 * 
	 * @param object $post The post object
	 */
	public function display( $post ) {
		global $gravityfields;
		
		# Now that the post is known, set it as a target for the datastore
		if( method_exists( $this->datastore, 'set_post' ) ) {
			# Only happens for post meta datastores
			$this->datastore->set_post( $post->ID );
		}

		# Spread the datastore through all fields and check their IDs
		foreach( $this->fields as $field ) {
			if( is_a( $field, 'GF_Field' ) && ! $field->get_datastore() && $this->datastore->check_field_id( $field->get_id() ) ) {
				$field->set_datastore( $this->datastore );
			}
		}

		# If there is an unclosed tab, close it
		if( $this->tab_open || $this->tabs_open ) {
			$this->end_tab();
		}

		# If there is an unclosed tabs group, close it
		if( $this->tabs_open ) {
			$this->fields[] = array( 'item' => 'tabs_end' );
		}

		# Get the template
		include( $gravityfields->themes->path( 'postmeta' ) );

		# After the template itself, the field-to-field dependencies are shown
		$this->output_dependencies();

		# After the container, some additional items might be needed
		do_action( 'gf_after_container' );
	}

	/**
	 * Generatea nonce field
	 */
	private function nonce( ) {
		wp_nonce_field( $this->id, '_postmeta' );
	}

	/**
	 * Checks the nonce field in the _POST array.
	 * 
	 * @return boolean An indicator that things are okay
	 */
	private function check_nonce( ) {
		return isset( $_POST['_options_nonce'] ) && wp_verify_nonce( $_POST['_postmeta'], $this->id );
	}

	/**
	 * Add fields to the container
	 * 
	 * @param GF_Field[] $fields An array containing GF_Field child-class objects
	 * @return GF_Postmeta The current instance of the class
	 */
	public function add_fields( array $fields ){
		# Add fields, one by one
		foreach( $fields as $field ) {
			$this->add_field( $field );
		}

		return $this;
	}

	/**
	 * Add a single field to the container
	 * 
	 * @param GF_Field $field The field
	 * @return GF_Postmeta The current instance of the class
	 */
	public function add_field( GF_Field $field ) {
		$this->fields[] = $field;
	}

	/**
	 * Lets each field save it's value
	 * 
	 * @param int $post_id The id of the post being saved
	 */
	public function save( $post_id ) {
		# Only do something for normal posts
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || get_post_status( $post_id ) == 'trash' ) {
			return;
		}

		# Check the post type among all available ones
		foreach( $this->post_types as $post_type ) {
			if( get_post_type( $post_id) != $post_type ) {
				continue;
			}

			# Pass the post ID to the datastore
			if( method_exists( $this->datastore, 'set_post' ) ) {
				$this->datastore->set_post( $post_id);				
			}

			# Save values for each field
			foreach( $this->fields as $field ) {
				if( ! is_a( $field, 'GF_Field' ) ) {
					continue;
				}
				
				if( is_a( $field, 'GF_Field' ) && ! $field->get_datastore() && $this->datastore->check_field_id( $field->get_id() ) ) {
					$field->set_datastore( $this->datastore );
				}

				# Pass the whole array of data to the field
				$field->save( $_POST );
			}
		}
	}

	/**
	 * Add a post type for the box
	 * 
	 * @param string $post_type The key of the post type
	 * @return GF_Postmeta the instance of the current box
	 */
	public function add_post_type( $post_type ) {
		if( is_array( $post_type) ) {
			$this->post_types += $post_type;
		} else {
			$this->post_types[] = $post_type;
		}
		return $this;
	}

	/**
	 * Sets the column for the box
	 * 
	 * @param string $content Either normal or side
	 * @return GF_Postmeta The instance of the ox
	 */
	public function set_context( $context ) {
		$this->context = $context;
		return $this;
	}
	
	/**
	 * Get the current context of the box
	 * 
	 * @return string
	 */
	public function get_context( ) {
		return $this->context;
	}

	/**
	 * Set the priority of the box.
	 * Could be default, high and low
	 * 
	 * @param string $priority The priority as said in the codex
	 * @return GF_Postmeta The instance of the box
	 */
	public function set_priority( $priority ) {
		$this->priority = $priority;
		return $this;
	}

	/**
	 * Get the current priority of the box
	 * 
	 * @return string
	 */	
	public function get_priority( ) {
		return $this->priority;
	}

	/**
	 * A static factory-like method that creates an isntance of the class
	 * 
	 * @param string $title The title of the box
	 * @param string|string[] $post_type The post type(s) the box should appear on
	 * @param mixed[] $args The same arguments as __construct
	 * @return GF_Postmeta The instance of the newly created box
	 */
	public static function box( $title, $post_type, $args = null ) {
		return new GF_Postmeta( $title, $post_type, $args );
	}

	/**
	 * A proxy to box
	 * 
	 * @see GF_Postmeta->box
	 */
	public static function factory( $title, $post_type, $args = null ) {
		return self::box( $title, $post_type, $args );
	}

	/**
	 * Adds a specific level to the allow/deny list
	 * 
	 * @param string|int $level The needed level. If minus is added, this will mean hidden
	 */
	private function add_level( $level ) {
		if( preg_match( '~^[\+\-]?\d+$~i', $level) ) {
			$add = strpos( $level, '-' ) !== 0;
			$key = preg_replace( '~^[\+\-]?(.*)$~', '$1', $level );

			if( $add ) {
				unset( $this->levels_hidden[ $key ] );
				$this->levels[ $key ] = 1;
			} else {
				unset( $this->levels[ $key ] );
				$this->levels_hidden[ $key ] = 1;
			}
		} else {
			gf_die( "<strong>GF_Postmeta</strong>: &quot;$level&quot; is not a valid level!" );
		}
	}

	/**
	 * Bulkly add level(s)
	 * 
	 * @param mixed $levels An array or a single level
	 * @return GF_Postmeta The instance of the box
	 * @see add_level
	 */
	public function set_levels( $levels ) {
		if( is_array( $levels) ) {
			foreach( $levels as $level ) {
				$this->add_level( $level);
			}
		} else {
			$this->add_level( $levels);
		}

		return $this;
	}

	/**
	 * Adds a single template to the allow/deny list
	 * 
	 * @param string $template The filenameof the template. Might have a - to deny that template
	 */
	private function add_template( $template ) {
		$add = strpos( $template, '-' ) !== 0;
		$key = preg_replace( '~^[\+\-]?(.*)$~', '$1', $template );

		if( $add ) {
			unset( $this->templates_hidden[ $key ] );
			$this->templates[ $key ] = 1;
		} else {
			unset( $this->templates[ $key ] );
			$this->templates_hidden[ $key ] = 1;
		}
	}

	/**
	 * Add multiple templates at once.
	 * 
	 * @param string|string[] $templates The allowed/denied template(s)
	 * @return GF_Postmeta The instance of the box
	 */
	public function set_templates( $templates ) {
		if( is_array( $templates) ) {
			foreach( $templates as $template){
				$this->add_template( $template);
			}
		} else {
			$this->add_template( $templates);
		}

		return $this;
	}

	/**
	 * Adds a single term to the list of allowed/disabled ones
	 * 
	 * @param string $taxonomy The name of the taxonomy
	 * @param string|int $term_id The ID of the term, with possible - in the beginnning
	 * @return GF_Postmeta The instance of the box
	 */
	public function add_term( $taxonomy, $term_id ) {
		$add = strpos( $term_id, '-' ) === false;
		$key = preg_replace( '~^[\+\-]?(.*)$~', '$1', $term_id);

		if( !isset( $this->terms[$taxonomy] ) ) {
			$this->terms[$taxonomy] = array();
			$this->terms_hidden[$taxonomy] = array();
		}

		if( $add ) {
			unset( $this->terms_hidden[$taxonomy][$key] );
			$this->terms[$taxonomy][$key] = 1;
		} else {
			unset( $this->terms[$taxonomy][$key] );
			$this->terms_hidden[$taxonomy][$key] = 1;
		}

		return $this;
	}

	/**
	 * Add multiple terms at once
	 * 
	 * @param int[] $terms IDs of terms
	 * @return GF_Postmeta The instance of the current box
	 */
	public function set_terms(array $terms ) {
		foreach( $terms as $taxonomy => $term ) {
			if( is_array( $term) && taxonomy_exists( $taxonomy) ) {
				foreach( $term as $id ) {
					$this->add_term( $taxonomy, $id);
				}
			} elseif( is_array( $term) ) {
				$this->set_terms( $term);
			} else {
				gf_die( "<strong>GF_Postmeta</strong>: Taxonomy &quot;$taxonomy&quot; does not exist!" );
			}
		}

		return $this;
	}

	/**
	 * Output scripts like dependencies, etc.
	 */
	public function output_scripts( ) {
		$data = array(
			'levels'           => $this->levels,
			'levels_hidden'    => $this->levels_hidden,
			'templates'        => $this->templates,
			'templates_hidden' => $this->templates_hidden,
			'terms'            => $this->terms,
			'terms_hidden'     => $this->terms_hidden
		);

		?>
		<script type="text/javascript">
		if( typeof(GF_Postmeta) == 'undefined' ) {
			GF_Postmeta = {};
		}

		GF_Postmeta['<?php echo $this->id ?>'] = jQuery.parseJSON( '<?php echo json_encode( $data) ?>' );
		</script>
		<?php
	}

	/**
	 * Open a new tab. If one is already open, it will be closed.
	 * 
	 * @param string $id The identifier of the tab. Appears in the address
	 * @param string $title The title of the tab
	 * @param string $icon The icon that will appear next to the tabs's title
	 * @return GF_Options The instance of the page
	 */
	public function start_tab( $id, $title, $icon = null ) {
		if( $this->tab_open )
			$this->end_tab();

		if( ! $this->tabs_open ) {
			$this->fields[] = array(
				'item'      => 'tabs_start',
				'group' => (++$this->current_tab_group)
			);

			$this->tabs_open = true;
		}

		$this->fields[] = array(
			'item'  => 'tab_start',
			'id'    => $id
		);

		$this->tabs[] = array(
			'title' => $title,
			'icon'  => $icon,
			'id'    => $id,
			'group' => $this->current_tab_group
		);

		$this->tab_open = true;

		return $this;
	}

	/**
	 * CLoses the current tab.
	 * Only needed if after the last tab, there will be more fields
	 * 
	 * @return GF_Options The instance of the page
	 */
	public function end_tab( ) {
		if( ! $this->tab_open )
			return $this;

		$this->fields[] = array(
			'item' => 'tab_end'
		);

		$this->tab_open = false;

		return $this;
	}

	/**
	 * Add a whole tab with it's start, end and fields.
	 * 
	 * @param string $key The ID of the tab
	 * @param GF_Field[] $fields The fields that will appear in the tab
	 * @param string $icon The icon of the tab, an absolute URL
	 * @param string $title The title if needed. Otherwise the key will be used
	 * @return GF_Options The instance of the page
	 */
	public function tab( $key, array $fields, $icon=null, $title = null ) {
		if( ! $title ) {
			$title = ucwords( str_replace( '_', ' ', $key) );
		}

		$key = sanitize_title( $key);

		$this->start_tab( $key, $title, $icon);
		$this->add_fields( $fields);
		$this->end_tab();

		return $this;
	}

	/**
	 * Set the positioning of the tabs
	 * 
	 * @param string $align The align, either left or top
	 * @return GF_Options The instance of the page
	 */
	public function set_tabs_align( $align ) {
		$aligns = array( 'left', 'top' );

		if( !in_array( $align, $aligns) ) {
			gf_die( '<strong>GF_Options:</strong> Tabs align may only be ' . implode( ' or ', $aligns) );
		}

		$this->tabs_align = $align;
	}

	/**
	 * Displays a notice above the content
	 */
	public function display_notice() {
		static $displayed = false;

		if( $displayed ) {
			return;
		}

		$text = __( 'There are errors in your data. Please review the highlighted fields below!', 'gf' );
		echo '<div class="error" id="gf-postmeta-error" style="display:none"><span style="padding:5px; display:block;">' . $text . '</span></div>';

		$displayed = true;
	}
}