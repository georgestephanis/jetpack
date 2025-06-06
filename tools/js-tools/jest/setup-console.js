// Mocks and noops `console.log`, `console.info`, `console.warn`, and `console.error`.
// Also, if any of those get called, the test will fail unless it did the approrpiate tests of
//
// `expect( console ).toHaveLogged()` or `expect( console ).toHaveLoggedWith( msg )`
// `expect( console ).toHaveInformed()` or `expect( console ).toHaveInformedWith( msg )`
// `expect( console ).toHaveWarned()` or `expect( console ).toHaveWarnedWith( msg )`
// `expect( console ).toHaveErrored()` or `expect( console ).toHaveErroredWith( msg )`
//
// Note `console.debug` and `console.trace` are not mocked, and so may be used for debugging.
require( '@wordpress/jest-console' );

// Work around https://github.com/WordPress/gutenberg/issues/48042
beforeEach( () => {
	for ( const func of [ 'log', 'info', 'warn', 'error' ] ) {
		if ( console[ func ]?.mockReturnValue ) {
			console[ func ].mockReturnValue();
		}
	}
} );
