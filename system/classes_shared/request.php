<?php

// update: 2023-03-22


class Request {

	private $user_agent;
	private $timeout;
	private $url;
	private $request_type = 'get';
	private $request_headers = [];
	private $post_data = [];

	private $http_status_code;
	private $headers = [];
	private $body;

	function __construct( $url = false ) {

		global $core;

		$this->user_agent = get_user_agent();
		$this->timeout = 10;

		if( $url ) $this->url = $url;

	}

	function set_url( $url ) {
		$this->url = $url;

		return $this;
	}


	function set_headers( $headers ) {
		if( ! is_array($headers) ) $headers = array( $headers );

		$this->request_headers = $headers;

		return $this;
	}


	function set_post_data( $data ) {
		if( ! is_array($data) ) $data = array( $data );

		$this->post_data = $data;

		$this->request_type = 'post';

		return $this;
	}


	function set_request_type( $request_type ) {

		$this->request_type = $request_type;

		return $this;
	}


	function curl_request( $followlocation = true, $nobody = false ) {

		if( ! $this->url ) return false;

		$ch = curl_init( $this->url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, $this->user_agent );

		if( count($this->request_headers) ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->request_headers );
		}

		if( $this->request_type == 'post' ) {
			curl_setopt( $ch, CURLOPT_POST, 1 );
			if( count($this->post_data) ) {
				$post_data = $this->post_data;
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
			}
		}

		if( $nobody ) {
			curl_setopt( $ch, CURLOPT_NOBODY, true );
		}
		
		if( $followlocation ) curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

		curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );

		$headers = [];
		curl_setopt( $ch, CURLOPT_HEADERFUNCTION, function( $curl, $header ) use (&$headers) {
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if( count($header) < 2 ) return $len; // ignore invalid headers

			$headers[strtolower(trim($header[0]))] = trim($header[1]);

			return $len;
		});


		$body = curl_exec( $ch );

		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$body = substr( $body, $header_size );

		$http_status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );


		if( $nobody && $http_status_code == 404 ) {
			// NOTE: some servers may respond with a 404 status code if we use CURLOPT_NOBODY, although the content is there. We need to not use CURLOPT_NOBODY in that case:
			$this->curl_request( $followlocation, false );
			return $this;
		}

		$this->http_status_code = $http_status_code;
		$this->headers = $headers;
		$this->body = $body;

		curl_close( $ch );

		return $this;
	}


	function get_status_code() {

		if( ! $this->http_status_code ) return false;

		return $this->http_status_code;
	}

	function get_body() {

		if( ! $this->body ) return false;

		return $this->body;
	}

	function get_headers() {

		if( ! $this->headers || ! count($this->headers) ) return false;

		return $this->headers;
	}


	function get( $url, $query = false, $headers = [] ) {

		if( $query ) {
			$query_arr = [];
			foreach( $query as $key => $value ) {
				$query_arr[] = $key.'='.$value;
			}
			$url .= '?'.implode( '&', $query_arr );
		}

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		if( is_array($headers) && count($headers) ) curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		curl_setopt( $ch, CURLOPT_USERAGENT, $this->user_agent );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );

		$response = curl_exec($ch);

		curl_close( $ch );

		return $response;
	}

	function post( $url, $query = false, $headers = [] ) {

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		if( is_array($headers) && count($headers) ) curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		curl_setopt( $ch, CURLOPT_POST, true );

		if( $query ) curl_setopt( $ch, CURLOPT_POSTFIELDS, $query );

		curl_setopt( $ch, CURLOPT_USERAGENT, $this->user_agent );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );

		$response = curl_exec($ch);

		curl_close( $ch );

		return $response;
	}

}
