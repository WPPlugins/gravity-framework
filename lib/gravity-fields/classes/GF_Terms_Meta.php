<?php
/**
 * Creates a term meta container, which outputs fields on
 * term edit screens. For now, this is not available on the add screen
 */
class GF_Terms_Meta extends GF_Container_Base implements GF_Container {
	/** @type int Holds the ID of the associated term */
	protected $term_id;

	/** @type string[] Contains taxonomies which the container works with */
	protected $taxonomies = array();

	/** @type booelan Indicates if fields are set up */
	protected $set = false;

	/**
	 * Creates a container by setting attributes and adding actions.
	 * The third parameter accepts arguments that will be passed to setters
	 * ex. array( 'priority' => 10 ) will call ->set_priority( 10 )
	 * 
	 * @param string $title The title of the box. Used for ID
	 * @param string|string[] $taxonomy The taxonomy( s ) of the container
	 * @param mixed[] $args Arguments that are passed to setters.
	 */
	public function __construct( $title, $taxonomy, $args = null ) {
		# Process the title
		$this->set_title( $title );

		# Process the taxonomies
		if( is_array( $taxonomy ) ) {
			$this->taxonomies = $taxonomy;
		} else {
			$this->taxonomies = array( $taxonomy );
		}

		# Process args
		if( $args ) {
			if( is_array( $args ) ) {
				foreach( $args as $property => $value ) {
					if( method_exists( $this, 'set_' . $property ) ) {
						call_user_func( array( $this, 'set_' . $property ) , $value );
					} else {
						gf_die( '<strong>GF_Terms_Meta</strong>: ' . $property . ' is not a valid argument!' );
					}
				}
			} else {
				gf_die( '<strong>GF_Terms_Meta</strong>: Only arrays may be passed as options to the container!' );
			}
		}

		# Add hooks for each taxonomy
		foreach( $this->taxonomies as $taxonomy ) {
			# Show & save on edit
			add_action( $taxonomy . '_edit_form', array( $this, 'display' ) );
			add_action( 'edit_term', array( $this, 'save' ) );

			# Delete term stuff
			add_action( 'delete_term', array( $this, 'delete' ) );

		}

		# Add the taxonomy( s ) as an option to get_gf
		add_filter( 'gf_datastores', array( $this, 'register_datastore' ) );

		# Display error message
		add_action( 'admin_notices', array( $this, 'display_notice' ) );

		# Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * A factory-like method for creating containers
	 * 
	 * @param string $title The title of the container, will be visible as a heading.
	 * @param string|string[] $taxonomy The taxonomies for the container
	 * @param mixed[] $args Arguments that will be sent to setters
	 * @return GF_Terms_Meta The new instance
	 */
	public static function panel( $title, $taxonomy, array $args = null ) {
		return new GF_Terms_Meta( $title, $taxonomy, $args );
	}

	/**
	 * A proxy to panel, used for unifying all containers
	 * 
	 * @see panel()
	 */
	public static function factory( $title, $taxonomy, array $args = null ) {
		return self::panel( $title, $taxonomy, $args );
	}

	/**
	 * Add the taxonomy slug to the gf_datastores array to enable fetching of values there.
	 * 
	 * @param string[] $datastores The current datastores
	 * @return string[] The modified array
	 */
	function register_datastore( $datastores ) {
		foreach( $this->taxonomies as $taxonomy ) {
			$datastores[ $taxonomy ] = 'GF_Datastore_Termsmeta';
		}

		return $datastores;
	}

	/**
	 * Enqueues scripts in the admin
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'gravity-fields' );
		wp_enqueue_style( 'gravityfields-css' );
	}

	/**
	 * When the ID of the term is available, pass it to
	 * the datastore and spread through fields
	 */
	private function setup() {
		if( $this->set )
			return;

		if( !$this->id ) {
			gf_die( '<strong>GF_Terms_Meta</strong>: You need to set a title/ID for each page!' );
		}

		if( !$this->datastore ) {
			$this->datastore = new GF_Datastore_Termsmeta();
			$this->datastore->set_term( $this->term_id );
		}

		foreach( $this->fields as $field ) {
			if( is_a( $field, 'GF_Field' ) && $this->datastore->check_field_id( $field->get_id() ) ) {
				$field->set_datastore( $this->datastore, true );
			}
		}

		$this->set = true;
	}

	/**
	 * Add fields to the container
	 * 
	 * @param GF_Field[] $fields The fields that will appear in the container
	 * @return GF_Terms_Meta The instance of the container
	 */
	public function add_fields( array $fields ){
		foreach( $fields as $field ) {
			$this->add_field( $field );
		}

		return $this;
	}

	/**
	 * Adds a single file, ensuring that it's GF_Field
	 * 
	 * @param GF_Field $field The field itself
	 */
	private function add_field( GF_Field $field ) {
		$this->fields[] = $field;
	}

	/**
	 * Outputs the fields
	 *
	 * @param object $term_data The term object
	 */
	public function display( $term_data ) {
		global $gravityfields;
		
		# Save the ID of the term
		$this->term_id = $term_data->term_id;

		# Setup the datastore and all
		$this->setup();

		# Output field-to-field dependencies
		$this->output_dependencies();

		# Output items after the container
		do_action( 'gf_after_container' );

		# And the container itself
		include( $gravityfields->themes->path( 'term-edit' ) );
	}

	/**
	 * Checks if the term is from the right taxonomy.
	 * 
	 * @return boolean Indicates if the term is in the same taxonomy
	 */
	public function check_term() {
		$term_is_good = false;

		foreach( $this->taxonomies as $taxonomy ) {
			if( get_term( $this->term_id, $taxonomy ) ) {
				$term_is_good = true;
				break;
			}
		}

		return $term_is_good;
	}

	/**
	 * Save the values of the fields
	 * 
	 * @param object $term The term that's being saved
	 */
	public function save( $term ) {
		$this->term_id = $term;

		if( ! $this->check_term() )
			return;

		$this->setup();

		foreach( $this->fields as &$field ) {
			if( is_a( $field, 'GF_Field' ) )
				$field->save( $_POST );
		}
	}

	/**
	 * Detele a term's values.
	 * 
	 * @param $term_id Term
	 */
	public function delete( $term ) {
		delete_term_meta( $term );
	}

	/**
	 * Displays an validation error message thats hidden by default.
	 */
	public function display_notice() {
		# Only display the message once
		static $displayed = false;

		if( $displayed ) {
			return;
		}

		$text = __( 'There are errors in your data. Please review the highlighted fields below!', 'gf' );
		echo '<div class="error" id="gf-termsmeta-error" style="display:none"><span style="padding:5px; display:block;">' . $text . '</span></div>';

		$displayed = true;
	}
}