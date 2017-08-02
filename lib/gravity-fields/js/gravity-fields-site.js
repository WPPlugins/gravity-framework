(function( $ ) {

	/**
	 * Add Google Maps for each map
	 */
	$( '.gf-map' ).each(function() {
		var $map = $( this ), value, center, map, marker;

		value = $map.data( 'value' ).split( ',' );
		center = new google.maps.LatLng( value[ 0 ], value[ 1 ] );

		map = new google.maps.Map( this, {
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			center: center,
			zoom: parseInt( value[ 2 ] )
		});

		marker = new google.maps.Marker({
			map: map,
			position: center
		})
	});

})( jQuery );