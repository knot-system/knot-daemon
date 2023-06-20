<?php

// 2023-06-20


class IndieAuth {

	private $url;
	private $scope;
	private $indieauth_metadata = NULL;
	private $authorization_endpoint;
	private $token_endpoint;

	private $requests = [];

	function __construct() {

	}

	function login( $url, $scope = false ) {

		$url = $this->normalize_url( $url );

		if( ! $url ) {
			return $this->error( 'invalid_url' );
		}

		$this->url = $url;

		# see https://indieauth.spec.indieweb.org/#discovery-by-clients

		$authorization_endpoint = $this->discover_endpoint( 'authorization_endpoint', $url );

		if( ! $authorization_endpoint ) {
			return $this->error( 'no_authorization_endpoint' );
		}

		$this->authorization_endpoint = $authorization_endpoint;

		$this->scope = $this->parse_scope( $scope );

		if( count($this->scope) ) {

			$token_endpoint = $this->discover_endpoint( 'token_endpoint', $url );

			if( ! $token_endpoint ) {
				return $this->error( 'no_token_endpoint' );
			}

			$this->token_endpoint = $token_endpoint;
		}


		global $core;


		if( get_config('microsub') ) {
			$microsub_endpoint = $this->discover_endpoint( 'microsub', $url );
			if( $microsub_endpoint ) {
				$_SESSION['indieauth_microsub_endpoint'] = $microsub_endpoint;
			}
		}

		if( get_config('micropub') ) {
			$micropub_endpoint = $this->discover_endpoint( 'micropub', $url );
			if( $micropub_endpoint ) {
				$_SESSION['indieauth_micropub_endpoint'] = $micropub_endpoint;
			}
		}

		
		$client_id = $this->client_id();
		$redirect_uri = $this->redirect_uri();
		$state = $this->generate_state_parameter();
		$code_verifier = $this->generate_pkce_code_verifier();
		$scope = implode( ' ', $this->scope );

		$_SESSION['indieauth_url'] = $url;
		$_SESSION['indieauth_state'] = $state;
		$_SESSION['indieauth_code_verifier'] = $code_verifier;
		$_SESSION['indieauth_authorization_endpoint'] = $authorization_endpoint;
		if( $token_endpoint ) {
			$_SESSION['indieauth_token_endpoint'] = $token_endpoint;
		}

		$authorization_data = [
			'me' => $url,
			'redirect_uri' => $redirect_uri,
			'client_id' => $client_id,
			'state' => $state,
			'code_verifier' => $code_verifier,
			'scope' => $scope
		];

		$authorization_url = $this->build_authorization_url( $authorization_endpoint, $authorization_data );

		// the client gets redirected to the $authorization_url, which then should call the /action/redirect/ url, which in turn calls the complete() function below to finish the authorization

		return $authorization_url;
	}

	function set_absolute_url( $url ) {
		$this->url = $url;
	}


	function complete( $params ) {

		$requiredSessionKeys = [ 'indieauth_url', 'indieauth_state', 'indieauth_authorization_endpoint' ];

		foreach( $requiredSessionKeys as $key ) {
			if( ! isset($_SESSION[$key]) ) {
				return $this->error('invalid_session');
			}
		}

		if( isset($params['error']) ) {
			return $this->error( $params['error'], $params['error_description'] );
		}

		if( ! isset($params['code']) ) {
			return $this->error( 'invalid_response' );
		}

		if( ! isset($params['state']) ) {
			return $this->error( 'missing_state' );
		}

		if( $params['state'] != $_SESSION['indieauth_state'] ) {
			return $this->error( 'invalid_state' );
		}


		if( isset($_SESSION['indieauth_token_endpoint']) ) {
			$data_endpoint = $_SESSION['indieauth_token_endpoint'];
		} else {
			$data_endpoint = $_SESSION['indieauth_authorization_endpoint'];
		}

		$data = $this->exchange_authorization_code( $data_endpoint, [
			'code' => $params['code'],
			'redirect_uri' => $this->redirect_uri(),
			'client_id' => $this->client_id(),
			'code_verifier' => $_SESSION['indieauth_code_verifier'],
		]);


		if( ! empty($_SESSION['indieauth_microsub_endpoint']) ) {
			$data['microsub_endpoint'] = $_SESSION['indieauth_microsub_endpoint'];
		}
		if( ! empty($_SESSION['indieauth_micropub_endpoint']) ) {
			$data['micropub_endpoint'] = $_SESSION['indieauth_micropub_endpoint'];
		}

		if( ! isset($data['response']['me']) ) {
			return $this->error( 'indieauth_error' );
		}


		// If the returned "me" is not the same as the entered "me", check that the authorization server linked to by the returned URL is the same as the one used
		if( $_SESSION['indieauth_url'] != $data['response']['me'] ) {
			// Go find the authorization endpoint that the returned "me" URL declares
			$authorization_endpoint = $this->discover_endpoint( 'authorization_endpoint', $data['response']['me'] );

			if( $authorization_endpoint != $_SESSION['indieauth_authorization_endpoint'] ) {
				return $this->error( 'invalid_authorization_endpoint' );
			}
		}

		$data['me'] = $this->normalize_url( $data['response']['me'] );

		$this->clear_session_data();

		return $data;
	}


	function parse_scope( $scope ) {

		if( ! is_array($scope) ) $scope = explode( ' ', $scope );

		$scope = array_unique( $scope );
		$scope = array_filter( $scope ); // remove empty entries

		return $scope;
	}


	function error( $error_code, $error_description = false ) {

		$error = [
			'error' => $error_code,
			'error_description' => $error_description
		];

		return $error;
	}


	function get_metadata( $endpoint ) {
		if( ! $this->indieauth_metadata ) return false;

		if( ! array_key_exists( $endpoint, $this->indieauth_metadata ) ) return false;

		return $this->indieauth_metadata[$endpoint];
	}


	function normalize_url( $url ) {
		return normalize_url($url, false);
	}

	function build_url( $parsed_url ) {
		return build_url($parsed_url);
	}


	function discover_endpoint( $name, $url, $multiple = false ) {

		if( ! $this->url_is_valid($url) ) return false;

		if( $name != 'indieauth-metadata' && $this->indieauth_metadata === NULL ) {

			$this->indieauth_metadata = false;

			$indieauth_metadata = $this->discover_endpoint( 'indieauth-metadata', $url );
			if( $indieauth_metadata ) {

				$request = $this->request($indieauth_metadata);
				$body = $request->get_body();

				if( $body ) {
					$json = json_decode($body, true);
					if( is_array($json) ) {
						$this->indieauth_metadata = $json;
					}
				}
			}
		}

		if( $this->indieauth_metadata ) {
			$url_from_metadata = $this->get_metadata( $name );
			if( $url_from_metadata ) {
				$url_from_metadata = $this->cleanup_relative_url($url_from_metadata);
				return $url_from_metadata;
			}
		}

		$request = $this->request($url);
		
		$elements = [];

		$headers = $request->get_headers();
		if( ! empty($headers['link']) ) {
			// endpoint provided via http 'link' header
			$links = explode(',', $headers['link']);
			$links = array_map( 'trim', $links );

			foreach( $links as $link ) {
				if( preg_match( '/\<(.*?)\>.*?rel="'.$name.'"/i', $link, $matches ) ) {
					$url_from_header = $matches[1];

					if( ! $url_from_header ) continue;
					
					$elements[] = $this->cleanup_relative_url( $url_from_header );

				}
			}

		}


		if( ! count($elements) ) {

			// endpoint may be provided via <link rel=".." href=".."> metatag

			$body = $request->get_body();

			if( ! $body ) return false;

			$dom = new Dom( $body );

			$endpoints = $dom->find_elements( 'link' )->filter_elements( 'rel', $name )->return_elements( 'href' );

			if( empty($endpoints) ) return false;

			$elements = [];

			foreach( $endpoints as $endpoint ) {

				$url_from_link_tag = $endpoint;

				if( ! $url_from_link_tag ) continue;

				$url_from_link_tag = $this->cleanup_relative_url( $url_from_link_tag );

				$elements[] = $url_from_link_tag;

			}

		}


		if( empty($elements) ) {
			return false;
		}


		if( $multiple ) {
			return $elements;
		} else {
			return $elements[0];
		}

	}


	function request( $url ) {

		if( ! array_key_exists( $url, $this->requests ) ) {
			$this->requests[$url] = new Request($url);
			$this->requests[$url]->curl_request();
		}

		return $this->requests[$url];
	}


	function url_is_valid( $url ) {

		$url = parse_url( $url );

		if( ! $url ) return false;
		if( ! array_key_exists('scheme', $url) ) return false;
		if( ! in_array($url['scheme'], array('http','https')) ) return false;
		if( ! array_key_exists('host', $url) ) return false;
		
		return true;
	}


	function cleanup_relative_url( $maybe_relative_url ) {

		if( $this->is_url_absolute($maybe_relative_url) ) {
			return $maybe_relative_url;
		}

		$absolute_url = trailing_slash_it($this->url).ltrim( $maybe_relative_url, '/' );

		return $absolute_url;
	}

	function is_url_absolute( $url ) {
		return isset(parse_url($url)['host']);
	}


	function client_id() {
		return url('');
	}

	function redirect_uri() {
		return url('action/redirect/');
	}

	function clear_session_data() {
		unset($_SESSION['indieauth_url']);
		unset($_SESSION['indieauth_state']);
		unset($_SESSION['indieauth_code_verifier']);
		unset($_SESSION['indieauth_authorization_endpoint']);
		unset($_SESSION['indieauth_token_endpoint']);
		unset($_SESSION['indieauth_microsub_endpoint']);
		unset($_SESSION['indieauth_micropub_endpoint']);
	}

	function exchange_authorization_code( $endpoint, $params ) {

		$query = [
			'grant_type' => 'authorization_code',
			'code' => $params['code'],
			'redirect_uri' => $params['redirect_uri'],
			'client_id' => $params['client_id'],
		];

		if( isset($params['code_verifier']) ) {
			$query['code_verifier'] = $params['code_verifier'];
		}



		$request = new Request();

		$url = $endpoint;
		$query = http_build_query( $query );
		$headers = [ 'Accept: application/json, application/x-www-form-urlencoded;q=0.8' ];

		$response = $request->post( $url, $query, $headers );

		$data = json_decode( $response, true );

		if( ! $data ) {
			// Parse as form-encoded for legacy server support
			$data = array();
			parse_str( $response, $data );
		}

		return [
			'response' => $data,
			'raw_response' => $response,
		];
	}

	function build_authorization_url( $endpoint, $data ) {

		$url = parse_url( $endpoint );

		$request = array();
		if( array_key_exists('query', $url) ) {
			parse_str( $url['query'], $request );
		}

		$request['response_type'] = 'code';
		$request['me'] = $data['me'];
		$request['redirect_uri'] = $data['redirect_uri'];
		$request['client_id'] = $data['client_id'];
		$request['state'] = $data['state'];

		if( ! empty($data['scope']) ) {
			$request['scope'] = $data['scope'];
		}

		if( isset( $data['code_verifier']) ) {
			$request['code_challenge'] = $this->generate_pkce_code_challenge( $data['code_verifier'] );
			$request['code_challenge_method'] = 'S256';
		}

		$url['query'] = http_build_query( $request );

		return $this->build_url( $url );
	}

	function generate_state_parameter(){
		return $this->generate_random_string( 8 );
	}

	function generate_pkce_code_verifier(){
		return $this->generate_random_string( 64 );
	}

	function generate_random_string( $num_bytes ) {
		$bytes = random_bytes( $num_bytes );

		return bin2hex($bytes);
	}

	function generate_pkce_code_challenge( $plaintext ) {
		$hash = hash( 'sha256', $plaintext, true );
		return $this->base64_urlencode( $hash );
	}

	function base64_urlencode( $string ) {
		$string = base64_encode($string);
		$string = str_replace('+/', '-_', $string);
		return rtrim($string, '=' );
	}



}
