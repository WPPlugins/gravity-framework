<?php
/**
 * Includes most files of the plugin while doing actions
 * and applying filters so new items can be added in the order
 * of classes or other ones might get replaced.
 * 
 * The array below contains most files that will be used by the plugin.
 */

$files = array(
	'basic' => array(
		GF_CLASS_DIR . 'GF_Notices.php',
		GF_CLASS_DIR . 'GF_Exceptions.php',
		GF_CLASS_DIR . 'GF_Themes.php'
	),

	'multilingual' => array(
		GF_CLASS_DIR . 'GF_ML_Adapter.php',
		GF_CLASS_DIR . 'GF_ML.php',
		GF_CLASS_DIR . 'GF_Qtranslate.php',
		GF_CLASS_DIR . 'GF_ML_WordPress.php'
	),

	'datastores' => array(
		GF_CLASS_DIR . 'GF_Unavailable_Key_Exception.php',
		GF_CLASS_DIR . 'GF_Datastore.php',
		GF_CLASS_DIR . 'GF_Datastore_Options.php',
		GF_CLASS_DIR . 'GF_Datastore_Postmeta.php',
		GF_CLASS_DIR . 'GF_Datastore_Usermeta.php',
		GF_CLASS_DIR . 'GF_Datastore_Termsmeta.php',
		GF_CLASS_DIR . 'GF_Datastore_Getter.php'
	),

	'containers' => array(
		GF_CLASS_DIR . 'GF_Container.php',
		GF_CLASS_DIR . 'GF_Container_Base.php',
		GF_CLASS_DIR . 'GF_Options.php',
		GF_CLASS_DIR . 'GF_Postmeta.php',
		GF_CLASS_DIR . 'GF_Widget.php',
		GF_CLASS_DIR . 'GF_Terms_Meta.php',
		GF_CLASS_DIR . 'GF_Usermeta.php'
	),

	'fields' => array(
		GF_CLASS_DIR . 'GF_Field.php',
		GF_CLASS_DIR . 'GF_Field_Repeater.php',
		GF_CLASS_DIR . 'GF_Field_Separator.php',
		GF_CLASS_DIR . 'GF_Field_Text.php',
		GF_CLASS_DIR . 'GF_Field_Number.php',
		GF_CLASS_DIR . 'GF_Field_Select.php',
		GF_CLASS_DIR . 'GF_Field_Set.php',
		GF_CLASS_DIR . 'GF_Field_Tags.php',
		GF_CLASS_DIR . 'GF_Field_Textarea.php',
		GF_CLASS_DIR . 'GF_Field_Select_Page.php',
		GF_CLASS_DIR . 'GF_Field_Radio.php',
		GF_CLASS_DIR . 'GF_Field_Checkbox.php',
		GF_CLASS_DIR . 'GF_Field_Image_Select.php',
		GF_CLASS_DIR . 'GF_Field_Map.php',
		GF_CLASS_DIR . 'GF_Field_Header_Scripts.php',
		GF_CLASS_DIR . 'GF_Field_Footer_Scripts.php',
		GF_CLASS_DIR . 'GF_Field_File.php',
		GF_CLASS_DIR . 'GF_Field_Image.php',
		GF_CLASS_DIR . 'GF_Field_Audio.php',
		GF_CLASS_DIR . 'GF_Field_Select_Term.php',
		GF_CLASS_DIR . 'GF_Field_Color.php',
		GF_CLASS_DIR . 'GF_Field_Select_Sidebar.php',
		GF_CLASS_DIR . 'GF_Field_Date.php',
		GF_CLASS_DIR . 'GF_Field_Time.php',
		GF_CLASS_DIR . 'GF_Field_Richtext.php',
		GF_CLASS_DIR . 'GF_Field_Google_Font.php'
	)
);

# Modify files before the start
$fields = apply_filters( 'gf_includes', $files );

foreach( $files as $group => $paths ) {
	# Allow actions before the group is included
	do_action( 'gf_inc_before_' . $group );

	# Include files
	foreach( $paths as $path ) {
		include_once( apply_filters( 'gf_file_path', $path, $group ) );
	}

	# Allow actions after the group is included
	do_action( 'gf_inc_after_' . $group );
}