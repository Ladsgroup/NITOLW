$( function() {
	var $from = $( '#fromLang' ),
		$to = $( '#toLang' ),
		$category = $( '#category' ),
		$submit = $( 'button[type="submit"]' ),
		isValid = function () {
			return (
				!!$from.val() &&
				!!$to.val() &&
				!!$category.val()
			);
		},
		makeApiURL = function () {
			var lang = $from.val();

			return lang ?
				'https://' +
					lang + '.wiktionary.org/w/api.php' :
				null;
		},
		onChange = function () {
			$submit.prop( 'disabled', isValid() );
			$category.prop( 'disabled', !$from.val() );
		},
		fetchCategories = function ( value ) {
			var url = makeApiURL();

			if ( !url ) {
				return $.Deferred().reject( [] );
			}

			return $.ajax(
				{
					url: makeApiURL(),
					dataType: "jsonp",
					data: {
						action: 'query',
						format: 'json',
						list: 'prefixsearch',
						pssearch: value,
						psnamespace: 14, // Category:
						pslimit: 10
					}
				}
			)
			.then( function ( result ) {
				return ( result.query && result.query.prefixsearch ) || [];
			} )
			.then( function ( pages ) {
				return pages
					.map( function ( page ) {
						var title = page.title || '',
							// Hack; remove anything up to the first :
							// that's the namespace, in any language
							category = title.substring( title.indexOf( ':' ) + 1 );

						return category;
					} );
			} );
		};

	// Events
	$from.on( 'change keydown', onChange );
	$to.on( 'change keydown', onChange );
	$category.autocomplete( {
		source: function ( request, response ) {
			fetchCategories( $category.val() )
				.then( function ( results ) {
					response( results );
				} )
		}
	} );

	// Initialize
	$submit.prop( 'disabled', !isValid() );
	$category.prop( 'disabled', !$from.val() );
} );
