<?php
/**
 * Only returns the items that are a GF_Field
 * 
 * @param mixed $field The argument to be chebked
 * @return boolean if it's a field
 */
function gf_leave_fields_only( $field ) {
	return is_a( $field, 'GF_Field' );
}

/**
 * Convert an array to a working container
 */
function gf_setup_container( $data ) {
	$args = array(
		'title'       => '',
		'description' => ''
	);
	$data = wp_parse_args( $data, $args );

	$p = new stdClass();
	$p->post_title = $data[ 'title' ];
	$p->post_content = $data[ 'description' ];

	$container = array(
		'post' => $p,
		'meta' => $data
	);

	gf_setup_containers( $container );
}

/**
 * Get an array of all registered containers
 */
function gf_setup_containers( $data = null ) {
	static $added_containers;

	if( ! isset( $added_containers ) ) {
		$added_containers = array();
	}

	$containers = $data ? array( $data ) : get_option( 'gf_containers' );

	if( ! $containers || ! is_array( $containers ) ) {
		return;
	}

	# Prevent duplicate ID exits.
	GF_Exceptions::buffer( 'unavailable_field_key' );
	GF_Exceptions::buffer( 'unavailable_container_key' );

	foreach( $containers as $container ) {
		extract( $container );

		if( isset( $added_containers[ $post->post_title ] ) ) {
			continue;
		}

		# Setup options pages
		if( isset( $meta[ 'gf_options_page' ] ) && $meta[ 'gf_options_page' ] ) {
			gf_setup_options_page( gf_setup_fields( $meta['fields'], 'GF_Datastore_Options' ), $container );
		}

		# Setup postmeta
		if( isset( $meta[ 'gf_postmeta_box' ] ) && $meta[ 'gf_postmeta_box' ] ) {
			gf_setup_postmeta_box( gf_setup_fields( $meta['fields'], 'GF_Datastore_Postmeta' ), $container );
		}

		# Setup terms meta
		if( isset( $meta[ 'gf_termsmeta' ] ) && $meta[ 'gf_termsmeta' ] ) {
			gf_setup_termsmeta( gf_setup_fields( $meta['fields'], 'GF_Datastore_Termsmeta' ), $container );
		}

		# Setup user meta
		if( isset( $meta[ 'gf_usermeta' ] ) && $meta[ 'gf_usermeta' ] ) {
			gf_setup_usermeta( gf_setup_fields( $meta['fields'], 'GF_Datastore_Usermeta' ), $container );
		}

		# Setup widgets
		if( isset( $meta[ 'gf_widget' ] ) && $meta[ 'gf_widget' ] ) {
			gf_setup_widget( gf_setup_fields( $meta['fields'], 'GF_Widget' ), $container );
		}

		$added_containers[ $post->post_title ] = 1;
	}

	// GF_Exceptions::stop_buffering( 'unavailable_field_key' );
}

/**
 * Parse args by removing certain prefixes
 *
 * @param mixed[] $data
 * @param string[] $keys The required keys
 * @param string $prefix
 * @return mixed[] Parsed data
 */
function gf_parse_args_array( $source, $keys, $prefix ) {
	$data = array();

	foreach( $keys as $key ) {
		$full_key = $prefix ? $prefix . $key : $key;

		if( isset( $source[ $full_key ] ) ) {
			$data[ $key ] = $source[ $full_key ];
		} else {
			$data[ $key ] = '';
		}
	}

	return $data;
}

/**
 * Setup an options page
 */
function gf_setup_options_page( $fields, $data ) {
	# An array of settings that will be passed to the page
	$args = array();

	# Extract what we actually care about
	$meta = extract( gf_parse_args_array( $data[ 'meta' ], array( 'page_type', 'parent_page', 'page_parent_slug', 'page_slug', 'icon', 'menu_position' ), 'gf_options_' ) );

	# Prepare the title
	$title = GF_ML::split( $data['post']->post_title );
	$args[ 'title' ] = $title;

	# Prepare the description
	if( $description = GF_ML::split( $data[ 'post' ]->post_content ) ) {
		$args[ 'description' ] = $description;
	}

	# If there's a slug set, it will be used
	if( $page_slug )
		$args[ 'id' ] = $page_slug;

	if( $page_type == 'menu' ) {
		# Main page, it's default but might have extra options
		if( $menu_position )
			$args[ 'position' ] = $menu_position;

		# Icon
		if( $icon )
			$args[ 'icon' ] = wp_get_attachment_url( $icon );
	} elseif( $page_type == 'other_page' ) {
		$args[ 'parent' ] = $page_parent_slug;
	} elseif( $page_type == 'other_gf_page' ) {
		if( isset( $parent_page ) && $page = get_post( $parent_page ) ) {
			$args[ 'parent' ] = ( $custom = get_post_meta( $page->ID, 'gf_options_page_slug', true ) ) ? $custom : sanitize_title( $page->post_title );	
		}
	} else {
		$args[ 'type' ] = $page_type;
	}

	$page = GF_Options::page( $title, $args );

	# Add fields
	$page->add_fields_array( $fields );
}

/**
 * Setup post meta container
 * 
 * @param GF_Field[] $fields
 * @param mixed[] $data
 */
function gf_setup_postmeta_box( $fields, $data ) {
	$args = array();

	# Extract what we actually care about
	extract( gf_parse_args_array( $data[ 'meta' ], array( 'posttype', 'templates', 'levels' ), 'gf_postmeta_' ) );

	# Don't do anything in some cases
	if( !isset( $posttype) || ! is_array( $posttype ) || empty( $posttype ) ) {
		return;
	}
	
	# Prepare the title
	$title = GF_ML::split( $data['post']->post_title );
	$args[ 'title' ] = $title;

	# If there's a slug set, it will be used
	if( $data[ 'meta' ][ 'gf_options_page_slug' ] )
		$args[ 'id' ] = $data[ 'meta' ][ 'gf_options_page_slug' ];

	# Create the page
	$container = GF_Postmeta::box( $title, $posttype, $args );

	# Prepare the description
	if( $description = GF_ML::split( $data[ 'post' ]->post_content ) ) {
		$container->set_description( $description );
	}

	# Choose templates if set
	if( in_array( 'page', $posttype ) && isset( $templates ) && is_array( $templates ) && $templates ) {
		$container->set_templates( $templates );
	}

	# Add level
	if( $levels ) {
		$container->set_levels( $levels );
	}

	# Set taxonomy info
	$taxonomies = get_taxonomies( array( 'show_ui' => 1 ), 'objects' );
	foreach( $taxonomies as $id => $taxonomy ) {
		# Only hierarchical taxonomies have checkboxes
		if( ! $taxonomy->hierarchical ) {
			continue;
		}

		if( isset( $data[ 'meta' ][ "gf_postmeta_terms_{$id}" ] ) && is_array( $data[ 'meta' ][ "gf_postmeta_terms_{$id}" ] ) ) {
			$terms = $data[ 'meta' ][ "gf_postmeta_terms_{$id}" ];

			if( ! empty( $terms ) ) {
				foreach( $terms as $term ) {
					$container->add_term( $id, $term );
				}
			}
		}
	}

	# Add the fields
	$container->add_fields_array( $fields );
}

/**
 * Setup a terms meta container
 * 
 * @param GF_Field[] $fields
 * @param mixed[] $data
 */
function gf_setup_termsmeta( $fields, $data ) {
	$args = array();

	$taxonomies = isset( $data[ 'meta' ][ 'gf_termsmeta_taxonomies' ] ) ? $data[ 'meta' ][ 'gf_termsmeta_taxonomies' ] : array();

	# Prepare the title
	$title = GF_ML::split( $data['post']->post_title );
	$args[ 'title' ] = $title;

	# If there's a slug set, it will be used
	if( $data[ 'meta' ][ 'gf_options_page_slug' ] )
		$args[ 'id' ] = $data[ 'meta' ][ 'gf_options_page_slug' ];

	$container = GF_Terms_Meta::panel( $title, $taxonomies, $args );

	# Prepare the description
	if( $description = GF_ML::split( $data[ 'post' ]->post_content ) ) {
		$container->set_description( $description );
	}

	# This container doesn't support tabs, remove them
	$fields = array_filter( $fields, 'gf_leave_fields_only' );

	# Add fields to the container
	$container->add_fields( $fields );
}

/**
 * Setup a usermeta container
 *
 * @param GF_Field[] $fields
 * @param mixed[] $data
 */
function gf_setup_usermeta( $fields, $data ) {
	$args = array();

	# Prepare the title
	$title = GF_ML::split( $data['post']->post_title );
	$args[ 'title' ] = $title;

	# If there's a slug set, it will be used
	if( $data[ 'meta' ][ 'gf_options_page_slug' ] )
		$args[ 'id' ] = $data[ 'meta' ][ 'gf_options_page_slug' ];

	$container = GF_USermeta::factory( $title, $args );

	# Prepare the description
	if( $description = GF_ML::split( $data[ 'post' ]->post_content ) ) {
		$container->set_description( $description );
	}

	# This container doesn't support tabs, remove them
	$fields = array_filter( $fields, 'gf_leave_fields_only' );

	# Add fields to the container
	$container->add_fields( $fields );
}

/**
 * A parent widget for dynamic widgets
 */
class GF_Dynamic_Widget extends GF_Widget {
	protected $widget_data;

	public function __construct() {
		global $gf_dynamic_widgets;

		# Get the info for the widget
		$data = $gf_dynamic_widgets[ get_class( $this ) ];

		$this->widget_data = $data;

		# This container doesn't support tabs, remove them
		$data[ 'fields' ] = array_filter( $data[ 'fields' ], 'gf_leave_fields_only' );

		# Add fields
		$this->add_fields( $data[ 'fields' ] );

		# Set a title
		$title = GF_ML::split( $data[ 'data' ][ 'post' ]->post_title );
		$this->set_title( $title );

		# Prepare the description
		if( $description = GF_ML::split( $data[ 'data' ][ 'post' ]->post_content ) ) {
			$this->set_description( $description );
		}

		if( $gf_widget_css_class = $data[ 'data' ][ 'meta' ][ 'gf_widget_css_class' ] ){
			$this->add_css_class( $gf_widget_css_class );
		}

		if( $gf_widget_width = $data[ 'data' ][ 'meta' ][ 'gf_widget_width' ] ){
			$this->set_width( $gf_widget_width );
		}

		parent::__construct();
	}

	protected function display( $args, $instance ) {
		$source   = $this->widget_data[ 'data' ][ 'meta' ][ 'gf_widget_source' ];
		$code     = $this->widget_data[ 'data' ][ 'meta' ][ 'gf_widget_code' ];
		$callback = $this->widget_data[ 'data' ][ 'meta' ][ 'gf_widget_callback' ];

		switch( $source ) {
			case 'inline':
				$data = array_merge( $args, $instance );
				foreach( $data as $key => $value ) {
					$code = str_replace( "%$key%", $value, $code );
				}
				$code = do_shortcode( $code );
				echo $code;
				break;

			case 'callback':
				call_user_func_array( $callback, array( $args, $instance ) );
				break;

			default:
				echo __( 'This widget has no source selected. Please edit it&apos;container and set a template source.', 'gf' );
		}
	}
}

/**
 * Setup a dynamic widget container
 * 
 * @param GF_Field[] $fields
 * @param mixed[] $data
 */
function gf_setup_widget( $fields, $data ) {
	global $gf_dynamic_widgets;

	if( ! isset( $gf_dynamic_widgets ) ) {
		$gf_dynamic_widgets = array();
	}

	# Prepare the title
	$title = GF_ML::split( $data['post']->post_title );

	# If there's a slug set, it will be used
	if( $data[ 'meta' ][ 'gf_options_page_slug' ] )
		$id = $data[ 'meta' ][ 'gf_options_page_slug' ];
	else
		$id = $title;

	# Prepare a classname for the widget
	$classname = 'GF_Dynamic_Widget_' . preg_replace( '~[^a-z]~i', '_', ucwords( $id ) );

	$gf_dynamic_widgets[ $classname ] = array(
		'fields' => $fields,
		'data'   => $data,
		'id'     => $classname
	);

	# Create a widget
	if( class_exists( $classname ) ) {
		GF_Notices::add( sprintf( __( 'You are trying to register a widget with class %s twice! Please check if you have duplicate containers and remove one of them.', 'gf' ), $classname ), true );
	} else {
		eval( "class $classname extends GF_Dynamic_Widget {}" );
		register_widget( $classname );
	}
}

# Do it
gf_setup_containers();