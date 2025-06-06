import { useEffect } from '@wordpress/element';

const useDebounce = ( callback, delay, deps ) => {
	useEffect( () => {
		const handler = setTimeout( () => callback( deps ), delay );
		return () => clearTimeout( handler );
	}, [ deps, callback, delay ] );
};

export default useDebounce;
