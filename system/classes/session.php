<?php

class Session {

	public $me;
	public $scope;
	private $access_token;

	function __construct( $postamt ) {

		// TODO: rate-limit

		$access_token = $this->get_bearer_token();

		if( ! $access_token && isset($_REQUEST['access_token'])) {
			$access_token = $_REQUEST['access_token'];
		}

		if( ! $access_token ) {
			$postamt->error( 'unauthorized', 'no access token was provided' );
		}

		if( ! isset($_REQUEST['me']) ) {
			$postamt->error( 'unauthorized', 'no me parameter was provided' );
		}
		$me = $_REQUEST['me'];

		$this->me = $me;

		$indieauth = new IndieAuth();
		$url = $indieauth->normalize_url( $me );
		$token_endpoint = $indieauth->discover_endpoint( 'token_endpoint', $url );
		if( ! $token_endpoint ) {
			$postamt->error( 'unauthorized', 'could not find token endpoint' );
		}

		$this->token_endpoint = $token_endpoint;

		$token_endpoint_request = new Request();
		$token_verify = $token_endpoint_request->get( $token_endpoint, false, [ 'Authorization: Bearer '.$access_token ] );

		$token_verify = explode( '&', $token_verify );
		$token_response = [];
		foreach( $token_verify as $url_part ) {
			$url_part = explode( '=', $url_part );
			$token_response[$url_part[0]] = urldecode($url_part[1]);
		}

		if( isset($token_response['active']) && ! $token_response['active'] ) {
			$postamt->error( 'unauthorized', 'could not verify via token endpoint' );
		}

		if( ! isset($token_response['me']) || ! isset($token_response['scope']) ) {
			$postamt->error( 'unauthorized', 'could not verify via token endpoint' );
		}

		if( un_trailing_slash_it($token_response['me']) != un_trailing_slash_it($me) ) {
			$postamt->error( 'forbidden', 'The authenticated user does not have permission to perform this request', 403 );
		}

		if( isset($token_response['client_id']) && isset($_SERVER['HTTP_REFERER']) ) { // TODO: check & test this!
			$client_id = un_trailing_slash_it($token_response['client_id']);
			$referer = un_trailing_slash_it($_SERVER['HTTP_REFERER']);

			if( $client_id != $referer ) {
				$postamt->error( 'forbidden', 'The authenticated user does not have permission to perform this request', 403 );
			}
		}

		$this->access_token = $access_token;
		
		$scope = explode( ' ', $token_response['scope'] );

		$this->scope = $scope;

	}


	function check_scope( $expected_scope ) {

		$validated = true;

		if( is_array($expected_scope) ) {
			foreach( $expected_scope as $expected_sub_scope ) {
				if( ! $this->check_scope($expected_sub_scope) ) {
					$validated = false;
				}
			}
		} else {

			if( ! $this->scope ) {
				$validated = false;
			}

			if( ! in_array( $expected_scope, $this->scope ) ) {
				$validated = false;
			}

		}

		global $postamt;

		if( ! $validated ) {
			$postamt->error( 'insufficient_scope', 'The scope of this token does not meet the requirements for this request', 403 );
		}

		return true;
	}


	function get_authorization_header(){
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

	function get_bearer_token() {

		$headers = $this->get_authorization_header();
		if( empty($headers) ) return false;

		if( ! preg_match('/Bearer\s(\S+)/', $headers, $matches) ) return false;

		return $matches[1];
	}


};
