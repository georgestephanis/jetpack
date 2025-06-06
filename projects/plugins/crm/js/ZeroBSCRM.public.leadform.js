/* eslint-disable jsdoc/require-param-type */
/* eslint-disable jsdoc/require-param-description */
/* eslint-disable jsdoc/require-description */
/*!
 * Jetpack CRM
 * https://jetpackcrm.com
 * V1.0
 *
 * Copyright 2020 Automattic
 *
 * Date: 17/06/2016
 */
/* global grecaptcha */
window.zbsCRMFormsBlocker = false;
jQuery( function () {
	// Infobox:

	// Debug console.log('front end form script is here');
	const zbs_ajaxurl = jQuery( '#zbs_form_ajax_action' ).data( 'zbsformajax' );

	let zbs_form_id;
	// track views of the lead form... could be multiple forms on the same page so loop through (but advise that only one per page should be used..)
	jQuery( '.zbscrmFrontEndForm' ).each( function () {
		zbs_form_id = jQuery( '#zbs_form_view_id' ).val();
		const zbs_ajaxurl_track = jQuery( '#zbs_form_ajax_action' ).data( 'zbsformajax' );
		zbscrm_JS_leadformview( zbs_form_id, zbs_ajaxurl_track );
	} );

	jQuery( '.zbscrmFrontEndForm .send' )
		.off( 'click' )
		.on( 'click', function ( e ) {
			// this checks if "already posting" and stops if so...
			if ( ! window.zbsCRMFormsBlocker ) {
				// set "in progress"
				window.zbsCRMFormsBlocker = true;

				//get the data from this form...
				e.preventDefault();
				const zbs_style = jQuery( '#zbs_form_style' ).val();
				const zbs_email = jQuery( '#zbs_email' ).val();

				// Debug console.log('style is ' + zbs_style);

				// MIKE: for future reference, when validating, I like to do this:
				// Have a variable "errors" and basically count (or add) the errors as I go, then submit at end, if legit.
				// I've modified this to do that, for your interest

				const errors = [];
				let reCaptchaResponse;

				// check reCaptcha!
				if ( typeof window.zbscrmReCaptcha !== 'undefined' ) {
					// check has response:
					reCaptchaResponse = grecaptcha.getResponse();

					if ( ! reCaptchaResponse ) {
						// later lets add fancier debug!
						// and move it to the end of this func so can do all at once...
						// eslint-disable-next-line no-alert
						alert( 'Captcha is required.' );

						// just adds an error flag (this'll let us highlight in code later)
						errors.push( 'recaptcha' );
					}
				}

				// Check email
				if ( ! zbscrm_JS_validateEmail( zbs_email ) ) {
					// later lets add fancier debug!
					// and move it to the end of this func so can do all at once...
					// eslint-disable-next-line no-alert
					alert( 'The email you have entered is not valid.' );

					// pass this
					errors.push( 'email' );
				}

				// WHEN you've got multiple if's, use switch, it's cleaner
				let t = {};

				switch ( zbs_style ) {
					case 'zbs_simple':
						//simple layout
						t = {
							action: 'zbs_lead_form_capture',
							zbs_form_id: zbs_form_id,
							zbs_email: zbs_email,
							zbs_hpot_email: jQuery( '#zbs_hpot_email' ).val(),
							zbs_form_style: zbs_style,
						};

						break;

					case 'zbs_cgrab':
						//content grab layout...
						t = {
							action: 'zbs_lead_form_capture',
							zbs_form_id: zbs_form_id,
							zbs_fname: jQuery( '#zbs_fname' ).val(),
							zbs_lname: jQuery( '#zbs_lname' ).val(),
							zbs_email: zbs_email,
							zbs_notes: jQuery( '#zbs_notes' ).val(),
							zbs_hpot_email: jQuery( '#zbs_hpot_email' ).val(),
							zbs_form_style: zbs_style,
						};

						break;

					case 'zbs_naked':
						// Debug console.log('naked style');
						t = {
							action: 'zbs_lead_form_capture',
							zbs_form_id: zbs_form_id,
							zbs_fname: jQuery( '#zbs_fname' ).val(),
							zbs_email: zbs_email,
							zbs_hpot_email: jQuery( '#zbs_hpot_email' ).val(),
							zbs_form_style: zbs_style,
						};

						break;

					default:
						// this is "no style!"
						errors.push( 'nostyle' );

						break;
				}

				if ( errors.length === 0 ) {
					// pass this along (if present)
					if ( typeof reCaptchaResponse !== 'undefined' ) {
						t.recaptcha = reCaptchaResponse;
					}

					// src
					if ( typeof window.zbscrmJSFormspid !== 'undefined' ) {
						t.pid = parseInt( window.zbscrmJSFormspid );
					}

					// no errors, sub
					zbscrm_JS_leadformcapture( zbs_form_id, zbs_ajaxurl, t );
				} else {
					// errors!
					// Needs proper exposing here
					// #NEEDSERRORMSG
					// Debug console.error(errors);

					// turn off blocker
					window.zbsCRMFormsBlocker = false;

					return false;
				}
			} // end of "if not blocked"
		} );
} );

/**
 * @param zbs_form_id
 * @param zbs_ajaxurl
 * @param t
 */
function zbscrm_JS_leadformcapture( zbs_form_id, zbs_ajaxurl, t ) {
	//capture the lead if submit is hit..  pre-process first though....
	//action zbs_lead_form_capture
	// Debug console.log(zbs_form_id, zbs_ajaxurl, t);
	const zbs_form_id_l = zbs_form_id;
	const i = jQuery.ajax( {
		url: zbs_ajaxurl,
		type: 'POST',
		crossDomain: true,
		data: t,
		dataType: 'json',
	} );
	i.done( function () {
		// localising (this pulls the id from parent func into here, so we can use it!)
		const zbs_form_id_l2 = zbs_form_id_l;

		// Debug console.log('FINI!',[e,zbs_form_id_l,zbs_form_id_l2]);

		// wh modified, slide up form, slide down success
		jQuery( '.zbsFormWrap', jQuery( '#zbs_form_' + zbs_form_id_l2 ) ).slideUp();
		jQuery( '.zbsForm_success', jQuery( '#zbs_form_' + zbs_form_id_l2 ) ).slideDown();

		// turn off blocker
		window.zbsCRMFormsBlocker = false;
	} );
	i.fail( function () {
		// Needs proper exposing here
		// #NEEDSERRORMSG
		// Debug console.log(e)

		// turn off blocker
		window.zbsCRMFormsBlocker = false;
	} );
}

//http://stackoverflow.com/questions/46155/validate-email-address-in-javascript
/**
 * @param {string} email - Email to test.
 * @return {boolean} True if it matches the regex, otherwise false.
 */
function zbscrm_JS_validateEmail( email ) {
	const re =
		/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test( email );
}

/**
 * @param zbs_form_id
 * @param zbs_ajaxurl
 */
function zbscrm_JS_leadformview( zbs_form_id, zbs_ajaxurl ) {
	const t = {
		action: 'zbs_lead_form_views',
		id: zbs_form_id,
	};
	jQuery
		.ajax( {
			url: zbs_ajaxurl,
			crossDomain: true,
			type: 'POST',
			data: t,
			dataType: 'json',
		} )
		.done( function () {} )
		.fail( function () {} );
}

if ( typeof module !== 'undefined' ) {
	module.exports = {
		zbscrm_JS_leadformcapture,
		zbscrm_JS_validateEmail,
		zbscrm_JS_leadformview,
	};
}
