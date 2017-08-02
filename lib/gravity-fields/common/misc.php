<?php
/**
 * Custom die function. Uses wp_die() if no headers sent, otherwise exits
 * 
 * @param string $error The text of the error
 */
function gf_die( $error='' ) {
	$error = apply_filters( 'gf_die_error', $error );

	if( headers_sent() ) {
		echo '<strong>' . $error . '</strong>';
		exit;
	} else {
		wp_die( $error );
	}
}

/**
 * Creates a link by given base and additional params
 * 
 * @param string $base The base link to start building on
 * @param string[] $params The parameters that should be added to the URL
 * @return string The complete URL
 */
function gf_build_link( $base, $params = array() ) {
	# Extract the base link, removing parameters
	$link = preg_replace( '~^(.+)\??$~i', '$1', $base );

	# Add the additional params if any
	if( ! empty( $params ) ) {
		$strs = array();
		foreach($params as $key => $value) {
			$strs[] = $key . '=' . esc_attr($value);
		}
		$link .= '?' . implode( '&amp;', $strs);
	}

	return apply_filters( 'gf_build_link', $link );
}

/**
 * Clones an array recursively
 *
 * @param mixed[] The source array
 * @return mixed[] The cloned array
 */
function gf_clone_array( $array ) {
	$new = array();

	foreach( $array as $key => $value ) {
		$new[ $key ] = is_array( $value ) ? gf_clone_array( $value ) : clone $value;
	}

	return $new;
}