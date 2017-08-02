<?php
/**
 * Retrieve a repeater with all available fields + a nested repeater
 * 
 * @return GF_Repeater The filled repeater
 */
function gf_get_available_fields( $repeater_id = 'fields' ) {
	# The top
	$repeater = GF_Field::factory( 'repeater', $repeater_id, __( 'Fields', 'gf' ) );
	GF_Field::get_fields( $repeater );

	# The inner repeater
	$inner_repeater = GF_Field::factory( 'repeater', 'group_fields', __( 'Fields', 'gf' ) );
	GF_Field::get_fields( $inner_repeater );

	$inner_settings = array(
		'title'       => __( 'Repeater', 'gf' ),
		'description' => __( 'Enables repeateable field groups. Check the docs for more info.', 'gf' )
	);

	$default_fields = GF_Field::settings_fields( 'repeater' );
	unset( $default_fields[ 'default_value' ] );
	unset( $default_fields[ 'default_value_ml' ] );
	unset( $default_fields[ 'multilingual' ] );

	$repeater_settings = array_merge( $default_fields, array(
		GF_Field::factory( 'repeater', 'repeater_fields', __( 'Repeater Fields', 'gf' ) )
			->add_fields( 'group', __( 'Group', 'gf' ) , array(
				GF_Field::factory( 'text', 'title' )
					->multilingual()
					->make_required(),
				GF_Field::factory( 'text', 'key' )
					->make_required( '/[a-z0-9_]+/' ),
				$inner_repeater
			) )
	) );

	$repeater->add_fields( 'repeater', $inner_settings, $repeater_settings );

	/**
	 * Tab starts & ends
	 */
	$details = array(
		'title' => __( 'Tab Start', 'gf' ),
		'description' => __( 'Adds a tab to the container. <strong>Only available for Options & Post Meta!</strong>' )
	);
	$repeater->add_fields( 'tab_start', $details, array(
		GF_Field::factory( 'text', 'title', __( 'Title', 'gf' ) )
			->multilingual(),
		GF_Field::factory( 'image', 'icon', __( 'Icon' ) )	
	));

	return $repeater;
}

/**
 * Add the type box
 */

# Create the box
$box = GF_Postmeta::box( 'Gravity Fields Panel Settings', 'gravityfields', array(
		'title' => __( 'Container Settings', 'gf' )
	) );

# Fetch other pages that are top-level
$top_level_pages = array();
$args = array(
	'post_type'      => 'gravityfields',
	'posts_per_page' => -1,
	'order'          => 'ASC',
	'orderby'        => 'post_title',
	'meta_query'     => array(
		array(
			'key'   => 'gf_options_page_type',
			'value' => 'menu'
		),
		array(
			'key'   => 'gf_options_page',
			'value' => 1
		)
	)
);
if( isset( $_GET[ 'post' ] ) ) {
	$args[ 'post__not_in' ] = array( $_GET[ 'post' ] );
}
$raw = get_posts( $args );
foreach( $raw as $container ) {
	$top_level_pages[ $container->ID ] = apply_filters( 'the_title', $container->post_title );
}

// $box->set_tabs_align( 'left' );

/**
 * Basic settings
 */
// $box->add_fields( array(
$box->tab( 'general', array(
	GF_Field::factory( 'text', 'gf_title', __( 'Title', 'gf' ) )
		->multilingual()
		->set_description( __( 'The title is the key element of a container. It will appear in the menu for options pages, as a box heading for widgets and post meta.', 'gf' ) )
		->make_required(),
	GF_Field::factory( 'textarea', 'gf_description', __( 'Description', 'gf' ) )
		->set_description( __( 'The description is optional and will appear in the beginning of a container.', 'gf' ) )
		->multilingual()
), GF_URL . 'settings/images/tabs/general.png', __( 'General', 'gf' ) );

/**
 * Options page settings
 */
$box->tab( 'options-page', array(
	GF_Field::factory( 'separator', 'gf_options_page_separator', __( 'Options Page', 'gf' ) )
		->set_description( __( 'Options pages are useful for general settings. They will appear as separate pages in the administration menu.', 'gf' ) ),
	GF_Field::factory( 'checkbox', 'gf_options_page', __( 'Display as Options Page', 'gf' ) )
		->set_text( __( 'Yes', 'gf' ) ),
	GF_Field::factory( 'select', 'gf_options_page_type', __( 'Show page', 'gf' ) )
		->add_options( array(
			'menu'          => __( 'In the Main Menu', 'gf' ),
			'settings'      => __( 'Under the Settings tab', 'gf' ),
			'appearance'    => __( 'Under the Appearance tab', 'gf' ),
			'tools'         => __( 'Under the Tools Tab', 'gf' ),
			'other_gf_page' => __( 'Under another GravityFields page.', 'gf' ),
			'other_page'    => __( 'Under another page, specified by slug', 'gf' )
		) )
		->set_dependency( 'gf_options_page' ),
	GF_Field::factory( 'select', 'gf_options_parent_page', __( 'Parent Page', 'gf' ) )
		->set_dependency( 'gf_options_page' )
		->set_dependency( 'gf_options_page_type', 'other_gf_page' )
		->set_no_options_message( __( 'Right now, there are no top level GravityFields options pages. Until you add a top level page, this page will be displayed under in main menu.', 'gf' ) )
		->add_options( $top_level_pages ),
	GF_Field::factory( 'text', 'gf_options_page_parent_slug', __( 'Parent Page Slug' ) )
		->set_dependency( 'gf_options_page' )
		->set_dependency( 'gf_options_page_type', 'other_page' ),
	GF_Field::factory( 'text', 'gf_options_page_slug', __( 'Slug', 'gf' ) )
		->set_description( __( '<strong>Required if the container has multilingual title!</strong> The ID of the container is neccessary if you want a specific slug for the page. If you leave this empty, the slug will be generated from the title. If the container has a multilignaul title though, you need to set this, because not all languages might work as slugs.', 'gf' ) )
		->set_dependency( 'gf_options_page' ),
	GF_Field::factory( 'image', 'gf_options_icon', __( 'Menu Icon', 'gf' ) )
		->set_dependency( 'gf_options_page' )
		->set_dependency( 'gf_options_page_type', 'menu' )
		->set_description( __( 'Top level pages might use a custom icon.', 'gf' ) ),
	GF_Field::factory( 'number', 'gf_options_menu_position', __( 'Menu Position', 'gf' ) )
		->slider( 1, 120 )
		->set_default_value( 100 )
		->set_dependency( 'gf_options_page' )
		->set_dependency( 'gf_options_page_type', 'menu' )
		->set_description( __( 'Be careful with this setting, because you might silently overwrite another item&apos;s icon as WordPress does not check if there is anything at the particular position.', 'gf' ) )
), GF_URL . 'settings/images/tabs/options.png' , __( 'Options Page', 'gf' ) );

# Prepare post types
$post_types = array();
$hierarchical_post_types = array();
$excluded = apply_filters( 'gf_excluded_post_types', array( 'attachment', 'gravityfields' ) );
$raw = get_post_types( array(
	'show_ui' => true
), 'objects' );
foreach( $raw as $id => $post_type ) {
	if( in_array( $id, $excluded ) ) {
		continue;
	}

	$post_types[ $id ] = $post_type->labels->name;
	if( is_post_type_hierarchical( $id ) ) {
		$hierarchical_post_types[ $id ] = $post_type->labels->name;
	}
}

# Prepare page templates
$templates = array(
	'default' => __( 'Default' )
);

$raw = wp_get_theme()->get_page_templates();
foreach( $raw as $template => $name ) {
	$templates[ $template ] = $name;
}

/**
 * Post meta settings
 */
$post_meta_fields = array(
	GF_Field::factory( 'separator', 'gf_postmeta_separator', __( 'Post Meta Box', 'gf ') )
		->set_description( __( "Post Meta boxes allow you to add custom fields to pages, posts and items from other post types. Use the fields below yo customize the box and choose which screens will it appear on.\n\nPlease note that some of the settings might not be available for all post types and will be ignored on their screens (ex. Templates on Posts).", 'gf' ) ),
	GF_Field::factory( 'checkbox', 'gf_postmeta_box', __( 'Display as Post Meta box', 'gf' ) )
		->set_text( __( 'Yes', 'gf' ) ),
	GF_Field::factory( 'set', 'gf_postmeta_posttype', __( 'Show on post types', 'gf' ))
		->add_options( $post_types )
		->set_dependency( 'gf_postmeta_box' ),
	GF_Field::factory( 'set', 'gf_postmeta_templates', __( 'Show on page templates:', 'gf' ) )
		->add_options( $templates )
		->set_dependency( 'gf_postmeta_box' )
		->set_dependency( 'gf_postmeta_posttype', array( 'page' ), 'IN' )
		->set_description( __( 'The box will only appear on the checked templates, if any. If none are checked, the container will appear on all pages.', 'gf' ) ),
	GF_Field::factory( 'number' , 'gf_postmeta_levels', __( 'Levels', 'gf' ) )
		->set_dependency( 'gf_postmeta_box' )
		->set_dependency( 'gf_postmeta_posttype', array_keys( $hierarchical_post_types ), 'IN' )
		->set_description( __( 'On hierarchical post types, the box will only be visible on the selected levels. Leave 0 for all levels', 'gf' ) )
		->set_default_value( 0 )
		->slider( 0, 10 )
);

$taxonomies = get_taxonomies( array( 'show_ui' => 1 ), 'objects' );
foreach( $taxonomies as $id => $taxonomy ) {
	# Only hierarchical taxonomies have checkboxes
	if( ! $taxonomy->hierarchical ) {
		continue;
	}
	
	$options = array();
	$terms = get_terms( $id, array( 'hide_empty' => false ) );
	foreach( $terms as $term ) {
		$options[ $term->term_id ] = apply_filters( 'single_term_title', $term->name );
	}

	$field = GF_Field::factory( 'tags', "gf_postmeta_terms_{$id}", sprintf( __( '%s terms', 'gf' ), $taxonomy->labels->name ) )
		->set_dependency( 'gf_postmeta_box' )
		->set_dependency( 'gf_postmeta_posttype', $taxonomy->object_type, 'IN' )
		->add_options( $options );

	$post_meta_fields[] = $field;
}

$box->tab( 'postmeta', $post_meta_fields, GF_URL . 'settings/images/tabs/postmeta.png', __( 'Post Meta', 'gf' ) );

/**
 * Terms Meta settings
 */
$taxonomies_nice = array();
foreach( $taxonomies as $id => $taxonomy ) {
	$taxonomies_nice[ $id ] = $taxonomy->labels->name . " <$id>";
}
$box->tab( 'termmeta', array(
	GF_Field::factory( 'separator', 'gf_termsmeta_separator', __( 'Terms Meta', 'gf' ) )
		->set_description( __( 'Terms Meta containers allow you to associate fields and save custom data for categories, tags and any other taxonomy. Fields are only visible on edit screens.', 'gf' ) ),
	GF_Field::factory( 'checkbox', 'gf_termsmeta', __( 'Enable Terms Meta', 'gf' ) )
		->set_text( __( 'Yes', 'gf' ) ),
	GF_Field::factory( 'set', 'gf_termsmeta_taxonomies', __( 'Taxonomies', 'gf' ) )
		->add_options( $taxonomies_nice )
		->set_dependency( 'gf_termsmeta' )
		->set_description( __( 'The container will only appear on the checked taxonomies.' ) )
), GF_URL . 'settings/images/tabs/termmeta.png', __( 'Term/Category Meta', 'gf' ) );

/**
 * User Meta Settings
 */
$box->tab( 'usermeta', array(
	GF_Field::factory( 'separator', 'gf_usermeta_separator', __( 'User Meta', 'gf' ) )
		->set_description( __( 'User Meta containers allow you to add fields to user profile pages..', 'gf' ) ),
	GF_Field::factory( 'checkbox', 'gf_usermeta', __( 'Enable User Meta', 'gf' ) )
		->set_text( __( 'Yes', 'gf' ) )
), GF_URL . 'settings/images/tabs/usermeta.png', __( 'User Meta', 'gf' ) );

/**
 * Widget Settings
 */
$box->tab( 'widget', array(
	GF_Field::factory( 'separator', 'gf_widget_separator', __( 'Widget', 'gf' ) ),
	GF_Field::factory( 'checkbox', 'gf_widget', __( 'Enable Widget', 'gf' ) )
		->set_text( __( 'Yes', 'gf' ) ),
	GF_Field::factory( 'text', 'gf_widget_css_class', __( 'CSS Class (Optional)', 'gf' ) )
		->set_dependency( 'gf_widget' ),
	GF_Field::factory( 'number', 'gf_widget_width', __( 'Width', 'gf' ) )
		->slider( 200, 600 )
		->set_default_value( 200 )
		->set_dependency( 'gf_widget' ),
	GF_Field::factory( 'select', 'gf_widget_source', __( 'Template Source', 'gf' ) )
		->set_dependency( 'gf_widget' )
		->set_description( __( 'The other types of containers simply save values which you will retrieve later. Widgets on the other hand, need their own templates. Please choose how will this template be provided - you can either enter it here, or provide a callback function. <a href="http://gravity-fields.com/documentation/#widgets" target="_blank">More on the topic.</a>', 'gf' ) )
		->add_options( array(
			'Please Choose',
			'inline'   => 'Inline',
			'callback' => 'Callback'
		) )
		->make_required(),
	GF_Field::factory( 'richtext', 'gf_widget_code', __( 'Template', 'gf' ) )
		->set_dependency( 'gf_widget' )
		->set_dependency( 'gf_widget_source', 'inline' )
		->set_description( __( '<strong>Please note that fields from this widget cannot be added before they are saved!</strong><br />Enter the template for the widget in the front end here. You can use the values of the fields below like %field_key%.<br />Sortcodes will also work, which means that you can use the [gf] shortcode to output formatted values.', 'gf' ) ),
	GF_Field::factory( 'text', 'gf_widget_callback', __( 'Callback Function', 'gf' ) )
		->set_dependency( 'gf_widget' )
		->set_dependency( 'gf_widget_source', 'callback' )
		->set_description( __( 'This function will be called when the widget is displayed. It will receive two arguments: $args, which are the arguments of the sidebar it is in and $instance, which will contain the values for the particular widget. You can place this function anywhere in your code, as long as it is declared before trying to display the widget.', 'gf' ) )
), GF_URL . 'settings/images/tabs/widget.png', __( 'Widget', 'gf' ) );

$box->tab( 'fields', array(
	gf_get_available_fields()
		->set_custom_template( 'field-no-label' )
), GF_URL . 'settings/images/tabs/fields.png', __( 'Fields', 'gf' ) );

/**
 * The fields themselves, but into another box
 */
// $box = GF_Postmeta::box( 'GravityFields Fields', 'gravityfields', array(
// 	'title' => __( 'Fields', 'gf' )
// ) );
// $box->add_Fields( array(
	
// ) );

/**
 * Gide the default publish box
 */
add_action( 'admin_menu', 'gf_hide_submitdiv' );
function gf_hide_submitdiv() {
	# Remove the default submit div
	remove_meta_box( 'submitdiv', 'gravityfields', 'side' );

	# Add a separate box which replaces the old one
	add_meta_box( 'gf_submitdiv', __( 'Actions', 'gf' ), 'gf_submitdiv', 'gravityfields', 'side', 'high' );
}

/**
 * Change the submit box
 * 
 * @param $post_id The ID of the post
 */
function gf_submitdiv( $post ) {
	global $action;

	$post_type = $post->post_type;
	$post_type_object = get_post_type_object( $post_type );
	?>
	<style type="text/css">#post-body-content { margin-top:0 !important; }</style>
	<div class="submitbox" id="submitpost">
		<span class="spinner"></span>
		<div id="delete-action">
			<a class="button-secondary button-large" href="<?php echo get_delete_post_link($post->ID); ?>"><?php _e( 'Move to Trash' ); ?></a>
		</div>

		<div class="alignright">
			<?php submit_button( __( 'Save' ), 'primary', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
		</div>

		<div class="clear"></div>
	</div>
	<?php
}