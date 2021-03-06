<?php
/**
 * This class contains common functions for most containers.
 * It's used in clases which can inherit it - mostly all of them,
 * except widgets.
 */
class GF_Container_Base {
	/** @type GF_Field[] The fields that are added to the container */
	protected $fields = array();

	/** @type string The identifier of the container */
	protected $id;

	/** @type string The title of the container as it appears in the menu and on the page */
	protected $title;

	/** @type string The description of the container, which appears on it's page */
	protected $description;

	/** @type GF_Datastore The datastore that the page is working with */
	protected $datastore;
	
	/**
	 * Outputs the dependencies of the options page
	 * 
	 * @param boolean $output Output the dependencies or return an array
	 */
	public function output_dependencies( $output = true ) {
		$deps = array();

		foreach( $this->fields as $field ) {
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

		$deps = apply_filters( strtolower( get_class( $this ) ) . '_dependencies', apply_filters( 'gf_dependencies', $deps, $this ), $this );

		if( empty( $deps ) ) {
			return array();
		}

		if( $output ) { ?>
			<script type="text/javascript">
			if( typeof(GF_Dependencies) == 'undefined' ) {
				GF_Dependencies = {};
			}

			GF_Dependencies[ '<?php echo $this->id ?>' ] = jQuery.parseJSON('<?php echo json_encode($deps) ?>');
			</script>
		<?php } else {
			return $deps;
		}
	}

	/**
	 * Sets a title to the container
	 * 
	 * @param string $title The title of the container
	 * @return GF_Container_Base The instance of the container
	 */
	public function set_title( $title ){
		$title = apply_filters( 'gf_container_title', $title, $this );

		$this->title = $title;

		if( ! $this->id )
			$this->id = sanitize_title( $title );

		return $this;
	}

	/**
	 * Get the current title of the container
	 * 
	 * @return string The title of the container
	 */
	public function get_title(){
		return $this->title;
	}

	/**
	 * Sets an ID to the container
	 * 
	 * @param string $id The ID
	 * @return GF_Container_Base The instance of the container
	 */
	public function set_id( $id ){
		$id = apply_filters( 'gf_container_id', $id, $this );
		$this->id = sanitize_title( $id );

		return $this;
	}

	/**
	 * Retrieve the ID of the container
	 * 
	 * @return string The current ID
	 */
	public function get_id(){
		return $this->id;
	}

	/**
	 * Set a description to the container
	 * 
	 * @param string $description The new description
	 * @return GF_Container_Base The instance of the container
	 */
	public function set_description( $description ){
		$this->description = apply_filters( 'gf_container_description', $description, $this );

		return $this;
	}

	/**
	 * Get the current description of the container
	 * 
	 * @return string The current description
	 */
	public function get_description(){
		return $this->description;
	}

	/**
	 * Set a specific datastore to the container
	 * 
	 * @param GF_Datastore $datastore The datastore that is to be set
	 * @return GF_Container_Base The instance of the container
	 */
	public function set_datastore(GF_Datastore $datastore) {
		$this->datastore = apply_filters( 'gf_container_datastore', $datastore, $this );
		
		return $this;
	}

	/**
	 * Made for bulk fields adding
	 * 
	 * @param mixed[] $items The items that are stored in the dabase or loaded dynamically
	 */
	public function add_fields_array( array $items ) {
		$tabIndex = 0;

		$items = apply_filters( 'gf_add_fields_array', $items );

		foreach( $items as $item ) {
			if( is_a( $item, 'GF_Field' ) ) {
				$this->add_field( $item );
			} else {
				if( $item[ 'type' ] == 'tab_start' ) {
					$this->start_tab( 'tab-' . ($tabIndex++), GF_ML::split( $item['title'] ), $item['icon'] );
				} else {
					$this->end_tab();
				}
			}
		}
	}
}