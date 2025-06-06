import debugFactory from 'debug';
import Emitter from 'mixins/emitter';

import './style.scss';

const debug = debugFactory( 'calypso:notices' );
debug( 'initializing notices' );

const list = { containerNames: {} };
Emitter( list );
let delayedNotices = [];

const notices = {
	/**
	 * Creates a new notice
	 * @private
	 * @param {string} text    - the text to show
	 * @param {object} options - options for the notice
	 * @param {string} status  - the classname to affect the notice color.
	 * @return {object} notice
	 */
	new: function ( text, options, status ) {
		// Set container
		const container = options.overlay ? 'overlay-notices' : 'notices';

		// keep track of container
		list.containerNames[ container ] = container;

		debug( 'creating notice', text, options, status );

		list[ container ] = [];
		const noticeObject = {
			type: options.type || 'message',
			status: status,
			text: text,
			duration: options.duration,
			container: container,
			button: options.button,
			href: options.href,
			onClick: event => {
				if ( typeof options.onClick === 'function' ) {
					const closeFn = notices.removeNotice.bind( notices, noticeObject );
					return options.onClick( event, closeFn );
				}
			},
			onRemoveCallback: options.onRemoveCallback || function () {},
			arrow: options.arrow,
			isCompact: options.isCompact,
			showDismiss: options.showDismiss,
			persistent: options.persistent,
		};

		// if requested, delay the notice until the next page load
		if ( options.displayOnNextPage ) {
			delayedNotices.push( noticeObject );
		} else {
			list[ container ].push( noticeObject );
		}

		list.emit( 'change' );

		return noticeObject;
	},

	/**
	 * Helper function for creating a new "Success" notice
	 * @public
	 * @param {string} text    - the text to show
	 * @param {object} options - options for the notice
	 * @return {object} notice
	 */
	success: function ( text, options ) {
		options = options || {};
		return this.new( text, options, 'is-success' );
	},

	/**
	 * Helper function for creating a new "Error" notice
	 * @public
	 * @param {string} text    - the text to show
	 * @param {object} options - options for the notice
	 * @return {object} notice
	 */
	error: function ( text, options ) {
		options = options || {};
		return this.new( text, options, 'is-error' );
	},

	/**
	 * Helper function for creating a new general "Info" notice
	 * @public
	 * @param {string} text    - the text to show
	 * @param {object} options - options for the notice
	 * @return {object} notice
	 */
	info: function ( text, options ) {
		options = options || {};
		return this.new( text, options, 'is-info' );
	},

	/**
	 * Helper function for creating a new general "Info" notice
	 * @public
	 * @param {string} text    - the text to show
	 * @param {object} options - options for the notice
	 * @return {object} notice
	 */
	warning: function ( text, options ) {
		options = options || {};
		return this.new( text, options, 'is-warning' );
	},

	/**
	 * Exposes the closure variable `list` to build out notices
	 */
	list: list,

	/**
	 * Removes a specific notice when you click its `X` button
	 * @param {object} notice - The data that was originally used to create the notice
	 */
	removeNotice: function ( notice ) {
		if ( ! notice.container ) {
			return;
		}
		const containerList = list[ notice.container ],
			index = containerList.indexOf( notice );

		if ( -1 === index ) {
			return;
		}
		containerList.splice( index, 1 );
		list.emit( 'change' );
	},

	/**
	 * Callback handler to clear notices when a user leaves current page
	 * @public
	 * @param {*}        context - Not used ?
	 * @param {Function} next    - next callback to execute
	 */
	clearNoticesOnNavigation: function ( context, next ) {
		debug( 'clearNoticesOnNavigation' );
		let length,
			container,
			changed = false;
		const isNoticePersistent = function ( notice ) {
			return notice.persistent;
		};

		for ( container in list.containerNames ) {
			length = list[ container ].length;
			list[ container ] = list[ container ].filter( isNoticePersistent );
			if ( length !== list[ container ].length ) {
				changed = true;
			}
		}

		// Rotate in any delayed notices
		if ( delayedNotices.length ) {
			delayedNotices.forEach( function ( noticeObject ) {
				list[ noticeObject.container ] = [];
				list[ noticeObject.container ].push( noticeObject );
			} );
			delayedNotices = [];
		}

		if ( changed ) {
			list.emit( 'change' );
		}
		next();
	},

	/**
	 * Clear all notices at once for a given container
	 * @public
	 *
	 * @param {string} container - DOM ID of notices container to clear
	 */
	clearNotices: function ( container ) {
		list[ container ] = [];
		list.emit( 'change' );
	},

	getStatusHelper: function ( noticeObject ) {
		if ( noticeObject.error ) {
			return 'is-error';
		}

		if ( noticeObject.warning ) {
			return 'is-warning';
		}

		if ( noticeObject.info ) {
			return 'is-info';
		}

		if ( noticeObject.success ) {
			return 'is-success';
		}
	},
};

export default notices;
