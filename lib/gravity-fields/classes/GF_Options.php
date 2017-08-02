<?php
/**
 * Creates an options page, which has it's own display.
 * As the name suggests, by default this container works with
 * the WordPress options API
 */
class GF_Options extends GF_Container_Base implements GF_Container {
	/** @type string The capability that's required for the page */
	protected $required_capability = 'manage_options';

	/** @type string An absolute URL for the icon in the menu */
	protected $icon;

	/** @type string The ID of the icon as it appears on the page */
	protected $icon_id = 'themes';

	/** @type int The position in the main menu */
	protected $menu_position = null;

	/** @type string The slug of the parent page. Overrides $type */
	protected $parent_slug;

	/** @type string Indicates where should the page be located - menu/appearance/subpage, etc. */
	protected $type = 'menu';

	/** @type boolean Indicates if the container has already set it's fields up */
	protected $set = false;

	/** @type boolean Indicates if there is a tab that's current open */
	protected $tab_open = false;

	/** @type boolean Indicates if there is a tabs group that's current open */
	protected $tabs_open = false;

	/** @type mixed[] Holds tabs that should be shown on top of the page */
	protected $tabs = array();

	/** @type int The ID of the next tabs group */
	protected $current_tab_group = -1;

	/** @type string Sets the location of the tabs navigation. top/left */
	protected $tabs_align = "top";

	/**
	 * Creates a page by setting attributes and adding actions.
	 * The second parameter accepts arguments that will be passed to setters
	 * ex. array( 'type' => 'appearance' ) will call ->set_type( 'appearance' )
	 * 
	 * @param string $title The title of the page. Used for ID
	 * @param mixed[] $args Arguments that are passed to setters.
	 */
	function __construct( $title, $args = null ) {
		# Process title
		$this->set_title( $title );

		# Set default values before additional setters
		$this->icon = GF_URL . 'templates/css/images/icon_settings.png';

		# Process args. They can be in the format set_{$key} => $value
		# and the appropriate setter will be called.
		if( $args ) {
			if( is_array( $args ) ) {
				$args = apply_filters( 'gf_options_args', $args );

				foreach( $args as $property => $value ) {
					if( method_exists( $this, 'set_' . $property ) ) {
						call_user_func( array( $this, 'set_' . $property ) , $value );
					} else {
						gf_die( '<strong>GF_Options</strong>: ' . $property . ' is not a valid argument!' );
					}
				}
			} else {
				gf_die( '<strong>GF_Options</strong>: Only arrays may be passed as options to the container!' );
			}
		}

		# Add hooks which link the page and save values
		add_action( 'admin_menu', array( $this, 'attach_to_menu' ) );
		add_action( 'gf_save',   array( $this, 'save' ) );

		# Enqueue required scripts and styles in admin
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		do_action( 'gf_options_created', $this );
	}

	/**
	 * Enqueues scripts for the page in the admin
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'gravity-fields' );
		wp_enqueue_style( 'gravityfields-css' );
	}

	/**
	 * Factory method for creating pages.
	 * 
	 * @param string $title The title of the page
	 * @param mixed[] $args The arguments that will be passed to setters
	 * @return GF_Options The handle for the options page
	 */
	public static function page( $title, array $args = null) {
		return new GF_Options( $title, $args);
	}

	/**
	 * A proxy fo the page method above
	 */
	public static function factory( $title, array $args = null ) {
		return self::page( $title, $args );
	}

	/**
	 * Connects fields to the datastore, processes tabs and adds notices.
	 */
	private function setup() {
		if( $this->set )
			return;

		if( ! $this->id ) {
			gf_die( '<strong>GF_Options</strong>: You need to set a title/ID for each page!' );
		}

		if( ! $this->datastore ) {
			$this->datastore = apply_filters( 'gf_options_datastore', new GF_Datastore_Options() );
		}

		foreach( $this->fields as $field ) {
			if( is_a( $field, 'GF_Field' ) && $this->datastore->check_field_id( $field->get_id() ) ) {
				$field->set_datastore( $this->datastore, true );
			}
		}

		$this->end_tab();

		if( $this->tabs_open && ! $this->tab_open ) {
			$this->fields[] = array(
				'item' => 'tabs_end'
			);
		}

		$this->set = true;

		if( isset( $_GET['success'] ) ) {
			GF_Notices::add( __( 'Your changes were succesgfully saved!', 'gf' ) );
		}
	}

	/**
	 * Generates a nonce field and outputs it.
	 */
	private function nonce() {
		$key = wp_create_nonce( $this->id );
		echo '<input type="hidden" name="_options_nonce" value="' . $key . '" />';
	}

	/**
	 * Checks if the nonce field is okay
	 */
	private function check_nonce() {
		if( ! isset( $_POST['_options_nonce'] ) )
			return false;

		return wp_verify_nonce( $_POST['_options_nonce'], $this->id );
	}

	/**
	 * Iterates through all fields and provides them $_POST to save.
	 * In the end redirects the user to the right URL + success parameter.
	 */
	function save() {
		$this->setup();

		if( $this->check_nonce() ) {
			foreach( $this->fields as $field) {
				if( is_a( $field, 'GF_Field' ) )
					$field->save( $_POST );
			}

			wp_redirect( 'admin.php?page=' . $this->id . '&success=true' );
			exit;
		}
	}

	/**
	 * Attaches the page to the menu based on multiple conditions
	 */
	function attach_to_menu() {
		$this->setup();

		# Params for all functions, since they're all the same
		$page_title = $this->title;
		$menu_title = $this->title;
		$capability = $this->required_capability;
		$menu_slug  = $this->id;
		$function   = array( $this, 'display' );

		# Icon and menu position are only available for top-level items
		$icon_url   = $this->icon;
		$position   = $this->menu_position;

		# Do one thing for a regular page
		if( ! $this->parent_slug ) {
			# Call the needed function depending on the type
			switch( $this->type ) {
				case 'tools':
					add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function);
					break;
				case 'settings':
					add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
					break;
				case 'appearance':
					add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $function);
					break;
				default:
				case 'menu':
					add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
					break;
			}
		} else {
			# And something else for a sub-page
			add_submenu_page( $this->parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		}
	}

	/**
	 * Adds fields to the container
	 * 
	 * @param GF_Field[] $fields The field to be added
	 * @return GF_Options The current instance of the class
	 */
	public function add_fields( array $fields ){
		$fields = apply_filters( 'gf_add_fields', apply_filters( 'gf_options_add_fields', $fields ) );

		if( $this->tabs_open && ! $this->tab_open ) {
			$this->fields[] = array(
				'item' => 'tabs_end'
			);

			$this->tabs_open = false;
		}

		foreach( $fields as $field) {
			$this->add_field( $field);
		}

		return $this;
	}

	/**
	 * Adds a single field to the container
	 * 
	 * @param GF_Field $field The field that will be pushed 
	 */
	public function add_field( GF_Field $field ) {
		$this->fields[] = apply_filters( 'gf_add_field', $field );
	}

	/**
	 * Displays the container
	 */
	public function display() {
		global $gravityfields;
		
		if( $this->tab_open || $this->tabs_open ) {
			$this->end_tab();
		}

		include( $gravityfields->themes->path( 'options-page' ) );

		do_action( 'gf_after_container' );

		$this->output_dependencies();
	}

	/**
	 * Sets an icon that will appear in the menu
	 * 
	 * @param string $icon An absolute URL to the icon
	 * @return GF_Options The page instance
	 */
	public function set_icon( $icon ) {
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Sets the type of the page, a.k.a. it's parent in the menu.
	 * The available types can be seen in the first array below.
	 * 
	 * @param string $type One of the types below
	 * @return GF_Options The instance of the page
	 */
	public function set_type( $type ) {
		$available = array(
			'settings',    # In the settings tab
			'appearance',  # In the Appearance tab
			'menu',        # Directly in the menu
			'tools'        # In the tools tab
		);

		if( ! in_array( $type, $available ) ) {
			gf_die( '<strong>GF_Options</strong>: ' . $type . ' is not a valid Options page type!' );
		}

		$this->type = $type;

		return $this;
	}

	/**
	 * Get the type of the page
	 * 
	 * @return string The type
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * If the page is located in the main menu, set it's position
	 * 
	 * @param int $position The position according to the codex
	 * @return GF_Options The instance of the page
	 * @see http://codex.wordpress.org/Function_Reference/add_menu_page
	 */
	public function set_position( $position ) {
		$this->menu_position = intval( $position );
		return $this;
	}

	/**
	 * Set a parent page by either it's slug or it's instance.
	 * Only applies to items that are located directly in the main menu
	 * 
	 * @param string|GF_Options $parent The parent page or it's slug
	 * @return GF_Options The instance of the page
	 */
	public function set_parent( $parent ) {
		if( is_a( $parent, 'GF_Options' ) ) {
			$this->parent_slug = $parent->get_id();
		} else {
			$this->parent_slug = $parent;
		}

		return $this;
	}

	/**
	 * Controls the capability that's required in order for the page to be visible.
	 * 
	 * @param string $capability
	 * @return GF_Options The inance of the class
	 */
	public function set_capability( $capability ) {
		$this->required_capability = $capability;
		return $this;
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
				'item'  => 'tabs_start',
				'group' => ( ++$this->current_tab_group )
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
	public function end_tab() {
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

		$this->start_tab( $key, $title, $icon );
		$this->add_fields( $fields );
		$this->end_tab();

		return $this;
	}

	/**
	 * Set the positioning of the tabs
	 * 
	 * @param string $align The align, either left or top
	 * @return GF_Options The instance of the page
	 */
	public function set_tabs_align( $align) {
		$aligns = array( 'left', 'top' );

		if( !in_array( $align, $aligns) ) {
			gf_die( '<strong>GF_Options:</strong> Tabs align may only be ' . implode( ' or ', $aligns ) );
		}

		$this->tabs_align = $align;

		return $this;
	}

	/**
	 * Sets an ID for the icon that will appear on the page itself.
	 * This id might be used to style the icon with CSS
	 *
	 * @param string $id
	 * @return GF_Options The instance of the page
	 */
	public function set_icon_id( $id ) {
		$this->icon_id = $id;
		return $this;
	}

	/**
	 * Get the id of the current icon
	 * 
	 * @return string
	 */
	public function get_icon_id() {
		return $this->icon_id();
	}
}