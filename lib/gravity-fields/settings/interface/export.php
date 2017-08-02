<?php
# Removes an array if it's empty and an array
function gf_array_filter_recursive( $input ) { 
	foreach ( $input as &$value ) { 
		if ( is_array( $value ) ) { 
			$value = gf_array_filter_recursive( $value ); 
		}
	} 

	return array_filter( $input ); 
} 

# Add the page
add_action( 'admin_menu', 'gf_add_export_page' );
function gf_add_export_page() {
	add_submenu_page( 'edit.php?post_type=gravityfields', __( 'Export', 'gf' ) , __( 'Export', 'gf' ) , 'manage_options', 'gf-export', 'gf_export_page' );
}

function gf_export_page() {
	if( ! isset( $_GET[ 'export_container' ] ) ) {
		?>
		<div class="wrap">
			<div id="icon-edit" class="icon32 icon32-posts-gravityfields"></div>
			<h2><?php _e( 'Export', 'gf' ) ?></h2>
			<p><?php _e( 'Please go to the containers list and click the <b>Export to PHP</b> link for a specific container.', 'gf' ) ?></p>
			<hr />
			<h3><?php _e( 'XML Export', 'gf' ) ?></h3>
			<p><?php printf( __( 'You can export containers by using the WordPress <a href="%s">Export</a> tool. The generated XML file can be imported on another site through the Import tool, just like a standard posts XML file.', 'gf' ), admin_url( 'export.php' ) ) ?></p>
			<p><?php printf( __( 'To see how this works, you can read the <a href="%s">Tools Export Screen</a> article at WordPress.org', 'gf' ), 'http://codex.wordpress.org/Tools_Export_Screen' ) ?></p>
		</div>
		<?php
		return;
	}

	$container = get_post( $_GET[ 'export_container' ] );

	# Prepare items
	$function_name = 'setup_' . str_replace( '-', '_', sanitize_title( $container->post_title ) ) . '_fields';

	$all_containers = get_option( 'gf_containers' );
	$container_data = $all_containers[ $container->ID ];
	
	$exportable = array_merge( array(
		'title'       => $container_data['post']->post_title,
		'description' => $container_data['post']->post_content
	), $container_data['meta'] );

	# Clean up a bit
	unset( $exportable[ '_edit_lock' ] );
	unset( $exportable[ '_edit_last' ] );

	if( ! $exportable['gf_options_page'] ) {
		unset( $exportable[ 'gf_options_page_type' ] );
		unset( $exportable[ 'gf_options_parent_page' ] );
		unset( $exportable[ 'gf_options_page_parent_slug' ] );
		unset( $exportable[ 'gf_options_page_slug' ] );
		unset( $exportable[ 'gf_options_icon' ] );
		unset( $exportable[ 'gf_options_menu_position' ] );
	}

	if( ! $exportable['gf_postmeta_box'] ) {
		unset( $exportable[ 'gf_postmeta_posttype' ]);
		unset( $exportable[ 'gf_postmeta_templates' ]);
		unset( $exportable[ 'gf_postmeta_levels' ]);
	}

	if( ! $exportable['gf_widget'] ) {
		unset( $exportable[ 'gf_widget_css_class' ] );
		unset( $exportable[ 'gf_widget_width' ] );
		unset( $exportable[ 'gf_widget_source' ] );
		unset( $exportable[ 'gf_widget_code' ] );
		unset( $exportable[ 'gf_widget_callback' ] );
	}

	if( ! $exportable['gf_termsmeta'] ) {
		unset( $exportable['gf_termsmeta_taxonomies'] );
	}

	# Remove empty arrays
	$exportable = gf_array_filter_recursive( $exportable );

	# Convert to an exportable string
	$out = var_export( $exportable, true );

	# Convert spaces to tabs
	$out = preg_replace( '~  ~', "\t", $out );
	$out = preg_replace( '~\n~', "\n\t", $out );
	$out = esc_html( $out );

	$xml_export_link = admin_url( 'export.php?download=true&post_author=0&post_start_date=0&post_end_date=0&post_status=0&page_author=0&page_start_date=0&page_end_date=0&page_status=0&content=gravityfields&gf_id=' . $container->ID );
	?>
	<div class="wrap gf-wrap gf-options">
	<div class="head">
		<div id="icon-edit" class="icon32 icon32-posts-gravityfields"></div>
		<h2><?php printf( __( 'Export "%s" to PHP', 'gf' ), apply_filters( 'the_title', $container->post_title ) ) ?></h2>

		<div style="padding: 10px 0 10px 280px; overflow:hidden;">
			<div class="metabox-holder" style="float: left; width: 250px; position: relative; margin-left: -280px; padding: 0;">
				<div class="postbox">
					<h3 class="hndle" style="cursor:default"><span>Instructions</span></h3>
					<div class="inside">
						<p><?php _e( 'You can export containers to PHP and add them to your theme&apos;s files. This way when the theme is activated, you would not need to setup containers and their fields up manualy, the code will do it for you.', 'gf' ) ?></p>
						<p><?php _e( 'When a container is set-up this way, it will not appear in the list on the All Containers page!', 'gf' ) ?></p>
						<p><?php _e( 'The code can be added anywhere in your files, just make sure it is done before the <strong>init</strong> hook. If you add it directly to your functions.php file, it is okay. Also, keep in mind that you don&apos;need Gravity Fields to be embedded in your theme in order for this to work. The gf_setup action will not be triggered if the plugin is not active, so there will be no errors. In such cases you might want to consider adding some code to your theme, which adds a notification if the plugin is not active.', 'gf' ) ?></p>
						<p><?php _e( '<strong>Containers in the admin have higher priority than the ones in the code!</strong> When you add this code to your functions file, if you don&apos;t delete the container from the admin, it will overwrite the one in the code.', 'gf' ) ?></p>
					</div>
				</div>
			</div>

			<textarea class="gf-export" readonly>&lt;?php
/**
 * Gravity Fields Container Setup
 *
 * This code will setup a container called <?php echo apply_filters( 'the_title', $container->post_title ) ?>.
 * In order for this code to work, you need to have the Gravity Fields plugin installed or embedded in the theme.
 * 
 * Add this code directly to you functions.php file or a file that's included in it.
 *
 * For more information, please visit http://gravity-fields.com/
 */
add_action( 'gf_setup_containers', '<?php echo $function_name ?>' );
function <?php echo $function_name ?>() {
	gf_setup_container( <?php echo $out ?> );
}
?&gt;</textarea>
		</div>
	</div>
	<?php
}