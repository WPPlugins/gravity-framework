<?php
# Add the post type
register_post_type( 'gravityfields', array(
	'public'             => false,
	'publicly_queryable' => false,
	'show_ui'            => true, 
	'show_in_menu'       => true, 
	'query_var'          => false,
	'rewrite'            => false,
	'capability_type'    => 'post',
	'has_archive'        => true, 
	'hierarchical'       => false,
	'menu_position'      => 90,
	'supports'           => array( 'slug' ),
	'menu_icon'          => 'dashicons-editor-kitchensink',
	'labels'             => array(
		'name'               => __( 'Gravity Fields', 'gf' ),
		'singular_name'      => __( 'Container', 'gf' ),
		'add_new'            => __( 'Add New', 'gf' ),
		'add_new_item'       => __( 'Add New Container', 'gf' ),
		'edit_item'          => __( 'Edit Container', 'gf' ),
		'new_item'           => __( 'New Container', 'gf' ),
		'all_items'          => __( 'All Containers', 'gf' ),
		'view_item'          => __( 'View Container', 'gf' ),
		'search_items'       => __( 'Search Containers', 'gf' ),
		'not_found'          => __( 'No Containers found', 'gf' ),
		'not_found_in_trash' => __( 'No Containers found in Trash', 'gf' ),
		'parent_item_colon'  => __( '', 'gf' ),
		'menu_name'          => __( 'Gravity Fields', 'gf' )
	)
) );

# Disable bulk actions
// add_filter( 'bulk_actions-' . 'edit-gravityfields', '__return_empty_array' );

# The default title and editor are disabled, because the editor is too big
# and the title depends on it when qTranslate is on
add_action( 'save_post', 'gf_save_gravityfields', 12 );
function gf_save_gravityfields( $post_id ) {
	global $wpdb;

	if( get_post_type( $post_id ) != 'gravityfields' ) {
		return;
	}
	
	$title   = get_post_meta( $post_id, 'gf_title', true );
	$content = get_post_meta( $post_id, 'gf_description', true );

	# Prevent recursion
	remove_action( 'save_post', 'gf_save_gravityfields', 12 );

	# Update the post
	wp_update_post( array(
		'ID'           => $post_id,
		'post_title'   => $title,
		'post_content' => $content
	) );

	# Now that the post is updated, fetch all fields and cache 'em
	$containers = array();

	$raw = get_posts( array(
		'post_type' => 'gravityfields',
		'posts_per_page' => -1
	) );

	foreach( $raw as $container ) {
		$meta = array();

		$raw_meta = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$container->ID" );
		foreach( $raw_meta as $m ) {
			$meta[ $m->meta_key ] = maybe_unserialize( $m->meta_value );
		}

		$containers[ $container->ID ] = array(
			'post' => $container,
			'meta' => $meta
		);
	}

	# Save everything in an option
	update_option( 'gf_containers', $containers );
}

# Change columns
add_filter( 'manage_edit-gravityfields_columns', 'gf_edit_gravityfields_columns' ) ;
function gf_edit_gravityfields_columns( $columns ) {
	$columns = array(
		'cb'           => '<input type="checkbox" />',
		'title'        => __( 'Title' ),
		'containers'   => __( 'Types', 'gf' ),
		'fields_count' => __( 'Fields', 'gf' )
	);

	return $columns;
}

add_action( 'manage_gravityfields_posts_custom_column', 'gf_manage_gravityfields_columns', 10, 2 );
function gf_manage_gravityfields_columns( $column, $post_id ) {
	global $post;

	$containers = get_option( 'gf_containers' );
	foreach( $containers as $c ) {
		if( $c[ 'post' ]->ID == $post_id ) {
			$container = $c;
			break;
		}
	}

	switch( $column ) {
		case 'containers':
			$text = array();

			if( get_post_meta( $post_id, 'gf_options_page', true ) ) {
				$text[] = __( 'Options Page', 'gf' );
			}

			if( get_post_meta( $post_id, 'gf_postmeta_box', true ) ) {
				$text[] = __( 'Post Meta', 'gf' );
			}

			if( get_post_meta( $post_id, 'gf_termsmeta', true ) ) {
				$text[] = __( 'Term Meta', 'gf' );
			}

			if( get_post_meta( $post_id, 'gf_usermeta', true ) ) {
				$text[] = __( 'User Meta', 'gf' );
			}

			if( get_post_meta( $post_id, 'gf_widget', true ) ) {
				$text[] = __( 'Widget', 'gf' );
			}

			echo empty( $text ) ? __( 'None', 'gf' ) : implode( ', ', $text );

			break;
		case 'fields_count':
			echo count( get_post_meta( $post_id, 'fields', true ) );
			break;
	}
}

# Modify actions - remove quick edit and add export
add_filter('post_row_actions','remove_quick_edit',10,2);
function remove_quick_edit( $actions ) {
	global $post;

	if( $post->post_type != 'gravityfields' || ! isset( $actions[ 'edit' ] ) ) {
		return $actions;
	}

	$export_link = admin_url( 'edit.php?post_type=gravityfields&page=gf-export&export_container=' . $post->ID );

	# Add export link
	$actions = array(
		'edit' => $actions[ 'edit' ],
		'export-link' => '<a href="' . esc_attr( $export_link ) . '">' . __( 'Export to PHP', 'gf' ) . '</a>',
		'trash' => $actions[ 'trash' ]
	);

    return $actions;
}