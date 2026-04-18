( function( $ ) {
	'use strict';

	$( function() {
		var $field = $( '#directorist-category-based-tags-categories' );

		if ( ! $field.length || 'function' !== typeof $.fn.select2 || $field.hasClass( 'select2-hidden-accessible' ) ) {
			return;
		}

		$field.select2(
			{
				allowClear: true,
				closeOnSelect: false,
				width: '100%',
			}
		);
	} );
}( jQuery ) );
