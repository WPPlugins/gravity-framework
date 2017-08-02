jQuery(function( $ ) {

	// Don't do anything unless we're on the right page
	if( ! $( '#gravityfields-fields' ).size() ) {
		return;
	}

	$( document ).on( 'change', '.gf-field[data-id="field_title"] input[type=text]', function() {
		var value = $( this ).val(),
			$idField = $( this ).closest( '.gf-field' ).siblings( '[data-id="field_id"]' ).find( 'input[type=text]' );

		if( ! $idField.val() ) {
			value = value.replace( /[\-_ ]/g, '_' ).replace( /[^_a-z]/ig, '' ).toLowerCase().replace( /^_*?(.+[^_])_*?$/, '$1' );
			$idField.val( value ).trigger( 'keyup' );
		}
	} );

	$( document ).on( 'change', '.gf-field[data-id="title"] input[type=text]', function() {
		var value = $( this ).val(),
			$idField = $( this ).closest( '.gf-field' ).siblings( '[data-id="key"]' ).find( 'input[type=text]' );

		if( ! $idField.val() ) {
			value = value.replace( /[\-_ ]/g, '_' ).replace( /[^_a-z]/ig, '' ).toLowerCase().replace( /^_*?(.+[^_])_*?$/, '$1' );
			$idField.val( value ).trigger( 'keyup' );
		}
	} );

});