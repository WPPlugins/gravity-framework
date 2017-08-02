<?php
/**
 * Add the Add Field shortcode button next to the Add Media one
 * 
 * @param string $editor_id The editor that this button is linked to
 */
add_action( 'media_buttons', 'gf_shortcode_button', 20 );
function gf_shortcode_button( $editor_id ) {
	echo '<a href="#" id="insert-field-button" class="button" data-editor="' . $editor_id . '" title="' . __( 'Add Field', 'gf' ) . '">
		<span class="wp-field-buttons-icon"></span>
		' . __( 'Add Field Value', 'gf' ) . '
	</a>';
}

/**
 * Collect all available fields and create the Gravity Fields shortcode popup
 */
add_action( 'after_wp_tiny_mce', 'gf_shortcode_popup' );
function gf_shortcode_popup( $settings ) {
	# The popup is only needed once
	remove_action( 'after_wp_tiny_mce', 'gf_shortcode_popup' );

	# The items that will go in the select
	$select_html = '';

	# Hold the types, which will be outputed as JSON
	$types = array();

	# Get containers
	$option = get_option( 'gf_containers' );
	if( $option ) {
		foreach ( $option as $container ) {
			# Prepare the title
			$title = apply_filters( 'the_title', $container[ 'post' ]->post_title );

			# Get the ID of the container
			$container_id = $container[ 'post' ]->ID;

			# Get the types of the container
			$container_types = array();
			if( isset( $container[ 'meta' ][ 'gf_options_page' ] ) && $container[ 'meta' ][ 'gf_options_page' ] )
				$container_types[] = 'option';
			if( isset( $container[ 'meta' ][ 'gf_postmeta_box' ] ) && $container[ 'meta' ][ 'gf_postmeta_box' ] )
				$container_types[] = 'post';
			if( isset( $container[ 'meta' ][ 'gf_termsmeta' ] ) && $container[ 'meta' ][ 'gf_termsmeta' ] )
				$container_types[] = 'term';
			if( isset( $container[ 'meta' ][ 'gf_usermeta' ] ) && $container[ 'meta' ][ 'gf_usermeta' ] )
				$container_types[] = 'user';
			if( isset( $container[ 'meta' ][ 'gf_widget' ] ) && $container[ 'meta' ][ 'gf_widget' ] )
				$container_types[] = 'widget';

			# If the container is not connected to any type, it couldn't be used.
			if( empty( $container_types ) ) {
				continue;
			}

			$select_html .= '<optgroup label="' . esc_attr( $title ) . '" data-container-id="' . $container_id . '">';
			foreach( $container[ 'meta' ][ 'fields' ] as $field ) {
				if( $field[ 'type' ] == 'tab_start' || $field[ 'type' ] == 'separator' ) {
					continue;
				}
				
				$select_html .= '<option value="' . esc_attr( $container_id . '-' . $field[ 'field_id' ] ) . '">' . GF_ML::split( $field[ 'field_title' ] ) . '</option>';
				$types[ $container_id . '-' . $field[ 'field_id' ] ] = array(
					'types' => $container_types,
					'id' => $field[ 'field_id' ]
				);
			}
			$select_html .= '</optgroup>';
		}
	}

	?>
	<div class="gf-shortcode-popup">
		<div class="overlay"></div>
		<form class="cnt">
			<a href="#" class="media-modal-close"><span class="media-modal-icon"></span></a>
			<h1><?php _e( 'Insert Gravity Fields Field', 'gf' ) ?></h1>
			<p><?php _e( 'The field will be inserted as a shortcode and it will be replaced with it&apos;s value.', 'gf' ) ?></p>
			<p><?php _e( 'If you do not select a type or select &quot;Custom Field&quot;, the value will be the one associated with the displayed page.', 'gf' ) ?></p>

			<table class="form-table">
				<tr>
					<th><label for="field_key"><?php _e( 'Field' ) ?></label></th>
					<td>
						<select id="field_key">
							<option value="">-- <?php _e( 'Select Field', 'gf' ) ?> --</option>
							<?php echo $select_html ?>
						</select>
					</td>
				</tr>
				<tr id="field_type_wrap" style="display:none">
					<th><label for="field_type"><?php _e( 'Type', 'gf' ) ?></label></th>
					<td>
						<label>
							<input type="radio" name="field_type" value="post" />
							<?php _e( 'Custom Field', 'gf' ) ?>
						<br /></label>
						
						<label>
							<input type="radio" name="field_type" value="option" />
							<?php _e( 'Option', 'gf' ) ?>
						<br /></label>
						
						<label>
							<input type="radio" name="field_type" value="term" />
							<?php _e( 'Term Meta', 'gf' ) ?>
						<br /></label>
						
						<label>
							<input type="radio" name="field_type" value="user" />
							<?php _e( 'User Meta', 'gf' ) ?>
						<br /></label>
						
						<label>
							<input type="radio" name="field_type" value="widget" />
							<?php _e( 'Widget', 'gf' ) ?>
						</label>
					</td>
				</tr>
				<tr id="field_item_id_wrap" style="display:none">
					<th><label for="field_item_id"><?php _e( 'Item', 'gf' ) ?></label></th>
					<td>
						<select id="field_item_id" />
						<p class="description"><?php _e( 'There are no items that are associated with the chosen field.', 'gf' ) ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Insert', 'gf' ) ) ?>
		</form>
	</div>

	<script type="text/javascript">
	gf_shortcode_field_types = jQuery.parseJSON( '<?php echo json_encode( $types ) ?>' );
	</script>
	<?php
}

/**
 * Handles requests for items that a container applies for.
 * 
 * Used by the shortcode popup, where you can choose which item to pull
 * data from.
 */
add_action( 'wp_ajax_gf_container_items', 'gf_container_items' );
function gf_container_items() {
	if( ! isset( $_POST[ 'container' ] ) || ! isset( $_POST[ 'type' ] ) ) {
		return;
	}

	$container = $_POST[ 'container' ];
	$type      = $_POST[ 'type' ];
	$items     = array();
	$found     = true;

	$option = get_option( 'gf_containers' );
	if( isset( $option[ $container ] ) ) {
		$container = $option[ $container ];
	} else {
		return;
	}

	switch( $type ) {
		case 'post':
			$items[ 0 ] = __( '-- Current Post --', 'gf' );

			$args = array(
				'post_type'      => $container[ 'meta' ][ 'gf_postmeta_posttype' ],
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'orderby'        => 'post_title'
			);

			$args = apply_filters( 'gf_container_items_posts_args', $args );

			$raw = get_posts( $args );
			foreach( $raw as $i ) {
				$items[ $i->ID ] = apply_filters( 'the_title', $i->post_title ? $i->post_title : __( '(no title)' ) );
			}

			break;
		case 'term':
			$args = array(
				'hide_empty' => false
			);

			$args = apply_filters( 'gf_container_items_terms_args', $args );

			$raw = get_terms( $container[ 'meta' ][ 'gf_termsmeta_taxonomies' ] );
			if( ! empty( $raw ) ) {
				foreach( $raw as $t ) {
					$items[ $t->term_id ] = apply_filters( 'single_cat_title', $t->name );
				}				
			} else {
				$found = false;
			}

			break;
		case 'user':
			$args = array();
			$args = apply_filters( 'gf_container_items_users_args', $args );

			$raw = get_users();
			foreach( $raw as $u ) {
				$items[ $u->ID ] = $u->data->display_name;
			}

			break;
	}

	$data = array(
		'found' => 1,
		'items' => $items
	);

	echo json_encode( apply_filters( 'gf_container_items_data', $data, $container, $type ) );
	exit;
}