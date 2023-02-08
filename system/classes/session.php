<?php

class Session {

	public $me;
	public $scope;
	private $access_token;

	function __construct( $postamt ) {

		$access_token = $this->getBearerToken();

		if( ! $access_token && isset($_REQUEST['access_token'])) {
			$access_token = $_REQUEST['access_token'];
		}

		if( ! $access_token ) {
			$postamt->error( 'unauthorized', 'no access token was provided');
		}

		// TODO: how do we know, against which endpoint we need to verify?
		// the client needs to send a 'me' parameter as well? then we can find
		// the authorization endpoint, and verify against it.

		// TODO: check authorization token
		// failure: HTTP 403: "error":"forbidden" - The authenticated user does not have permission to perform this request.

		$this->access_token = $access_token;
		

		// TODO: check scope
		// failure: HTTP 403: "error":"insufficient_scope" - The scope of this token does not meet the requirements for this request. The client may wish to re-authorize the user to obtain the necessary scope. The response MAY include the "scope" attribute with the scope necessary to successfully perform this request.


		// TODO: rate-limit


	}


	function getAuthorizationHeader(){
		$headers = null;
		if( isset($_SERVER['Authorization']) ){
			$headers = trim($_SERVER["Authorization"]);
		} elseif( isset($_SERVER['HTTP_AUTHORIZATION']) ){ //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} elseif( function_exists('apache_request_headers') ){
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if( isset($requestHeaders['Authorization']) ){
				$headers = trim($requestHeaders['Authorization']);
			}
		}

		return $headers;
	}

	function getBearerToken() {

		$headers = $this->getAuthorizationHeader();
		if( empty($headers) ) return false;

		if( ! preg_match('/Bearer\s(\S+)/', $headers, $matches) ) return false;

		return $matches[1];
		
	}


};
