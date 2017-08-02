<?php
/**
 * Create field objects by a plain array
 * 
 * @param mixed[] $fields - The plain array, containing raw data
 * @return GF_Field[] $prepared - The fields, ready to be added to a container
 */
function gf_setup_fields( $fields, $container_type ) {
	$gf_processors = & $GLOBALS[ 'gf_datastore_getter' ]->processors;

	$prepared = array( );

	if( ! is_array( $fields ) ) {
		return;
	}

	foreach( $fields as $field ) {
		if( $field[ 'type' ] == 'tab_start' || $field[ 'type' ] == 'tab_end' ) {
			# Add the icon as a path
			if( $field[ 'type' ] == 'tab_start' && $field[ 'icon' ] ) {
				$field[ 'icon' ] = wp_get_attachment_url( $field['icon'] );
			}

			$prepared[] = $field;
		} else {
			$obj = null;

			switch( $field['type'] ) {
				case 'separator':
					$obj = GF_Field::factory( 'separator', 'separator_' . md5( microtime() ) );
					break;

				case 'text':
					$obj = GF_Field::factory( $field[ 'type' ], $field['field_id'] );

					if( isset( $field['autocomplete_suggestions'] ) ) {
						$obj->add_suggestions( explode( "\n", $field['autocomplete_suggestions'] ) );
					}

					break;

				case 'tags':
				case 'select':
				case 'set':
				case 'radio':
					$obj = GF_Field::factory( $field['type'], $field['field_id'] );

					if( $field['values_source'] == 'textarea' ) {
						$values = array( );

						if( isset( $field['options'] ) )
						foreach( $field['options'] as $option ) {
							$values[ $option['key'] ] = $option['value'];
						}

						$obj->add_options( $values );
					} else {
						$obj->add_posts( array( 
							'posts_per_page' => -1,
							'order'          => 'ASC',
							'orderby'        => 'post_title',
							'post_type'      => $field['post_type']
						 ) );
					}

					if( isset( $field['sortable'] ) && $field['sortable'] && $field['type'] == 'set' ) {
						$obj->sortable( true );
					}

					if( isset( $field[ 'jquery_plugin' ] ) && $field[ 'jquery_plugin' ] ) {
						$obj->chosen();
					}

					break;

				case 'number':
					$obj = GF_Field::factory( 'number', $field['field_id'] );

					if( isset( $field['enable_slider'] ) && $field['enable_slider'] ) {
						$obj->slider( $field['slider_minimum'], $field['slider_maxmimum'] );
					}

					break;


				case 'header_scripts':
				case 'footer_scripts':
				case 'textarea':
					$obj = GF_Field::factory( $field[ 'type' ], $field['field_id'] );

					$obj->set_rows( $field['rows'] );

					break;

				case 'select_page':
				case 'file':
				case 'audio':
				case 'image':
				case 'color':
				case 'richtext':
					$obj = GF_Field::factory( $field['type'], $field['field_id'] );
					break;

				case 'checkbox':
					$obj = GF_Field::factory( 'checkbox', $field['field_id'] );

					if( isset( $field['text'] ) ) {
						$obj->set_text( $field['text'] );
					}

					break;

				case 'image_select':
					$obj = GF_Field::factory( 'image_select', $field['field_id'] );

					$options = array( );
					if( isset( $field['options'] ) )
					foreach( $field['options'] as $row ) {
						$option = $row;

						$att = wp_get_attachment_image_src( $option['image'], 'full' );

						if( !$att ) {
							continue;
						}

						if( isset( $option[ 'use_image_src' ] ) && $option[ 'use_image_src' ] ) {
							$src = wp_get_attachment_image_src( $option[ 'image' ], $option[ 'image_size' ] );
							$option[ 'key' ] = $src[ 0 ];
						}

						$options[ $option['key'] ] = array( 
							'label' => $option['value'],
							'image' => $att[0]
						 );
					}

					$obj->add_options( $options );

					break;

				case 'map':
					$obj = GF_Field::factory( 'map', $field['field_id'] );

					if( isset( $field['height'] ) && $field['height'] ) {
						$obj->set_height( $field['height'] );
					}

					if( isset( $field['show_locator'] ) ) {
						$obj->show_locator( $field['show_locator'] );
					}

					if( isset( $field['locator_text'] ) ) {
						$obj->set_locator_text( $field['locator_text'] );
					}

					break;

				case 'select_term':
					$obj = GF_Field::factory( 'select_term', $field['field_id'] );
					$obj->set_taxonomy( $field['taxonomy'] );

					break;

				case 'select_sidebar':
					$obj = GF_Field::factory( 'select_sidebar', $field['field_id'] );
					$obj->allow_manipulation( $field['allow_adding'] );
					
					break;

				case 'date':
					$obj = GF_Field::factory( 'date', $field['field_id'] );
					$obj->set_format( $field['date_format'] );

					break;

				case 'time':
					$obj = GF_Field::factory( 'time', $field['field_id'] );
					$obj->set_format( $field['date_format'] );

					break;

				case 'google_font':
					$obj = GF_Field::factory( 'google_font', $field['field_id'] );

					if( isset( $field[ 'api_key' ] ) && $field[ 'api_key' ] ) {
						$obj->set_api_key( $field['api_key'] );
					} elseif( $global = get_option( 'gf_google_fonts_api_key' ) ) {
						$obj->set_api_key( $global );
					}

					break;

				case 'repeater':
					$obj = GF_Field::factory( 'repeater', $field[ 'field_id' ] );

					if( isset( $field[ 'repeater_fields' ] ) )
					foreach( $field[ 'repeater_fields' ] as $group ) {
						$sub_fields_arr = gf_setup_fields( $group[ 'group_fields' ], 'GF_Field_Repeater' );

						$obj->add_fields( $group[ 'key' ], array(
							'title' => GF_ML::split( $group[ 'title' ] )
						), $sub_fields_arr );
					}

					break;
			}

			if( $obj ) {
				foreach( $field as $key => $value ) {
					switch( $key ) {
						case 'title': case 'field_title':   $obj->set_title( GF_ML::split( $value ) );         break;
						case 'default_value': $obj->set_default_value( $value ); break;
						case 'help_text':     $obj->set_help_text( GF_ML::split( $value ) );     break;
						case 'description':   $obj->set_description( GF_ML::split( $value ) );   break;
						case 'multilingual':  if( $value ) $obj->multilingual();                 break;
					}
				}

				# Add the field as a processor
				if( method_exists( $obj, 'process_value' ) ) {
					if( ! isset( $gf_processors[ $container_type ] ) ) {
						$gf_processors[ $container_type ] = array();
					}

					if( ! isset( $gf_processors[ $container_type ][ $field[ 'field_id' ] ] ) ) {
						$gf_processors[ $container_type ][ $field[ 'field_id' ] ] = array(
							10 => array()
						);
					}

					$gf_processors[ $container_type ][ $field[ 'field_id' ] ][ 10 ][] = array(
						'callback' => array( $obj, 'process_value' ),
						'data'     => $field
					);
				}

				$prepared[] = $obj;
			}
		}
	}

	return $prepared;
}