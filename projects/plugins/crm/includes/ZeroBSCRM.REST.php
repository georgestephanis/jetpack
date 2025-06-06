<?php 
/*!
 * Jetpack CRM
 * https://jetpackcrm.com
 * V2.94+
 *
 * Copyright 2020 Automattic
 *
 * Date: 31/08/2018
 */

/**
 *  This is really the way we should be doing anything AJAX'y in the CORE
 *  It's more stable and better than admin-ajax.php 
 * 
 */


defined( 'ZEROBSCRM_PATH' ) || exit( 0 );


/* ======================================================
	Admin WP REST API 
   ====================================================== */
/**
 * urls become get_rest_url() . /zbscrm/v1/companies/ 
 * urls become get_rest_url() . /zbscrm/v1/contacts/
 */

 /*

 For developers making manual Ajax requests, the nonce will need to be passed with each request. 
 The API uses nonces with the action set to wp_rest. 
 These can then be passed to the API via the _wpnonce data parameter 
 (either POST data or in the query for GET requests), or via the X-WP-Nonce header. 
 If no nonce is provided the API will set the current user to 0, turning the request 
 into an unauthenticated request, even if you’re logged into WordPress.

 http://v2.wp-api.org/extending/adding/#permissions-callback

 */

add_action( 'rest_api_init', function () {
    #} sites come with this enabled (but require non-plain permalinks)
    register_rest_route( 'zbscrm/v1', '/companies/', array(
      'methods' => 'GET',
      'callback' => 'zeroBSCRM_rest_getCompanies',
      'args' => array(
          /* not implemented? 
            wh, see http://v2.wp-api.org/extending/adding/ arguments
            'id' => array(
                'validate_callback' => 'is_numeric'
            ), */
        ),
      'permission_callback' => function () {
            return zeroBSCRM_permsCustomers();  //permissions.
      }

    ) );

    register_rest_route( 'zbscrm/v1', '/contacts/', array(
        'methods' => 'GET',
        'callback' => 'zeroBSCRM_rest_getContacts',
        'args' => array(
            /*'id' => array(
                'validate_callback' => 'is_numeric'
            ),*/
          ),
          'permission_callback' => function () {
                return zeroBSCRM_permsCustomers();  //permissions.
          }

      ) );


      #}additional here for v3.0 type-ahead. Used to get both companies AND contacts the array will hold 
      #} ID, email, object_id  
      #} concom = con[tact]com[pany]

        register_rest_route( 'zbscrm/v1', '/concom/', array(
        'methods' => 'POST, GET',
        'callback' => 'zeroBSCRM_rest_getConCom',
        'args' => array(
            /*'id' => array(
                'validate_callback' => 'is_numeric'
            ),*/
          ),
          'permission_callback' => function () {
                return zeroBSCRM_permsCustomers();  //permissions.
          }

      ) ); 

	/**
	 * Feature flag to conditionally load in development API.
	 *
	 * @ignore
	 * @since 6.1.0
	 *
	 * @param bool Determine if we should initialize the new REST APIs.
	 */
	if ( apply_filters( 'jetpack_crm_feature_flag_api_v4', false ) ) {
		$controller = new Automattic\Jetpack\CRM\REST_API\V4\REST_Contacts_Controller();
		$controller->register_routes();

		$automation_controller = new Automattic\Jetpack\CRM\REST_API\V4\REST_Automation_Workflows_Controller();
		$automation_controller->register_routes();
	}
});

//the callbacks (for the above URLS - restricted by the permission_callback above).
function zeroBSCRM_rest_getCompanies(WP_REST_Request $request){

    // as per http://v2.wp-api.org/extending/adding/ (argument section):
    $searchQuery = ''; if (isset($request['s'])) $searchQuery = sanitize_text_field( $request['s'] );
    $potentialID = -1; if (isset($request['id'])) $potentialID = (int)sanitize_text_field( $request['id'] );

      // if id, pass back obj singular
      if ($potentialID > 0) return zeroBS_getCompany($potentialID);


    $ret = array();
    //  $ret = zeroBS_getCompanies(true,100000,0);
    $ret = zeroBS_getCompaniesForTypeahead($searchQuery); // limitless simplified query (for now)
    $retA = array();
    foreach ($ret as $r){
        if (isset($r['name']) && $r['name'] !== 'Auto Draft') $retA[] = $r;
    }
    $ret = $retA; unset($retA);
    return $ret;
}

function zeroBSCRM_rest_getContacts(WP_REST_Request $request){

    global $zbs;

    // as per http://v2.wp-api.org/extending/adding/ (argument section):
    $searchQuery = ''; if (isset($request['s'])) $searchQuery = sanitize_text_field( $request['s'] );
    $potentialID = -1; if (isset($request['id'])) $potentialID = (int)sanitize_text_field( $request['id'] );

    // if id, pass back obj singular
    if ($potentialID > 0) return zeroBS_getCustomer($potentialID);

    /* WH temp rewrite. This shouldn't really be autocaching 100k contacts... but for now, at least get via LEAN SQL, 
    if DAL3+ */

      // Contacts
      
        $retA = $zbs->DAL->contacts->getContacts(array(
          'simplified' => true,
          'searchPhrase' => $searchQuery
          ));

    return $retA;
}

function zeroBSCRM_rest_getConCom(WP_REST_Request $request){

    global $zbs;

    // as per http://v2.wp-api.org/extending/adding/ (argument section):
    $searchQuery    = ''; if (isset($request['s']))         $searchQuery = sanitize_text_field( $request['s'] );
    $potentialID    = -1; if (isset($request['id']))        $potentialID = (int)sanitize_text_field( $request['id'] );
    $objectType     = -1; if (isset($request['obj_type']))  $potentialID = (int)sanitize_text_field( $request['obj_type'] );

    // if id, pass back obj simpler, but now also take in objectType too.
    if ($potentialID > 0 && $objectType == ZBS_TYPE_CONTACT) return zeroBS_getCustomer($potentialID);
    if ($potentialID > 0 && $objectType == ZBS_TYPE_COMPANY) return zeroBS_getCompany($potentialID);

    // ultimate return
    $retA = array();  

    /* WH temp rewrite. This shouldn't really be autocaching 100k contacts... but for now, at least get via LEAN SQL, 
    if DAL3+ */

      // Contacts

        $retA = $zbs->DAL->contacts->getContacts(array(
          'simplified' => true,
          'searchPhrase' => $searchQuery,
          'sortByField'   => 'ID',
          'sortOrder'     => 'DESC',
          'page'          => 0, 
          'perPage'       => 300,
          'ignoreowner'   => zeroBSCRM_DAL2_ignoreOwnership(ZBS_TYPE_CONTACT)
          ));

        // quickly cycle through + add obj_type + name/email ... inefficient
        if (is_array($retA) && count($retA) > 0)
          for ($i = 0; $i < count($retA); $i++){

              $retA[$i]['name_email'] = $retA[$i]['email'].' '.$retA[$i]['name'];
              $retA[$i]['obj_type'] = 1;

          }
        else
          $retA = array();

      // Companies
        
        $retB = array();
        $retB = $zbs->DAL->companies->getCompanies(array(
          'simplified' => true,
          'searchPhrase' => $searchQuery,
          'sortByField'   => 'ID',
          'sortOrder'     => 'DESC',
          'page'          => 0, 
          'perPage'       => 300,
          'ignoreowner'   => zeroBSCRM_DAL2_ignoreOwnership(ZBS_TYPE_COMPANY)
          ));

        // quickly cycle through + add obj_type + name/email ... inefficient
        if (is_array($retB) && count($retB) > 0)
          for ($i = 0; $i < count($retB); $i++){

              $retB[$i]['name_email'] = $retB[$i]['email'].' '.$retB[$i]['name'];
              $retB[$i]['obj_type'] = 2;

          }

        $retA = array_merge($retA,$retB);
        unset($retB);

    return $retA;
}
