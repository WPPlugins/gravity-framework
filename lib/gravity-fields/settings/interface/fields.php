<?php
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

	return $repeater;
}