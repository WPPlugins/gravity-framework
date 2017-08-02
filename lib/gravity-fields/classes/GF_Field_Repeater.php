<?php
class GF_Field_Repeater extends GF_Field implements GF_Datastore, GF_Container {
	protected $field_groups     = array(),
			  $fields           = array(),
			  $i_placeholder    = '__gfi__',
			  $value            = array(),
			  $current_data_row = -1,
			  $current_data_type,
			  $rows_limit = -1;

	public function after_constructor() {
		$this->i_placeholder = 'placeholder_' . substr( md5( $this->input_id ), 0, 10 );

		$this->html_attributes = array(
			 'data-placeholder' => $this->i_placeholder,
			 'data-limit'       => -1
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
	}

	public function set_datastore(GF_Datastore $datastore, $optional = false) {
		if( ($optional && !$this->datastore) || !$optional ) {
			$this->datastore = $datastore;

			$this->value = $this->datastore->get_multiple($this->id);
			
			$i = 0;
			foreach($this->field_groups as $group) {
				foreach($group['fields'] as $field) {
					$field->set_datastore($this, true);
				}

				$i++;
			}

			if( !is_array($this->value) ) {
				return $this;
			}

			if(!is_a($this->datastore, 'GF_Field'))
				$this->set_value($this->value);
		}

		return $this;
	}

	function set_value($value) {
		foreach($value as $i => $data) {
			if( !isset($data['type']) || !isset($this->field_groups[$data['type']]) )
				continue;

			# Compatibility with the old version, saving array( 'type' => 'type', 'values' => array() )
			if( count( $data ) == 2 && isset( $data['values'] ) ) {
				$data = array_merge( $data[ 'values' ], array( 'type' => $data[ 'type' ] ) );
			}

			$base_row = $this->field_groups[ $data['type'] ];
			$row = array(
				'type'   => $data['type'],
				'title'  => $base_row['title'],
				'fields' => array()
			);

			$group_keys = array('group_name', 'min_width', 'max_width', 'title_field');
			foreach($group_keys as $key) {
				if(isset($base_row[$key])) {
					$row[$key] = $base_row[$key];
				}
			}

			if(method_exists($this, 'prepare_row')) {
				$this->prepare_row($row, $base_row, $data);
			}

			foreach($base_row['fields'] as $field) {
				$field = clone $field;

				if(isset($data[ $field->get_id() ])) {
					$field->set_value($data[ $field->get_id() ]);
				}
				$field->set_input_id("$this->input_id[$i][" . $field->get_id() . "]");

				$row['fields'][] = clone $field;
			}

			$this->fields[] = $row;
		}


	}

	function set_input_id($id) {
		$this->input_id = $id;

		$i = 0;
		foreach($this->fields as $row) {
			foreach($row['fields'] as $field) {
				$field->set_input_id("$this->input_id[$i][" . $field->get_id() . "]");
			}

			$i++;
		}
	}

	function save_value($key, $value) {
		$this->value[$this->current_data_row][$key] = $value;
	}

	function get_value($key) {
		if( $this->current_data_row != -1 && $this->value && isset($this->value[$this->current_data_row][$key]) ) {
			return $this->value[$this->current_data_row][$key];
		} else {
			return false;
		}
	}

	function get_multiple($key) {
		return $this->get_value($key);
	}

	protected function next_data_row($src_row) {
		$row = array(
			'type'   => $src_row['__type']
		);

		$this->value[++$this->current_data_row] = $row;

		if(method_exists($this, 'add_row_data')) {
			$this->add_row_data($src_row, $this->value[$this->current_data_row]);
		}
	}

	function save($src) {
		if( !isset($src[$this->id]) || !is_array($src[$this->id]) ) {
			return;
		}

		$this->value = array();
		$this->current_data_row = -1;

		foreach($src[$this->id] as $i => $row) {
			if( ($i . '') == $this->i_placeholder || strpos( $i, 'placeholder_' ) === 0 ) {
				continue;
			}

			if( !isset($row['__type']) || !isset($this->field_groups[ $row['__type'] ]) ) {
				gf_die( '<strong>GF_Field_Repeater</strong>: Malformed data!<br>' . serialize($src[$this->input_id]) );
			}

			$this->next_data_row($row);

			$group = $this->field_groups[ $row['__type'] ];
			foreach($group['fields'] as $field) {
				$field->set_input_id("$this->input_id[$i][" . $field->get_id() . "]");
				$field->save($row);
			}
		}

		$this->datastore->save_multiple($this->id, $this->value);
	}


	function save_multiple($key, $values = array()) {
		return $this->save_value($key, $values);
	}

	function delete_value($key) {
		unset($this->value[$key]);
	}

	protected function add_fields_group($atts, array $fields) {
		$group_key   = sanitize_title($atts['group_key']);
		
		$field_group = array(
			'title'       => $atts[ 'group_title' ],
			'fields'      => array(),
			'type'        => $group_key,
			'title_field' => isset( $atts['title_field'] ) ? $atts['title_field'] : '',
			'description' => $atts[ 'group_description' ],
			'icon'        => $atts[ 'group_icon' ]
		);

		$field_keys = array();

		foreach($fields as $field) {
			$key = $field->get_id();
			
			if( isset( $field_keys[ $key ] ) ) {
				gf_die( sprintf( __( 'Error: Trying to register a field with the %s key twice in a repeater group!', 'gf' ), $key ) );
			}

			if( $key == 'type' ) {
				gf_die( __( '&quot;type&quot; is a reserved key in repeaters and cannot be overwritten!', 'gf' ) );
			}

			if( !is_a($field, 'GF_Field') ) {
				gf_die('<strong>GF_Field_Repeater</strong> only supports fields of type GF_Field!');
			}

			$field_group['fields'][] = $field;
			$field_keys[ $field->get_id() ] = 1;
		}

		$this->field_groups[$group_key] = $field_group;		
	}

	public function add_fields( $key, $data, $fields = null ){
		$atts = array(
			'group_key'   => $key,
			'group_title' => ucwords( str_replace( '_', ' ', $key) ),
			'group_description' => '',
			'group_icon'        => ''
		);

		if( ! $fields && isset( $data[0] ) && is_a( $data[0], 'GF_Field' ) ) {
			# If the third argument isn's set, the fields are in the second one
			$fields = $data;
		} else {
			# If the third argument is set, it means that the second one contains atts or title
			if( is_array( $data ) ) {
				if( isset( $data['title'] ) ) {
					$atts[ 'group_title' ] = $data['title'];
				}

				if( isset( $data['description' ] ) ) {
					$atts[ 'group_description' ] = $data[ 'description' ];
				}

				if( isset( $data[ 'icon' ] ) ) {
					$atts[ 'group_icon' ] = $data[ 'icon' ];
				}

				if( isset( $data[ 'title_field' ] ) ) {
					$atts[ 'title_field' ] = $data[ 'title_field' ];
				}
			} else {
				# Only a title is passed
				$atts[ 'group_title' ] = $data;
			}
		}

		if($key == '') {
			$atts['group_key'] = 0;
			$atts['group_title'] = '';
		}

		if( ! isset( $atts[ 'title_field' ] ) ) {
			foreach( $fields as $field ) {
				if( is_a( $field, 'GF_Field' ) ) {
					$atts[ 'title_field'] = $field->get_id();
					break;					
				}
			}
		}

		$this->add_fields_group( $atts, $fields );

		return $this;
	}

	function display_input() {
		global $gravityfields;
		
		include( $gravityfields->themes->path( 'repeater' ) );
	}

	function display_prototype(GF_Field $field) {
		$old_input_id = $field->get_id();
		$field->set_input_id($this->input_id . '[' . $this->i_placeholder . '][' . $old_input_id . ']');
		$field->reset();
		$field->display( 'repeater' );
	}

	public function get_inner_dependencies() {
		$deps = array();

		foreach( $this->field_groups as $group_key => $group ) {
			foreach( $group['fields'] as $field ) {
				$field_deps = $field->get_dependencies();

				if( ! empty( $field_deps ) ) {
					$deps[ $group_key ][ $field->get_id() ] = $field_deps;
				}

				if( method_exists( $field, 'get_inner_dependencies' ) ) {
					$inner = $field->get_inner_dependencies();
					if( count( $inner ) ) {
						$deps[ $group_key ][  $field->get_id() . '__inner' ] = $inner;
					}
				}
			}
		}

		return $deps;
	}

	public function limit_rows( $limit ) {
		$this->rows_limit = $limit;
		$this->html_attributes[ 'data-limit' ] = $limit;

		return $this;
	}
}